<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/app/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        $products = $user->getProducts();

        return $this->render('dashboard/index.html.twig', ['products' => $products]);
    }
}
