<?php
declare(strict_types=1);

namespace Cidroy\HandlingFee\Plugin\Block\Cart;

use Cidroy\HandlingFee\Model\Config;
use Magento\Checkout\Block\Cart\Sidebar;

class SidebarPlugin
{
    public function __construct(
        private readonly Config $config
    ) {}

    public function afterGetJsLayout(Sidebar $subject, string $result): string
    {
        $layout = json_decode($result, true);

        $layout['components']['minicart_content']['children']
            ['subtotal.container']['children']['handling_fee']['config']['title'] =
            $this->config->getLabel();

        return json_encode($layout);
    }
}
