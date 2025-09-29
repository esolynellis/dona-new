<?php
/**
 * AddressController.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2022-06-28 20:17:04
 * @modified   2022-06-28 20:17:04
 */

namespace Plugin\Commission\Controllers;

use Beike\Repositories\AddressRepo;
use Beike\Shop\Http\Controllers\Controller;
use Beike\Shop\Http\Resources\Account\AddressResource;
use Illuminate\Http\Request;
use Plugin\Commission\Models\Bank;
use Plugin\Commission\Requests\BankRequest;

class BankController extends Controller
{
    public function index(Request $request)
    {

        $bank = Bank::query()->where("customer_id", current_customer()->id)->first();
        $data = [
            'bank' => $bank,
        ];

        return view('Commission::shop.bank', $data);
    }

    public function show(Request $request, $id)
    {
        $address = AddressRepo::find($id);

        return json_success(trans('common.get_success'), new AddressResource($address));
    }

    public function store(BankRequest $request)
    {
        $data = $request->all();


        $data['customer_id'] = current_customer()->id;
        Bank::query()->insert($data);

        return json_success(trans('common.created_success'));
    }

    public function update(BankRequest $request, int $id)
    {
        $data = $request->all();


        $bank = Bank::query()->where("customer_id", current_customer()->id)->first();

        $bank->update($data);


        return json_success(trans('common.created_success'));
    }

    public function destroy(Request $request, int $id)
    {
        $bank = Bank::query()->where("customer_id", current_customer()->id)->first();
        $bank->delete($id);

        return json_success(trans('common.deleted_success'));
    }

    public function admin_edit(Request $request)
    {
        $customerId = $request->id;
        $bank       = Bank::query()->where('customer_id', $customerId)->first();
        $data       = [
            'bank'       => $bank,
            'name'       => '修改银行卡',
            'customerId' => $customerId,
        ];

        return view('Commission::admin.bank', $data);
    }

    public function admin_update(BankRequest $request)
    {
        $data = $request->all();

        $id = $request->customer_id;

        $bank = Bank::query()->where("customer_id", $id)->first();
        if ($bank) {

            $bank->update($data);
        } else {
            $data['customer_id'] = $id;
            Bank::query()->insert($data);
        }


        return json_success(trans('common.created_success'));
    }
}
