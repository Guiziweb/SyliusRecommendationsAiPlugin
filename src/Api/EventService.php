<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Api;

use Exception;
use Google\Cloud\Retail\V2\Client\UserEventServiceClient;
use Google\Cloud\Retail\V2\WriteUserEventRequest;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductData;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\EventFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\RequestFormatterService;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

class EventService
{
    public const DETAIL_PAGE_VIEW = 'detail-page-view';

    public const ADD_TO_CART = 'add-to-cart';

    public const REMOVE_FROM_CART = 'remove-from-cart';

    public const PURCHASE_COMPLETE = 'purchase-complete';

    public const SHOPPING_CART_PAGE_VIEW = 'shopping-cart-page-view';

    private UserEventServiceClient $userEventServiceClient;

    private EventFormatterService $eventFormatterService;

    private RequestFormatterService $requestFormatterService;

    private LoggerInterface $logger;

    public function __construct(
        RequestFormatterService $requestFormatterService,
        EventFormatterService $eventFormatterService,
        LoggerInterface $logger
    ) {
        $this->eventFormatterService = $eventFormatterService;
        $this->requestFormatterService = $requestFormatterService;
        $this->logger = $logger;
        $this->userEventServiceClient = new UserEventServiceClient();
    }

    /**
     * @param ProductData[] $data
     */
    public function sendUserEvent(string $eventName, array $data, int $userId, ChannelInterface $channel, ?OrderInterface $order = null): void
    {
        try {
            $userEvent = $this->eventFormatterService->generateEventUserFromSyliusOrder($eventName, $data, $userId, $channel, $order);

            $request = new WriteUserEventRequest();
            $request->setParent($this->requestFormatterService->formatUserEventPath());
            $request->setUserEvent($userEvent);

            $this->userEventServiceClient->writeUserEvent($request);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }
}
