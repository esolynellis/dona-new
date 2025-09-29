<?php


namespace Plugin\Commission\Controllers;

use app\model\Licenses;
use Beike\Admin\Http\Controllers\Controller;
use Beike\Repositories\LanguageRepo;
use Illuminate\Http\Request;
use Mockery\Exception;
use Plugin\Commission\Models\CommissionCustomerService;
use Plugin\Commission\Models\CommissionWithdrawalGroup;
use Plugin\Commission\Models\CommissionWithdrawalGroupItem;
use Plugin\Commission\Models\CommissionWithdrawalGroupItemDescriptions;

class WithdrawalGroupController extends Controller
{
    public function index()
    {
        $groups = CommissionWithdrawalGroup::query()->with(['items'])->get();

        $plugin = app('plugin')->getPlugin('commission');
        $data   = [
            'name'        => '提现管理',
            'description' => $plugin->getLocaleDescription(),
            'groups'      => $groups->toArray(),
            'languages2'  => LanguageRepo::all(),
        ];

        return view('Commission::admin.withdrawal_group', $data);
    }

    public function store(Request $request)
    {
        if ($request->id) {
            CommissionWithdrawalGroup::query()->where('id', $request->id)->update([
                'name' => $request->name,
            ]);
        } else {
            CommissionWithdrawalGroup::query()->insert([
                'name' => $request->name,
            ]);
        }

        return response()->json([
            'code' => 0,
            'msg'  => '保存成功'
        ]);

    }

    public function destory(Request $request)
    {
        CommissionWithdrawalGroup::query()->where('id', $request->id)->delete();
        return response()->json([
            'code' => 0,
            'msg'  => 'success'
        ]);

    }

    public function item_store(Request $request)
    {
        $id = $request->id;
        if ($id) {
            CommissionWithdrawalGroupItem::query()->where('id', $id)->update([
                'name'      => $request->name,
                'show_sort' => $request->show_sort
            ]);
        } else {
            $id = CommissionWithdrawalGroupItem::query()->insertGetId([
                'name'      => $request->name,
                'group_id'  => $request->group_id,
                'show_sort' => $request->show_sort
            ]);
        }

        CommissionWithdrawalGroupItemDescriptions::query()->where('commission_withdrawal_group_item_id', $id)->delete();

        foreach ($request->descriptions as $description) {
            $content = $description['content'];
            $locale  = $description['locale'];
            CommissionWithdrawalGroupItemDescriptions::query()->insert([
                'commission_withdrawal_group_item_id' => $id,
                'content'                             => $content,
                'locale'                              => $locale,
            ]);
        }

        return response()->json([
            'code' => 0,
            'msg'  => '保存成功'
        ]);

    }

    public function item_destory(Request $request)
    {
        CommissionWithdrawalGroupItem::query()->where('id', $request->id)->delete();
        CommissionWithdrawalGroupItemDescriptions::query()->where('commission_withdrawal_group_item_id', $request->id)->delete();
        return json_success(trans('success'));

    }



}
