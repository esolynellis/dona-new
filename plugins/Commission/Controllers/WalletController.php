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

use Beike\Models\Customer;
use Beike\Models\Order;
use Beike\Repositories\AddressRepo;
use Beike\Shop\Http\Controllers\Controller;
use Beike\Shop\Http\Resources\Account\AddressResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugin\Commission\Models\CustomerExpand;
use Plugin\Commission\Models\Bank;
use Plugin\Commission\Models\CommissionAmountLog;
use Plugin\Commission\Models\CommissionUser;
use Plugin\Commission\Requests\BankRequest;

class WalletController extends Controller
{
    public function index(Request $request)
    {

        $balances = CustomerExpand::query()->where("customer_id", current_customer()->id)->get();
        $data     = [
            'balances' => $balances,
            'balance'  => current_customer()->balance
        ];

        return view('Commission::shop.balance', $data);
    }

    public function shop_orders(Request $request)
    {
        $logs = CustomerExpand::query()->where('customer_id', current_customer()->id);
        $logs = $logs->orderByDesc('id')->paginate(perPage());

        foreach ($logs->items() as $log) {
            $log->update_at = time_format($log->updated_at);
        }
        $customer = current_customer();
        return response()->json([
            'logs'        => $logs,
            'balance'     => current_customer()->balance,
            'showGetCash' => current_customer()->can_apply_to_bank,
            'customer'    => $customer
        ]);
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

    public function shop_cash_apply(Request $request)
    {

        if (!$request->amount || !is_numeric($request->amount) || $request->amount <= 0) {

            return response()->json([
                'code' => '-1',
                'msg'  => trans('shop/common2.apply_post_param_error')
            ]);
        }
        $bank = Bank::query()->where("customer_id", current_customer()->id)->first();
        if (empty($bank)) {
            return response()->json([
                'code' => '-1',

                'msg' => trans('shop/common2.create_bank_card_before')
            ]);
        }

        $customer = current_customer();
        if ($customer->balance < $request->amount) {
            return response()->json([
                'code' => '-1',
                'msg'  => trans('shop/common2.amount_lt')
            ]);
        }

        $customer->update(['balance' => bcsub($customer->balance, $request->amount, 2)]);//立即减扣余额


        $apply_data = [
            'customer_id'      => $customer->id,
            'customer_account' => $customer->account,
            'bank_user_name'   => $bank->bank_user_name,
            'bank_name'        => $bank->bank_name,
            'bank_code'        => $bank->bank_code,
            'amount'           => $request->amount,
            'admin_user_id'    => $customer->admin_user_id,
            'status'           => CustomerExpand::ACTION_APPLY_UNPAID,
            'created_at'       => date('Y-m-d H:i:s', time()),
            'updated_at'       => date('Y-m-d H:i:s', time()),
        ];

        DB::table('balance_logs')->insertGetId($apply_data);
        return response()->json([
            'code' => 0,
            'msg'  => trans('common.success')
        ]);

    }

    public function audit_cash_apply(Request $request)
    {
        $log_id = $request->id;

        $commissionAmountLog = CustomerExpand::query()->where('id', $log_id)->where('status', CustomerExpand::ACTION_APPLY_UNPAID)->first();

        if (empty($commissionAmountLog)) {
            return response()->json([
                'code' => -1,
                'msg'  => '记录不存在'
            ]);
        }


        DB::beginTransaction();
        try {
            //变更这条记录
            if ($request->action == 'refuse') {
                $customer = Customer::query()->where("id", $commissionAmountLog->customer_id)->first();
                $amount   = $commissionAmountLog->amount;
                $commissionAmountLog->update([
                    'status' => CustomerExpand::ACTION_APPLY_REFUSE,
                    'note'   => $request->audit_note ? $request->audit_note : '',
                ]);
                $customer->update(['balance' => bcadd($customer->balance, $amount, 2)]);//加回余额
            } else {//同意
                $commissionAmountLog->update([
                    'status' => CustomerExpand::ACTION_APPLY_PAID,
                    'note'   => $request->audit_note ? $request->audit_note : '',
                ]);
            }
            DB::commit();
            return response()->json([
                'code' => 0,
                'msg'  => '提现成功'
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code'      => -2,
                'msg'       => '结算异常，结算失败',
                'exception' => json_encode($exception, true),
            ]);
        }
    }
}
