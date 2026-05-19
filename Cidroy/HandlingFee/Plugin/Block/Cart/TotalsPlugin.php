<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\Block\Cart;

use Cidroy\HandlingFee\Model\Config;
use Magento\Checkout\Block\Cart\Totals;

class TotalsPlugin
{
    public function __construct(
        private readonly Config $config
    ) {}

    public function afterGetJsLayout(Totals $subject, string $result): string
    {
        $layout = json_decode($result, true);

        $layout['components']['block-totals']['children']['handling-fee']['config']['title'] =
            $this->config->getLabel();

        return json_encode($layout);
    }
}
