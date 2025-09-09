<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(
        Request $request,
        PostRepository $posts,
        CategoryRepository $categories,
        TagRepository $tagRepository
    ): Response {
        // 🔎 Filtres depuis l’URL
        $q     = trim((string) $request->query->get('q', ''));
        $catId = (int) $request->query->get('category', 0) ?: null; // 0 => null (pas de filtre)
        $tagId = (int) $request->query->get('tag', 0) ?: null;      // 0 => null (pas de filtre)
        $page  = max(1, (int) $request->query->get('page', 1));

        // 🔧 Nombre d’articles par page (cartes)
        $perPage = 3;

        // 📄 Recherche paginée (repo doit accepter le paramètre $tagId)
        $pager = $posts->searchPublishedPaginated(
            q: $q,
            categoryId: $catId,
            tagId: $tagId,
            page: $page,
            perPage: $perPage
        );

        // 📂 Données pour les filtres
        $allCategories = $categories->findAll();
        $allTags       = $tagRepository->findBy([], ['name' => 'ASC']);

        // 📅 Stats du mois
        $start       = new \DateTimeImmutable('first day of this month 00:00:00');
        $end         = new \DateTimeImmutable('last day of this month 23:59:59');
        $totalMonth  = $posts->countPublishedBetween($start, $end);
        $totalsByCat = $posts->countByCategoryBetween($start, $end);

        // 🎨 Rendu
        return $this->render('blog/index.html.twig', [
            // liste + pagination
            'posts'         => $pager['items'],
            'totalResults'  => $pager['total'],
            'page'          => $pager['page'],
            'pages'         => $pager['pages'],

            // filtres (pour formulaire + pastilles)
            'q'             => $q,
            'category'      => $catId,      // id de catégorie sélectionné (ou null)
            'tag'           => $tagId,      // id de tag sélectionné (ou null)
            'categories'    => $allCategories,
            'allTags'       => $allTags,

            // stats bandeau
            'totalMonth'    => $totalMonth,
            'totalsByCat'   => $totalsByCat,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        PostRepository $posts,
        CommentRepository $commentsRepo,
        EntityManagerInterface $em,
        string $slug
    ): Response {
        // 🔎 Article publié correspondant au slug
        $post = $posts->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // 🔗 Articles liés (même catégorie, autres que l’actuel) — 3 max
        $related = array_filter(
            $posts->findBy(
                ['category' => $post->getCategory(), 'status' => 'published'],
                ['publishedAt' => 'DESC'],
                4
            ),
            fn ($p) => $p->getId() !== $post->getId()
        );
        $related = array_slice($related, 0, 3);


        // 💬 Commentaires approuvés
        $approved = $commentsRepo->findBy(
            ['post' => $post, 'status' => 'approved'],
            ['createdAt' => 'DESC']
        );

        // 📝 Formulaire commentaire (si connecté)
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
                $comment->setStatus('pending');

                $em->persist($comment);
                $em->flush();

                $this->addFlash('success', 'Commentaire envoyé. Il sera visible après validation.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
            $formView = $form->createView();
        }    $related = $posts->findRelated($post, 3);
             $trending = $posts->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->andWhere('(c.status IS NULL OR c.status = :approved)')
            ->setParameter('approved', 'approved')
            ->addSelect('COUNT(c.id) AS HIDDEN commentsCount')
            ->groupBy('p.id')
            ->orderBy('commentsCount', 'DESC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // $post est l'article courant
        if ($post->getTags()->count() > 0) {
            $dql = <<<DQL
    SELECT p2 AS post, COUNT(t2.id) AS commonTags
    FROM App\Entity\Post p2
    JOIN p2.tags t2
    WHERE p2 != :post AND t2 IN (:tags) AND p2.status = 'published'
    GROUP BY p2.id
    ORDER BY commonTags DESC, p2.publishedAt DESC
    DQL;

            $reco = $em->createQuery($dql)
                ->setParameters(['post' => $post, 'tags' => $post->getTags()])
                ->setMaxResults(6)
                ->getResult();
        } else {
            $reco = [];
        }

        return $this->render('blog/show.html.twig', [
            'post'     => $post,
            'comments' => $approved,
            'form'     => $formView,
            'related'  => $related,
            'trending' => $trending,
            'reco'     => $reco,
        ]);
    }
}
