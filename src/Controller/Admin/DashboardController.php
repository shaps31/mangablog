<?php

namespace App\Controller\Admin;

use App\Repository\PostRepository;
use App\Repository\TagRepository;
use App\Repository\CategoryRepository;
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

        // 5 commentaires "pending"
        $pendingComments = $comments->findBy(
            ['status' => 'pending'],
            ['createdAt' => 'DESC'],
            5
        );

        // Top 5 catégories (sur articles publiés)
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

        return $this->render('admin/dashboard.html.twig', [
            'postCount'        => $postCount,
            'categoryCount'    => $categoryCount,
            'tagCount'         => $tagCount,
            'commentCount'     => $commentCount,
            'latestPosts'      => $latestPosts,
            'pendingComments'  => $pendingComments,
            'topCategories'    => $topCategories,
        ]);
    }
}
