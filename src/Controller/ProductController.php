<?php

namespace NetJan\ProductServerBundle\Controller;

use NetJan\ProductServerBundle\Entity\Product;
use NetJan\ProductServerBundle\Form\ProductType;
use NetJan\ProductServerBundle\Repository\ProductRepository;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints;

/**
 * @Route("/products")
 */
class ProductController extends AbstractFOSRestController
{
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Rest\Get("")
     * @Rest\QueryParam(name="stock", nullable=true, description="Products in stock")
     */
    public function index(ParamFetcher $paramFetcher): Response
    {
        $filters = [
            'stock' => $this->normalizeStock($paramFetcher->get('stock')),
        ];

        $products = $this->productRepository->getList($filters);

        return $this->handleView($this->view($products));
    }

    /**
     * @Rest\Post("")
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->productRepository->save($product);

            if (false === $result['error']) {
                return $this->handleView($this->view($product));
            } else {
                return $this->handleView($this->view($result['messages']));
            }
        }

        return $this->handleView($this->view($form));
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function show($id): Response
    {
        $product = $this->getProduct($id);

        return $this->handleView($this->view($product));
    }

    /**
     * @Rest\Put("/{id}")
     */
    public function edit($id, Request $request): Response
    {
        $product = $this->getProduct($id);

        $form = $this->createForm(ProductType::class, $product);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->productRepository->save($product);

            if (false === $result['error']) {
                return $this->handleView($this->view($product));
            } else {
                return $this->handleView($this->view($result['messages']));
            }
        }

        return $this->handleView($this->view($form));
    }

    /**
     * @Rest\Delete("/{id}")
     */
    public function delete($id, Request $request): Response
    {
        $product = $this->getProduct($id);

        $result = $this->productRepository->remove($product);

        if (false === $result['error']) {
            return $this->json([], 204);
        }

        return $this->handleView($this->view($result['messages']));
    }

    /**
     * @Rest\Patch("/{id}")
     */
    public function update($id, Request $request): Response
    {
        $product = $this->getProduct($id);

        $orginalPoduct = [
            'name' => $product->getName(),
            'amount' => $product->getAmount(),
        ];

        $form = $this->createForm(ProductType::class, $product, [
            'fields_required' => false,
        ]);
        $data = json_decode($request->getContent(), true);

        $form->submit(array_merge($orginalPoduct, $data));

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->productRepository->save($product);

            if (false === $result['error']) {
                return $this->handleView($this->view($product));
            } else {
                return $this->handleView($this->view($result['messages']));
            }
        }

        return $this->handleView($this->view($form));
    }

    private function getProduct($id): ?Product
    {
        $product = $this->productRepository->find($id);

        if (null === $product) {
            throw new NotFoundHttpException();
        }

        return $product;
    }

    private function normalizeStock($stock): ?bool
    {
        if (\in_array($stock, [true, 'true', '1'], true)) {
            return true;
        }

        if (\in_array($stock, [false, 'false', '0'], true)) {
            return false;
        }

        return null;
    }
}
