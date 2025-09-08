<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/profil', name: 'app_profile_')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('profile/show.html.twig');
    }

    #[Route('/edition', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
                @mkdir($uploadsDir, 0775, true);

                // nom de fichier
                $safe = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $ext  = strtolower($file->guessExtension() ?: 'jpg');
                $name = sprintf('%s-%s.%s', $safe, substr(md5(uniqid()), 0, 8), $ext);
                $tmp  = $uploadsDir.'/tmp-'.$name;

                $file->move($uploadsDir, 'src-'.$name); // dépose brute
                $srcPath = $uploadsDir.'/src-'.$name;

                // -> redimension carré 256 (GD)
                $this->resizeSquare($srcPath, $uploadsDir.'/'.$name, 256, 256);
                @unlink($srcPath);

                // supprime ancien avatar si présent
                if ($user->getAvatarPath() && is_file($uploadsDir.'/'.$user->getAvatarPath())) {
                    @unlink($uploadsDir.'/'.$user->getAvatarPath());
                }

                $user->setAvatarPath($name);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_profile_show');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }

// helper privé (dans le même contrôleur)
    private function resizeSquare(string $src, string $dst, int $w, int $h): void
    {
        $info = getimagesize($src);
        if (!$info) return;

        [$sw, $sh] = $info;
        $type = $info[2]; // IMAGETYPE_*

        // center crop
        $side = min($sw, $sh);
        $sx = (int)(($sw - $side) / 2);
        $sy = (int)(($sh - $side) / 2);

        $srcImg = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => imagecreatefrompng($src),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($src) : null,
            default => null
        };
        if (!$srcImg) return;

        $dstImg = imagecreatetruecolor($w, $h);
        // fond transparent si PNG/WebP
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $clear = imagecolorallocatealpha($dstImg, 0,0,0,127);
            imagefill($dstImg, 0,0, $clear);
        }

        imagecopyresampled($dstImg, $srcImg, 0,0, $sx,$sy, $w,$h, $side,$side);

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dstImg, $dst, 88),
            IMAGETYPE_PNG  => imagepng($dstImg, $dst, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dstImg, $dst, 88) : imagejpeg($dstImg, $dst, 88),
            default        => imagejpeg($dstImg, $dst, 88),
        };

        imagedestroy($srcImg);
        imagedestroy($dstImg);
    }

    #[Route('/me/posts', name: 'app_my_posts')]
    public function myPosts(PostRepository $repo, Request $req): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $page = max(1, (int)$req->query->get('page', 1));
        $limit = 9;

        $qb = $repo->createQueryBuilder('p')
            ->andWhere('p.author = :u')->setParameter('u', $this->getUser())
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult(($page-1)*$limit)
            ->setMaxResults($limit);

        $posts = $qb->getQuery()->getResult();

        // total
        $total = (int)$repo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.author = :u')->setParameter('u', $this->getUser())
            ->getQuery()->getSingleScalarResult();

        $pages = max(1, (int)ceil($total / $limit));

        return $this->render('profile/my_posts.html.twig', compact('posts','page','pages','total'));
    }
}

