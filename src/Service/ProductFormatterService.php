<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

use Google\Cloud\Retail\V2\Product;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;

class ProductFormatterService
{
    private CategoryFormatterService $categoryFormatterService;

    private PriceInfoFormatterService $priceInfoFormatterService;

    private RequestFormatterService $requestFormatterService;

    public function __construct(CategoryFormatterService $categoryFormatterService, PriceInfoFormatterService $priceInfoFormatterService, RequestFormatterService $requestFormatterService)
    {
        $this->categoryFormatterService = $categoryFormatterService;
        $this->priceInfoFormatterService = $priceInfoFormatterService;
        $this->requestFormatterService = $requestFormatterService;
    }

    /**
     * Génère un produit google depuis un produit Sylius.
     *
     * @return Product $product
     */
    public function generateGoogleProductFromSyliusProduct(ProductInterface $product, ChannelInterface $channel): Product
    {
        $googleProduct = new Product();

        if ($product->getCode()) {
            $googleProduct->setId($product->getCode());  // Use the product code as ID
            $googleProduct->setName($this->requestFormatterService->formatProductPath($product->getCode()));  // Use the product code as ID
        }
        if ($product->getName()) {
            $googleProduct->setTitle($product->getName());
        }
        if ($product->getDescription()) {
            $googleProduct->setDescription($product->getDescription());
        }

        $categories = $this->categoryFormatterService->generateCategoriesForProduct($product);
        $priceInfo = $this->priceInfoFormatterService->generatePriceInfoFromSyliusProduct($product, $channel);

        $googleProduct->setPriceInfo($priceInfo);
        $googleProduct->setCategories($categories);

        return $googleProduct;
    }
}
