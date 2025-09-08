<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
#[IsGranted('ROLE_ADMIN')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        // Tri du plus récent au plus ancien
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire créé.');
            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form'    => $form->createView(), // <- important
        ]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Commentaire mis à jour.');
            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form'    => $form->createView(), // <- important
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $token = $request->request->getString('_token'); // récupère depuis <form> POST
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $token)) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/approve', name: 'app_comment_approve', methods: ['POST'])]
    public function approve(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('approve' . $comment->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_comment_index');
        }

        if ($comment->getStatus() !== 'approved') {
            $comment->setStatus('approved');
            $em->flush();
            $this->addFlash('success', 'Commentaire approuvé.');
        } else {
            $this->addFlash('info', 'Ce commentaire est déjà approuvé.');
        }

        return $this->redirectToRoute('app_comment_index');
    }

    #[Route('/{id}/reject', name: 'app_comment_reject', methods: ['POST'])]
    public function reject(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $token = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('reject' . $comment->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_comment_index');
        }

        $em->remove($comment);
        $em->flush();
        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('app_comment_index');
    }

    #[Route('/admin/comment/{id}/toggle', name: 'app_comment_toggle', methods: ['POST'])]
    public function toggle(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('comment_toggle'.$comment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF token invalide.');
        }

        $comment->setStatus($comment->getStatus() === 'approved' ? 'pending' : 'approved');
        $em->flush();

        $this->addFlash('success', $comment->getStatus() === 'approved'
            ? 'Commentaire approuvé.'
            : 'Approbation annulée.'
        );

        $back = $request->request->get('back') ?: $request->headers->get('referer');
        return $this->redirect($back ?: $this->generateUrl('app_comment_index'));
    }
}
