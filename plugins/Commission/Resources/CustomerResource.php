<?php

namespace Plugin\Commission\Resources;

use Beike\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function toArray($request)
    {
        $adminUser      = null;
        $adminUserID = $this->admin_user_id;
        if (!empty($adminUserID)) {
            $adminUser = AdminUser::query()->where('id', $adminUserID)->first();
        }
        $data = [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'created_at'      => !empty($this->created_at) ? substr(time_format($this->created_at), 0, 16) : substr(time_format($this->updated_at), 0, 16),
            'admin_user_id'   => $adminUserID,
            'admin_user_name' => $adminUser ? $adminUser->email : "",
        ];


        return $data;
    }

}
