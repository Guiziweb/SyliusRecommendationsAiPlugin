<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;

class CategoryFormatterService
{
    /**
     * Génère les catégories d'un produit sous forme de chemin hiérarchique.
     *
     * @return array<string> $data
     */
    public function generateCategoriesForProduct(ProductInterface $product): array
    {
        $categories = [];

        // Récupérer les associations product-taxons (catégories)
        /** @var ProductTaxonInterface $productTaxon */
        foreach ($product->getProductTaxons() as $productTaxon) {
            $taxon = $productTaxon->getTaxon();

            if ($taxon instanceof TaxonInterface) {
                // Construire le chemin complet pour chaque catégorie
                $categories[] = $this->buildCategoryPath($taxon);
            }
        }

        if (0 === \count($categories)) {
            $categories[] = 'All';
        }

        return $categories;
    }

    /**
     * Construit le chemin de catégorie avec le symbole '>'.
     * Remplace '>' dans le nom de la catégorie s'il est présent.
     */
    private function buildCategoryPath(TaxonInterface $taxon): string
    {
        $path = [];

        while (null !== $taxon) {
            if (null !== $taxon->getName()) {
                $name = str_replace('>', '-', $taxon->getName());
                array_unshift($path, $name);

                $taxon = $taxon->getParent();
            }
        }

        // Joindre les noms de catégories avec le symbole '>'
        return implode(' > ', $path);
    }
}
