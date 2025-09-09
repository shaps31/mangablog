<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tops')]
class TopController extends AbstractController
{
    // ex: /tops/shojo
    #[Route('/{tag}', name: 'tops_by_tag')]
    public function byTag(string $tag, EntityManagerInterface $em): Response
    {
        $since = new \DateTimeImmutable('-30 days');

        $dql = <<<DQL
        SELECT p AS post, (COUNT(DISTINCT r.id) + COUNT(DISTINCT c.id)) AS score
        FROM App\Entity\Post p
        LEFT JOIN p.tags t
        LEFT JOIN App\Entity\Reaction r WITH r.post = p AND r.createdAt >= :since
        LEFT JOIN p.comments c WITH c.status = 'approved' AND c.createdAt >= :since
        WHERE p.status = 'published' AND t.name = :tag
        GROUP BY p.id
        ORDER BY score DESC, p.publishedAt DESC
        DQL;

        $rows = $em->createQuery($dql)
            ->setParameters(['since'=>$since, 'tag'=>$tag])
            ->setMaxResults(10)
            ->getResult();

        return $this->render('tops/by_tag.html.twig', [
            'tag'  => $tag,
            'rows' => $rows,
        ]);
    }
}
