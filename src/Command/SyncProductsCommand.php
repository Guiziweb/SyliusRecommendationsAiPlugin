<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Command;

use Doctrine\ORM\EntityNotFoundException;
use Guiziweb\SyliusRecommendationsAiPlugin\Api\ProductService;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncProductsCommand extends Command
{
    protected static $defaultName = 'sync:google-products';

    private ProductService $catalogSyncService;

    /**
     * @var ProductRepositoryInterface<ProductInterface>
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ChannelRepositoryInterface<ChannelInterface>
     */
    private ChannelRepositoryInterface $channelRepository;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(ProductService $catalogSyncService, ProductRepositoryInterface $productRepository, ChannelRepositoryInterface $channelRepository)
    {
        parent::__construct();
        $this->catalogSyncService = $catalogSyncService;
        $this->productRepository = $productRepository;
        $this->channelRepository = $channelRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronizes products with Google Recommendations AI')
            ->addArgument('channel_code', InputArgument::REQUIRED, 'The channel code for the products')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelCode = $input->getArgument('channel_code'); // Récupération de l'argument

        $channel = $this->channelRepository->findOneByCode($channelCode);
        if (!$channel instanceof ChannelInterface) {
            throw new EntityNotFoundException("'$channelCode' not found");
        }
        $products = $this->productRepository->findAll();

        foreach ($products as $product) {
            if ($product instanceof ProductInterface) {
                $this->catalogSyncService->createOrUpdateGoogleProduct($product, $channel, $output);
            }
        }

        return Command::SUCCESS;
    }
}
