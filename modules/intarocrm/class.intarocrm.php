<?php

require 'apiclient.php';

class IntaroCRM extends virtualModule
{

    var $data;

    var $dd;
    var $eCategories;
    var $eOffers;


    /**
     * Конструктор
     *
     * @param integer $moduleConfigId
     */

    function IntaroCRM($moduleConfigId = 0)
    {
        $this->ModuleType = 0;
        $this->SingleInstall = true;
        $this->ModuleVersion = 0.1;
        virtualModule::virtualModule($moduleConfigId);

        $this->getOptions();

        if ($_POST["action"] == 'save') {
            $this->dataSave($_POST);
        }

        if (isset($this->data['options']['moduleTable'])) {
            $this->getData();
        }

       
        if ($this->isConnected()) {
            $this->getHandbk();
        }
    }

    /**
     * Логгер
     *
     *@param string $message
     *@param string $type
     */
    public function logger($message, $type)
    {
        switch ($type) {
            case 'error':
                $path = $this->data['options']['errorLog'];
                error_log($this->data['options']['formatLog'] . $message, 3, $path);
                break;
            case 'order':
                $path = $this->data['options']['ordersLog'];
                file_put_contents($path, $message);
                break;
            case 'history':
                $path = $this->data['options']['historyLog'];
                file_put_contents($path, $message);
                break;
            case 'customer':
                $path = $this->data['options']['customerLog'];
                file_put_contents($path, $message);
                break;
        }
    }

    /**
     * Извлекаем ФИО
     *
     *@param string $fio
     *@return array
     */
    private function explodeFIO($fio) {
        return (!$fio) ? false : explode(" ", $fio, 3);
    }

    /**
     * Подготовка конфигов
     *
     */
    public function getOptions()
    {
        require '../../cfg/intarocrm.inc.php';

        $this->data['options']                = $options;
        $this->data['options']['date']        = date('Y-m-d H:i:s');
        $this->data['options']['formatLog']   = "[" . $this->data['options']['date'] . "]";
        $this->data['options']['errorLog']    = $this->data['options']['logDir'] . $this->data['options']['errorLog'];
        $this->data['options']['ordersLog']   = $this->data['options']['logDir'] . $this->data['options']['ordersLog'];
        $this->data['options']['historyLog']  = $this->data['options']['logDir'] . $this->data['options']['historyLog'];
        $this->data['options']['customerLog'] = $this->data['options']['logDir'] . $this->data['options']['customerLog'];
    }

    /**
     * Подготовка сохраненых настроек
     *
     */
    public function getData()
    {
        $result = db_query("SELECT * FROM `" . $this->data['options']['moduleTable'] . "`");
    
        while ($data = db_fetch_row($result)) {
            $this->data['crmValues'][ $data['key'] ] = (!unserialize($data["value"])) ? $data["value"] : unserialize($data["value"]);
        }
    }

    /**
     * Подключение к crm
     *
     */
    public function isConnected()
    {
        if (!empty($this->data['crmValues']["url"]) && !empty($this->data['crmValues']["apiKey"])) {
            $this->data['crm'] = new ApiClient(
                                       $this->data['crmValues']["url"],
                                       $this->data['crmValues']["apiKey"]
                                 );

            return true;
        } else {
            return false;
        }
    }

