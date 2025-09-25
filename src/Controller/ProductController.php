<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;

final class ProductController extends AbstractController
{
    // #[Route('/product', name: 'app_product')]
    public function index(): Response
    {
        $product = [
            ['id' => 1, 'name' => 'Intex', 'price' => 800],
            ['id' => 2, 'name' => 'Micromax', 'price' => 1200],
        ];
        return $this->render('product/index.html.twig', [
            'products' => $product
        ]);
    }

    public function show(int $id): Response
    {
        $products = [
            1 => ['name' => 'iPhone', 'price' => 800],
            2 => ['name' => 'Laptop', 'price' => 1200],
        ];

        if (!isset($products[$id])) {
            return new Response("Product not found", 404);
        }

        $product = $products[$id];

        return $this->render('product/show.html.twig', [
            'id' => $id,
            'name' => $product['name'],
            'price' => $product['price'],
        ]);
    }

    public function new(Request $req): Response
    { 

        if($req->isMethod('POST')){
            
            $name = $req->request->get('name');
            $price = $req->request->get('price');

            return new Response("Product Created : $name ($$price)");
        }
        return $this->render('product/new.html.twig');
    }
    
    // Connected to DB
    public function list(ProductRepository $productRepository) : Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/list.html.twig',[
            'products' => $products
        ]);
    }

    public function add(Request $request, EntityManagerInterface $em) : Response
    {
        // EntityManagerInterface $em → Doctrine’s tool to save/update/delete entities in the database (like DB or Eloquent in Laravel).
        // Response → means this method must return an HTTP response.

        if($request->isMethod('POST')){
            // request->get('name') → get <input name="name"> value.
            $name = $request->request->get('name');
            $price = $request->request->get('price');

            // creates a new row (entity object)
            $product = new Product();
            // fill data (like $product->name = $name;
            $product->setName($name);
            $product->setPrice((float)$price);

            // tells Doctrine "this object should be saved"
            // prepare for saving
            $em->persist($product);
            // actually runs the SQL INSERT into the database
            // ->save();
            $em->flush();

            // Redirect to product list after saving
            return $this->redirectToRoute('product_lists');
        }

        return $this->render('product/new.html.twig');
    }

    // EDIT -> GET shows form, POST handles update
    public function edit(int $id, Request $request, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        if ($request->isMethod('POST')) {
            $product->setName($request->request->get('name'));
            $product->setPrice((float) $request->request->get('price'));
            $em->flush(); // entity is already managed so no persist needed

            return $this->redirectToRoute('product_lists');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
        ]);
    }

    // DELETE -> POST /product/{id}/delete
    public function delete(int $id, Request $request, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // CSRF check (token name = 'delete' . id)
        if (!$this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('product_lists');
        }

        $em->remove($product);
        $em->flush();

        return $this->redirectToRoute('product_lists');
    }

    // SHOW -> GET /product/{id}
    public function getById(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
