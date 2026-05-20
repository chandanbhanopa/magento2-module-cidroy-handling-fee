<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepositoryPlugin
{
    public function __construct(
        private readonly OrderExtensionFactory $orderExtensionFactory
    ) {}

    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ): OrderInterface {
        return $this->attachHandlingFee($order);
    }

    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->attachHandlingFee($order);
        }

        return $searchResult;
    }

    private function attachHandlingFee(OrderInterface $order): OrderInterface
    {
        $extension = $order->getExtensionAttributes() ?? $this->orderExtensionFactory->create();
        $extension->setHandlingFee((float) $order->getHandlingFee());
        $extension->setBaseHandlingFee((float) $order->getBaseHandlingFee());
        $order->setExtensionAttributes($extension);

        return $order;
    }
}
