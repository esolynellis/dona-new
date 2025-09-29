<?php

namespace Beike\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
class QPayService
{
    protected $client;
    protected $baseUrl;
    protected $token;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->client = new Client();
//        $this->baseUrl = config('qpay.sandbox') ? config('qpay.sandbox_url') : config('qpay.production_url');
//        $this->username = config('qpay.username');
//        $this->password = config('qpay.password');

        $this->baseUrl = 'https://merchant.qpay.mn';
        $this->username = 'DONA';
        $this->password = '4xeHZfOt';

        // 获取访问令牌
        $this->token = $this->getAccessToken();
    }

    /**
     * 获取访问令牌
     */
    protected function getAccessToken()
    {
        try {
            $response = $this->client->post($this->baseUrl . '/v2/auth/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                ],
                'json' => []
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'] ?? null;

        } catch (\Exception $e) {
            Log::error('QPay获取Token失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 创建发票
     */
    public function createInvoice(array $invoiceData)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/v2/invoice', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $invoiceData
            ]);

            return json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            Log::error('QPay创建发票失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 创建简单发票
     */
    public function createSimpleInvoice(array $invoiceData)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/v2/invoice', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'invoice_code' => $invoiceData['invoice_code'],
                    'sender_invoice_no' => $invoiceData['sender_invoice_no'],
                    'invoice_receiver_code' => $invoiceData['invoice_receiver_code'],
                    'invoice_description' => $invoiceData['invoice_description'],
                    'sender_branch_code' => $invoiceData['sender_branch_code'] ?? null,
                    'amount' => $invoiceData['amount'],
                    'callback_url' => $invoiceData['callback_url'],
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            Log::error('QPay创建简单发票失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 取消发票
     */
    public function cancelInvoice($invoiceId)
    {
        try {
            $response = $this->client->delete($this->baseUrl . '/v2/invoice/' . $invoiceId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            Log::error('QPay取消发票失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取支付信息
     */
    public function getPayment($paymentId)
    {
        try {
            $response = $this->client->get($this->baseUrl . '/v2/payment/' . $paymentId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            Log::error('QPay获取支付信息失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 检查支付状态
     */
    public function checkPayment($objectType, $objectId, $pageNumber = 1, $pageLimit = 100)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/v2/payment/check', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'object_type' => $objectType,
                    'object_id' => $objectId,
                    'offset' => [
                        'page_number' => $pageNumber,
                        'page_limit' => $pageLimit,
                    ]
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            Log::error('QPay检查支付状态失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 取消支付
     */
    public function cancelPayment($paymentId, $note = '')
    {
        try {
            $response = $this->client->delete($this->baseUrl . '/v2/payment/cancel/' . $paymentId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'note' => $note
                ]
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            Log::error('QPay取消支付失败: ' . $e->getMessage());
            return false;
        }
    }
}
