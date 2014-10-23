Installation
============

### Clone module.
``` shell
git clone git@github.com:intarocrm/shopscript-module.git
```

### Install module
``` shell
cp -r shopscript-module/* /path/to/shopscript/
```
Add this lines into:
* /index.php script, into after include ('./modules/smsmail/class.smsnotify.php');

``` php
include('./modules/intarocrm/class.intarocrm.php');
```


### Install database
```
Run the installer to the address http://yourdomain/installdb-intarocrm.php
```

### Activate via Admin interface.
Add this lines into:
* /includes/admin/modules.php script, into an array $admin_dpt

``` php
array("id"=>"intarocrm", "name"=>"IntaroCRM" )
```
### Export Customers and Orders
Start with ssh script for cutomers and orders export

``` shell
/usr/bin/php /path/to/modules/intarocrm/app.php -e upload
```

### Export Catalog
Setup cron job for periodically catalog export

``` shell
* */12 * * * /usr/bin/php /path/to/modules/intarocrm/app.php -e icml
```

Into your CRM settings set path to exported file

``` shell
/modules/intarocrm/upload/intarocrm.xml
```

### Exchange setup

#### Export new order from shop to CRM

Add this lines into:
* /core_functions/order_functions.php script, into ordOrderProcessing function after stChangeOrderStatus($orderID, $statusID)

``` php
$params = array(
      'customerInfo'    => $customerInfo,
      'orderId' 	      => $orderID,
      'createdAt'       => $order_time,
      'deliveryCost'    => ( (float) $shipping_costUC ),
      'paymentType'     => $paymentMethod["PID"],
      'deliveryType'    => $shippingMethod["SID"],
      'status'          => $statusID,
      'customerComment' => $customers_comment,
      'shippingAddress' => $shippingAddress,
      'discountPercent' => ( (float) $discount_percent ),
      'phone'           => $_POST["additional_field_2"],
      'cart'            => $cartContent["cart_content"]
  );

  $intaroCrm = new IntaroCRM();
  $intaroCrm->addOrder($params);
```

#### Export new order from CRM to shop

Setup cron job for exchange between CRM & your shop

``` shell
* */12 * * * /usr/bin/php /path/to/modules/intarocrm/app.php -e history
```
