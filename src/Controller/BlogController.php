<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(Request $request, PostRepository $posts, CategoryRepository $categories): Response
    {
        $q = $request->query->get('q');                 // recherche texte
        $catId = $request->query->getInt('category');    // filtre catégorie (id)

        $start = (new \DateTimeImmutable('first day of this month 00:00:00'));
        $end = (new \DateTimeImmutable('last day of this month 23:59:59'));

        $totalMonth = $posts->countPublishedBetween($start, $end);
        $totalsByCat = $posts->countByCategoryBetween($start, $end);


        $items = $posts->searchPublished($q, $catId);    // seulement les articles "published"
        $cats = $categories->findAll();

        return $this->render('blog/index.html.twig', [
            'posts' => $items,
            'q' => $q,
            'catId' => $catId,
            'categories' => $cats,
            'totalMonth' => $totalMonth,
            'totalsByCat' => $totalsByCat,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', methods: ['GET', 'POST'])]
    public function show(
        Request                $request,
        PostRepository         $posts,
        CommentRepository      $commentsRepo,
        EntityManagerInterface $em,
        string                 $slug
    ): Response
    {
        $post = $posts->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // Liste des commentaires approuvés pour cet article
        $approved = $commentsRepo->findBy(
            ['post' => $post, 'status' => 'approved'],
            ['createdAt' => 'DESC']
        );

        // Formulaire minimal pour poster un commentaire (contenu uniquement)
        $formView = null;
        if ($this->getUser()) {
            $comment = new Comment();

            $form = $this->createFormBuilder($comment)
                ->add('content')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $comment->setPost($post);
                $comment->setAuthor($this->getUser());
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setStatus('pending'); // modération

                $em->persist($comment);
                $em->flush();

                $this->addFlash('success', 'Commentaire envoyé. Il sera visible après validation.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }

            $formView = $form->createView();
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
            'comments' => $approved,
            'form' => $formView,
        ]);
    }
}
