<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cdcpay\CDCCheckout\Model\Config\Source;

/**
 *Crypto Model
 */
class Crypto implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {

        return [
            ['value' => 'BTC', 'label' => __('BTC')],
            ['value' => 'BCH', 'label' => __('BCH')]
        ];

    }
}
