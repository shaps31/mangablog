<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/manga', name: 'manga_')]
final class MangaController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(MangaRepository $repo): Response
    {
        $list = $repo->findBy([], ['title' => 'ASC']);

        return $this->render('manga/index.html.twig', [
            'list' => $list,
        ]);
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function show(
        #[MapEntity(expr: 'repository.findOneBy({slug: slug})')]
        Manga $manga
    ): Response {
        return $this->render('manga/show.html.twig', [
            'manga' => $manga,
        ]);
    }
}
