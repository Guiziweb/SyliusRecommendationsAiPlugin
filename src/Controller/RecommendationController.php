<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Controller;


use Google\ApiCore\ApiException;
use Google\Cloud\Retail\V2\PredictResponse\PredictionResult;
use Guiziweb\SyliusRecommendationsAiPlugin\Api\PredictionService;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RecommendationController extends AbstractController
{
    private CustomerContextInterface $customerContext;

    private PredictionService $predictionService;

    /**
     * @var ProductRepositoryInterface<ProductInterface>
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param PredictionService $predictionService
     * @param CustomerContextInterface $customerContext
     */
    public function __construct(ProductRepositoryInterface $productRepository, PredictionService $predictionService, CustomerContextInterface $customerContext)
    {
        $this->productRepository = $productRepository;
        $this->predictionService = $predictionService;
        $this->customerContext = $customerContext;
    }


    public function indexAction(Request $request,int $count): Response
    {
       $customer = $this->customerContext->getCustomer();

        try {

            $response = $this->predictionService->getPredictions($customer->getUser()->getId(), $count, );

            /** @var PredictionResult $result */

            $productIds = [];
            foreach ($response->getResults() as $result) {
                $productIds[] = $result->getId();
            }

            return $this->render('@SyliusShop/Product/_horizontalList.html.twig', [
                'products' => $this->productRepository->findBy(['code' => $productIds]),
            ]);

        }
        catch (ApiException $e) {
            dump($e->getMessage());
        }
    }

}
