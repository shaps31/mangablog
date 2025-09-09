<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\WatchlistItem;
use App\Repository\WatchlistItemRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me/watchlist', name: 'watchlist_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WatchlistController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'toggle', methods: ['POST'])]
    public function toggle(
        Post $post,
        Request $request,
        WatchlistItemRepository $repo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('watchlist' . $post->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $item = $repo->findOneBy(['user' => $user, 'post' => $post]);

        if ($item) {
            $em->remove($item);
            $state = 'removed';
        } else {
            $item = (new WatchlistItem())
                ->setUser($user)
                ->setPost($post);
            $em->persist($item);
            $state = 'added';
        }

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            // Conflit de concurrence bénin : l’item existe déjà
        }

        // Réponse AJAX pratique si tu déclenches via fetch()
        if ($request->isXmlHttpRequest() || 'json' === $request->getPreferredFormat()) {
            return $this->json(['ok' => true, 'state' => $state]);
        }

        return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(WatchlistItemRepository $repo): Response
    {
        $items = $repo->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('watchlist/index.html.twig', [
            'items' => $items,
        ]);
    }
}
