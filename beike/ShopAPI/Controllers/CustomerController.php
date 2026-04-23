<?php

namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Repositories\CustomerGroupRepo;
use Beike\Services\StateMachineService;
use Beike\Models\CustomerOfflinePayOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugin\Commission\Models\CommissionCustomers;
use Plugin\Commission\Models\CommissionUser;
use Beike\Models\Customer;

class CustomerController extends Controller
{
    public function groupList(Request $request)
    {
        return CustomerGroupRepo::list();
    }
    public function createCusorder(Request $request)
    {
        $data = [
            'group_id' => $request->group_id,
            'payment_method_code'  => $request->payment_method_code,
            'payment_method_name'  => $request->payment_method_name,
        ];
        return CustomerGroupRepo::createCusorder($data);
    }

    public function cusPay(Request $request)
    {
        $data = [
            'group_id' => $request->group_id,
            'payment_method_code'  => $request->payment_method_code,
            'payment_method_name'  => $request->payment_method_name,
        ];
        return CustomerGroupRepo::createCusorder($data);
    }


    public function uploadProof(Request $request)
    {
        // 判断上传的文件是否存在
        if ($request->hasFile('file')) {
            // 获取上传的文件
            $file = $request->file('file');
            $imagePaths = [];
            // 判断文件是否上传成功
            if ($file->isValid()) {
                $upload_path = plugin_path('LOffline/Static/uploads/');
                // 获取文件扩展名
                $ext = $file->getClientOriginalExtension();
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                if (!in_array(strtolower($ext), $allowedExtensions)) {
                    return response()->json([
                        'code'     => -1,
                        'msg'      => '文件格式错误',
                    ]);
                }
                // 生成新的文件名
                $newName = md5(time() . rand(0, 10000)) . "." . $ext;
                // 将文件移动到指定目录
                $file->move($upload_path, $newName);
                $imagePaths[] = '/uploads/' . $newName;
                // 返回文件路径
            }else{
                return response()->json([
                    'code'     => -1,
                    'msg'      => '上传失败',
                ]);
            }

        }else{
            return response()->json([
                'code'     => -1,
                'msg'      => '请选择文件',
            ]);
        }
        // 将图片路径数组转为JSON格式并存入数据库
        $images = json_encode($imagePaths,true);
        $customer = current_customer();
        $order    = CustomerGroupRepo::getOrderByNumber($request->order_no, $customer);
        if ($order && $order->status == StateMachineService::UNPAID) {
            CustomerOfflinePayOrder::query()->insert([
                'order_id' => $order->id,
                'imgs'     => $images
            ]);
//            StateMachineService::getInstance($order)->changeStatus(StateMachineService::PAID);
            //再修改为
            $order->status = 'l_offline';
            $order->update();
            // 更新客户等级
            $customer->customer_group_id = $order->customer_group_id;
            $customer->save();
            return response()->json([
                'code'     => 0,
                'msg'      => '成功',
            ]);
        }
        return response()->json([
            'code' => -1,
            'msg'  => "订单号错误"
        ]);
    }

    public function tuiguang()
    {
        $customer = current_customer();
        if(empty($customer)){
            return response()->json([
                'code' => -1,
                'msg'  => "请登录",
                'data' =>''
            ]);
        }

        $commission_userinfo = CommissionUser::where('customer_id',$customer->id)->first();
        if(empty($commission_userinfo)){
            return response()->json([
                'code' => -1,
                'msg'  => "请申请分销商",
                'data' =>''
            ]);
        }
        $res = CommissionCustomers::query()->with(['customer'])->where('commission_user_id',$commission_userinfo['id'])->get();
        return response()->json([
            'code' => 0,
            'msg'  => "成功",
            'data' => !empty($res)?$res:[]
        ]);
    }

}
