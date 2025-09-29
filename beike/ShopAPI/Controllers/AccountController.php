<?php
/**
 * AccountController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-08-15 11:02:22
 * @modified   2023-08-15 11:02:22
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Repositories\CustomerRepo;
use Beike\Shop\Http\Requests\EditRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\RegisterCaptcha\Models\Otp;

class AccountController extends Controller
{
    /**
     * @param EditRequest $request
     * @return JsonResponse
     */
    public function update(EditRequest $request): JsonResponse
    {
        try {
            CustomerRepo::update(current_customer(), $request->only('name', 'email', 'avatar'));

            return json_success(trans('common.edit_success'));
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function destroy(): JsonResponse
    {
        try {
            $customer = current_customer();
            if ($customer) {
                $customer->delete();
            }

            return json_success(trans('common.delete_success'));
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    public function captcha(Request $request)
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
    }

}
