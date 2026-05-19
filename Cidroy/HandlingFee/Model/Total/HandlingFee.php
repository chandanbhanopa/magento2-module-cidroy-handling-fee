<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Model\Total;

use Cidroy\HandlingFee\Logger\Logger;
use Cidroy\HandlingFee\Model\Config;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class HandlingFee extends AbstractTotal
{
    public function __construct(
        private readonly Config $config,
        private readonly Logger $logger
    ) {
        $this->setCode('handling_fee');
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): static {
        parent::collect($quote, $shippingAssignment, $total);

        // Reset on every collect so stale values never accumulate
        $total->setTotalAmount($this->getCode(), 0);
        $total->setBaseTotalAmount($this->getCode(), 0);
        $quote->setHandlingFee(0);
        $quote->setBaseHandlingFee(0);

        // Only collect on the shipping address; skip empty assignments
        $address = $shippingAssignment->getShipping()->getAddress();
        if ($address->getAddressType() !== Quote\Address::TYPE_SHIPPING
            || !count($shippingAssignment->getItems())
        ) {
            return $this;
        }

        $storeId = (int) $quote->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            $this->logger->debug('Handling fee disabled via config', ['store' => $storeId]);
            return $this;
        }

        if ($this->isCustomerGroupRestricted($quote, $storeId)) {
            $this->logger->debug('Handling fee skipped: restricted customer group', [
                'group_id' => $quote->getCustomerGroupId(),
                'store'    => $storeId,
            ]);
            return $this;
        }

        $baseSubtotal = (float) $total->getBaseSubtotalWithDiscount() ?: (float) $total->getBaseSubtotal();

        if ($this->isSubtotalExceeded($baseSubtotal, $storeId)) {
            $this->logger->debug('Handling fee skipped: subtotal exceeds limit', [
                'base_subtotal' => $baseSubtotal,
                'limit'         => $this->config->getExceedLimit($storeId),
                'store'         => $storeId,
            ]);
            return $this;
        }

        $feePercent      = $this->config->getFeePercent($storeId) / 100;
        $baseHandlingFee = round($baseSubtotal * $feePercent, 4);
        $handlingFee     = round(
            (float) ($total->getSubtotalWithDiscount() ?: $total->getSubtotal()) * $feePercent,
            4
        );

        $total->setTotalAmount($this->getCode(), $handlingFee);
        $total->setBaseTotalAmount($this->getCode(), $baseHandlingFee);

        // Persist on quote address (maps to quote_address table columns)
        $address->setHandlingFee($handlingFee);
        $address->setBaseHandlingFee($baseHandlingFee);

        // Persist on quote (maps to quote table columns)
        $quote->setHandlingFee($handlingFee);
        $quote->setBaseHandlingFee($baseHandlingFee);

        $this->logger->info('Handling fee applied', [
            'base_fee'      => $baseHandlingFee,
            'fee'           => $handlingFee,
            'fee_percent'   => $this->config->getFeePercent($storeId),
            'base_subtotal' => $baseSubtotal,
            'store'         => $storeId,
        ]);

        return $this;
    }

    public function fetch(Quote $quote, Total $total): ?array
    {
        $amount = $total->getTotalAmount($this->getCode());

        if (!$amount) {
            return null;
        }

        return [
            'code'  => $this->getCode(),
            'title' => $this->config->getLabel((int) $quote->getStoreId()),
            'value' => $amount,
        ];
    }

    public function getLabel(): string
    {
        return (string) __('Handling Fee');
    }

    private function isCustomerGroupRestricted(Quote $quote, int $storeId): bool
    {
        $restrictedGroups = $this->config->getRestrictedCustomerGroups($storeId);

        if (empty($restrictedGroups)) {
            return false;
        }

        return in_array((int) $quote->getCustomerGroupId(), $restrictedGroups, true);
    }

    private function isSubtotalExceeded(float $baseSubtotal, int $storeId): bool
    {
        $limit = $this->config->getExceedLimit($storeId);

        // 0 means no limit configured
        return $limit > 0 && $baseSubtotal > $limit;
    }
}
