<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface as EM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function edit(Request $request, EM $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1) Upload fichier si présent
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                $orig = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($orig)->lower();
                $ext  = $file->guessExtension() ?: 'bin';
                $name = sprintf('%s-%s.%s', $safe, bin2hex(random_bytes(4)), $ext);

                $targetDir = $this->getParameter('uploads_avatars_dir');

                try {
                    $file->move($targetDir, $name);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Échec de l'upload: ".$e->getMessage());
                    return $this->redirectToRoute('app_profile_edit');
                }

                // Optionnel: supprimer l’ancien fichier s’il est local
                $current = $user->getAvatarUrl();
                if ($current && !preg_match('#^https?://#i', $current)) {
                    $abs = dirname($targetDir, 1) . DIRECTORY_SEPARATOR . ltrim($current, '/');
                    if (is_file($abs)) @unlink($abs); // silencieux
                }

                // On stocke un chemin web public
                $user->setAvatarUrl('/uploads/avatars/'.$name);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('app_profile_show');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
