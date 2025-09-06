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
use App\Repository\TagRepository;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(
        Request $request,
        PostRepository $posts,
        CategoryRepository $categories,
        TagRepository $tagRepository

    ): Response
    {
        // 🔍 Récupération des filtres depuis l’URL (GET)
        $q      = $request->query->get('q', '');                 // texte recherché
        $catId  = $request->query->getInt('category', 0);        // id de catégorie (0 = pas de filtre)
        $tagId  = $request->query->getInt('tag', 0);             // id de tag (0 = pas de filtre)
        $page   = max(1, $request->query->getInt('page', 1));    // numéro de page (≥ 1)

        // 📄 Recherche paginée des articles publiés avec filtres (q, catégorie, tag)
        // ⚠️ Nécessite que ton PostRepository accepte $tagId.
        // Signature attendue côté repo: searchPublishedPaginated(?string $q, ?int $categoryId, ?int $tagId, int $page, int $perPage = 5)
        $pager = $posts->searchPublishedPaginated($q, $catId ?: null, $tagId ?: null, $page, 10);

        $items = $pager['items']; // les articles de la page courante

        // 📂 Données pour les filtres (liste complète)
        $allCategories = $categories->findAll();
        $allTags       = $tagRepository->findBy([], ['name' => 'ASC']);

        // 📅 Statistiques du mois en cours (totaux globaux et par catégorie)
        $start        = new \DateTimeImmutable('first day of this month 00:00:00');
        $end          = new \DateTimeImmutable('last day of this month 23:59:59');
        $totalMonth   = $posts->countPublishedBetween($start, $end);
        $totalsByCat  = $posts->countByCategoryBetween($start, $end);

        // 🎨 Envoi au template
        return $this->render('blog/index.html.twig', [
            'posts'       => $items,          // articles de la page
            'q'           => $q,              // valeur du champ recherche
            'category'       => $catId,          // filtre catégorie sélectionné
            'tag'         => $tagId,          // filtre tag sélectionné
            'categories'  => $allCategories,  // toutes les catégories
            'allTags'     => $allTags,        // tous les tags (triés par nom)
            'totalMonth'  => $totalMonth,     // total du mois
            'totalsByCat' => $totalsByCat,    // total par catégorie
            // 🔄 Infos de pagination
            'page'        => $pager['page'],
            'pages'       => $pager['pages'],
            'totalResults' => $pager['total'],



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
