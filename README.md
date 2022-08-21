# Crypto.com Pay Checkout for Magento 2

## Requirements

This module requires the following:

* Magento 2.x
* [Crypto.com Pay for Business](https://merchant.crypto.com/) account - [Sign Up](https://merchant.crypto.com/users/sign_up?ref=Magento2_Pay_Merchant)

## Setup

1. Download the module zip package in [Releases](https://github.com/crypto-com/crypto-pay-magento2/releases) page.
2. Extract and upload all files to your Magento 2 installation path (/code/Cdcpay in zip -> /app/code/Cdcpay in Magento server)
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

* Visit our [Official Website](https://crypto.com/pay-merchant?utm_source=Magento&utm_medium=Website&utm_campaign=Pay%20Merchant), [Merchant FAQ](https://help.crypto.com/en/collections/1512001-crypto-com-pay-merchant-faq?utm_source=Magento&utm_medium=Website&utm_campaign=Pay%20Merchant) and [API Documentation](https://pay-docs.crypto.com/).
* Open an issue if you are having troubles with this module.