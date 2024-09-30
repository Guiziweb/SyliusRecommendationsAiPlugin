<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\DTO;

use Google\Cloud\Retail\V2\PriceInfo;
use Google\Cloud\Retail\V2\Product;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\CategoryFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\PriceInfoFormatterService;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;

class ProductComparisonObject
{
    private string $code;

    private string $name;

    private string $description;

    /** @var array<string> */
    private array $categories;

    private PriceInfo $priceInfo;

    /**
     * @param array<string> $categories
     */
    public function __construct(string $code, string $name, string $description, array $categories, PriceInfo $priceInfo)
    {
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->categories = $categories;
        $this->priceInfo = $priceInfo;
    }

    public static function fromGoogleRetail(Product $googleProduct): self
    {
        $googleCategoriesArray = iterator_to_array($googleProduct->getCategories());

        return new self(
            $googleProduct->getId(),
            $googleProduct->getTitle(),
            $googleProduct->getDescription(),
            $googleCategoriesArray,
            $googleProduct->getPriceInfo() ?? new PriceInfo(),
        );
    }

    public static function fromSylius(ProductInterface $product, ChannelInterface $channel, PriceInfoFormatterService $priceInfoFormatterService, CategoryFormatterService $categoryFormatterService): self
    {
        $categories = $categoryFormatterService->generateCategoriesForProduct($product);
        $priceInfo = $priceInfoFormatterService->generatePriceInfoFromSyliusProduct($product, $channel);

        return new self(
            $product->getCode() ?? '',
            $product->getName() ?? '',
            $product->getDescription() ?? '',
            $categories,
            $priceInfo
        );
    }

    public function equals(self $syliusProduct): bool
    {
        return $this->code === $syliusProduct->code
            && $this->name === $syliusProduct->name
            && $this->description === $syliusProduct->description
            && $this->categoriesAreEqual($this->categories, $syliusProduct->categories)
            && $this->pricesAreEqual($this->priceInfo, $syliusProduct->priceInfo);
    }

    /**
     * Compare les tableaux de catégories.
     *
     * @param array<string> $categories1
     * @param array<string> $categories2
     */
    private function categoriesAreEqual(array $categories1, array $categories2): bool
    {
        // On vérifie d'abord si les deux tableaux ont la même taille
        if (\count($categories1) !== \count($categories2)) {
            return false;
        }

        // Ensuite, on compare les valeurs en ignorant l'ordre des catégories
        sort($categories1);
        sort($categories2);

        return $categories1 === $categories2;
    }

    /**
     * Compare les tableaux de prix.
     */
    private function pricesAreEqual(PriceInfo $googlePrice, PriceInfo $syliusPrice): bool
    {
        if ($googlePrice->getPrice() !== $syliusPrice->getPrice()) {
            return false;
        }

        // google met par default OriginalPrice dans price
        if ($googlePrice->getPrice() === $googlePrice->getOriginalPrice()) {
            return true;
        }

        if ($googlePrice->getOriginalPrice() !== $syliusPrice->getOriginalPrice()) {
            return false;
        }

        if ($googlePrice->getCurrencyCode() !== $syliusPrice->getCurrencyCode()) {
            return false;
        }

        return true;
    }
}
