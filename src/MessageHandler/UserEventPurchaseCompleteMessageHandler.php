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
use Guiziweb\SyliusRecommendationsAiPlugin\Message\UserEventPurchaseCompleteMessage;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserEventPurchaseCompleteMessageHandler
{
    /**
     * @var OrderRepositoryInterface<OrderInterface>
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ChannelRepositoryInterface<ChannelInterface>
     */
    private ChannelRepositoryInterface $channelRepository;

    private EventService $eventService;

    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository, ChannelRepositoryInterface $channelRepository, EventService $eventService)
    {
        $this->orderRepository = $orderRepository;
        $this->channelRepository = $channelRepository;
        $this->eventService = $eventService;
    }

    public function __invoke(UserEventPurchaseCompleteMessage $message): void
    {
        $order = $this->orderRepository->find($message->getOrderId());

        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $this->channelRepository->find($message->getChannelId());

        if (!$channel instanceof ChannelInterface) {
            return;
        }
        $productDataArray = [];

        foreach ($order->getItems() as $item) {
            if (!$item->getProduct() instanceof ProductInterface) {
                continue;
            }
            $productDataArray[] = new ProductData($item->getProduct(), $item->getQuantity());
        }

        $this->eventService->sendUserEvent(
            EventService::DETAIL_PAGE_VIEW,
            $productDataArray,
            $message->getUserId(),
            $channel,
        );
    }
}
