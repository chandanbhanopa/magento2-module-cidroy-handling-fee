<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Block\Checkout\Success;

use Cidroy\HandlingFee\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class OrderSummary extends Template
{
    private ?Order $order = null;

    public function __construct(
        Context $context,
        private readonly CheckoutSession $checkoutSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        if ($this->order === null) {
            $orderId = $this->checkoutSession->getLastOrderId();
            if ($orderId) {
                $this->order = $this->orderRepository->get((int) $orderId);
            }
        }

        return $this->order;
    }

    public function formatPrice(float $amount): string
    {
        $order = $this->getOrder();

        return $this->priceCurrency->format(
            $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $order?->getStoreId()
        );
    }

    public function getHandlingFeeLabel(): string
    {
        $order = $this->getOrder();
        return $this->config->getLabel($order ? (int) $order->getStoreId() : null);
    }
}
