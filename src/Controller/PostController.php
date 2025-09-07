<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/post')]
#[IsGranted('ROLE_ADMIN')]
final class PostController extends AbstractController
{
    #[Route(name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();                                // on crée un nouvel objet
        $form = $this->createForm(PostType::class, $post); // on construit le formulaire
        $form->handleRequest($request);                    // on lie la requête

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'auteur connecté
            $post->setAuthor($this->getUser());

            // Générer un slug si le champ est vide
            if (!$post->getSlug()) {
                $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $post->getTitle()), '-'));
                $post->setSlug($slug);
            }

            // Si publié sans date -> maintenant (optionnel)
            if ($post->getStatus() === 'published' && null === $post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }


            $em->persist($post);
            $em->flush();
            $this->addFlash('success', 'Article créé.');
            return $this->redirectToRoute('app_post_index'); // retour à la liste
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id}', name: 'app_post_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'auteur connecté
            $post->setAuthor($this->getUser());
            if ($post->getAuthor() !== $this->getUser() && ! $this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres articles.');
            }


            // Générer le slug si vide
            if (!$post->getSlug()) {
                $post->setSlug(strtolower($slugger->slug($post->getTitle())));
            }
            // Si le slug est vide (non défini par l’utilisateur)
            if (!$post->getSlug()) {
                // On génère un slug à partir du titre :
                // 1. preg_replace('/[^a-z0-9]+/i', '-', $post->getTitle())
                //    → on remplace tout ce qui n’est pas lettre ou chiffre par un tiret "-"
                // 2. trim(..., '-')
                //    → on enlève les tirets au début/fin s’il y en a
                // 3. strtolower(...)
                //    → on met tout en minuscules
                $slug = strtolower(
                    trim(
                        preg_replace('/[^a-z0-9]+/i', '-', $post->getTitle()),
                        '-'
                    )
                );

                // On affecte ce slug automatiquement à l’article
                $post->setSlug($slug);
            }


            // Si on publie sans date -> maintenant
            if ($post->getStatus() === 'published' && null === $post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTimeImmutable());
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))) {
            if ($post->getAuthor() !== $this->getUser() && ! $this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Tu ne peux modifier que tes propres articles.');
            }

            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/post/export', name: 'app_post_export', methods: ['GET'])]
    public function exportCsv(PostRepository $repo): StreamedResponse
    {
        // 📄 Nom du fichier exporté (ex: posts-20250906-153000.csv)
        $filename = 'posts-'.(new \DateTimeImmutable())->format('Ymd-His').'.csv';

        // ⏳ StreamedResponse = la réponse est envoyée petit à petit (flux),
        // idéal pour générer un gros fichier CSV
        $response = new StreamedResponse(function () use ($repo) {
            // Ouverture du flux de sortie (php://output = directement la réponse HTTP)
            $handle = fopen('php://output', 'w');

            // (Optionnel) écrire le BOM UTF-8 pour qu’Excel gère bien les accents
            // fwrite($handle, "\xEF\xBB\xBF");

            // ✍️ Ligne d'en-tête du CSV (colonnes)
            fputcsv($handle, ['id', 'title', 'slug', 'category', 'publishedAt', 'rating', 'author'], ';');

            // 📊 On parcourt les articles publiés
            foreach ($repo->findPublishedForExport() as $p) {
                fputcsv($handle, [
                    $p->getId(),                                  // id du post
                    $p->getTitle(),                               // titre
                    $p->getSlug(),                                // slug
                    $p->getCategory()?->getName(),                // nom de la catégorie (si elle existe)
                    $p->getPublishedAt()?->format('Y-m-d H:i'),   // date de publication
                    $p->getRating(),                              // note
                    $p->getAuthor()?->getUserIdentifier(),        // auteur (email ou username)
                ], ';'); // séparateur = point-virgule
            }

            // Fermeture du flux
            fclose($handle);
        });

        // 🔧 Configuration des en-têtes HTTP pour forcer le téléchargement
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

}
