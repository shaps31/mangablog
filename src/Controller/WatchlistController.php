<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\WatchlistItem;
use App\Repository\WatchlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

class WatchlistController extends AbstractController
{
    #[Route('/watchlist', name: 'watchlist_index')]
    public function index(WatchlistItemRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $items = $repo->findForUser($this->getUser());

        return $this->render('watchlist/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/watchlist/toggle/{id}', name: 'watchlist_toggle', methods: ['POST'])]
    public function toggle(
        Post $post,
        Request $request,
        WatchlistItemRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('watchlist'.$post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user   = $this->getUser();
        $item   = $repo->findOneBy(['user' => $user, 'post' => $post]);
        $added  = false;

        if ($item) {
            $em->remove($item);
        } else {
            $item = (new WatchlistItem())
                ->setUser($user)
                ->setPost($post)
                ->setCreatedAt(new \DateTimeImmutable());
            $em->persist($item);
            $added = true;
        }
        $em->flush();

        if ('fetch' === $request->headers->get('X-Requested-With')) {
            return $this->json(['ok' => true, 'added' => $added]);
        }

        $this->addFlash('success', $added ? 'Ajouté à ta liste.' : 'Retiré de ta liste.');
        return $this->redirect($request->headers->get('referer')
            ?: $this->generateUrl('blog_show', ['slug' => $post->getSlug()]));
    }
}
