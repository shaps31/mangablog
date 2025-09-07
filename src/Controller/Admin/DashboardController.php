<?php

namespace App\Controller\Admin;

use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function index(
        PostRepository $posts,
        CommentRepository $comments,
        CategoryRepository $cats,
        TagRepository $tags,
    ): Response {
        $counts = [
            'posts'     => $posts->count([]),
            'categories'=> $cats->count([]),
            'tags'      => $tags->count([]),
            'comments'  => $comments->count([]),
        ];

        $pending = $comments->findBy(
            ['status' => 'pending'],
            ['createdAt' => 'DESC'],
            5
        );

        $publishedThisMonth = $posts->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.status = :pub')->setParameter('pub','published')
            ->andWhere('p.publishedAt >= :start')->setParameter('start', new \DateTimeImmutable('first day of this month 00:00'))
            ->getQuery()->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'counts' => $counts,
            'pending' => $pending,
            'publishedThisMonth' => $publishedThisMonth,
        ]);
    }
}
