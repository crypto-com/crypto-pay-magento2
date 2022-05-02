<?php
 
namespace Cdcpay\CDCCheckout\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
 
class CDCPaymentMethodAvailable implements ObserverInterface
{
    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig 
    ) {
       
        $this->_scopeConfig = $scopeConfig;
       

    }
    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if($observer->getEvent()->getMethodInstance()->getCode()=="cdccheckout"){
            $env = $this->getStoreConfig('payment/cdccheckout/cdcpay_endpoint');
            $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_devtoken');
            if ($env == 'prod'):
                $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_prodtoken');
            endif;
            if($cdcpay_token == ''):
                #hide the payment method
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false); //this is disabling the payment method at checkout page
            endif;


           
        }
  
    }
}
