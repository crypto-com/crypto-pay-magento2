<?php

namespace Cdcpay\CDCCheckout\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\ChangeQuoteControlInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use \Cdcpay\CDCCheckout\Model\AccessChangeQuoteControl;
use \Cdcpay\CDCCheckout\Model\CDCSignature;
use Exception;

class IpnManagement   implements \Cdcpay\CDCCheckout\Api\IpnManagementInterface  
{
    protected $_invoiceService;
    protected $_transaction;

    public $apiToken;
    public $network;

    public $quoteFactory;
    protected $formKey;
    protected $product;
    protected $_responseFactory;
    protected $_url;
    protected $orderSender;

    protected $_checkoutSession;
    protected $_quoteFactory;
    private $_orderInterface;
    protected $coreRegistry;

    private $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,

        Context $context,
        QuoteFactory $quoteFactory,
        ProductFactory $product,
        PageFactory $resultPageFactory
         ) {
        $this->coreRegistry = $registry;

        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;

        $this->_quoteFactory = $quoteFactory;
        $this->_orderInterface = $orderInterface;
        $this->product = $product;
        $this->customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderSender = $orderSender;
        $this->_resourceConnection = $resourceConnection;


    }

    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }

    public function getOrder($_order_id)
    {
       
        $order = $this->_orderInterface->loadByIncrementId($_order_id);
        return $order;
    }

    public function postClose()
    {
        $_checkoutSession = $this->_checkoutSession;
        $_quoteFactory = $this->_quoteFactory;
        $orderID = $_GET['orderID'];
        $order = $this->getOrder($orderID);
        $orderData = $order->getData();
        $quoteID = $orderData['quote_id'];
        
        $quote = $_quoteFactory->create()->loadByIdWithoutStore($quoteID);
        if ($quote->getId()) {
            $registry =$this->coreRegistry;
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $_checkoutSession->replaceQuote($quote);
            $RedirectUrl = $this->_url->getUrl('checkout/cart');
            $registry->register('isSecureArea', 'true');
            $order->delete();
            $registry->unregister('isSecureArea');
            $this->_responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();
            die();
        }
    }

    public function CDC_getSignatureSecret() {
        $env = $this->getStoreConfig('payment/cdccheckout/cdcpay_endpoint');
        $secret = $this->getStoreConfig('payment/cdccheckout/cdcpay_testsignsecret');
        if ($env == 'prod'):
            $secret = $this->getStoreConfig('payment/cdccheckout/cdcpay_prodsignsecret');
        endif;
        return $secret;
    }

    public function postIpn()
    {
        #json ipn
        $body = file_get_contents("php://input");
        
        $headers = getallheaders();
        $webhook_signature = $headers['Pay-Signature'];
        $webhook_signature_secret = $this->CDC_getSignatureSecret();

        if(empty($webhook_signature) || empty($webhook_signature_secret) || empty($body)) {
            throw new Exception('No signature or signature se provided.');
        }

        CDCSignature::verify_header($body, $webhook_signature, $webhook_signature_secret, null);
        
        $json = json_decode($body, true);
        $event = $json['type'];

        if ($event == 'payment.captured') {
            $payment_status = $json['data']['object']['status'];

            if ($payment_status == 'succeeded') {
                $order_id = $json['data']['object']['order_id'];
                $order = $this->getOrder($order_id);

                if (!is_null($order)) {

                    $cdcpay_ipn_mapping = $this->getStoreConfig('payment/cdccheckout/cdcpay_ipn_mapping');
                    
                    if ($cdcpay_ipn_mapping != 'processing'):
                        #$order->setState(Order::STATE_NEW)->setStatus(Order::STATE_NEW);
                        $order->setState('new', true);
                        $order->setStatus('pending', true);
                    else:
                        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                        $this->createMGInvoice($order);
                    endif;
                    $order->setCanSendNewEmailFlag(true);
                    $this->_checkoutSession->setForceOrderMailSentOnSuccess(true);
                    $this->_orderSender->send($order, true);
                    $order->save();
                    return true;
                } else {
                    throw new Exception('Cannot find order ' . $order_id);
                }
            } else {
                throw new Exception('Illegal payment status ' . $payment_status);
            }
        }

    }
    public function createMGInvoice($order)
    {
        try {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
        } catch (Exception $e) {

        }
    }
}
