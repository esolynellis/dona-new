<?php
/**
 * ProductImportRequest.php
 *
 * @copyright  2023 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     TL <mengwb@guangda.work>
 * @created    2023-03-01 15:17:04
 * @modified   2023-03-01 15:17:04
 */

namespace Plugin\ProductImport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductImportRequest extends FormRequest
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
        $rules = [
            'import_file'       => 'required|mimes:xlsx|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $rules;
    }

    public function attributes()
    {
        return [
            'import_file'              => trans('ProductImport::common.import_excel_file'),
        ];
    }
}
