<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    #[Route('/', name: 'app_home')]
    #[Route('/home', name: 'app_home_legacy')]
    public function index(PostRepository $posts, EntityManagerInterface $em): Response
    {
        // 3 derniers en grand
        $latest = $posts->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()->getResult();

        // Catégories populaires (6) — nb d’articles publiés
        $popularCategories = $em->createQuery(
            'SELECT c AS category, COUNT(p.id) AS total
             FROM App\Entity\Post p JOIN p.category c
             WHERE p.status = :s
             GROUP BY c.id ORDER BY total DESC'
        )->setParameter('s','published')->setMaxResults(6)->getResult();

        // Tendances (6) — par nb de commentaires approuvés (puis date)
        $trending = $em->createQuery(
            'SELECT p AS post, COUNT(c.id) AS comments
             FROM App\Entity\Post p
             LEFT JOIN p.comments c WITH c.status = :ok
             WHERE p.status = :s
             GROUP BY p.id
             ORDER BY comments DESC, p.publishedAt DESC'
        )->setParameter('ok','approved')
            ->setParameter('s','published')
            ->setMaxResults(6)
            ->getResult();

        // Tags populaires (20) — nb d’articles publiés
        $topTags = $em->createQuery(
            'SELECT t AS tag, COUNT(p.id) AS total
             FROM App\Entity\Tag t JOIN t.posts p
             WHERE p.status = :s
             GROUP BY t.id ORDER BY total DESC'
        )->setParameter('s','published')->setMaxResults(20)->getResult();

        return $this->render('home/index.html.twig', [
            'latest' => $latest,
            'popularCategories' => $popularCategories,
            'trending' => $trending,
            'topTags' => $topTags,
        ]);
    }

    #[Route('/home', name: 'home_redirect')]
    public function homeRedirect(): Response
    {
        return $this->redirectToRoute('app_home', [], 301);
    }

}
