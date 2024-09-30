<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Message;

class UserEventPurchaseCompleteMessage
{
    private string $eventName;

    private int $orderId;

    private int $userId;

    private int $channelId;

    public function __construct(string $eventName, int $orderId, int $userId, int $channelId)
    {
        $this->eventName = $eventName;
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->channelId = $channelId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
