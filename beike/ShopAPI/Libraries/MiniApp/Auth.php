<?php
/**
 * MiniApp.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-03-22 19:05
 * @modified   2018-03-22 19:05
 */

namespace Beike\ShopAPI\Libraries\MiniApp;

use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\MiniApp\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Plugin\Social\Repositories\CustomerRepo;

class Auth
{
    private Application $app;

    private string $code;

    private array $socialData;

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function __construct($code)
    {
        $options = $this->getOptions();
        if (empty($code)) {
            throw new \Exception('Empty code for MiniApp');
        }
        $this->code       = $code;
        $this->app        = new Application($options);
        $this->socialData = [];
    }

    /**
     * @param $code
     * @return Auth
     * @throws InvalidArgumentException
     */
    public static function getInstance($code): self
    {
        return new self($code);
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        $options = [
            'app_id' => env('MINI_APP_ID', plugin_setting('we_chat_mini.appid')),
            'secret' => env('MINI_APP_SECRET', plugin_setting('we_chat_mini.secret')),
            'debug'  => true,
            'log'    => [
                'level' => 'debug',
                'file'  => storage_path('logs') . '/easywechat.log',
            ],
        ];
        Log::info(json_encode($options));

        return $options;
    }

    /**
     * 查找或者创建
     * @return mixed
     * @throws \Exception
     */
    public function findOrCreateCustomerByCode(): mixed
    {
        if ($customer = $this->findCustomerByCode()) {
            return $customer;
        }

        $socialData = $this->getSocialData();
        $userData   = [
            'uid'    => $socialData['uid'],
            'email'  => '',
            'name'   => '',
            'avatar' => '',
            'token'  => $socialData['access_token'],
            'raw'    => '',
        ];

        return CustomerRepo::createCustomer('miniapp', $userData);
    }

    /**
     * @return Builder|null
     * @throws \Exception
     */
    public function findCustomerByCode(): mixed
    {
        $socialData = $this->getSocialData();
        if (! Schema::hasTable('customer_socials')) {
            $message = '第三方登录未安装，请到网站后台 插件 - 插件设置 - Social，安装';

            throw new \Exception($message);
        }

        $customerSocial = CustomerRepo::getCustomerByProvider('miniapp', $socialData['uid']);
        $customer       = $customerSocial->customer ?? null;
        if ($customer) {
            $socialData['customer_id'] = $customer->id;
            CustomerRepo::createSocial($customer, 'miniapp', $socialData);

            return $customer;
        }

        return null;
    }

    private function getSocialData(): array
    {
        if ($this->socialData) {
            return $this->socialData;
        }

        $utils            = $this->app->getUtils();
        $session          = $utils->codeToSession($this->code);
        $this->socialData = [
            'uid'          => $session['openid'],
            'unionid'      => $session['unionid'] ?? '',
            'provider'     => 'miniapp',
            'access_token' => $session['session_key'],
        ];

        return $this->socialData;
    }
}
