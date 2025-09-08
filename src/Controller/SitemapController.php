<?php
// src/Controller/SitemapController.php
namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function index(PostRepository $posts): Response
    {
        $items = [];
        $items[] = ['loc' => $this->generateUrl('app_home', [], 0)];
        $items[] = ['loc' => $this->generateUrl('blog_index', [], 0)];

        foreach ($posts->findBy(['status' => 'published'], ['publishedAt' => 'DESC']) as $p) {
            $items[] = [
                'loc' => $this->generateUrl('blog_show', ['slug' => $p->getSlug()], 0),
                'lastmod' => $p->getPublishedAt()?->format('Y-m-d'),
            ];
        }

        $xml = $this->renderView('sitemap.xml.twig', ['items' => $items]);
        return new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
