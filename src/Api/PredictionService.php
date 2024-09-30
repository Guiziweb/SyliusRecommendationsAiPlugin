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
use Google\ApiCore\ApiException;
use Google\Cloud\Retail\V2\Client\PredictionServiceClient;
use Google\Cloud\Retail\V2\Client\UserEventServiceClient;
use Google\Cloud\Retail\V2\PredictRequest;
use Google\Cloud\Retail\V2\PredictResponse;
use Google\Cloud\Retail\V2\UserEvent;
use Google\Cloud\Retail\V2\WriteUserEventRequest;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductData;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\EventFormatterService;
use Guiziweb\SyliusRecommendationsAiPlugin\Service\RequestFormatterService;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

class PredictionService
{
    public const RECENTLY_VIEWED_DEFAULT = 'recently_viewed_default';

    private PredictionServiceClient $predictionServiceClient;

    private RequestFormatterService $requestFormatterService;

    public function __construct(
        RequestFormatterService $requestFormatterService,
        EventFormatterService $eventFormatterService,
    ) {
        $this->requestFormatterService = $requestFormatterService;
        $this->predictionServiceClient = new PredictionServiceClient();
    }


    /**
     * @param string $placement
     * @param int $userId
     * @param int $pageSize
     * @return PredictResponse
     * @throws ApiException
     */
    public function getPredictions(string $placement, int $userId, int $pageSize): PredictResponse
    {
            $userEvent = new UserEvent([
                'event_type' => EventService::SHOPPING_CART_PAGE_VIEW,
                'visitor_id' => $userId,
            ]);


            $request = new PredictRequest();
            $request->setPlacement($this->requestFormatterService->formatPlacementPath(self::RECENTLY_VIEWED_DEFAULT));
            $request->setUserEvent($userEvent);
            $request->setPageSize($pageSize);

            /** @var PredictResponse $reponse */
            return $this->predictionServiceClient->predict($request);

    }
}
