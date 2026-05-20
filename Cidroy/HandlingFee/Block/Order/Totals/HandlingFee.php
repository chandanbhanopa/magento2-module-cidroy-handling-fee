<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Block\Order\Totals;

use Cidroy\HandlingFee\Model\Config;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

class HandlingFee extends AbstractBlock
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Called by Magento\Sales\Block\Order\Totals::_initTotals() for each child block.
     * Inserts the handling fee line immediately before grand_total when the fee is non-zero.
     */
    public function initTotals(): static
    {
        
        $parent = $this->getParentBlock();
        $order  = $parent->getOrder();
        $fee    = (float) $order->getHandlingFee();
        
        if ($fee <= 0) {
            return $this;
        }

        $parent->addTotalBefore(
            new DataObject([
                'code'       => 'handling_fee',
                'field'      => 'handling_fee',
                'strong'     => false,
                'value'      => $fee,
                'base_value' => (float) $order->getBaseHandlingFee(),
                'label'      => $this->config->getLabel((int) $order->getStoreId()),
            ]),
            'grand_total'
        );
        
        return $this;
    }
}
