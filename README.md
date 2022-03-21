![A blue delivery truck on the road.](docs/img/banner.png)

# Biz Courier & Logistics for WooCommerce

A plugin designed to integrate WooCommerce with the Biz Courier & Logistics platform.

## Features

### Stock Levels Synchronization

If you want to be able to update remaining stock quantities from your Biz warehouse, simply use the same product SKUs that you use in your Biz warehouse, enable stock management, enable the "Biz Warehouse" option on the product or variation you wish to synchronise and click on _Synchronize_. To synchronize the stock levels for all products in your WooCommerce catalogue, select _Get stock levels_ in the _All Products_ page.

_Note: This doesn't automatically add products to your WooCommerce catalogue. It only updates stock levels._

### Shipping Rate Calculation

To enable shipping rate calculation:

1. Go to _WooCommerce Settings > Shipping > Biz Courier Shipping_.
1. Populate the fields with your contract rates and Save.
1. Create your WooCommerce Shipping Zones and add the Biz Courier shipping method.
1. Adjust pricing for each zone depending on your contract.

Save your Biz Courier credentials into _Dashboard > WooCommerce Settings > Integration > Biz Courier & Logistics_.

_Note: Consult Biz Courier for your shipping country's list of inaccessible areas and their postal codes._
_Note: Cash on Delivery fee calculation is included only when using the native WooCommerce COD payment method._

### Manage Shipments

#### Send

The fastest way to submit your shipment to Biz Courier, straight from WooCommerce. At each order page, you can click on _Send shipment_. You can also manually add and edit shipment voucher numbers.

You also have the option to enable automatic shipment sending when your order enters a specified state in _WooCommerce Settings > Shipping > Biz Courier Shipping_.

_Note: You need to synchronize your stock levels first for Biz Warehouse enabled products in the order._

#### Tracking & automatic status updates

If a shipment is sent from the WordPress Dashboard, you can view the tracking code and the shipment status history directly from the page of the order.

You can also enable automatic order status updates to change `processing` order statuses to `completed`, `cancelled`, or `failed` based on Biz Courier data.

#### Modify

You can send shipment modification instructions directly to Biz Courier. Simply head to your order, make sure you have submitted a Biz shipment for that order, and click on _Modify shipment_. You can also view your history of modifications in the order's notes.

#### Cancel

You can request cancellation of a Biz Courier shipment by heading to your order and clicking _Request shipment cancellation_.

You also have the option to enable automatic shipment cancellation requests when your order enters a specified state in _WooCommerce Settings > Shipping > Biz Courier Shipping_.

## Getting Started

Read on below to get started with Biz Courier & Logistics for WooCommerce.

### Requirements

Recommended requirements for this plugin are PHP 7.4+ (with `soap` extension enabled), WordPress 5.7+, WooCommerce 5.6.0+.

_Biz Courier & Logistics for WooCommerce has not been tested with prior versions of PHP, WordPress or WooCommerce._

**Note: PHP 8.1 users will notice many deprecation notices, but the plugin will function normally. [This is also true for the rest of WordPress 5.9](https://make.wordpress.org/core/2022/01/10/wordpress-5-9-and-php-8-0-8-1/).** This will be resolved over time as it requires deeper code base restructuring, due to significant changes in the PHP language down the line.

### Installation

Simply setup WordPress with WooCommerce and enable the plugin.

1. Download and setup WordPress from [the official website](https://wordpress.org/).
1. Download and setup WooCommerce from the WordPress dashboard.
1. Download the `wc-biz-courier-logistics.zip` package of Biz Courier & Logistics for WooCommerce from the latest release in the [releases](https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/releases) page and install it via the WordPress Dashboard.

You initially have to set up your credentials.

1. Login to the WordPress Dashboard
1. Go to _WooCommerce Settings > Integration > Biz Courier & Logistics_.
1. Register the credentials that were provided to you by Biz Courier.

Then you can set up your shipping options in _WooCommerce Settings > Shipping > Biz Courier Shipping_.

### Upgrade

To upgrade, simply download the latest `wc-biz-courier-logistics.zip` package of Biz Courier & Logistics for WooCommerce from the latest release in the [releases](https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/releases) page and install it via the WordPress Dashboard.

## Frequently Asked Questions

**Why is this plugin not part of the WordPress.org plugin directory?**

Unfortunately this WordPress plugin cannot be featured in the WordPress plugin directory, due to the strict guidelines regarding the mentions of trademarks (see _Legal Disclaimer_ below) and usage of GPLv2 or later as the open source license.

However, despite the lack of it's availability in the official plugin directory, this plugin is being consistently maintained for optimal performance and stability with the latest WordPress versions. Refer to the badge below for the code reliability rating by SonarCloud:

[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=bugs)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)

**Is this plugin safe to use?**

No personal information, nor any analytics data of any kind is processed within the scope of this plugin. Your assigned Biz Courier & Logistics credentials are safely stored in your WordPress database. Click on the badge below to learn more information regarding the overall quality status of the current version of Biz Courier & Logistics for WooCommerce:

[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=vulnerabilities)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)

