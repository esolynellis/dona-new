<?php

namespace Plugin\RegisterCaptcha;


use Illuminate\Http\Request;
use Matrix\Exception;
use Plugin\RegisterCaptcha\Models\Otp;
use Plugin\RegisterCaptcha\Services\JiYanTools;
use Plugin\RegisterCaptcha\Services\TencentTools;

class Bootstrap
{

    private $js = [
        '1' => '<script src="https://static.geetest.com/v4/gt4.js"></script>',
        '2' => '<script src="https://turing.captcha.qcloud.com/TCaptcha.js"></script>',
    ];

    private function checkCaptchaJs($output, $captcha_type)
    {
        if (isset($this->js[$captcha_type])) {
            $js     = $this->js[$captcha_type];
            $result = strpos($output, $js);
            if ($result !== false) {
                return true;
            }
        }
        return false;
    }

    private function check(Request $request, $setting)
    {
        if ($setting['captcha_type'] == 0) {
            return true;
        }
        if (!isset($setting['captcha_id']) || empty($setting['captcha_id']) || !isset($setting['captcha_key']) || empty($setting['captcha_key'])) {//
            return false;
        }
        if ($setting['captcha_type'] == 1) {
            $jy = new JiYanTools();
            return $jy->checkCaptcha($request, $setting);
        } else if ($setting['captcha_type'] == 2) {
            if (!isset($setting['tencent_secret_id']) || empty($setting['tencent_secret_id']) || !isset($setting['tencent_secret_key']) || empty($setting['tencent_secret_key'])) {//
                return false;
            }
            $tt = new TencentTools();
            return $tt->checkCaptcha($request, $setting);
        }
    }

    public function boot()
    {

        //注册时验证是否通过了人机校验
        add_hook_action('service.account.register.before', function ($data) {
            $request_data = request();
            if(empty($request_data['email'])){
                return;
            }
            $otp = Otp::query()->where("email", $request_data['email'])->orderByDesc('id')->first();
            //$ip = request()->ip();
            //$key  = $ip.'-register_captcha_code-' . $data['email'];
            //$code = Cache::get($key, 0);
            if (!$otp) {//验证不通过
                throw new Exception("code error(0)");
                /**
                 * $rs = [
                 * 'status'  => 'fail',
                 * 'message' => 'code error',//.$code.'++'.request()->code.'----'.$data['email'],
                 * 'data'    => $data,
                 * ];
                 * echo  json_encode($rs,true);
                 * exit;
                 * **/
            } else {
                $code      = request()->code;
                $send_code = $otp->code;
                if ($code != $send_code) {
                    throw new Exception("code error(1)");
                }
                if (strtotime($otp->expire_time) < time()) {
                    throw new Exception("code error(2)");
                }
            }
            return $data;
        });

        //增加验证码
        add_hook_blade('account.login.new.email', function ($callback, $output, $data) {
            $setting      = plugin_setting('register_captcha');
            $captcha_type = $setting['captcha_type'];
            //检测是否要加载图形验证码js文件
            if ($this->checkCaptchaJs($output, $captcha_type)) {
                $setting['js'] = '';
            } else {
                $setting['js'] = isset($this->js[$captcha_type])?$this->js[$captcha_type]:'';
            }
            //print_r($captcha_type);exit;
            $view = view('RegisterCaptcha::shop.reg_code', $setting)->render();
            return $output . $view;
        }, 3);
    }
}
