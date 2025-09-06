<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(PostRepository $posts): Response
    {
        $latest = $posts->findBy(
            ['status' => 'published'],
            ['publishedAt' => 'DESC'],
            3
        );

        return $this->render('home/index.html.twig', [
            'latest' => $latest, // <— le template saura l’afficher, sinon il a un fallback
        ]);
    }
}
