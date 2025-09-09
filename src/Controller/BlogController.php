<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ReactionRepository;
use App\Repository\TagRepository;
use App\Repository\WatchlistItemRepository;
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
        \App\Repository\PostRepository $posts,   // ✅ REPOSITORY, pas PostController
        CategoryRepository $categories,
        TagRepository $tagRepository,
        ReactionRepository $reactionRepo,
        WatchlistItemRepository $watchlists
    ): Response {
        // Filtres
        $q     = trim((string) $request->query->get('q', ''));
        $catId = $request->query->has('category') ? ((int) $request->query->get('category') ?: null) : null;
        $tagId = $request->query->has('tag')      ? ((int) $request->query->get('tag') ?: null)      : null;
        $page  = max(1, (int) $request->query->get('page', 1));
        $perPage = 3;

        // Récents ou Tendance 🔥
        $sort  = $request->query->get('sort', 'recent');
        $pager = ($sort === 'hot')
            ? $posts->findPublishedHot($q, $catId, $tagId, $page, $perPage)
            : $posts->searchPublishedPaginated($q, $catId, $tagId, $page, $perPage);

        // Données filtres
        $allCategories = $categories->findAll();
        $allTags       = $tagRepository->findBy([], ['name' => 'ASC']);

        // Stats du mois
        $start       = new \DateTimeImmutable('first day of this month 00:00:00');
        $end         = new \DateTimeImmutable('last day of this month 23:59:59');
        $totalMonth  = $posts->countPublishedBetween($start, $end);
        $totalsByCat = $posts->countByCategoryBetween($start, $end);

        // 🔥 totaux de réactions par post (badges sur la liste)
        $ids = array_map(fn($p) => $p->getId(), $pager['items']);
        $rxTotals = $ids ? $reactionRepo->totalsForPostIds($ids) : [];

        // ✅ IDs des posts dans la watchlist de l’utilisateur (si connecté)
        $inListIds = [];
        if ($this->getUser()) {
            $inListIds = $watchlists->findPostIdsForUser($this->getUser());
        }

        return $this->render('blog/index.html.twig', [
            'posts'         => $pager['items'],
            'totalResults'  => $pager['total'],
            'page'          => $pager['page'],
            'pages'         => $pager['pages'],
            'q'             => $q,
            'category'      => $catId,
            'tag'           => $tagId,
            'categories'    => $allCategories,
            'allTags'       => $allTags,
            'totalMonth'    => $totalMonth,
            'totalsByCat'   => $totalsByCat,
            'rxTotals'      => $rxTotals,
            'inListIds'     => $inListIds,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        \App\Repository\PostRepository $posts,   // ✅ REPOSITORY, pas PostController
        CommentRepository $commentsRepo,
        EntityManagerInterface $em,
        WatchlistItemRepository $watchlistRepo,
        string $slug, PostRepository $postRepository
    ): Response {
        // Article publié
        $post = $posts->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // Articles liés (même catégorie) — 3 max
        $related = array_filter(
            $posts->findBy(
                ['category' => $post->getCategory(), 'status' => 'published'],
                ['publishedAt' => 'DESC'],
                4
            ),
            fn ($p) => $p->getId() !== $post->getId()
        );
        $related = array_slice($related, 0, 3);

        // Commentaires approuvés
        $approved = $commentsRepo->findBy(
            ['post' => $post, 'status' => 'approved'],
            ['createdAt' => 'DESC']
        );

        // Form commentaire (si connecté)
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
        }

        // Tendances (nb de coms approuvés)
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

        // Reco (tags en commun)
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

        // Réactions
        $rxKinds = ['fire','lol','cry','wow'];
        $rows = $em->createQuery(
            'SELECT r.kind AS k, COUNT(r.id) AS c
             FROM App\Entity\Reaction r
             WHERE r.post = :post
             GROUP BY r.kind'
        )->setParameter('post', $post)->getArrayResult();

        $rxCounts = array_fill_keys($rxKinds, 0);
        foreach ($rows as $row) {
            $rxCounts[$row['k']] = (int) $row['c'];
        }

        $rxMine = [];
        if ($this->getUser()) {
            $mine = $em->createQuery(
                'SELECT r.kind AS k
                 FROM App\Entity\Reaction r
                 WHERE r.post = :post AND r.user = :u'
            )->setParameters(['post' => $post, 'u' => $this->getUser()])
                ->getArrayResult();
            $rxMine = array_map(fn($r) => $r['k'], $mine);
        }

        // Watchlist : est-il dans ma liste ?
        $inList = false;
        if ($this->getUser()) {
            $inList = $watchlistRepo->isInList($this->getUser(), $post);
        }
        $related = $postRepository->relatedByTags($post, 4);

        return $this->render('blog/show.html.twig', [
            'post'     => $post,
            'comments' => $approved,
            'form'     => $formView,
            'related'  => $related,
            'trending' => $trending,
            'reco'     => $reco,
            'rxCounts' => $rxCounts,
            'rxMine'   => $rxMine,
            'rxKinds'  => $rxKinds,
            'inList'   => $inList,
        ]);
    }
}
