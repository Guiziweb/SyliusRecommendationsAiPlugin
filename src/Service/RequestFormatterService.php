<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

class RequestFormatterService
{
    private string $projectId;

    private string $location;

    private string $catalog;

    private string $branch;

    public function __construct(
        string $projectId,
        string $location,
        string $catalog,
        string $branch
    ) {
        $this->projectId = $projectId;
        $this->location = $location;
        $this->catalog = $catalog;
        $this->branch = $branch;
    }

    public function formatProductPath(string $productCode): string
    {
        return \sprintf(
            'projects/%s/locations/%s/catalogs/%s/branches/%s/products/%s',
            $this->projectId,
            $this->location,
            $this->catalog,
            $this->branch,
            $productCode
        );
    }

    public function formatUserEventPath(): string
    {
        return \sprintf(
            'projects/%s/locations/%s/catalogs/%s',
            $this->projectId,
            $this->location,
            $this->catalog
        );
    }

    public function formatPlacementPath(string $placementId): string
    {
        return \sprintf(
            'projects/%s/locations/%s/catalogs/%s/placements/%s',
            $this->projectId,
            $this->location,
            $this->catalog,
            $placementId
        );
    }

    public function formatCatalogPath(): string
    {
        return \sprintf(
            'projects/%s/locations/%s/catalogs/%s/branches/%s',
            $this->projectId,
            $this->location,
            $this->catalog,
            $this->branch
        );
    }
}
