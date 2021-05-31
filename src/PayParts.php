<?php

namespace TDevAgency\PbSdk;

use InvalidArgumentException;

class PayParts
{

    private $amount;
    private $cancelHoldUrl = 'https://payparts2.privatbank.ua/ipp/v2/payment/cancel';                                                                                                                                                                                                //Уникальный номер платежа
    private $confirmHoldURL = 'https://payparts2.privatbank.ua/ipp/v2/payment/confirm';                                                                                                                                                                                              //Идентификатор магазина
    private $currency;                                                                                                                                                                                                                                                               //Пароль вашего магазина
    private $holdUrl = 'https://payparts2.privatbank.ua/ipp/v2/payment/hold';                                                                                                                                                                                                        //Количество частей на которые делится сумма транзакции ( >1)
    private $keysProds = ['name',
                          'count',
                          'price'];                                                                                                                                                                                                                                                  //Тип кредита
    private $log = [];                                                                                                                                                                                                                                                               //URL, на который Банк отправит результат сделки
    private $merchantType;                                                                                                                                                                                                                                                           //URL, на который Банк сделает редирект клиента
    private $options = ['partsCount',
                        'merchantType',
                        'productsList'];                                                                                                                                                                                                                                             //Окончательная сумма покупки, без плавающей точки
    private $orderId;                                                                                                                                                                                                                                                                //
    private $partsCount;
    private $password;
    private $payUrl = 'https://payparts2.privatbank.ua/ipp/v2/payment/create';
    private $prefix = 'ORDER';
    private $productsList;
    private $productsString;
    private $recipientId;
    private $redirectUrl;
    private $responseUrl;
    private $stateUrl = 'https://payparts2.privatbank.ua/ipp/v2/payment/state';
    private $storeId;

    /**
     * PayParts constructor.
     * создаём идентификаторы магазина
     * @param string $storeId - Идентификатор магазина
     * @param string $password - Пароль вашего магазина
     */
    public function __construct(string $storeId, string $password)
    {
        $this->setStoreId($storeId);
        $this->setPassword($password);
    }

    /**
     * @param array $array
     * @return string
     */
    private function calcSignature(array $array = [])
    {
        $signature = '';
        foreach ($array as $item) {
            $signature .= $item;
        }
        return (base64_encode(sha1($signature, true)));
    }

    /**
     * @param $param
     * @param $url
     * @return mixed
     */
    private function sendPost($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ["Content-Type: application/json", "Accept: application/json; charset=utf-8"]
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        $result = curl_exec($ch);
        return $result;
    }

    /**
     * @param mixed $argument
     */
    private function setCurrency($argument = null)
    {
        if (!empty($argument)) {
            if (in_array($argument, ['980', '840', '643'])) {
                $this->currency = $argument;
            } else {
                throw new InvalidArgumentException('something is wrong with currency');
            }
        }
    }

    /**
     * @param $argument
     */
    private function setMerchantType($argument)
    {
        if (in_array($argument, ['II', 'PP'])) {
            $this->merchantType = $argument;
        } else {
            throw new InvalidArgumentException('merchantType must be in array(\'II\', \'PP\')');
        }
    }

    private function setOrderId($argument = '')
    {
        if (empty($argument)) {
            $this->orderId = $this->prefix . '-' . strtoupper(sha1(time() . rand(1, 99999)));
        } else {
            $this->orderId = $this->prefix . '-' . strtoupper($argument);
        }

        $this->log['orderId'] = $this->orderId;
    }

    /**
     * @param int $argument
     */
    private function setPartsCount($argument = 0)
    {
        if ($argument < 1) {
            throw new InvalidArgumentException('PartsCount cannot be <1 ');
        }
        $this->partsCount = $argument;
    }

    /**
     * @param $argument
     */
    private function setPassword($argument)
    {
        if (empty($argument)) {
            throw new InvalidArgumentException('Password is empty');
        }
        $this->password = $argument;
    }

    /**
     * @param string $argument
     */
    private function setPrefix($argument = '') {
        if (!empty($argument)) {
            $this->prefix = $argument;
        }
    }

    private function setProductsList(

        $argument
    ) {
        if (!empty($argument) and is_array($argument)) {
            foreach ($argument as $arr) {
                foreach ($this->keysProds as $item) {
                    if (!array_key_exists($item, $arr)) {
                        throw new InvalidArgumentException("$item key does not exist");
                    }
                    if (empty($arr[$item])) {
                        throw new InvalidArgumentException("$item value cannot be empty");
                    }
                }

                $this->amount         += $arr['count'] * $arr['price'];
                $this->productsString .= $arr['name'] . $arr['count'] . $arr['price'] * 100;
            }
            $this->productsList = $argument;
        } else {
            throw new InvalidArgumentException('something is wrong');
        }
    }

