<?php

namespace Guiziweb\SyliusRecommendationsAiPlugin\Command;

use Guiziweb\SyliusRecommendationsAiPlugin\Service\CatalogSyncService;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncProductsCommand extends Command
{
    protected static $defaultName = 'create-products';

    private CatalogSyncService $catalogSyncService;
    private ProductRepositoryInterface $productRepository;

    public function __construct(CatalogSyncService $catalogSyncService, ProductRepositoryInterface $productRepository)
    {
        parent::__construct();
        $this->catalogSyncService = $catalogSyncService;
        $this->productRepository = $productRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Synchronizes products with Google Recommendations AI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            if ($product->getId() === 1) {
                $this->catalogSyncService->syncProductWithGoogle($product);
                $output->writeln(sprintf('Product "%s" synced.', $product->getName()));
            }
        }

        return Command::SUCCESS;
    }
}