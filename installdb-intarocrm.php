<?
    require './cfg/connect.inc.php';

    mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Error connecting to MySQL server: ' . mysql_error());

    mysql_select_db(DB_NAME) or die('Error selecting MySQL database: ' . mysql_error());

    $templine = '';
    $lines = file("./sql/ss_intarocrm.sql");

    foreach($lines as $line_num => $line) {
        if(substr($line, 0, 2) != '--' && $line != '') {
            $templine .= $line;
            if(substr(trim($line), -1, 1) == ';') {
                mysql_query($templine) or print('Error performing query \'<b>' . $templine . '</b>\': ' . mysql_error() . '<br /><br />');
                $templine = '';
            }
        }
    }
?>