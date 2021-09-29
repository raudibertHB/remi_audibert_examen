<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductFormType;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    #[Route('/app/add_product', name: 'add_product')]
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

        return $this->render('product/add_product.html.twig', [
            'productForm' => $productForm->createView()
        ]);
    }


    // TODO: TOGGLE STATUS
    #[Route('/app/toggle_product', name: 'toggle_product')]
    public function toggleProduct(Request $request): Response
    {

        $productRepository = $this->getDoctrine()->getRepository(Product::class);

        $product = $productRepository->find($request->get('productId'));

        if ($product->getStatus()) {
            $product->setStatus(false);
        } else {
            $product->setStatus(true);
        }

        $this->getDoctrine()->getManager()->persist($product);
        $this->getDoctrine()->getManager()->flush();

        return new RedirectResponse("/app/dashboard");
    }

    #[Route('/app/browse_product', name: 'browse_products')]
    public function browseProducts()
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);

        $products = $productRepository->findBy(['status' => true]);

        return $this->render('product/browse_products.html.twig',
            [
                'products' => $products
            ]);
    }

    #[Route('/api/products', name: 'getAvailableProducts', methods: 'GET')]
    public function getAvailableProducts(SerializerInterface $serializer): JsonResponse
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findBy(['status' => true]);

        $products = $serializer->serialize($products, "json", ['groups' => ['product', 'product_user']]);

        return new JsonResponse($products, 200, [], true);
    }

    #[Route('/api/products/{userId}', name: 'getAvailableProducts', methods: 'GET')]
    public function getAvailableProductsByUser(int $userId, SerializerInterface $serializer): JsonResponse
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->find($userId);

        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findBy(['status' => true, 'user' => $user]);

        $products = $serializer->serialize($products, "json", ['groups' => ['product']]);

        return new JsonResponse($products, 200, [], true);
    }

}
