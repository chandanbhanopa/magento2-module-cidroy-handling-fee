<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\Quote;

use Cidroy\HandlingFee\Logger\Logger;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

class QuoteManagementPlugin
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Logger $logger
    ) {}

    /**
     * After the order is created and saved from the quote, persist
     * handling_fee and base_handling_fee from sales_order.
     */
    public function afterSubmit(
        QuoteManagement $subject,
        OrderInterface $order,
        Quote $quote
    ): OrderInterface {
        $shippingAddress = $quote->getShippingAddress();
        $handlingFee     = (float) $shippingAddress->getHandlingFee();
        $baseHandlingFee = (float) $shippingAddress->getBaseHandlingFee();

        if ($handlingFee <= 0) {
            return $order;
        }

        $order->setHandlingFee($handlingFee);
        $order->setBaseHandlingFee($baseHandlingFee);
        $this->orderRepository->save($order);

        $this->logger->debug('Handling fee saved to order via plugin', [
            'order_id'         => $order->getEntityId(),
            'handling_fee'     => $handlingFee,
            'base_handling_fee' => $baseHandlingFee,
        ]);

        return $order;
    }
}
