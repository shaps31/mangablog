<?php


namespace App\Controller\Admin;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        PostRepository $posts,
        CategoryRepository $categories,
        TagRepository $tags,
        CommentRepository $comments,
        EntityManagerInterface $em
    ): Response {
        // Compteurs
        $postCount     = $posts->count([]);
        $categoryCount = $categories->count([]);
        $tagCount      = $tags->count([]);
        $commentCount  = $comments->count([]);

        // 5 derniers articles publiés
        $latestPosts = $posts->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        // 5 commentaires en attente
        $pendingComments = $comments->findBy(
            ['status' => 'pending'],
            ['createdAt' => 'DESC'],
            5
        );

        // Top 5 catégories (nb d’articles publiés)
        $topCategories = $em->createQuery(
            'SELECT c.name AS name, COUNT(p.id) AS total
             FROM App\Entity\Post p
             JOIN p.category c
             WHERE p.status = :s
             GROUP BY c.id, c.name
             ORDER BY total DESC'
        )
            ->setParameter('s', 'published')
            ->setMaxResults(5)
            ->getResult();

        // Nombre d’articles publiés ce mois-ci
        $from = new \DateTimeImmutable('first day of this month 00:00:00');
        $to   = $from->modify('first day of next month 00:00:00');
        $publishedThisMonth = (int) $posts->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->andWhere('p.publishedAt >= :from')->setParameter('from', $from)
            ->andWhere('p.publishedAt < :to')->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            // ✅ ce que ton Twig utilise
            'counts' => [
                'posts'      => $postCount,
                'categories' => $categoryCount,
                'tags'       => $tagCount,
                'comments'   => $commentCount,
            ],
            'latestPosts'        => $latestPosts,
            'pendingComments'    => $pendingComments,
            'topCategories'      => $topCategories,
            'publishedThisMonth' => $publishedThisMonth,
            'pending'            => $pendingComments, // ton Twig affiche aussi "pending"
        ]);
    }
}

