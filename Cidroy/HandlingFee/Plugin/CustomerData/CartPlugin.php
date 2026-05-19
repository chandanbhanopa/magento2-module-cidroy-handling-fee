<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\CustomerData;

use Cidroy\HandlingFee\Model\Config;
use Magento\Checkout\CustomerData\Cart as CustomerDataCart;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;

class CartPlugin
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly Config $config,
        private readonly QuoteRepository $quoteRepository,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {}

    /**
     * After a product is added/updated in cart, calculate handling fee and persist it to the quote table.
     */
    public function afterSave(Cart $subject, Cart $result): Cart
    {
        $quote   = $subject->getQuote();
        $storeId = (int) $quote->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            return $result;
        }

        $baseSubtotal = (float) $quote->getBaseSubtotal();

        if ($baseSubtotal <= 0) {
            return $result;
        }

        $exceedLimit = $this->config->getExceedLimit($storeId);
        if ($exceedLimit > 0 && $baseSubtotal > $exceedLimit) {
            $this->saveHandlingFee($quote, 0, 0);
            return $result;
        }
        if ($this->isCustomerGroupRestricted($quote, $storeId)) {
            $this->saveHandlingFee($quote, 0, 0);
            return $result;
        }
        $feePercent      = $this->config->getFeePercent($storeId) / 100;
        $baseHandlingFee = round($baseSubtotal * $feePercent, 4);
        $handlingFee     = round((float) $quote->getSubtotal() * $feePercent, 4);

        $this->saveHandlingFee($quote, $handlingFee, $baseHandlingFee);

        return $result;
    }

    /**
     * Expose handling_fee data in the cart customer-data section.
     * The minicart JS reads these values — it must NOT use the checkout quote
     * model because window.checkoutConfig is absent on non-checkout pages.
     */
    public function afterGetSectionData(CustomerDataCart $subject, array $result): array
    {
        $quote   = $this->checkoutSession->getQuote();
        $storeId = (int) $quote->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            return $result;
        }

        $fee = (float) $quote->getHandlingFee();

        $result['handling_fee']           = $fee;
        $result['handling_fee_formatted'] = $fee > 0
            ? $this->priceCurrency->format($fee, false)
            : '';
        $result['handling_fee_label']     = $this->config->getLabel($storeId);

        return $result;
    }

    private function saveHandlingFee(Quote $quote, float $handlingFee, float $baseHandlingFee): void
    {
        $quote->setHandlingFee($handlingFee);
        $quote->setBaseHandlingFee($baseHandlingFee);
        $this->quoteRepository->save($quote);
    }

    private function isCustomerGroupRestricted(Quote $quote, int $storeId): bool
    {
        $restrictedGroups = $this->config->getRestrictedCustomerGroups($storeId);
        if (empty($restrictedGroups)) {
            return false;
        }
        return in_array((int) $quote->getCustomerGroupId(), $restrictedGroups, true);
    }
}
