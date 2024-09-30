<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Api;

use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\Retail\V2\Client\ProductServiceClient;
use Google\Cloud\Retail\V2\GetProductRequest;
use Google\Cloud\Retail\V2\Product;
use Google\Cloud\Retail\V2\UpdateProductRequest;
use Google\Protobuf\FieldMask;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductComparisonObject;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\CategoryFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\PriceInfoFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\ProductFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\RequestFormatterService;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductService
{
    private ProductServiceClient $productServiceClient;

    public function __construct(
        private CategoryFormatterService $categoryFormatterService,
        private RequestFormatterService $requestFormatterService,
        private PriceInfoFormatterService $priceInfoFormatterService,
        private ProductFormatterService $productFormatterService,
    ) {
        $this->productServiceClient = new ProductServiceClient();
    }

    public function createOrUpdateGoogleProduct(ProductInterface $product, ChannelInterface $channel, OutputInterface $output): void
    {
        if (null === $product->getCode()) {
            return;
        }

        $productPath = $this->requestFormatterService->formatProductPath($product->getCode());

        try {
            $googleProduct = $this->getGoogleProduct($productPath) ?? $this->updateGoogleProduct($product, $channel);

            if ($googleProduct) {
                $this->syncProducts($googleProduct, $product, $channel);
            }
        } catch (Exception $exception) {
            $output->writeln(\sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }

    private function getGoogleProduct(string $productPath): ?Product
    {
        try {
            return $this->productServiceClient->getProduct((new GetProductRequest())->setName($productPath));
        } catch (ApiException $e) {
            if ('NOT_FOUND' === $e->getStatus()) {
                return null;
            }

            throw $e;
        }
    }

    private function syncProducts(Product $googleProduct, ProductInterface $product, ChannelInterface $channel): void
    {
        $googleProductObject = ProductComparisonObject::fromGoogleRetail($googleProduct);
        $syliusProductObject = ProductComparisonObject::fromSylius($product, $channel, $this->priceInfoFormatterService, $this->categoryFormatterService);

        if (!$googleProductObject->equals($syliusProductObject)) {
            $this->updateGoogleProduct($product, $channel);

            return;
        }

        echo "Product {$product->getCode()} is already synced to catalog." . \PHP_EOL;
    }

    private function updateGoogleProduct(ProductInterface $product, ChannelInterface $channel): ?Product
    {
        $googleProduct = $this->productFormatterService->generateGoogleProductFromSyliusProduct($product, $channel);
        $updateRequest = (new UpdateProductRequest())->setProduct($googleProduct)
            ->setUpdateMask((new FieldMask())->setPaths(['title', 'description', 'priceInfo', 'categories']))
            ->setAllowMissing(true)
        ;

        try {
            $this->productServiceClient->updateProduct($updateRequest);
            echo "Product {$product->getCode()} synced successfully to catalog." . \PHP_EOL;

            return $googleProduct;
        } catch (Exception $e) {
            echo 'Error syncing product: ' . $e->getMessage() . \PHP_EOL;

            return null;
        }
    }
}
