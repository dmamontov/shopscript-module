<?php

/**
 * IntaroCRM module
 */
if (!strcmp($sub, "intarocrm")){
    require_once('./modules/intarocrm/class.intarocrm.php');
    $IntaroCRMObj = new IntaroCRM();
    $IntaroCRMObj->generatePage();
}

?>