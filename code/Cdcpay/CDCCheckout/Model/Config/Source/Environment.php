<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cdcpay\CDCCheckout\Model\Config\Source;


/**
 * Environment Model
 */
class Environment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            'test' => 'Yes',
            'prod' => 'No',
        ];

    }
}
