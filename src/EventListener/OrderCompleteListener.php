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
use Guiziweb\SyliusRecommendationsAiPlugin\Message\UserEventPurchaseCompleteMessage;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OrderCompleteListener
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

    public function onOrderPostComplete(ResourceControllerEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $this->bus->dispatch(new UserEventPurchaseCompleteMessage(
            EventService::PURCHASE_COMPLETE,
            $order->getId(),
            $user->getId(),
            $this->channelContext->getChannel()->getId()
        ));
    }
}
