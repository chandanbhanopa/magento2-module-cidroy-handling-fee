<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\Api;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;

class CartTotalRepositoryPlugin
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly TotalsExtensionFactory $totalsExtensionFactory
    ) {}

    public function afterGet(
        CartTotalRepositoryInterface $subject,
        TotalsInterface $totals,
        int $cartId
    ): TotalsInterface {
        $quote           = $this->cartRepository->get($cartId);
        $shippingAddress = $quote->getShippingAddress();
        $handlingFee     = (float) $shippingAddress->getHandlingFee();
        $baseHandlingFee = (float) $shippingAddress->getBaseHandlingFee();

        $extension = $totals->getExtensionAttributes() ?? $this->totalsExtensionFactory->create();
        $extension->setHandlingFee($handlingFee);
        $extension->setBaseHandlingFee($baseHandlingFee);
        $totals->setExtensionAttributes($extension);

        return $totals;
    }
}
