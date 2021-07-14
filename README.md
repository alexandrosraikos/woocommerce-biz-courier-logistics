![The WooBiz icon.](doc/icon.png)

# WooBiz

A plugin designed to integrate WooCommerce with the Biz Courier logistics platform.

## Getting Started

Read on below to get started with WooBiz.

### Requirements

Recommended requirements for this plugin are PHP 8.0+ (with `soap` extension enabled), WordPress 5.7+, WooCommerce 5.2+.

_WooBiz has not been tested with prior versions of PHP, WordPress or WooCommerce as of yet._

### Installation

Simply setup WordPress with WooCommerce and enable the plugin.

1. Download and setup WordPress from [the official website](https://wordpress.org/).
1. Download and setup WooCommerce from the WordPress dashboard.
1. Download the .zip package of WooBiz from the [releases](https://github.com/alexandrosraikos/WooBiz/releases) page and install it via the WordPress Dashboard.

You initially have to set up your credentials.

1. Login to the WordPress Dashboard
1. Go to _WooCommerce Settings > Integration > Biz Courier & Logistics_.
1. Register the credentials that were provided to you by Biz Courier.

## Features

### Stock Levels Synchronization

If you want to be able to update remaining stock quantities from your Biz warehouse, simply use the same product SKUs that you use in your Biz warehouse and click on _Get stock levels_ in the _All Products_ page.

_Note: This doesn't automatically add products to your WooCommerce catalogue. It only updates stock levels._

### Shipping Rate Calculation

To enable shipping rate calculation:

1. Go to _WooCommerce Settings > Shipping > Biz Courier Shipping_.
1. Populate the fields with your contract rates and Save.
1. Create your WooCommerce Shipping Zones and add the Biz Courier shipping method.
1. Adjust pricing for each zone depending on your contract.

Save your Biz Courier credentials into _Dashboard > WooCommerce > Settings > Biz Courier_.

_Note: Consult Biz Courier for your shipping country's list of inaccessible areas._

### Send Shipments

The fastest way to submit your shipment to Biz Courier, straight from the WordPress Dashboard. At each order page, you can click on _Send shipment._

_Note: You need to synchronize your stock levels first._

### Track Shipments

If a shipment is sent from the WordPress Dashboard, you can view the tracking code and the shipment status history directly from the page of the order.

You can also include the `{biz_tracking_code}` dynamic field in order confirmation emails sent to your customers. To do that, head to _WooCommerce Settings > Emails_.

## Learn more

This documentation will be enriched as more features are added and processes get streamlined. In the meanwhile, if you have any questions about WooBiz, you can [contact me](https://www.araikos.gr/en/contact).
