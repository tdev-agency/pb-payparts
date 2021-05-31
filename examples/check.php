<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 16.05.2016
 * Time: 13:41
 */

use TDevAgency\PbSdk\PayParts;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src/PayParts.php';

$StoreId  = '01841655274A4951BBAF';               //Идентификатор магазина
$Password = '6b9ac727dae5484980db6a177537869f';   //Пароль вашего магазина


$pp = new PayParts($StoreId, $Password);


$getState = $pp->getState($_GET['ORDER'], false); //orderId, showRefund

var_dump($getState);