    /**
     * Выборка справочников
     *
     */
    public function getHandbk()
    {
        $crm = $this->data['crm'];
        $deliveryTypes = array();
        
        try {
            $deliveryTypes = $crm->deliveryTypesList();
        }
        catch (CurlException $e) {
            $this->logger($e->getMessage(), 'error');
        }
        catch (ApiException $e) {
            $this->logger($e->getMessage(), 'error');
        }

        foreach ($deliveryTypes as $key => $value) {
            $value['code'] = iconv('UTF-8', 'WINDOWS-1251', $value['code']);
            $value['name'] = iconv('UTF-8', 'WINDOWS-1251', $value['name']);

            $this->data['print']['deliveryTypes'][ $value['code'] ] = $value['name'];
        }

        $shipingModules = shGetAllShippingMethods();
        foreach ($shipingModules as $key => $value) {
            $this->data['print']['shipingModules'][ $value['SID'] ] = $value["Name"];
        }

        $orderStatusesList = array();

        try {
            $orderStatusesList = $crm->orderStatusesList();
        }
        catch (CurlException $e) {
            $this->logger($e->getMessage(), 'error');
        }
        catch (ApiException $e) {
            $this->logger($e->getMessage(), 'error');
        }

        foreach ($orderStatusesList as $key => $value) {
            $value['code'] = iconv('UTF-8', 'WINDOWS-1251', $value['code']);
            $value['name'] = iconv('UTF-8', 'WINDOWS-1251', $value['name']);
        
            $this->data['print']['orderStatusesList'][ $value['code'] ] = $value['name'];
        }

        $orderStatuses = ostGetOrderStatues(false, 'html');
        foreach ($orderStatuses as $key => $value) {
            $this->data['print']['orderStatuses'][ $value['statusID'] ] = $value['status_name'];
        }

        $paymentTypesList = array();

        try {
            $paymentTypesList = $crm->paymentTypesList();
        }
        catch (CurlException $e) {
            $this->logger($e->getMessage(), 'error');
        }
        catch (ApiException $e) {
            $this->logger($e->getMessage(), 'error');
        }

        foreach ($paymentTypesList as $key => $value) {
            $value['code'] = iconv('UTF-8', 'WINDOWS-1251', $value['code']);
            $value['name'] = iconv('UTF-8', 'WINDOWS-1251', $value['name']);

            $this->data['print']['paymentTypesList'][ $value['code'] ] = $value['name'];
        }

        $paymentMethods = payGetAllPaymentMethods();
        foreach ($paymentMethods as $key => $value) {
            $this->data['print']['paymentMethods'][ $value['PID'] ] = $value['Name'];
        }

        $this->data['print']['param']['phone'] = "Номер телефона";

        $sql = "SELECT
                    `reg_field_ID` as `id`,
                    `reg_field_name` as `name`
                FROM " . CUSTOMER_REG_FIELDS_TABLE;
        $result = db_query($sql);

        while ($param = db_fetch_row($result)) {
            $value['id'] = iconv('UTF-8', 'WINDOWS-1251', $value['id']);
            $value['name'] = iconv('UTF-8', 'WINDOWS-1251', $value['name']);
            
            $this->data['print']['params'][ $value['id'] ] = $value['name'];
        }
    }

    /**
     * Генерация страницы в административной части
     */
    public function generatePage()
    {
        global $smarty;

        $authData = (empty($this->data['crmValues']['apiKey'])) ? false : true;

        $smarty->assign('authData', $authData);

        if ($authData) {
            $smarty->assign('crmData', $this->data['crmValues']);
            $smarty->assign('printData', $this->data['print']);
        }

        $smarty->assign('admin_sub_dpt', 'intarocrm.admin.tpl.html');
    }

