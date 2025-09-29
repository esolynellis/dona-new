<?php
/**
 * MiniappController.php
 *
 * @copyright  2024 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2024-01-02 18:15:57
 * @modified   2024-01-02 18:15:57
 */

namespace Beike\ShopAPI\Controllers;

use Beike\Repositories\CartRepo;
use Beike\ShopAPI\Libraries\MiniApp\Auth;
use Illuminate\Http\Request;

class MiniappController extends AuthController
{
    /**
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $code = $request->get('code');
        if (empty($code)) {
            throw new \Exception('Empty MiniApp Code');
        }

        $guestCartProduct = CartRepo::allCartProducts(0);

        $miniAppAuth = Auth::getInstance($code);
        $customer    = $miniAppAuth->findOrCreateCustomerByCode();
        $token       = auth('api_customer')->login($customer);

        CartRepo::mergeGuestCart($customer, $guestCartProduct);

        return $this->respondWithToken($token);
    }
}
