<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;

class SyncHandlingFeeToOrderGrid implements ObserverInterface
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $handlingFee     = (float) $order->getHandlingFee();
        $baseHandlingFee = (float) $order->getBaseHandlingFee();

        if ($handlingFee <= 0) {
            return;
        }

        $connection = $this->resourceConnection->getConnection('sales');
        $gridTable  = $this->resourceConnection->getTableName('sales_order_grid');

        $connection->update(
            $gridTable,
            [
                'handling_fee'      => $handlingFee,
                'base_handling_fee' => $baseHandlingFee,
            ],
            ['entity_id = ?' => (int) $order->getEntityId()]
        );
    }
}