    /**
     * Сохранение данных
     *
     * @param array $param
     */
    public function dataSave($param)
    {
        $delivery = array();
        $statusses = array();
        $payment = array();

        if (!empty($param['delivery'])) {
            foreach ($param['delivery'] as $key => $value) {
                $value = explode("|", $value);
                $delivery[ $value[1] ] = $value[0];
            }
            $delivery = serialize($delivery);
        } else {
            $delivery = "";
        }

        if (!empty($param['statusses'])) {
            foreach ($param['statusses'] as $key => $value) {
                $value = explode("|", $value);
                $statusses[ $value[1] ] = $value[0];
            }
            $statusses = serialize($statusses);
        } else {
            $statusses = "";
        }

        if (!empty($param['payment'])) {
            foreach ($param['payment'] as $key => $value) {
                $value = explode("|", $value);
                $payment[ $value[1] ] = $value[0];
            }
            $payment = serialize($payment);
        } else {
            $payment = "";
        }

        if (!empty($param['params'])) {
            foreach ($param['params'] as $key => $value) {
                $value = explode("|", $value);
                $payment[ $value[1] ] = $value[0];
            }
            $params = serialize($params);
        } else {
            $params = "";
        }

        $sql = "UPDATE `" . $this->data['options']['moduleTable'] . "`
                SET `value` = (case `key`
                    when 'apiKey' then '" . $param["apiKey"] . "'
                    when 'url' then '" . $param["url"] . "'
                    when 'delivery' then '" . $delivery . "'
                    when 'statusses' then '" . $statusses . "'
                    when 'payment' then '" . $payment . "'
                    when 'params' then '" . $params . "' end)
                WHERE `key` in ('apiKey', 'url', 'delivery', 'statusses', 'payment', 'params')";

        db_query($sql);
    }

