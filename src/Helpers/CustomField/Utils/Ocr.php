<?php

namespace Lanerp\Common\Helpers\CustomField\Utils;

use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeBankCardRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeIdcardRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeInvoiceRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeMixedInvoicesRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Ocrapi;
use Darabonba\OpenApi\Models\Config;
use Exception;
use function _throwException;
use function config;

class Ocr
{

    public const TYPE_IDCARD         = 'idcard';
    public const TYPE_BANK_CARD      = 'bankCard';
    public const TYPE_INVOICE        = 'invoice';
    public const TYPE_MIXED_INVOICES = 'mixedInvoices';
    public static array $TYPE = [
        self::TYPE_IDCARD         => ['title' => '身份证', 'value' => self::TYPE_IDCARD],
        self::TYPE_BANK_CARD      => ['title' => '银行卡', 'value' => self::TYPE_BANK_CARD],
        self::TYPE_INVOICE        => ['title' => '增值发票', 'value' => self::TYPE_INVOICE],
        self::TYPE_MIXED_INVOICES => ['title' => '混贴发票', 'value' => self::TYPE_MIXED_INVOICES],
    ];

    private string $methodType;
    private        $body;


    /**
     * 使用AK&SK初始化账号Client
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @return Ocrapi Client
     */
    public function createClient($accessKeyId, $accessKeySecret)
    {
        $config           = new Config([
            "accessKeyId"     => $accessKeyId,
            "accessKeySecret" => $accessKeySecret
        ]);
        $config->endpoint = "ocr-api.cn-hangzhou.aliyuncs.com";
        return new Ocrapi($config);
    }

    /**
     * Notes:
     * Date: 2024/11/12
     * @param $type
     * @param $url
     * @return Ocr
     */
    public static function main($type, $url): Ocr
    {
        try {
            if (!isset(self::$TYPE[$type])) _throwException("识别类型不存在", -1);

            $ocr             = new self();
            $ocr->methodType = ucfirst($type);
            $ocr->client     = $ocr->createClient(config('common.aliOss.accessKeyId'), config('common.aliOss.accessKeySecret'));
            if (is_array($url)) {
                foreach ($url as $v) {
                    $object = $ocr->request($v);
                    $body[] = $object->body;
                }
            } else {
                $object = $ocr->request($url);
                $body   = $object->body;
            }
            $ocr->body = $body;
        } catch (Exception $error) {
            $message = $error->getMessage();
            if (in_array($error->getCode(), ["unmatchedImageType", "illegalImageContent"])) {
                $message = "请上传正确图片";
            }
            _throwException($message);//$error->code

            //if (!($error instanceof TeaError)) {
            //    $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            //}
            //// 如有需要，请打印 error
            //_throwException(Utils::assertAsString($error->message), -1);//$error->code
        }

        return $ocr;

    }

    private function request($url)
    {
        [$callbackMethod, $recognizeRequest] = $this->{'get' . $this->methodType . 'Request'}($url);//获取要识别的请求信息
        $object = $this->client->$callbackMethod($recognizeRequest);
        if ($object->statusCode !== 200) _throwException("识别状态码为【" . $object->statusCode . "】，请联系管理员");
        return $object;
    }

    private function getIdcardRequest($url)
    {
        //https://img.alicdn.com/tfs/TB1q5IeXAvoK1RjSZFNXXcxMVXa-483-307.jpg
        return ["recognizeIdcard", new RecognizeIdcardRequest(["url" => $url])];
    }

    private function getBankCardRequest($url)
    {
        //https://img.alicdn.com/tfs/TB1fL.fiCzqK1RjSZPcXXbTepXa-3116-2139.jpg
        return ["recognizeBankCard", new RecognizeBankCardRequest(["url" => $url])];
    }


    private function getInvoiceRequest($url)
    {
        //https://img.alicdn.com/tfs/TB1fL.fiCzqK1RjSZPcXXbTepXa-3116-2139.jpg
        return ["recognizeInvoice", new RecognizeInvoiceRequest(["url" => $url])];
    }

