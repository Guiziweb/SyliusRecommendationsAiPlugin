<?php

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;


use Google\Cloud\Retail\V2\Client\CatalogServiceClient;
use Google\Cloud\Retail\V2\ListCatalogsRequest;
use Sylius\Component\Core\Model\ProductInterface;

class CatalogSyncService
{
    public function syncProductWithGoogle(ProductInterface $product)
    {
        $client =  new CatalogServiceClient();

        $request = new ListCatalogsRequest();
        $request->setParent('projects/level-ward-435914-n2/locations/global');

        $catalogs = $client->listCatalogs(
            $request
        );

        foreach ($catalogs as $catalog) {
            echo 'Catalog: ' . $catalog->getName() . PHP_EOL;
        }
    }
}