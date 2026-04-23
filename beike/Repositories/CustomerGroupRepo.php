<?php
/**
 * AddressRepo.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     Edward Yang <yangjin@guangda.work>
 * @created    2022-06-28 15:22:05
 * @modified   2022-06-28 15:22:05
 */

namespace Beike\Repositories;

use Beike\Models\Customer;
use Beike\Models\CustomerGroup;
use Beike\Models\CustomerOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class CustomerGroupRepo
{
    /**
     * 创建一个CustomerGroup记录
     * @param $data
     * @return Builder|Model
     */
    public static function create($data): Model|Builder
    {
        return CustomerGroup::query()->create($data);
    }

    /**
     * @param $id
     * @param $data
     * @return Builder|Builder[]|Collection|Model
     * @throws \Exception
     */
    public static function update($id, $data): Model|Collection|Builder|array
    {
        $group = CustomerGroup::query()->find($id);
        if (! $group) {
            throw new \Exception("Customer Group id {$id} 不存在");
        }
        $group->update($data);

        return $group;
    }

    /**
     * @param $id
     * @return Builder|Builder[]|Collection|Model|null
     */
    public static function find($id): Model|Collection|Builder|array|null
    {
        return CustomerGroup::query()->findOrFail($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function delete($id)
    {
        $defaultCustomerGroupId = system_setting('base.default_customer_group_id');
        if ($id == $defaultCustomerGroupId) {
            throw new NotAcceptableHttpException(trans('admin/customer_group.default_cannot_delete'));
        }
        $group = CustomerGroup::query()->find($id);
        if ($group) {
            $group->delete();
        }
    }

    /**
     * 获取用户组列表
     *
     * @return Builder[]|Collection
     */
    public static function list(): Collection|array
    {
        $builder = CustomerGroup::query()->with('description', 'descriptions');

        return $builder->get();
    }

    /**
     * 根据id获取用户组列表
     *
     * @return Builder[]|Collection
     */
    public static function getgroupinfo($group_id)
    {
        $builder = CustomerGroup::query()->with('description', 'descriptions');
        $builder->leftJoin('customer_group_descriptions as pd', function ($build) {
            $build->whereColumn('pd.customer_group_id', 'customer_groups.id')
                ->where('locale', locale());
        });
            $builder->where('customer_groups.id',$group_id);

        return $builder->first();
    }

    /**
     * 客户开通会员订单
     * @return
     */
    public static function createCusorder(array $data): CustomerOrder
    {
        $customer = current_customer();
         if (empty($customer)) {
             return json_fail('empty customer');
         }
        // 获取客户组信息
        $group = CustomerGroup::query()->find($data['group_id']);
        if (empty($group)) {
            return json_fail('Customer group not found');
        }

        // 检查客户是否已经是会员
        // if ($customer->is_member) {
        //     return json_fail('Customer is already a member');
        // }
        $currencyCode  = current_currency_code();
        $currency      = CurrencyRepo::findByCode($currencyCode);
        $currencyValue = $currency->value ?? 1;
        // 创建会员订单
        $order = new CustomerOrder();
        $order->number = self::generateOrderNumber(); // 生成订单号
        $order->customer_id = $customer->id;
        $order->customer_group_id = $data['group_id'];
        $order->customer_name = $customer->name;
        $order->total = $group->price; // 假设会员费用存储在客户组中
        $order->locale = locale();
        $order->currency_code = $currencyCode; // 假设默认货币为 USD
        $order->currency_value = $currencyValue; // 假设汇率为 1
        $order->ip = request()->ip();
        $order->user_agent = request()->header('User-Agent');
        $order->comment = 'Membership activation';
        $order->status = 'unpaid'; // 初始状态为待支付
        $order->payment_method_code = $data['payment_method_code'];
        $order->payment_method_name = $data['payment_method_name'];
        $order->created_at = now();
        $order->updated_at = now();
        // 保存订单
        $order->save();

        // 返回订单信息
        return $order;
    }
    /**
     * 生成订单号
     *
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        $orderNumber = Carbon::now()->format('Ymd') . rand(10000, 99999);
        $exist       = CustomerOrder::query()->where('number', $orderNumber)->exists();
        if ($exist) {
            return self::generateOrderNumber();
        }

        return $orderNumber;
    }


    /**
     * 通过订单号获取订单
     *
     * @param $number
     * @param $customer
     * @return Builder|Model|object|null
     */
    public static function getOrderByNumber($number, $customer = null)
    {
        $builder = CustomerOrder::query()
            ->where('number', $number);

        $customerId = 0;
        if (is_int($customer)) {
            $customerId = $customer;
        } elseif ($customer instanceof Customer) {
            $customerId = $customer->id;
        }

        if ($customerId) {
            $builder->where('customer_id', $customerId);
        }

        return $builder->first();
    }
}
