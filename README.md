# Crypto.com Pay Checkout for Magento 2

## Requirements

This module requires the following:

* Magento 2.x
* [Crypto.com Pay for Business](https://merchant.crypto.com/) account - [Sign Up](https://merchant.crypto.com/users/sign_up?ref=Prestashop_Pay_Merchant)

## Setup

1. Download the module zip package (code -> Download ZIP)
2. Upload all files to your Magento installation path (/code/Cdcpay in zip -> /app/code/Cdcpay in Magento server)
3. Login to your server, and in the root of your Magento2 install, run the following commands:

```
php bin/magento setup:upgrade
php bin/magento module:enable Cdcpay_CDCCheckout
php bin/magento setup:static-content:deploy -f
```

* Flush your Magento2 Caches

```
php bin/magento cache:flush
```

4. You can now activate Crypto.com Pay in the *Stores->Configuration->Sales->Payment Methods*
5. Follow the instruction in setup page to setup secret key and the webhooks, and then select Test mode to start your testing.

## Support

THE PLUGIN IS STILL UNDER DEVELOPMENT. IT IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.