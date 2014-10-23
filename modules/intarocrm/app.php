<?
    include('../../classes/class.virtual.module.php');
    include('../../includes/database/mysql.php');
    include('../../cfg/connect.inc.php');
    include("../../cfg/tables.inc.php");
    include("../../core_functions/functions.php");
    include("../../core_functions/shipping_functions.php");
    include("../../core_functions/payment_functions.php");
    include("../../core_functions/order_status_functions.php");
    include("../../core_functions/category_functions.php");
    include('class.intarocrm.php');

    $shortopts  = "";
    $shortopts .= "e:";

    $options = getopt($shortopts);

    db_connect(DB_HOST,DB_USER,DB_PASS) or die (db_error());
    db_select_db(DB_NAME) or die (db_error());
    $intaroCrm = new IntaroCRM();

    switch ($options['e']) {
        case "icml":
            $intaroCrm->generateICML();
            break;
        case "history":
            if($intaroCrm->isConnected()) {
                $intaroCrm->updateOrders();
            }
            break;
        case "upload":
            if($intaroCrm->isConnected()) {
                $intaroCrm->uploadAll();
            }
            break;
    }
?>