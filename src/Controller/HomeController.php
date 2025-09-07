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
        // Derniers articles (exemple)
        $latest = $posts->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        // ✅ Catégories populaires (scalaires)
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

        // Tendances (post + nombre de commentaires approuvés) — OK car on sélectionne l’entité racine p
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

        return $this->render('home/index.html.twig', [
            'latest' => $latest,
            'popularCategories' => $popularCategories, // scalaires
            'trending' => $trending,          // post (entité) + comments (scalaire)
        ]);

    }

    #[Route('/home', name: 'home_redirect')]
    public function homeRedirect(): Response
    {
        return $this->redirectToRoute('app_home', [], 301);
    }

}
