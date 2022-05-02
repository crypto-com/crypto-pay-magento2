<?php
namespace Cdcpay\CDCCheckout\Observer;
use Magento\Framework\Event\ObserverInterface;

class CDCEmail implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    try{
        $order = $observer->getEvent()->getOrder();
        $this->_current_order = $order;

        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if($payment == "cdccheckout"){
            $this->stopNewOrderEmail($order);
        }
    }
    catch (\ErrorException $ee){

    }
    catch (\Exception $ex)
    {

    }
    catch (\Error $error){

    }

}

public function stopNewOrderEmail(\Magento\Sales\Model\Order $order){
    $order->setCanSendNewEmailFlag(false);
    $order->setSendEmail(false);
    try{
        $order->save();
    }
    catch (\ErrorException $ee){

    }
    catch (\Exception $ex)
    {

    }
    catch (\Error $error){

    }
}
} 