<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\MessageHandler;

use Guiziweb\SyliusRecommendationsAiPlugin\Api\EventService;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductData;
use Guiziweb\SyliusRecommendationsAiPlugin\Message\UserEventMessage;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserEventMessageHandler
{
    /**
     * @var ProductRepositoryInterface<ProductInterface>
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ChannelRepositoryInterface<ChannelInterface>
     */
    private ChannelRepositoryInterface $channelRepository;

    private EventService $eventService;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository, ChannelRepositoryInterface $channelRepository, EventService $eventService)
    {
        $this->productRepository = $productRepository;
        $this->channelRepository = $channelRepository;
        $this->eventService = $eventService;
    }

    public function __invoke(UserEventMessage $message): void
    {
        $product = $this->productRepository->find($message->getProductId());

        if (!$product instanceof ProductInterface) {
            return;
        }

        $channel = $this->channelRepository->find($message->getChannelId());

        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $this->eventService->sendUserEvent(
            $message->getEventName(),
            [
                new ProductData($product, 1),
            ],
            $message->getUserId(),
            $channel,
        );
    }
}
