<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductFormType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/app/add-product', name: 'add_product')]
    public function addProduct(Request $request): Response
    {
        $product = new Product();
        $productForm = $this->createForm(ProductFormType::class, $product);
        $productForm->handleRequest($request);

        if ($productForm->isSubmitted() && $productForm->isValid()) {
            $imgFile = $productForm->get('image')->getData();

            if ($imgFile) {
                $newFilename = uniqid() . '.' . $imgFile->guessExtension();

                try {
                    if ($imgFile->move($this->getParameter('img_directory'), $newFilename)) {
                        $user = $this->getUser();
                        $name = $productForm->get('name')->getData();
                        $description = $productForm->get('description')->getData();
                        $price = $productForm->get('price')->getData();

                        $product->setUser($user);
                        $product->setName($name);
                        $product->setImage($newFilename);
                        $product->setCreationDate(new \DateTime());
                        $product->setDescription($description);
                        $product->setPrice($price);
                        if ($productForm->get('status')->getData()) {
                            $product->setStatus(false);
                        } else {
                            $product->setStatus(true);
                        }

                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($product);
                        $entityManager->flush();
                    }
                } catch (FileException $e) {
                    dd($e);
                }
            }
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('product/add-product.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }
}