    /**
     * Генерация ICML
     *
     */
    public function generateICML()
    {
        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="' . date('Y-m-d H:i:s')  .'">
                <shop>
                    <name>' . iconv('WINDOWS-1251', 'UTF-8', $this->data['options']['shopName']) . '</name>
                    <company>' . iconv('WINDOWS-1251', 'UTF-8', $this->data['options']['companyName']) .'</company>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';

        $xml = new SimpleXMLElement($string, LIBXML_NOENT |LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $this->dd = new DOMDocument();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = true;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();

        if (!file_exists($this->data['options']['icmlDir'])) {
            mkdir($this->data['options']['icmlDir'], 0755);
        }
    
        $this->dd->save($this->data['options']['icmlDir'] . 'intarocrm.xml');
    }

    /**
     * Выбор категорий
     *
     */
    public function addCategories()
    {
        $category = catGetCategoryCList();
    
        foreach ($category as $key => $value) {
            if(empty($value['name']) || !$value['name']) continue;
            $value['name'] = iconv('WINDOWS-1251', 'UTF-8', $value['name']);
            $value['categoryID'] = iconv('WINDOWS-1251', 'UTF-8', $value['categoryID']);
    
            $e = $this->eCategories->appendChild($this->dd->createElement('category', $value['name']));
            $e->setAttribute('id',$value['categoryID']);
        }
    }

    /**
     * Выбор продукции
     *
     */
    public function addOffers()
    {
        $sql = "SELECT * FROM `" . PRODUCTS_TABLE . "`
                LEFT JOIN `" . PRODUCT_PICTURES . "`
                ON " . PRODUCTS_TABLE . ".productID = " . PRODUCT_PICTURES .".productID
                AND " . PRODUCTS_TABLE . ".default_picture = " . PRODUCT_PICTURES .".photoID
                ORDER BY sort_order";
        $result = db_query($sql);
        while ($offer = db_fetch_row($result)) {

            foreach($offer as $key => $value) {
                $offer[$key] = iconv('WINDOWS-1251', 'UTF-8', $value);
            }
            
            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));

            $e->setAttribute('id', $offer['productID']);
            $e->setAttribute('productId', $offer['productID']);
            $e->setAttribute('quantity', $offer['in_stock']);
            $e->setAttribute('available', $offer['enabled'] ? 'true' : 'false');

            $e->appendChild($this->dd->createElement('categoryId', $offer['categoryID']));
            $e->appendChild($this->dd->createElement('name'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('productName'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('price', $offer['Price']));

            if (!is_null($offer['list_price']) && $offer['list_price'] != 0) {
                $e->appendChild($this->dd->createElement('purchasePrice', $offer['list_price']));
            }

            if (!is_null($offer['default_picture'])) {
                    if (file_exists('./products_pictures/'.$offer["filename"])) {
                        $e->appendChild($this->dd->createElement('picture', htmlentities("http://www.eurosuvenir.ru/products_pictures/".$offer["filename"])));
                    }
            }

            $e->appendChild($this->dd->createElement('url', "http://www.eurosuvenir.ru/index.php?productID=".$offer['productID']));

            if ($offer['product_code']) {
                $article = $this->dd->createElement('param');
                $article->setAttribute('name', 'article');
                $article->appendChild($this->dd->createTextNode($offer['product_code']));
                $e->appendChild($article);
            }

            if ($offer['weight']) {
                $weight = $this->dd->createElement('param');
                $weight->setAttribute('name', 'weight');
                $weight->appendChild($this->dd->createTextNode($offer['weight']));
                $e->appendChild($weight);
            }
        }
    }

    /**
     * Добавление заказа
     *
     * @param array $param - массив свойст заказа
     */
    public function addOrder($param)
    {
        $crm = $this->data['crm'];

        try {
            $getCustumerId = $crm->customers(null, iconv('WINDOWS-1251', 'UTF-8', $param['customerInfo']['Email']), null, 200, 0);
        }
        catch (CurlException $e) {
            $this->logger($e->getMessage(), 'error');
        }
        catch (ApiException $e) {
            $this->logger($e->getMessage(), 'error');
        }

        if (!empty($getCustumerId)) {
            $customerId = $getCustumerId[0]['externalId'];
        }

        foreach ($this->data['crmValues']["payment"] as $key => $value) {
            if($value == $param["paymentType"]) {
                $param["paymentType"] = $key;
            } else {
                $param["paymentType"] = 'cash';
            }
        }

        foreach ($this->data['crmValues']["delivery"] as $key => $value) {
            if($value == $param["deliveryType"]) {
                $param["deliveryType"] = $key;
            } else {
                $param["deliveryType"] = 'courier';
            }
        }

        foreach ($this->data['crmValues']["statusses"] as $key => $value) {
            if($value == $param["status"]) {
                $param["status"] = $key;
            } else {
                $param["status"] = 'new';
            }
        }

        $orderData = array(
            'externalId'      => iconv('WINDOWS-1251', 'UTF-8', $param["orderId"]),
            'createdAt'       => iconv('WINDOWS-1251', 'UTF-8', $param["createdAt"]),
            'orderType'       => 'eshop-individual',
            'orderMethod'     => 'shopping-cart',
            'email'           => iconv('WINDOWS-1251', 'UTF-8', $param["customerInfo"]["Email"]),
            'number'          => iconv('WINDOWS-1251', 'UTF-8', $param["orderId"]),
            'paymentType'     => iconv('WINDOWS-1251', 'UTF-8', $param["paymentType"]),
            'status'          => iconv('WINDOWS-1251', 'UTF-8', $param["status"]),
            'customerComment' => iconv('WINDOWS-1251', 'UTF-8', $param["customerComment"]),
            'discountPercent' => iconv('WINDOWS-1251', 'UTF-8', $param["discountPercent"]),
            'delivery'        => array(
                                    'code' => iconv('WINDOWS-1251', 'UTF-8', $param["deliveryType"]),
                                    'cost' => iconv('WINDOWS-1251', 'UTF-8', $param["deliveryCost"]),
                                 ),
        );

        if (!$param["customerInfo"]["last_name"]) {
            $user['name'] = $this->explodeFIO($user['firstName']);

            switch (count($user['name'])) {
                case 0:
                    $orderData['firstName']  = 'ФИО  не указано';
                    break;
                case 1:
                    $orderData['firstName']  = $user['name'][0];
                    break;
                case 2:
                    $orderData['lastName']   = $user['name'][0];
                    $orderData['firstName']  = $user['name'][1];
                    break;
                case 3:
                    $orderData['lastName']   = $user['name'][0];
                    $orderData['firstName']  = $user['name'][1];
                    $orderData['patronymic'] = $user['name'][2];
                    break;
            }

        } else {
            $orderData['firstName'] = iconv('WINDOWS-1251', 'UTF-8', $param["customerInfo"]["first_name"]);
            $orderData['lastName']  = iconv('WINDOWS-1251', 'UTF-8', $param["customerInfo"]["last_name"]);
        }

        if (!empty($getCustumerId)) {
            $orderData['customerId'] = (string) $customerId;
        }

        if (!empty($param['shippingAddress']['address']) && !is_null($param['shippingAddress']['address'])) {
            $orderData["delivery"]['address']["text"] = iconv('WINDOWS-1251', 'UTF-8', $param['shippingAddress']['address']);
        }

        if (!is_null($param['shippingAddress']['country_name'])) {
            $orderData["delivery"]['address']["country"] = iconv('WINDOWS-1251', 'UTF-8', $param['shippingAddress']['country_name']);
        }

        if (!is_null($param['shippingAddress']['state'])) {
            $orderData["delivery"]['address']["region"] = iconv('WINDOWS-1251', 'UTF-8', $param['shippingAddress']['state']);
        }

        if (!is_null($param['shippingAddress']['city'])) {
            $orderData["delivery"]['address']["city"] = iconv('WINDOWS-1251', 'UTF-8', $param['shippingAddress']['city']);
        }

        if (!is_null($param['shippingAddress']['zip'])) {
            $orderData["delivery"]['address']["index"] = iconv('WINDOWS-1251', 'UTF-8', $param['shippingAddress']['zip']);
        }

        foreach ($param['cart'] as $key => $value) {
            $orderData['items'][] = array(
                'initialPrice'    => iconv('WINDOWS-1251', 'UTF-8', $value["costUC"]),
                'quantity'        => iconv('WINDOWS-1251', 'UTF-8', $value["quantity"]),
                'productId'       => iconv('WINDOWS-1251', 'UTF-8', $value["productID"]),
                'productName'     => iconv('WINDOWS-1251', 'UTF-8', $value["name"])
            );
        }

        try {
            $crm->orderCreate($orderData);
        }
        catch (CurlException $e) {
            $this->logger($e->getMessage(), 'error');
        }
        catch (ApiException $e) {
            $this->logger($e->getMessage(), 'error');
        }

    }

    /**
     * Выгрузка всех пользователей
     *
     *@return array - Список всех пользователей
     */
    private function uploadUser()
    {
        $crm = $this->data['crm'];

        $res = db_query("SELECT * FROM (
                              (
                                  SELECT
                                      `ct`.`customerID`      AS `externalId`,
                                      `ct`.`last_name`       AS `lastName`,
                                      `ct`.`first_name`      AS `firstName`,
                                      `ct`.`Email`           AS `email`,
                                      `ct`.`reg_datetime`    AS `createdAt`,
                                      `cr`.`reg_field_value` AS `phone`,
                                      NULL                   AS `country`,
                                      NULL                   AS `state`,
                                      NULL                   AS `city`,
                                      NULL                   AS `addres`,
                                      NULL                   AS `zip`
                                   FROM `" . CUSTOMERS_TABLE . "` AS `ct`
                                   LEFT JOIN `" . CUSTOMER_REG_FIELDS_VALUES_TABLE . "` AS `cr`
                                   ON `cr`.`customerID` = `ct`.`customerID`
                                   WHERE `cr`.`reg_field_ID` = `" . $this->data['crmValues']["delivery"]["phone"] . "`
                              )

                               UNION

                              (
                                   SELECT
                                      CONCAT('u', `ot`.`orderID`) AS `externalId`,
                                      `ot`.`shipping_lastname`    AS `lastName`,
                                      `ot`.`shipping_firstname`   AS `firstName`,
                                      `ot`.`customer_email`       AS `email`,
                                      `ot`.`order_time`           AS `createdAt`,
                                      `qr`.`reg_field_value`      AS `phone`,
                                      `ot`.`shipping_country`     AS `country`,
                                      `ot`.`shipping_state`       AS `state`,
                                      `ot`.`shipping_city`        AS `city`,
                                      `ot`.`shipping_address`     AS `addres`,
                                      `ot`.`shipping_zip`         AS `zip`
                                   FROM `" . ORDERS_TABLE . "` AS `ot`
                                   LEFT JOIN `" . CUSTOMER_REG_FIELDS_VALUES_TABLE_QUICKREG . "` AS `qr`
                                   ON `qr`.`orderID` = `od`.`orderID`
                                   WHERE `od`.`customerID` = 0 
                                       AND `qr`.`reg_field_ID` = " . $this->data['crmValues']["delivery"]["phone"] . "
                                   GROUP BY `od`.`shipping_firstname`
                               )
                     ) AS u
                       GROUP BY 
                           `u`.`firstName`
                       ORDER BY
                           `u`.`externalId` ASC");

        $customers = array();
        $returnCustomer = array();
        while ($user = db_fetch_row($res)) {
            
            foreach ($user as $key => $value) {
                $user[ $key ] = iconv('WINDOWS-1251', 'UTF-8', $value);
                if (is_numeric($key)) {
                    unset($user[ $key ]);
                }
            }

            if (empty($user['email'])) {
                unset($user['email']);
            }

            if (!empty($user['phone'])) {
                $user['phones'][]['number'] = $user['phone'];
            }
            unset($user['phone']);

            if (empty($user['lastName'])) {
                $user['name'] = $this->explodeFIO($user['firstName']);

                switch (count($user['name'])) {
                    default:
                        $user['firstName']  = 'ФИО  не указано';
                        break;
                    case 1:
                        $user['firstName']  = $user['name'][0];
                        break;
                    case 2:
                        $user['lastName']   = $user['name'][0];
                        $user['firstName']  = $user['name'][1];
                        break;
                    case 3:
                        $user['lastName']   = $user['name'][0];
                        $user['firstName']  = $user['name'][1];
                        $user['patronymic'] = $user['name'][2];
                        break;
                }

                unset($user['name']);
            }

            if (!empty($user['country'])) {
                $user['address']['country'] = $user['country'];
            }
            unset($user['country']);

            if (!empty($user['state'])) {
                $user['address']['state'] = $user['state'];
            }
            unset($user['state']);

            if (!empty($user['city'])) {
                $user['address']['city'] = $user['city'];
            }
            unset($user['city']);

            if (!empty($user['zip'])) {
                $user['address']['index'] = $user['zip'];
            }
            unset($user['zip']);

            if (!empty($user['addres'])) {
                $user['address']['text'] = $user['addres'];
            }
            unset($user['addres']);

            $returnCustomer[$user['email']] = $user['externalId'];
            $customers[] = $user;
        }

         if (isset($customers)) {
            $customers = array_chunk($customers, 50);

            foreach ($customers as $customer) {
                try {
                    $crm->customerUpload($customer);
                    time_nanosleep(0, 250000000);
                }
                catch (CurlException $e) {
                    $this->logger($e->getMessage(), 'error');
                }
                catch (ApiException $e) {
                    $this->logger($e->getMessage(), 'error');
                }
            }
        } 

        return $returnCustomer;
    }

    /**
     * Выгрузка всех заказов
     *
     *@param array $customers - Список всех пользователей
     */
    private function uploadOrders($customers)
    {
        $crm = $this->data['crm'];

        $items = $this->getItems();
        $timeout = 1;

        $shipingMethod = shGetAllShippingMethods();
        foreach ($shipingMethod as $key => $value) {
            $this->data['reverse']["shipingModules"][iconv('WINDOWS-1251', 'UTF-8', $value["Name"])] = $value["SID"];
        }

        $paymentMethod = payGetAllPaymentMethods();
        foreach (payGetAllPaymentMethods() as $key => $value) {
            $this->data['reverse']["paymentMethods"][iconv('WINDOWS-1251', 'UTF-8', $value["Name"])] = $value["PID"];
        }

        $res = db_query("SELECT
                           `orderID`            AS `externalId`,
                           `orderID`            AS `number`,
                           `order_time`         AS `createdAt`,
                           `shipping_lastname`  AS `lastName`,
                           `shipping_firstname` AS `firstName`,
                           `customer_email`     AS `email`,
                           `shipping_type`      AS `shipping_type`,
                           `payment_type`       AS `paymentType`,
                           `customers_comment`  AS `customerComment`,
                           `statusID`           AS `status`,
                           `order_discount`     AS `discount`,
                           `shipping_country`   AS `country`,
                           `shipping_state`     AS `state`,
                           `shipping_city`      AS `city`,
                           `shipping_address`   AS `addres`,
                           `shipping_zip`       AS `zip`
                        FROM `" . ORDERS_TABLE . "`
                        ");

        $orders =array();
        while ($order = db_fetch_row($res)) {

           foreach ($order as $key => $value) {
                $order[ $key ] = iconv('WINDOWS-1251', 'UTF-8', $value);
                if (is_numeric($key)) {
                    unset($order[ $key ]);
                }
            }

            $order["orderType"] = 'eshop-individual';
            $order["orderMethod"] = 'shopping-cart';

            if ($customers[$order['email']]) {
                $order['customerId'] = $customers[$order['email']];
            } else {
                unset($order['customerId']);
            }

            if (empty($order['customerComment'])) {
                unset($order['customerComment']);
            }

            if (empty($order['lastName'])) {
                $order['name'] = $this->explodeFIO($order['firstName']);

                switch (count($order['name'])) {
                    default:
                        $order['firstName']  = 'ФИО  не указано';
                        break;
                    case 1:
                        $order['firstName']  = $order['name'][0];
                        break;
                    case 2:
                        $order['lastName']   = $order['name'][0];
                        $order['firstName']  = $order['name'][1];
                        break;
                    case 3:
                        $order['lastName']   = $order['name'][0];
                        $order['firstName']  = $order['name'][1];
                        $order['patronymic'] = $order['name'][2];
                        break;
                }

                unset($order['name']);
            }

            foreach ($this->data['crmValues']['delivery'] as $key => $value) {
                if($value == $this->data['reverse']["shipingModules"][ $order["shipping_type"] ]) {
                    $order['delivery']["code"] = iconv('WINDOWS-1251', 'UTF-8', $key);
                    unset($order["shipping_type"]);
                    break;
                }
            }

            foreach ($this->data['crmValues']['payment'] as $key => $value) {
                if($value == $this->data['reverse']["paymentMethods"][ $order["paymentType"] ]) {
                    $order["paymentType"] = iconv('WINDOWS-1251', 'UTF-8', $key);
                    break;
                }
            }

            foreach ($this->data['crmValues']['statusses'] as $key => $value) {
                if($value == $order["status"]) {
                    $order["status"] = iconv('WINDOWS-1251', 'UTF-8', $key);
                    break;
                }
            }

            if (!empty($order['addres'])) {
                $order["delivery"]['address']["text"]    = $order['addres'];
            }
            unset($order['addres']);

            if (!empty($order['country'])) {
                $order["delivery"]['address']["country"] = $order['country'];
            }
            unset($order['country']);

            if (!empty($order['state'])) {
                $order["delivery"]['address']["region"]  = $order['state'];
            }
            unset($order['state']);

            if (!empty($order['city'])) {
                $order["delivery"]['address']["city"]    = $order['city'];
            }
            unset($order['city']);

            if (!empty($order['city'])) {
                $order["delivery"]['address']["index"]   = $order['zip'];
            }
            unset($order['zip']);

            $order['items'] = $items[ $order['externalId'] ];

            $orders[] = $order;

        }

        if(isset($orders)) {
          $orders = array_chunk(array_values($orders), 50);

            foreach ($orders as $order) {
                try {
                    $crm->orderUpload($order);
                    time_nanosleep(0, 250000000);
                }
                catch (CurlException $e) {
                    $this->logger($e->getMessage(), 'error');
                }
                catch (ApiException $e) {
                    $this->logger($e->getMessage(), 'error');
                }
            }

       }
    }

    /**
     * Выгрузка всех заказов и пользователей
     *
     *@param array $customers - Список всех пользователей
     */
    public function uploadAll()
    {
        $customers = $this->uploadUser();
        $this->uploadOrders($customers);
    }

    private function getItems()
    {
        $res = db_query("SELECT 
                     " . ORDERED_CARTS_TABLE . ".orderID    AS `orderID`,
                     " . ORDERED_CARTS_TABLE . ".Price      AS `initialPrice`,
                     " . ORDERED_CARTS_TABLE . ".Quantity   AS `quantity`,
                     " . PRODUCTS_TABLE . ".productID       AS `productId`,
                     " . PRODUCTS_TABLE . ".name       AS `productName`
                FROM `" . SHOPPING_CART_ITEMS_TABLE . "`
                LEFT JOIN `" . ORDERED_CARTS_TABLE . "`
                ON " . SHOPPING_CART_ITEMS_TABLE . ".itemID = " . ORDERED_CARTS_TABLE .".itemID
                LEFT JOIN `" . PRODUCTS_TABLE . "`
                ON " . SHOPPING_CART_ITEMS_TABLE . ".productID = " . PRODUCTS_TABLE .".productID");
        
        $items = array();
        while ($item = db_fetch_row($res)) {

            foreach ($item as $key => $value) {
                $item[ $key ] = iconv('WINDOWS-1251', 'UTF-8', $value);
                if (is_numeric($key)) {
                    unset($item[ $key ]);
                }
            }

            if (!empty($item['orderID']) && !empty($item['productId'])) {
                $orderId = $item['orderID'];
                unset($item['orderID']);
                $items[ $orderId ][] = $item;
            }
        }

        return $items;
    }

    public function updateOrders()
    {
        $crm = $this->data['crm'];
        $history = $crm->orderHistory($this->getDate($this->data['options']['historyLog']));
        $this->logger($crm->getGeneratedAt()->format('Y-m-d H:i:s'), 'history');

        foreach ($history as $orderData) {
            if (isset($orderData['externalId'])) {
                $query = "UPDATE `" . ORDERS_TABLE . "` ";

                if (isset($orderData['status']) && $this->data['crmValues']["statusses"][$orderData["status"]]) {
                    $query .= "
                        SET
                            `statusID` = " . $this->data['crmValues']["statusses"][$orderData["status"]];
                }

                if (isset($orderData['paymentType']) && $this->data['crmValues']["payment"][$orderData["paymentType"]]) {
                    $query .= "
                        SET
                            `payment_type` = " . $this->data['crmValues']["payment"][$orderData["paymentType"]];
                }

                if (isset($orderData['delivery']['code']) && $this->data['crmValues']["delivery"][$orderData['delivery']['code']]) {
                    $query .= "
                        SET
                            `shipping_type` = " . $this->data['crmValues']["delivery"][$orderData['delivery']['code']];
                }

                if (isset($orderData['firstName'])) {
                    $query .= "
                        SET
                            `shipping_firstname` = " . $orderData['firstName'];
                }

                if (isset($orderData['lastName'])) {
                    $query .= "
                        SET
                            `shipping_lastname` = " . $orderData['lastName'];
                }

                $query .= "
                        WHERE
                            `orderID` = " . $orderData['externalId'];

                $query = iconv('UTF-8', 'WINDOWS-1251', $query);
                db_query($query);
            }
        }
    }

    public function getDate($file) {
        if (file_exists($file)) {
            $result = file_get_contents($file);
        } else {
            $result = date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s'))));
        }
        
        return $result;
    }
}
?>