    private function getMixedInvoicesRequest($url)
    {
        //https://img.alicdn.com/tfs/TB1fL.fiCzqK1RjSZPcXXbTepXa-3116-2139.jpg
        return ["recognizeMixedInvoices", new RecognizeMixedInvoicesRequest(["url" => $url])];
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getData()
    {
        return $this->{'get' . $this->methodType . "Data"}();//返加最终数据
    }

    /**
     * Notes:身份证最终返回数据
     * Date: 2023/11/16
     * @return [
     * "name"      => "方大呆",
     * "sex"       => "女",
     * "ethnicity" => "汉",
     * "birthDate" => "2006年10月2日",
     * "address"   => "上海市西藏南路-瞿溪路弘辉名苑",
     * "idNumber"  => "371002200610020000"
     * ]
     */
    protected function getIdcardData()
    {
        return \GuzzleHttp\json_decode($this->body->data)->data->face->data;
    }


    /**
     * Notes:银行卡最终返回数据
     * Date: 2023/11/16
     * @return [
     * "bankName"    => "交通银行",
     * "cardNumber"  => "6222621370000783456",
     * "validToDate" => "2024/12",
     * "cardType"    => "DC"
     * ]
     */
    protected function getBankCardData()
    {
        return \GuzzleHttp\json_decode($this->body->data)->data;
    }

    /**
     * Notes:混贴发票最终返回数据
     * Date: 2023/11/16
     * @return [
     * "op" => "类型",
     * ]
     */
    protected function getMixedInvoicesData()
    {
        $datas = [];
        if (is_array($this->body)) {
            foreach ($this->body as $body) {
                $datas[] = $this->mixedInvoicesDataFormat($body);
            }
        } else {
            $datas = $this->mixedInvoicesDataFormat($this->body);
        }
        //dj(($this->body));
        return $datas;
    }

    /**
     * Notes:混贴发票数据处理
     * Date: 2024/4/12
     */
    protected function mixedInvoicesDataFormat($body)
    {
        $invoices = \GuzzleHttp\json_decode($body->data)->subMsgs;
        $datas    = [];
        foreach ($invoices as $invoice) {
            $data = $invoice->result->data;
            //dj($data);
            $data->op = $invoice->op;
            if ($data->op === "train_ticket") {
                $datas[] = [
                    'op'                   => $data->op,
                    'invoice_number'       => $data->ticketNumber ?? "",
                    'invoice_date'         => $data->invoiceDate ?? "",
                    'title'                => $data->title ?? "",
                    'purchaser_name'       => $data->buyerName ?? "",
                    'purchaser_tax_number' => $data->buyerCreditCode ?? "",
                    'total_amount'         => $data->fare ?? $data->totalAmount ?? $data->Amount,
                ];
            } else {
                $datas[]  = [
                    'op'                          => $data->op ?? "",
                    'invoice_type'                => $data->invoiceType ?? "",
                    'invoice_code'                => $data->invoiceCode ?? "",
                    'invoice_number'              => $data->invoiceNumber ?? "",
                    'printed_invoice_code'        => $data->printedInvoiceCode ?? "",
                    'printed_invoice_number'      => $data->printedInvoiceNumber ?? "",
                    'check_code'                  => $data->checkCode ?? "",
                    'invoice_date'                => $data->invoiceDate ?? "",
                    'title'                       => $data->title ?? "",
                    'form_type'                   => $data->formType ?? "",
                    'purchaser_name'              => $data->purchaserName ?? "",
                    'purchaser_tax_number'        => $data->purchaserTaxNumber ?? "",
                    'purchaser_contact_info'      => $data->purchaserContactInfo ?? "",
                    'purchaser_bank_account_info' => $data->purchaserBankAccountInfo ?? "",
                    'total_amount'                => $data->totalAmount ?? $data->Amount ?? $data->fare,
                ];
            }
            //$datas[]  = $data;
        }
        return $datas;
    }
    /*protected function getInvoiceData()
    {
        p(21323, \GuzzleHttp\json_decode($this->body->data));
        return \GuzzleHttp\json_decode($this->body->data)->data;
    }*/

}

