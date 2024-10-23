<?php

namespace App\Controller\Admin;

use App\Entity\Cause;
use App\Form\CauseType;
use App\Repository\CauseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/cause', name: 'admin.cause', methods: ['GET'])]
class CauseController extends AbstractController
{
    #[Route('/', name: '.index')]
    public function index(CauseRepository $causeRepo): Response
    {
        return $this->render('Backend/Admin/Cause/index.html.twig', [
            'causes' => $causeRepo->findAll(),
        ]);
    }

    #[Route('/create', name: '.create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $cause = new Cause();

        $form = $this->createForm(CauseType::class, $cause);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cause);
            $em->flush();

            $this->addFlash('success', 'Cause créer avec succès.');
            return $this->redirectToRoute('admin.cause.index');
        }

        return $this->render('Backend/Admin/Cause/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: '.delete', methods: ['POST'])]
    public function delete(Cause $cause, Request $request, EntityManagerInterface $em): Response 
    {
        if ($this->isCsrfTokenValid('delete' . $cause->getId(), $request->request->get('_token'))) {
            $em->remove($cause);
            $em->flush();

            $this->addFlash('success', 'Cause supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la cause.');
        }

        return $this->redirectToRoute('admin.cause.index');
    }
}
