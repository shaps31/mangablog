<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class TagApiController extends AbstractController
{
    #[Route('/admin/tag/search', name: 'admin_tag_search')]
    public function search(Request $request, TagRepository $repo): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return $this->json([]);
        }

        $tags = $repo->createQueryBuilder('t')
            ->where('t.name LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Renvoie [{id, name}]
        $out = array_map(fn(Tag $t) => ['id' => $t->getId(), 'name' => $t->getName()], $tags);

        return $this->json($out);
    }

    #[Route('/admin/tag', name: 'admin_tag_create', methods: ['POST'])]
    public function create(
        Request $request,
        TagRepository $repo,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'empty'], 400);
        }

        // Si existe déjà, renvoyer l’existant
        if ($existing = $repo->findOneBy(['name' => $name])) {
            return $this->json(['id' => $existing->getId(), 'name' => $existing->getName()]);
        }

        $tag = new Tag();
        $tag->setName($name);
        $tag->setSlug(strtolower((string)$slugger->slug($name)));

        $em->persist($tag);
        $em->flush();

        return $this->json(['id' => $tag->getId(), 'name' => $tag->getName()]);
    }
}