**Which languages are supported by this plugin?**

Currently, Biz Courier & Logistics for WooCommerce supports:

- :us: English
- :greece: Greek

If you'd like to contribute to the translation of this plugin in other supported Biz Courier locations, such as:

- :czech_republic: Czech
- :hungary: Hungarian
- :it: Italian
- :poland: Polish
- :romania: Romanian
- :slovakia: Slovak
- :slovenia: Slovenian

You can join the public crowdsourcing translation effort [here](https://poeditor.com/join/project?hash=Vkqajt1Tio) through the POEditor platform.

**What can I do to ensure the future of this plugin?**

You got me, this isn't a frequently asked question, but it's an important one if this plugin has really helped you. There are two ways you can help ensure the future of Biz Courier & Logistics for WooCommerce.

_Option #1: Contribute_

You are free to create issues for me to review. Please be thorough in your explanations, as it will help me help you, faster and better. In case you'd like to dive in to the source code yourself, you'd be happy to find out that everything is clearly written and properly documented in docblocks.

[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=alexandrosraikos_woocommerce-biz-courier-logistics&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=alexandrosraikos_woocommerce-biz-courier-logistics)

_Option #2: Donate_

Although I do try to keep this plugin stable and constantly up to date, I usually do it at the expense of my spare time. Consider supporting me with a donation through [GitHub Sponsors](https://github.com/sponsors/alexandrosraikos), so that I can be allowed to pour more time and care into it.

## Legal Disclaimer

I am not affiliated with Biz Courier Services. This is a third party plugin developed and provided free of charge (non-commercial) under [The Unlicense](https://unlicense.org) used to assist users and businesses in connecting a WooCommerce installation with the Biz Courier Services API (see [Web Services for eShops](https://www.bizcourier.eu/WebServices)). All of the services provided to you by Biz Courier Services are subject to their [Terms & Conditions](https://www.bizcourier.eu/faq/usefulinformation.html) and you should always use their official software for your logistics operations, when using any complementary solutions utilising their available APIs such as this plugin ("Biz Courier & Logistics for WooCommerce").

The name "Biz Courier & Logistics", the `wc-biz-courier-logistics` identifier and any user interface text references made within this repository and the active use of the plugin are purely within the boundaries of non-commercial use, used for internal reference and functionality purposes (i.e. PHP `class` names, PHP `function` names, etc.), as well as a direct delineation of named functionalities offered by the official Biz Courier Services Web API and its descriptive identifiers.

If you are a representative of Biz Courier Services and would like to pose concerns regarding possible trademark infringement, please [contact me](https://www.araikos.gr/en/contact) directly.

The WooCommerce™️ trademark included in the documentation and title of this non-commercial project promotes the use of the free and freely available WooCommerce open-source platform, licensed under the [GNU General Public License](https://github.com/woocommerce/woocommerce/blob/trunk/license.txt).

Some banner icons were made by [Freepik]("https://www.freepik.com") from [www.flaticon.com](https://www.flaticon.com/).
