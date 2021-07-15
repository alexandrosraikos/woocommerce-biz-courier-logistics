=== Biz Courier & Logistics for  WooCommerce ===
Contributors: alexandrosraikos
Donate link: https://github.com/sponsors/alexandrosraikos
Tags: woocommerce, shipping
Requires at least: 5.7
Tested up to: 5.7
Stable tag: 5.7
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A plugin designed to integrate WooCommerce with the Biz Courier logistics platform.

== Description ==

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

This documentation will be enriched as more features are added and processes get streamlined. In the meanwhile, if you have any questions about Biz Courier & Logistics for WooCommerce, you can [contact me](https://www.araikos.gr/en/contact).

== Changelog ==

= 1.0.1 =
This update includes UI corrections, documentation updates, and code cleanup.

= 1.0.0 =
This is the initial release version of Biz Courier & Logistics for WooCommerce.

Main features:

- Stock Level Synchronisation
- Shipping Rate Calculation
- Send Shipments
- Track Shipment Status

This specific version also includes various fixes.