<?php
// src/Controller/FeedController.php
namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FeedController extends AbstractController
{
    #[Route('/feed.xml', name: 'app_feed', methods: ['GET'])]
    public function atom(PostRepository $posts): Response
    {
        $items = $posts->findBy(
            ['status' => 'published'],
            ['publishedAt' => 'DESC'],
            20
        );

        return $this->render('feed/atom.xml.twig', [
            'items' => $items,
        ], new Response('', 200, ['Content-Type' => 'application/atom+xml; charset=UTF-8']));
    }
}
