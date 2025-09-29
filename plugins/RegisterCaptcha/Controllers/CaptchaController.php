<?php


namespace Plugin\RegisterCaptcha\Controllers;

use app\model\Licenses;
use Beike\Admin\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugin\RegisterCaptcha\Models\Otp;
use Plugin\RegisterCaptcha\Services\JiYanTools;
use Plugin\RegisterCaptcha\Services\TencentTools;

class CaptchaController extends Controller
{


    public function checkCaptcha(Request $request)
    {

        $setting = plugin_setting('register_captcha');
        if ($setting['captcha_type'] == 0) {//未开启
            $this->sendEmail($request);
            return response()->json([
                'code' => 0,
                'msg'  => ''
            ]);
        }


        if ($this->check($request, $setting)) {//验证通过
            $this->sendEmail($request);
            return response()->json([
                'code' => 0,
                'msg'  => ''
            ]);
        }


        return response()->json([
            'code' => -3,
            'msg'  => ''
        ]);


    }


    private function sendEmail(Request $request)
    {
        $code = str_pad(mt_rand(10, 999999), 6, '0', STR_PAD_LEFT);
        //缓存验证结果
        $email = $request->email;
        //$ip    = $request->ip();
        //$key   = $ip . '-register_captcha_code-' . $email;
        //Cache::put($key, $code, Carbon::now()->addMinutes(5));


        $otp = Otp::query()->where("email", $email)->where("code", $code)->first();
        if (!$otp) {
            Otp::query()->insertGetId([
                "email"       => $email,
                'code'        => $code,
                'expire_time' => \Illuminate\Support\Carbon::now()->addMinutes(5)->toDateTimeString()
            ]);
            $otp = Otp::query()->where("email", $email)->where("code", $code)->first();
        }
        $otp->notifyAdmin($email, 'RegisterCaptcha::shop/reg_email_model',trans('RegisterCaptcha::login.email_subject'), ['code' => $code]);

        /**
         * $setting = plugin_setting('register_captcha');
         * config([
         * 'mail' => [
         * 'driver'     => $setting['email_driver'],
         * 'host'       => $setting['email_host'],
         * 'port'       => $setting['email_port'],
         * 'from'       => [
         * 'address' => $setting['email_username'],
         * 'name'    => $setting['email_from_name'],
         * ],
         * 'username'   => $setting['email_username'],
         * 'password'   => $setting['email_password'],
         * 'encryption' => $setting['email_encryption'],
         * ]
         * ]);
         * Mail::send('RegisterCaptcha::shop/reg_email_model', ['code' => $code], function ($message) use ($email) {
         * //$message->from('xxxxxxxxxx@163.com', "laravel 测试");
         * $message->subject(trans('RegisterCaptcha::login.email_subject'));
         * $message->to($email);
         * });
         * **/


    }


    function send($email, $code)
    {

    }

    private function check(Request $request, $setting)
    {
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
}
