<?php

namespace Plugin\LangPackGenerator\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LpgSaveRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id'        => 'required|integer|max:11',
            'to_code'   => 'alpha_dash',
            'to_name'   => 'required',
            'from_code' => 'required|alpha_dash',
        ];
    }

    /**
     * 获取验证错误的自定义属性
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'to_code'   => trans('LangPackGenerator::common.to_code'),
            'to_name'   => trans('LangPackGenerator::common.to_name'),
            'from_code' => trans('LangPackGenerator::common.from_code'),
            'id'        => trans('LangPackGenerator::common.id'),
        ];
    }

    /**
     * 获取已定义验证规则的错误消息
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => trans('LangPackGenerator::common.id require') ,
        ];
    }

}
