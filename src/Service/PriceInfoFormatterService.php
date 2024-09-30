<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

use Google\Cloud\Retail\V2\PriceInfo;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;

class PriceInfoFormatterService
{
    /**
     * Génère le PriceInfo de Google à partir d'un produit Sylius.
     */
    public function generatePriceInfoFromSyliusProduct(ProductInterface $product, ChannelInterface $channel): PriceInfo
    {
        $priceInfo = new PriceInfo();
        $variant = $product->getVariants()->first();

        if ($variant instanceof ProductVariantInterface) {
            $channelPricing = $variant->getChannelPricingForChannel($channel);

            if ($channelPricing instanceof ChannelPricingInterface) {
                // Set original price
                $originalPrice = $channelPricing->getOriginalPrice();
                if (null !== $originalPrice) {
                    $priceInfo->setOriginalPrice($originalPrice / 100);
                }

                // Set current price
                $price = $channelPricing->getPrice();
                if (null !== $price) {
                    $priceInfo->setPrice($price / 100);
                }

                // Set currency code
                $currency = $channel->getBaseCurrency();
                if ($currency instanceof CurrencyInterface && $currency->getCode()) {
                    $priceInfo->setCurrencyCode($currency->getCode());
                }
            }
        }

        return $priceInfo;
    }
}
