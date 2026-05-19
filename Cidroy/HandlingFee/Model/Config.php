<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED           = 'handling_fee/general/enabled';
    private const XML_PATH_ENABLE_LOG        = 'handling_fee/general/enable_log';
    private const XML_PATH_LABEL             = 'handling_fee/general/label';
    private const XML_PATH_FEE_PERCENT       = 'handling_fee/general/fee_percent';
    private const XML_PATH_EXCEED_LIMIT      = 'handling_fee/general/exceed_limit';
    private const XML_PATH_RESTRICTED_GROUPS = 'handling_fee/general/restricted_customer_groups';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function isLogEnabled(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_LOG,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function isEnabled(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getLabel(mixed $scopeCode = null): string
    {
        $label = (string) $this->scopeConfig->getValue(
            self::XML_PATH_LABEL,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        return $label !== '' ? $label : (string) __('Handling Fee');
    }

    public function getFeePercent(mixed $scopeCode = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_FEE_PERCENT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /**
     * Exceed limit is stored and compared in base currency.
     * Returns 0 when no limit is configured (fee applies to all amounts).
     */
    public function getExceedLimit(mixed $scopeCode = null): float
    {
        return (float) $this->scopeConfig->getValue(
            self::XML_PATH_EXCEED_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    /** @return int[] */
    public function getRestrictedCustomerGroups(mixed $scopeCode = null): array
    {
        $raw = $this->scopeConfig->getValue(
            self::XML_PATH_RESTRICTED_GROUPS,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        if (empty($raw)) {
            return [];
        }

        return array_map('intval', explode(',', (string) $raw));
    }
}
