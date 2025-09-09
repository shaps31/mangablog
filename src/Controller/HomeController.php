<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[Route('/home', name: 'app_home_legacy')]
    public function index(PostRepository $posts, EntityManagerInterface $em): Response
    {
        // Derniers articles publiés (3)
        $latest = $posts->findBy(
            ['status' => 'published'],
            ['publishedAt' => 'DESC'],
            3
        );

        // Catégories populaires (scalaires)
        $popularCategories = $em->createQuery(
            'SELECT c.id AS id, c.name AS name, c.slug AS slug, COUNT(p.id) AS total
             FROM App\Entity\Post p
             JOIN p.category c
             WHERE p.status = :s
             GROUP BY c.id, c.name, c.slug
             ORDER BY total DESC'
        )
            ->setParameter('s', 'published')
            ->setMaxResults(6)
            ->getArrayResult();

        // Tendances (post + nb commentaires approuvés)
        $trending = $em->createQuery(
            'SELECT p AS post, COUNT(c.id) AS comments
             FROM App\Entity\Post p
             LEFT JOIN p.comments c WITH c.status = :cs
             WHERE p.status = :ps
             GROUP BY p.id
             ORDER BY comments DESC, p.publishedAt DESC'
        )
            ->setParameter('cs', 'approved')
            ->setParameter('ps', 'published')
            ->setMaxResults(6)
            ->getResult();

        // Top tags (scalaires)
        $topTags = $em->createQuery(
            'SELECT t.id AS id, t.name AS name, COUNT(p.id) AS total
             FROM App\Entity\Tag t
             LEFT JOIN t.posts p WITH p.status = :s
             GROUP BY t.id, t.name
             ORDER BY total DESC'
        )
            ->setParameter('s', 'published')
            ->setMaxResults(20)
            ->getArrayResult();

        // Cover pour le hero : dernier article avec cover non nulle, sinon image locale
        $withCover = $posts->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->andWhere('p.cover IS NOT NULL')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $cover = $withCover?->getCover();
        $heroCover = \filter_var($cover, FILTER_VALIDATE_URL) ? $cover : 'img/hero-cover.jpg';

        // À découvrir (articles publiés aléatoires, en excluant les tendances)
        $discover = $em->createQuery(
            'SELECT p FROM App\Entity\Post p
             WHERE p.status = :s
             ORDER BY p.publishedAt DESC'
        )
            ->setParameter('s', 'published')
            ->setMaxResults(30)
            ->getResult();

        $trendingIds = array_map(fn ($row) => $row['post']->getId(), $trending);
        $discover = array_values(array_filter(
            $discover,
            fn (Post $p) => !in_array($p->getId(), $trendingIds, true)
        ));
        shuffle($discover);
        $discover = array_slice($discover, 0, 6);

        // (optionnel) Manga mis en avant
        $featured = $em->getRepository(Manga::class)->createQueryBuilder('m')
            ->where('m.featuredUntil IS NOT NULL AND m.featuredUntil >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('m.featuredUntil', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $this->render('home/index.html.twig', [
            'latest'            => $latest,
            'popularCategories' => $popularCategories,
            'trending'          => $trending,
            'topTags'           => $topTags,
            'heroCover'         => $heroCover,
            'discover'          => $discover,
            'featured'          => $featured,
        ]);
    }
}
