<?php

/*
 * This file is part of Guiziweb's SyliusRecommendationsAiPlugin for Sylius.
 * (c) Guiziweb <guiziweb@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Guiziweb\SyliusRecommendationsAiPlugin\Service;

use App\Entity\Payment\Payment;
use Google\Cloud\Retail\V2\PurchaseTransaction;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Model\PaymentInterface;

class TransactionFormatterService
{
    /**
     * Génère les prix d'un produit.
     *
     * @return PurchaseTransaction $purchaseTransaction
     */
    public function generateGoogleTransactionFromOrder(OrderInterface $order, PaymentInterface $payment): PurchaseTransaction
    {
        $purchaseTransaction = new PurchaseTransaction();

        $purchaseTransaction->setCurrencyCode($payment->getCurrencyCode());

        $purchaseTransaction->setId($payment->getId());
        $purchaseTransaction->setRevenue($payment->getAmount() / 100);
        $purchaseTransaction->setTax(0);
        $purchaseTransaction->setCost(0);


        return $purchaseTransaction;
    }
}
