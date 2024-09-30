<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\EventListener;

use Guiziweb\SyliusRecommendationsAiPlugin\Api\EventService;
use Guiziweb\SyliusRecommendationsAiPlugin\Message\UserEventMessage;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AddItemCartListener
{
    private ChannelContextInterface $channelContext;

    private TokenStorageInterface $tokenStorage;

    private MessageBusInterface $bus;

    public function __construct(ChannelContextInterface $channelContext, TokenStorageInterface $tokenStorage, MessageBusInterface $bus)
    {
        $this->channelContext = $channelContext;
        $this->tokenStorage = $tokenStorage;
        $this->bus = $bus;
    }

    public function onAddItemToCart(ResourceControllerEvent $event): void
    {
        $this->handleCartEvent($event, EventService::ADD_TO_CART);
    }

    public function onRemoveItemToCart(ResourceControllerEvent $event): void
    {
        $this->handleCartEvent($event, EventService::REMOVE_FROM_CART);
    }

    /**
     * Handle cart event logic for both add and remove actions.
     */
    private function handleCartEvent(ResourceControllerEvent $event, string $eventType): void
    {
        $orderItem = $event->getSubject();

        if (!$orderItem instanceof OrderItemInterface) {
            return;
        }

        $product = $orderItem->getProduct();
        if (!$product instanceof ProductInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $this->bus->dispatch(new UserEventMessage(
            $eventType,
            $product->getId(),
            $user->getId(),
            $this->channelContext->getChannel()->getId()
        ));
    }
}
