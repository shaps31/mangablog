<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(Request $request, PostRepository $posts, CategoryRepository $categories): Response
    {
        $q  = $request->query->get('q');                 // recherche texte
        $catId = $request->query->getInt('category');    // filtre catégorie (id)

        $items = $posts->searchPublished($q, $catId);    // seulement les articles "published"
        $cats  = $categories->findAll();

        return $this->render('blog/index.html.twig', [
            'posts' => $items,
            'q' => $q,
            'catId' => $catId,
            'categories' => $cats,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(PostRepository $posts, string $slug): Response
    {
        $post = $posts->findOneBy(['slug' => $slug, 'status' => 'published']);
        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        return $this->render('blog/show.html.twig', ['post' => $post]);
    }
}
