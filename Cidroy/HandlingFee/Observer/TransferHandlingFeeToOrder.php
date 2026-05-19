<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Observer;

use Cidroy\HandlingFee\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class TransferHandlingFeeToOrder implements ObserverInterface
{
    public function __construct(
        private readonly Logger $logger
    ) {}

    /**
     * Event: sales_model_service_quote_submit_before
     * Copies handling_fee / base_handling_fee from the quote to the order
     * so the values persist in sales_order table.
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $handlingFee     = (float) $quote->getHandlingFee();
        $baseHandlingFee = (float) $quote->getBaseHandlingFee();

        $order->setHandlingFee($handlingFee);
        $order->setBaseHandlingFee($baseHandlingFee);

        $this->logger->debug('Handling fee transferred to order', [
            'quote_id'         => $quote->getId(),
            'handling_fee'     => $handlingFee,
            'base_handling_fee' => $baseHandlingFee,
        ]);
    }
}
