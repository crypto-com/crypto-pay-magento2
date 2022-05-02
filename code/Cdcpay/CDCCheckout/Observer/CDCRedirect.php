<?php
namespace Cdcpay\CDCCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class CDCRedirect implements ObserverInterface
{
    protected $_checkoutSession;
    protected $_redirect;
    protected $_response;
    public $apiToken;
    public $network;
    private $_orderInterface;
    private $_storeManagerInterface;
    private $_resourceConnection;
    private $_customerSession;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\SessionFactory $customerSession
            ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_actionFlag = $actionFlag;
        $this->_redirect = $redirect;
        $this->_response = $response;
        $this->_orderInterface = $orderInterface;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_resourceConnection = $resourceConnection;
        $this->_customerSession = $customerSession;

    }

    function CDCC_Configuration($token,$network){
        $this->apiToken = $token;
        $config = (new \stdClass());
        $config->network = $network;
        $config->token = $token;
        return $config;
        
    }

    function CDCC_getAPIToken() {
        $env = $this->getStoreConfig('payment/cdccheckout/cdcpay_endpoint');
        $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_devtoken');
        if ($env == 'prod'):
            $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_prodtoken');
        endif;
        $this->apiToken = $cdcpay_token;
        return $this->apiToken;
    }
    
    public function CDCC_Item($config,$item_params){
        $_item = (new \stdClass());
        $_item->token =$config->token;
        $_item->endpoint =  $config->network;
        $_item->item_params = $item_params;
        $_item->invoice_endpoint = 'pay.crypto.com/api/payments';
        return $_item;
    }
    
    public function CDCC_createInvoice($item)
    {
        $post_fields = json_encode($item->item_params);

        $request_headers = array();
        $request_headers[] = 'Authorization: Bearer ' . $item->token;
        $request_headers[] = 'Content-Type: application/json';
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $item->invoice_endpoint);

        // TBR
        // curl_setopt($ch, CURLOPT_URL, 'https://webhook.site/683ec4bd-ffcf-4d69-9827-61c22b8851b5');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // TBR for testing
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($ch);

        // TBR for testing 2
        // $result2 = curl_error($ch);

        curl_close($ch);

        // TBR for testing 3
        // throw new Exception('Test error: ' . $result . $result2);

        return ($result);

    }

    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }

    public function getOrder($_order_id)
    {
        $order = $this->_orderInterface->load($_order_id);
        return $order;

    }

    public function getBaseUrl()
    {
        $storeManager = $this->_storeManagerInterface;
        return $storeManager->getStore()->getBaseUrl();
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controller = $observer->getControllerAction();
        $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

        $level = 1;

        $order_id = $this->_checkoutSession->getData('last_order_id');
        $order = $this->getOrder($order_id);
        $order_id_long = $order->getIncrementId();

        if ($order->getPayment()->getMethodInstance()->getCode() == 'cdccheckout') {
            #set to pending and override magento coding
            $order->setState('new', true);
            $order_status = $this->getStoreConfig('payment/cdccheckout/order_status');

            if(!isset($order_status)):
                $order_status = "pending";
            endif;
            $order->setStatus($order_status, true);
            $order->save();

            #get the environment
            $env = $this->getStoreConfig('payment/cdccheckout/cdcpay_endpoint');
            $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_devtoken');
            if ($env == 'prod'):
                $cdcpay_token = $this->getStoreConfig('payment/cdccheckout/cdcpay_prodtoken');
            endif;

            $config = $this->CDCC_Configuration($cdcpay_token,$env);

            //create an item, should be passed as an object'
            $params = (new \stdClass());
            $params->amount = ((float) $order['base_grand_total']) * 100;
            $params->currency = $order['base_currency_code']; //set as needed

            #buyer email
            $userSession = $this->_customerSession;

            $buyerInfo = (new \stdClass());
            
            $buyerInfo->name = $order->getBillingAddress()->getFirstName() . ' ' . $order->getBillingAddress()->getLastName();
            $buyerInfo->email = $order->getCustomerEmail();
            $buyerInfo->plugin_name = $this->getExtensionVersion();
          
            #address info
            $billingAddress = $order->getBillingAddress()->getData();
            setcookie('buyer_email', $buyerInfo->email, time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_first_name', $order->getBillingAddress()->getFirstName() , time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_last_name', $order->getBillingAddress()->getLastName() , time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_street', $billingAddress['street'] , time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_city', $billingAddress['city'] , time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_postcode', $billingAddress['postcode'] , time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('buyer_telephone', $billingAddress['telephone'] , time() + (86400 * 30), "/"); // 86400 = 1 day

            $params->metadata = $buyerInfo;
            $params->order_id = trim($order_id_long);

            setcookie('oar_order_id', $order_id_long, time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('oar_billing_lastname', $order->getBillingAddress()->getLastName(), time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('oar_email', $order->getCustomerEmail(), time() + (86400 * 30), "/"); // 86400 = 1 day

            $params->return_url = $this->getBaseUrl() .'cdcpay-invoice/?order_id='.$order_id_long;
            $params->cancel_url = $this->getBaseUrl() . 'rest/V1/cdcpay-cdccheckout/close?orderID='.$order_id_long;

            $item = $this->CDCC_Item( $config,$params);

            //this creates the invoice with all of the config params from the item
            $invoice = $this->CDCC_createInvoice($item);
            $invoiceData = json_decode($invoice);

            $this->_redirect->redirect($this->_response, $invoiceData->payment_url);
        }
    } //end execute function

    public function getExtensionVersion()
    {
        return 'CDCCheckout_Magento2_0.1';
    }

}
