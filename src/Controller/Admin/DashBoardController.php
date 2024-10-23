<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/dashboard', name: 'admin.dashboard', methods: ['GET'])]
class DashBoardController extends AbstractController
{
    #[Route('/', name: '.index')]
    public function index(): Response
    {
        return $this->render('Backend/Admin/DashBoard/index.html.twig');
    }
}
