<?php
/**
 * AuthController.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2023-04-11 17:44:26
 * @modified   2023-04-11 17:44:26
 */

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Repositories\CartRepo;
use Beike\Repositories\CustomerGroupRepo;
use Beike\Shop\Http\Requests\RegisterRequest;
use Beike\Shop\Http\Resources\CustomerResource;
use Beike\Shop\Services\AccountService;
use Beike\Models\Customer;
use Plugin\Commission\Models\CommissionUser;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function register(RegisterRequest $request)
    {
        $credentials = $request->only('email', 'password');

        AccountService::register($credentials);

        if (! $token = auth('api_customer')->attempt($credentials)) {
            return response()->json(['error' => trans('auth.failed')], 401);
        }

        $customer = auth('api_customer')->user();
        $cid = request('cid') ?? '';

        if (!empty($cid)) {
            // 确保 $cid 的唯一性
            Customer::where('cid', $cid)->where('id', '!=', $customer->id)->update(['cid' => null]);

            $customer->cid = $cid;
            $customer->save();
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        $guestCartProduct       = CartRepo::allCartProducts(0);
        if (! $token = auth('api_customer')->attempt($credentials)) {
            return response()->json(['error' => trans('auth.failed')], 401);
        }

        $customer = auth('api_customer')->user();
        $cid = request('cid') ?? '';

        if (!empty($cid)) {
            // 确保 $cid 的唯一性
            Customer::where('cid', $cid)->where('id', '!=', $customer->id)->update(['cid' => null]);

            $customer->cid = $cid;
            $customer->save();
        }

        CartRepo::mergeGuestCart($customer, $guestCartProduct);

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $customer = auth('api_customer')->user();
        if (empty($customer)) {
            return json_fail(trans('auth.empty_customer'), [], 401);
        }
        $customer_group_info  = CustomerGroupRepo::getgroupinfo($customer['customer_group_id']);
        if(!empty($customer_group_info)){
            $customer->customer_group_name = $customer_group_info['name'];
        }else{
            $customer->customer_group_name = null;
        }

        $comUser = CommissionUser::query()->where('customer_id', $customer->id)->first();
        if ($comUser) {
            $customer->code = $comUser->code;
        }else{
            $customer->code = '';
        }
        return response()->json(new CustomerResource($customer));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api_customer')->logout();

        return response()->json(['message' => trans('logout_success')]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api_customer')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api_customer')->factory()->getTTL() * 120,
        ]);
    }
}
