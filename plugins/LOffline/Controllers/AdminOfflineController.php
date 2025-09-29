<?php

namespace Plugin\LOffline\Controllers;

use Beike\Admin\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugin\LOffline\Models\OfflinePaymentConfigDescriptions;

class AdminOfflineController extends Controller
{
    public function save_config(Request $request)
    {
        $descriptions = $request->descriptions;
        $saveData     = [];
        foreach ($descriptions as $locale => $description) {
            if (empty($description)) {
                return response()->json([
                    'code' => -1,
                    'msg'  => '内容不能为空'
                ]);
            }
            $saveData[] = [
                'content' => $description,
                'locale'  => $locale,
            ];
        }
        OfflinePaymentConfigDescriptions::query()->delete();
        OfflinePaymentConfigDescriptions::query()->insert($saveData);
        return response()->json([
            'code' => 0,
            'msg'  => '保存成功'
        ]);
    }

}
