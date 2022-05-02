<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cdcpay\CDCCheckout\Model;



/**
 * Pay In Store payment method model
 */
class CDCCheckout extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'cdccheckout';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;


  

}