    private function setRecipientId(

        $argument = ''
    ) {
        if (!empty($argument)) {
            $this->recipientId = $argument;
        }
    }

    private function setRedirectUrl(

        $argument
    ) {
        if (!empty($argument)) {
            $this->redirectUrl = $argument;
        }
    }


    /** @noinspection PhpUnusedPrivateMethodInspection */

    private function setResponseUrl(

        $argument
    ) {
        if (!empty($argument)) {
            $this->responseUrl = $argument;
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * @param $argument
     */
    private function setStoreId($argument)
    {
        if (empty($argument)) {
            throw new InvalidArgumentException('StoreId is empty');
        }
        $this->storeId = $argument;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * PayParts cancelHold.
     * <a href="https://bw.gitbooks.io/api-oc/content/cancel.html">Отмена платежа</a>
     * @param string $orderId Уникальный номер платежа
     * @param string $recipientId Идентификатор получателя, по умолчанию берется основной получатель. Установка основного получателя происходит в профиле магазина.
     * @return mixed|string
     */
    public function cancelHold($orderId, $recipientId = '')
    {
        $signatureForCancelHold = [$this->password, $this->storeId, $orderId, $this->password];


        $data = array(
            "storeId"   => $this->storeId,
            "orderId"   => $orderId,
            "signature" => $this->calcSignature($signatureForCancelHold)
        );
        if (!empty($recipientId)) {
            $data['recipientId'] = $recipientId;
        }


        $res = json_decode($this->sendPost($data, $this->cancelHoldUrl), true);

        return $res;

        /** @noinspection PhpUnreachableStatementInspection */
        $ResSignature = array($this->password, $res['storeIdentifier'], $res['orderId'], $this->password);

        if ($this->calcSignature($ResSignature) == $res['signature']) {
            return $res;
        } else {
            return 'error';
        }
    }

    /**
     * PayParts checkCallBack.
     * Получение результата сделки (асинхронный коллбэк)
     * @param string $string результат post запроса
     * @return mixed|string валидирует и отдаёт ответ
     */
    public function checkCallBack($string)
    {
        $sa = json_decode($string, true);

        $srt = [$this->password, $this->storeId, $sa['orderId'], $sa['paymentState'], $sa['message'], $this->password];

        if ($this->calcSignature($srt) == $sa['signature']) {
            return $sa;
        } else {
            return ('error');
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * PayParts confirmHold.
     * <a href="https://bw.gitbooks.io/api-oc/content/confirm.html">Подтверждение платежа</a>
     * @param string $orderId Уникальный номер платежа
     * @return mixed|string
     */
    public function confirmHold(string $orderId)
    {
        $signatureForConfirmHold = [$this->password, $this->storeId, $orderId, $this->password];

        $data = array(
            "storeIdentifier" => $this->storeId,
            "orderId"         => $orderId,
            "signature"       => $this->calcSignature($signatureForConfirmHold)
        );

        $res = json_decode($this->sendPost($data, $this->confirmHoldURL), true);

        return $res;

        /** @noinspection PhpUnreachableStatementInspection */
        $ResSignature = array($this->password, $res['storeIdentifier'], $res['orderId'], $this->password);

        if ($this->calcSignature($ResSignature) == $res['signature']) {
            return $res;
        } else {
            return 'error';
        }
    }

    /**
     * PayParts create.
     * Создание платежа
     * @param string $method
     * <a href="https://bw.gitbooks.io/api-oc/content/pay.html">'hold'</a> - Создание платежа без списания<br>
     * <a href="https://bw.gitbooks.io/api-oc/content/hold.html">'pay'</a> - Создание платежа со списания
     *
     *
     * @return mixed|string
     *
     */
    public function create($method = 'pay')
    {
        if ($this->options['SUCCESS']) {
            //проверка метода
            if ($method === 'hold') {
                $Url               = $this->holdUrl;
                $this->log['Type'] = 'Hold';
            } else {
                $Url               = $this->payUrl;
                $this->log['Type'] = 'Pay';
            }

            $SignatureForCall = [
                $this->password,
                $this->storeId,
                $this->orderId,
                (string)($this->amount * 100),
                $this->partsCount,
                $this->merchantType,
                $this->responseUrl,
                $this->redirectUrl,
                $this->productsString,
                $this->password
            ];

            $param['storeId']      = $this->storeId;
            $param['orderId']      = $this->orderId;
            $param['amount']       = $this->amount;
            $param['partsCount']   = $this->partsCount;
            $param['merchantType'] = $this->merchantType;
            $param['products']     = $this->productsList;
            $param['responseUrl']  = $this->responseUrl;
            $param['redirectUrl']  = $this->redirectUrl;
            $param['signature']    = $this->calcSignature($SignatureForCall);

            if (!empty($this->currency)) {
                $param['currency'] = $this->currency;
            }

            if (!empty($this->recipientId)) {
                $param['recipient'] = array('recipientId' => $this->recipientId);
            }


            $this->log['CreateData'] = json_encode($param);

            $CreateResult = json_decode($this->sendPost($param, $Url), true);

            $checkSignature = [
                $this->password,
                $CreateResult['state'],
                $CreateResult['storeId'],
                $CreateResult['orderId'],
                $CreateResult['message'],
                $CreateResult['token'],
                $this->password
            ];

            $this->log['CreateResult'] = json_encode($CreateResult);

            if ($this->calcSignature($checkSignature) == $CreateResult['signature']) {
                return $CreateResult;
            } else {
                return 'error';
            }
        } else {
            throw new InvalidArgumentException("No options");
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * PayParts getLog. частичный лог
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * PayParts getState.
     * <a href="https://bw.gitbooks.io/api-oc/content/state.html">Получение результата сделки</a>
     * @param string $orderId -Уникальный номер платежа
     * @param bool $showRefund true - получить детали возвратов по платежу,<br> false - получить статус платежа без дополнительных деталей о возвратах
     * @return mixed|string
     */
    public function getState($orderId, $showRefund = true)
    {
        $SignatureForCall = [$this->password, $this->storeId, $orderId, $this->password];

        $data = array(
            "storeId"    => $this->storeId,
            "orderId"    => $orderId,
            "showRefund" => var_export($showRefund, true), //($showRefund) ? 'true' : 'false'
            "signature"  => $this->calcSignature($SignatureForCall)
        );

        $res = json_decode($this->sendPost($data, $this->stateUrl), true);

        $ResSignature = [
            $this->password,
            $res['state'],
            $res['storeId'],
            $res['orderId'],
            $res['paymentState'],
            $res['message'],
            $this->password
        ];

        if ($this->calcSignature($ResSignature) == $res['signature']) {
            return $res;
        } else {
            return 'error';
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */

    /**
     * PayParts setOptions.
     * @param array $options
     *
     * responseUrl - URL, на который Банк отправит результат сделки (НЕ ОБЯЗАТЕЛЬНО)<br>
     * redirectUrl - URL, на который Банк сделает редирект клиента (НЕ ОБЯЗАТЕЛЬНО)<br>
     * partsCount - Количество частей на которые делится сумма транзакции ( >1)<br>
     * Prefix - параметр не обязательный если Prefix указан с пустотой или не указа вовсе префикс будет ORDER<br>
     * orderId' - если orderId задан с пустотой или не укан вовсе orderId сгенерится автоматически<br>
     * merchantType - II - Мгновенная рассрочка; PP - Оплата частями<br>
     *currency' - можна указать другую валюту 980 – Украинская гривна; 840 – Доллар США; 643 – Российский рубль. Значения в соответствии с ISO<br>
     *productsList - Список продуктов, каждый продукт содержит поля: name - Наименование товара price - Цена за еденицу товара (Пример: 100.00) count - Количество товаров данного вида<br>
     *recipientId - Идентификатор получателя, по умолчанию берется основной получатель. Установка основного получателя происходит в профиле магазина.
     *
     */

    public function setOptions($options = [])
    {
        if (empty($options) or !is_array($options)) {
            throw new InvalidArgumentException("Options must by set as array");
        } else {
            foreach ($options as $PPOptions => $value) {
                if (method_exists($this, "set$PPOptions")) {
                    call_user_func_array(array($this, "set$PPOptions"), array($value));
                } else {
                    throw new InvalidArgumentException($PPOptions . ' cannot be set by this setter');
                }
            }
        }

        $flag = 0;

        foreach ($this->options as $variable) {
            if (isset($this->{$variable})) {
                $flag++;
            } else {
                throw new InvalidArgumentException($variable . ' is necessary');
            }
        }
        if ($flag == count($this->options)) {
            $this->options['SUCCESS'] = true;
        } else {
            $this->options['SUCCESS'] = false;
        }
    }

}
