<?php


namespace App\Controller;

use App\Entity\Post;
use App\Entity\Reaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/react')]
class ReactionController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'reaction_toggle', methods: ['POST'])]
    public function toggle(Post $post, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $req->headers->get('X-CSRF-TOKEN') ?? '';
        if (!$this->isCsrfTokenValid('react' . $post->getId(), $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide');
        }

        $data = json_decode($req->getContent(), true) ?? [];
        $kind = $data['kind'] ?? '';

        if (!in_array($kind, ['fire', 'lol', 'cry', 'wow'], true)) {
            return $this->json(['ok' => false, 'error' => 'kind'], 400);
        }

        $repo = $em->getRepository(Reaction::class);
        $exist = $repo->findOneBy(['user' => $this->getUser(), 'post' => $post, 'kind' => $kind]);

        if ($exist) {
            $em->remove($exist);
        } else {
            $em->persist((new Reaction())
                ->setUser($this->getUser())
                ->setPost($post)
                ->setKind($kind)
                ->setCreatedAt(new \DateTimeImmutable())
            );
        }
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
