<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Reaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ReactionController extends AbstractController
{
    #[Route('/react/toggle/{id}', name: 'reaction_toggle', methods: ['POST'])]
    public function toggle(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $tokenHeader = $request->headers->get('X-CSRF-TOKEN');
        if (!$csrf->isTokenValid(new CsrfToken('react'.$post->getId(), $tokenHeader))) {
            throw $this->createAccessDeniedException('csrf');
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $kind = $payload['kind'] ?? null;
        if (!in_array($kind, ['fire','lol','cry','wow'], true)) {
            return $this->json(['ok' => false], 400);
        }

        $repo = $em->getRepository(Reaction::class);
        $existing = $repo->findOneBy([
            'user' => $this->getUser(),
            'post' => $post,
            'kind' => $kind,
        ]);

        $active = false;
        if ($existing) {
            $em->remove($existing);
        } else {
            $r = (new Reaction())
                ->setUser($this->getUser())
                ->setPost($post)
                ->setKind($kind)
                ->setCreatedAt(new \DateTimeImmutable());
            $em->persist($r);
            $active = true;
        }
        $em->flush();

        // Recompte
        $rows = $em->createQueryBuilder()
            ->select('r.kind AS k, COUNT(r.id) AS c')
            ->from(Reaction::class, 'r')
            ->where('r.post = :post')
            ->groupBy('r.kind')
            ->setParameter('post', $post)
            ->getQuery()
            ->getArrayResult();

        $counts = ['fire'=>0,'lol'=>0,'cry'=>0,'wow'=>0];
        foreach ($rows as $row) {
            $counts[$row['k']] = (int)$row['c'];
        }

        return $this->json(['ok' => true, 'active' => $active, 'counts' => $counts]);
    }

    // Fallback doux si un lien GET subsiste : on redirige vers l’article
    #[Route('/react/toggle/{id}', name: 'reaction_toggle_get', methods: ['GET'])]
    public function toggleGet(Post $post): RedirectResponse
    {
        return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
    }
}
