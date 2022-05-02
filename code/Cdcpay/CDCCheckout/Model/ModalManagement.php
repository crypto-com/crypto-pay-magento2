<?php


namespace Cdcpay\CDCCheckout\Model;

class ModalManagement implements \Cdcpay\CDCCheckout\Api\ModalManagementInterface
{
    private $_resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
        )
    {
        $this->_resourceConnection = $resourceConnection;

    }
    /**
     * {@inheritdoc}
     */
    public function postModal()
    {
        #json ipn
        $data = json_decode(file_get_contents("php://input"), true);
    }
}
