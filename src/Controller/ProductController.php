<?php

namespace NetJan\ProductServerBundle\Controller;

use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use NetJan\ProductServerBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use NetJan\ProductServerBundle\Form\ProductType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NetJan\ProductServerBundle\Filter\ProductFilter;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use NetJan\ProductServerBundle\Exception\ExceptionInterface;
use NetJan\ProductServerBundle\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

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
        $filter = new ProductFilter();
        $filter->stock = $this->normalizeStock($paramFetcher->get('stock'));

        $products = $this->productRepository->list($filter);

        return $this->handleView($this->view($products, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("")
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        try {
            $form->submit($request->toArray());
        } catch (JsonException $e) {
            return $this->handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->save($product);
        }

        return $this->formErrors($form);
    }

    /**
     * @ParamConverter("product", class="NetJanProductServerBundle:Product")
     * @Rest\Get("/{id}")
     */
    public function show(Product $product): Response
    {
        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
     * @ParamConverter("product", class="NetJanProductServerBundle:Product")
     * @Rest\Put("/{id}")
     */
    public function edit(Product $product, Request $request): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        try {
            $form->submit($request->toArray());
        } catch (JsonException $e) {
            return $this->handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->save($product);
        }

        return $this->formErrors($form);
    }

    /**
     * @ParamConverter("product", class="NetJanProductServerBundle:Product")
     * @Rest\Delete("/{id}")
     */
    public function delete(Product $product): Response
    {
        try {
            $this->productRepository->remove($product);
        } catch (ExceptionInterface $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    /**
     * @ParamConverter("product", class="NetJanProductServerBundle:Product")
     * @Rest\Patch("/{id}")
     */
    public function update(Product $product, Request $request): Response
    {
        $orginalPoduct = [
            'name' => $product->getName(),
            'amount' => $product->getAmount(),
        ];
        $form = $this->createForm(ProductType::class, $product, [
            'fields_required' => false,
        ]);
        try {
            $form->submit(array_merge($orginalPoduct, $request->toArray()));
        } catch (JsonException $e) {
            return $this->handleView($this->view([], Response::HTTP_BAD_REQUEST));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->save($product);
        }

        return $this->formErrors($form);
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

    private function save(Product $product): Response
    {
        try {
            $this->productRepository->save($product);
        } catch (ExceptionInterface $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    private function formErrors(FormInterface $form): Response
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->handleView($this->view($errors, Response::HTTP_BAD_REQUEST));
    }
}
