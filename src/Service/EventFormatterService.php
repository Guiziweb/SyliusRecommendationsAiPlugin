<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

use DateTimeInterface;
use Google\Cloud\Retail\V2\ProductDetail;
use Google\Cloud\Retail\V2\UserEvent;
use Google\Protobuf\Int32Value;
use Google\Protobuf\Timestamp;
use Guiziweb\SyliusRecommendationsAiPlugin\Api\EventService;
use Guiziweb\SyliusRecommendationsAiPlugin\DTO\ProductData;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

class EventFormatterService
{
    private ProductFormatterService $productFormatterService;

    private TransactionFormatterService $transactionFormatterService;

    public function __construct(
        ProductFormatterService $productFormatterService,
        TransactionFormatterService $transactionFormatterService,
    ) {
        $this->productFormatterService = $productFormatterService;
        $this->transactionFormatterService = $transactionFormatterService;
    }

    /**
     * @param ProductData[] $data
     */
    public function generateEventUserFromSyliusOrder(string $eventName, array $data, int $userId, ChannelInterface $channel, ?OrderInterface $order = null): UserEvent
    {
        $productDetails = [];

        foreach ($data as $productData) {
            $productDetail = new ProductDetail();

            $product = $this->productFormatterService->generateGoogleProductFromSyliusProduct($productData->getProduct(), $channel);
            $productDetail->setProduct($product);

            if (EventService::ADD_TO_CART === $eventName || EventService::PURCHASE_COMPLETE === $eventName) {
                $googleQuantity = new Int32Value();
                $googleQuantity->setValue($productData->getQuantity());
                $productDetail->setQuantity($googleQuantity);
            }

            $productDetails[] = $productDetail;
        }

        $userEvent = new UserEvent();

        if (EventService::PURCHASE_COMPLETE === $eventName && $order instanceof OrderInterface) {
            $payment = $order->getLastPayment();

            if ($payment instanceof PaymentInterface) {
                $transaction = $this->transactionFormatterService->generateGoogleTransactionFromOrder($order, $payment);
                $userEvent->setPurchaseTransaction($transaction);
            }

            if ($order->getCreatedAt() instanceof DateTimeInterface) {
                $seconds = $order->getCreatedAt()->getTimestamp();
                $nanos = (int) $order->getCreatedAt()->format('u') * 1000;

                $timestamp = new Timestamp([
                    'seconds' => $seconds,  // Secondes écoulées depuis l'Epoch
                    'nanos' => $nanos,       // Nanosecondes (microsecondes * 1000)
                ]);

                $userEvent->setEventTime($timestamp);
            }
        }
        $userEvent->setEventType($eventName);
        $userEvent->setProductDetails($productDetails);
        $userEvent->setVisitorId((string) $userId);

        return $userEvent;
    }
}
