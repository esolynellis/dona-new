<?php

namespace Plugin\Commission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'bank_user_name' => 'required',
            'bank_name'      => 'required',
            'bank_code'      => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'bank_user_name' => trans('address.name'),
            'bank_name'      => trans('address.phone'),
            'bank_code'      => trans('address.country_id'),
        ];
    }
}
