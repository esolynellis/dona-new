<?php

namespace Plugin\Commission\Resources;

use Beike\Admin\Http\Resources\OrderSimple;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Plugin\Commission\Models\CommissionAmountLog;
use Plugin\Commission\Services\CommissionService;

class CommissionAmountLogsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request): array
    {

        $status_format = "";
        if ($this->status) {
            if ($this->action == CommissionAmountLog::ACTION_APPLY_CLOSE) {//要显示待受理
                $status_format = trans('Commission::orders.status_' . $this->status . '_1');
            } else if ($this->action == CommissionAmountLog::ACTION_REFUND) {//要显示待受理
                $status_format = trans('Commission::orders.status_' . $this->status . '_1');
            } else if ($this->action == CommissionAmountLog::ACTION_SYS_SUB) {//要显示待受理
                $status_format = trans('Commission::orders.status_' . '2_1');
            } else {
                $status_format = trans('Commission::orders.status_' . $this->status);
            }
        }//1正常,2.已退款,3.已打款,4.已拒绝打款，5.待结算
        $data = [
            'id'            => $this->id,
            'action'        => $this->action,
            'action_format' => $this->action,
            'customer_id'   => $this->customer_id,
            'customer'      => ($this->customer_id && $this->customer) ? CustomerResource::make($this->customer) : null,
            'level'         => $this->level,
            'base_amount'   => $this->base_amount,
            'c_base_amount' => $this->c_base_amount,
            'rate'          => $this->rate,
            'amount'        => $this->amount,
            'c_amount'      => $this->c_amount,
            'date_at'       => $this->date_at,
            'status'        => $this->status,
            'status_format' => $status_format,
            'apply_data'    => $this->apply_data,
            'c_apply_data'  => $this->c_apply_data,
            'audit_note'    => $this->audit_note,
            'rate2'         => $this->rate2,
            'order_id'      => $this->order_id,
            'order'         => ($this->order_id && $this->order) ? $this->order : null,
        ];
        $data = CommissionService::parseActionFormat($data);
        return $data;
    }


}
