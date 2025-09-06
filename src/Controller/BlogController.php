<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Comment;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(Request $request, PostRepository $posts, CategoryRepository $categories): Response
    {
        // 🔍 On récupère les filtres envoyés dans l’URL (GET)
        $q     = $request->query->get('q');                 // texte à rechercher
        $catId = $request->query->getInt('category');       // id de catégorie (si sélectionnée)
        $page  = max(1, $request->query->getInt('page', 1)); // numéro de page (par défaut 1)

        // 📊 On utilise la méthode avec pagination
        $pager = $posts->searchPublishedPaginated($q, $catId, $page, 5);
        $items = $pager['items']; // les articles de la page courante

        // 📅 On calcule le total des articles publiés ce mois-ci
        $start = (new \DateTimeImmutable('first day of this month 00:00:00'));
        $end   = (new \DateTimeImmutable('last day of this month 23:59:59'));
        $totalMonth  = $posts->countPublishedBetween($start, $end);

        // 📊 On calcule les totaux par catégorie ce mois-ci
        $totalsByCat = $posts->countByCategoryBetween($start, $end);

        // 📂 Toutes les catégories (pour afficher un filtre dans le template)
        $cats = $categories->findAll();

        // 🎨 On envoie toutes les infos au template
        return $this->render('blog/index.html.twig', [
            'posts'       => $items,         // les articles de la page
            'q'           => $q,             // valeur du champ recherche
            'catId'       => $catId,         // catégorie sélectionnée
            'categories'  => $cats,          // toutes les catégories
            'totalMonth'  => $totalMonth,    // total d’articles publiés ce mois
            'totalsByCat' => $totalsByCat,   // totaux par catégorie
            // 📄 infos de pagination
            'page'        => $pager['page'],
            'pages'       => $pager['pages'],
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
        // 🔎 On récupère l’article publié correspondant au slug
        $post = $posts->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // 💬 On récupère les commentaires approuvés (status = approved)
        $approved = $commentsRepo->findBy(
            ['post' => $post, 'status' => 'approved'],
            ['createdAt' => 'DESC']
        );

        // 📝 Formulaire de commentaire (visible uniquement pour un utilisateur connecté)
        $formView = null;
        if ($this->getUser()) {
            $comment = new Comment();
            $form = $this->createFormBuilder($comment)
                ->add('content')
                ->getForm();

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // Associer le commentaire à l’article et à l’utilisateur
                $comment->setPost($post);
                $comment->setAuthor($this->getUser());
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setStatus('pending'); // pas publié tant que non validé

                $em->persist($comment);
                $em->flush();

                $this->addFlash('success', 'Commentaire envoyé. Il sera visible après validation.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
            $formView = $form->createView();
        }

        return $this->render('blog/show.html.twig', [
            'post'     => $post,
            'comments' => $approved,
            'form'     => $formView,
        ]);
    }
}
