<?php

namespace Payanyway;

use Payanyway\Exception\EmptyDescriptionException;
use Payanyway\Exception\InvalidInvoiceIdException;
use Payanyway\Exception\InvalidParamException;
use Payanyway\Exception\InvalidSumException;

class Payment
{
    private $baseUrl = 'https://www.moneta.ru/assistant.htm';
    private $isTestMode = false;
    private $valid = false;
    private $data;
    private $customParams = [];
    private $submit_button_class;
    private $submit_button_name;

    private $mnt_id;
    private $mnt_currency_code;
    private $moneta_locale;
    private $paymentPassword;

    public function __construct(
        $mnt_id, 
        $mnt_currency_code, 
        $moneta_locale, 
        $paymentPassword, 
        $testMode = false, 
        $submit_button_class = '', 
        $submit_button_name = 'Pay'
    ) {
        $this->mnt_id              = $mnt_id;
        $this->mnt_currency_code   = $mnt_currency_code;
        $this->moneta_locale       = $moneta_locale;
        $this->isTestMode          = $testMode;
        $this->paymentPassword     = $paymentPassword;
        $this->submit_button_class = $submit_button_class;
        $this->submit_button_name  = $submit_button_name;

        if($this->isTestMode)
        {
            $this->baseUrl = 'https://demo.moneta.ru/assistant.htm';
        }

        $this->data = [
            'MNT_ID'                => $this->mnt_id,
            'MNT_TRANSACTION_ID'    => null,
            'MNT_AMOUNT'            => '',
            'MNT_CURRENCY_CODE'     => $this->mnt_currency_code,
            'MNT_SUBSCRIBER_ID'     => '',
            'MNT_TEST_MODE'         => (int)$this->isTestMode,
        ];
    }

    public function getPaymentForm()
    {
        if ($this->data['MNT_AMOUNT'] <= 0)
        {
            throw new InvalidSumException();
        }

        if (empty($this->customParams['MNT_DESCRIPTION']))
        {
            throw new EmptyDescriptionException();
        }

        if ($this->data['MNT_TRANSACTION_ID'] <= 0)
        {
            throw new InvalidInvoiceIdException();
        }

        $signature = '';

        foreach($this->data as $item)
        {
            $signature .= $item;
        }

        $signature .= $this->paymentPassword;

        $this->data['MNT_SIGNATURE'] = md5($signature);

        if(!$this->isTestMode)
        {
            unset($this->data['MNT_TEST_MODE']);
        }

        $string = "<form action='{$this->baseUrl}' method='post' style='display:inline-block;'>";

        foreach(array_merge($this->data, $this->customParams) as $name => $item)
        {
            $string .= "<input type='hidden' name='$name' value='$item'>";
        }

        $string .= "<input type='hidden' name='moneta.locale' value='$this->moneta_locale'>";
        $string .= "<input type='submit' class='{$this->submit_button_class}' value='{$this->submit_button_name}'></form>";

        return $string;
    }

    public function validateResult($data)
    {
        return $this->validate($data);
    }

    public function validateSuccess($data)
    {
        return $this->data['MNT_TRANSACTION_ID'] = $data['MNT_TRANSACTION_ID'];
    }

    private function validate($data)
    {
        $this->data = [
            'MNT_ID' => $data['MNT_ID'],
            'MNT_TRANSACTION_ID' => $data['MNT_TRANSACTION_ID'],
            'MNT_OPERATION_ID' => $data['MNT_OPERATION_ID'],
            'MNT_AMOUNT' => $data['MNT_AMOUNT'],
            'MNT_CURRENCY_CODE' => $data['MNT_CURRENCY_CODE'],
            'MNT_SUBSCRIBER_ID' => $data['MNT_SUBSCRIBER_ID'],
            'MNT_TEST_MODE' => (int) $data['MNT_TEST_MODE'],
        ];

        $signature = '';

        foreach($this->data as $item)
        {
            $signature .= $item;
        }

        $signature .= $this->paymentPassword;

        $this->valid = (md5($signature) === strtolower($data['MNT_SIGNATURE']));

        return $this->valid;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getSuccessAnswer()
    {
        return 'SUCCESS';
    }

    public function getTransactionId()
    {
        return $this->data['MNT_TRANSACTION_ID'];
    }

    public function setTransactionId($id)
    {
        $this->data['MNT_TRANSACTION_ID'] = (int) $id;

        return $this;
    }

    public function getSum()
    {
        return $this->data['MNT_AMOUNT'];
    }

    public function setSum($sum)
    {
        $sum = number_format($sum, 2, '.', '');

        if ($sum > 0)
        {
            $this->data['MNT_AMOUNT'] = $sum;

            return $this;
        }
        else
        {
            throw new InvalidSumException();
        }
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->customParams['MNT_DESCRIPTION'];
    }

    /**
     * @param  string $description
     *
     * @return Payment
     */
    public function setDescription($description)
    {
        $this->customParams['MNT_DESCRIPTION'] = (string) $description;

        return $this;
    }

    public function getSubscriberId()
    {
        return $this->data['MNT_SUBSCRIBER_ID'];
    }

    public function setSubscriberId($id)
    {
        $this->data['MNT_SUBSCRIBER_ID'] = (int) $id;

        return $this;
    }

    public function setButtonName($name)
    {
        $this->submit_button_name = $name;

        return $this;
    }
}
