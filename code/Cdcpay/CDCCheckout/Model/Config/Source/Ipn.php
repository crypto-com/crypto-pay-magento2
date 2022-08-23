<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cdcpay\CDCCheckout\Model\Config\Source;


/**
 * IPN Model
 */
class Ipn implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            'processing' => 'Processing',
            'pending' => 'Pending',
        ];

    }
}
