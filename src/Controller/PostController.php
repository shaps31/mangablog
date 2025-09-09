<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{
    Request, Response
};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/posts')]
final class PostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Liste des articles.
     * - Admin : tous
     * - User : uniquement ses articles
     */
    #[Route('', name: 'app_post_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $posts): Response
    {
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $qb = $posts->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('p.author = :me')->setParameter('me', $this->getUser());
        }

        // (option) petit filtre search/status
        if ($q = trim((string) $request->query->get('q', ''))) {
            $qb->andWhere('(LOWER(p.title) LIKE :q OR LOWER(p.content) LIKE :q)')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($st = $request->query->get('status')) {
            $qb->andWhere('p.status = :st')->setParameter('st', $st);
        }

        $countQb = (clone $qb)->select('COUNT(DISTINCT p.id)');
        $total   = (int) $countQb->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()->getResult();

        return $this->render('post/index.html.twig', [
            'posts' => $items,
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
            'q'     => $q,
            'status'=> $st,
        ]);
    }

    /**
     * Création d’un article.
     * Accessible à tout utilisateur connecté (ROLE_USER).
     */
    #[Route('/new', name: 'app_post_new', methods: ['GET','POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request): Response
    {
        $post = new Post();
        $post->setAuthor($this->getUser());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Slug auto si vide
            if (!$post->getSlug()) {
                $post->setSlug($this->slugify($post->getTitle()));
            }

            // publishedAt si publié
            if ($post->getStatus() === 'published' && !$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }
            if ($post->getStatus() !== 'published') {
                $post->setPublishedAt(null);
            }

            $this->em->persist($post);
            $this->em->flush();

            $this->addFlash('success', 'Article créé.');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * Édition d’un article.
     * - Admin : tout
     * - User : seulement ses articles
     */
    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Post $post): Response
    {
        $this->denyUnlessOwnerOrAdmin($post);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Re-slugger si slug vide ou si tu veux reslugger quand le titre change (ici on fait que si vide)
            if (!$post->getSlug() && $post->getTitle()) {
                $post->setSlug($this->slugify($post->getTitle()));
            }

            // Gestion publishedAt selon status
            if ($post->getStatus() === 'published' && !$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }
            if ($post->getStatus() !== 'published') {
                $post->setPublishedAt(null);
            }

            $this->em->flush();
            $this->addFlash('success', 'Article mis à jour.');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    /**
     * Suppression.
     */
    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post): Response
    {
        $this->denyUnlessOwnerOrAdmin($post);

        if (!$this->isCsrfTokenValid('delete'.$post->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_post_index');
        }

        $this->em->remove($post);
        $this->em->flush();

        $this->addFlash('success', 'Article supprimé.');
        return $this->redirectToRoute('app_post_index');
    }

    /**
     * Toggle publication (published <-> draft).
     */
    #[Route('/{id}/toggle', name: 'app_post_toggle', methods: ['POST'])]
    public function toggle(Request $request, Post $post): Response
    {
        $this->denyUnlessOwnerOrAdmin($post);

        if (!$this->isCsrfTokenValid('toggle'.$post->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_post_index');
        }

        if ($post->getStatus() === 'published') {
            $post->setStatus('draft');
            $post->setPublishedAt(null);
            $msg = 'Article dépublié.';
        } else {
            $post->setStatus('published');
            if (!$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }
            $msg = 'Article publié.';
        }

        $this->em->flush();
        $this->addFlash('success', $msg);

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_post_index'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function denyUnlessOwnerOrAdmin(Post $post): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        if ($post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function slugify(?string $title): string
    {
        $t = trim((string) $title);
        if ($t === '') return '';
        return strtolower($this->slugger->slug($t)->toString());
    }
}
