<?php

namespace Plugin\LangPackGenerator\Logic;

use Beike\Admin\Http\Resources\PluginResource;
use Beike\Admin\Services\LanguageService;
use Beike\Models\Language;
use Beike\Models\Plugin;
use Beike\Repositories\LanguageRepo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Plugin\LangPackGenerator\Libraries\File;
use Plugin\LangPackGenerator\Models\LangPackGenerator;
use Plugin\LangPackGenerator\Services\AutoModelService;
use Plugin\LangPackGenerator\Services\factory\TranslateFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class LangPackGeneratorLogic
{
    public static function stop(mixed $id)
    {

        $row = LangPackGenerator::query()
                                ->where(['id' => $id])
                                ->update(['status' => 3]);
        return $row;
    }

    /**
     * @throws \Exception
     */
    public function run($params)
    {
        $id   = $params['id'];
        $type = $params['type'] ?? 0;
        $row  = LangPackGenerator::query()
                                 ->findOrFail($id);

        $langPathFrom = lang_path() . '/' . $row->from_code;

        if (intval($row->type) === 1) {
            $plugin       = plugin($row->plugin_code);
            $langPathFrom = $plugin->getPath() . '/Lang/' . $row->from_code;
        }

        if (!is_dir($langPathFrom)) {
            throw new \Exception(trans('LangPackGenerator::common.Source language pack does not exist'));
        }
        $platform        = self::getAvailableServices();
        $platform        = array_filter(array_column($platform, 'id'));
        $taskId          = $id;
        $threadId        = md5(uniqid());

        $object          = new \Plugin\LangPackGenerator\Libraries\LangPackGenerator([
            'thread_id' => $threadId,
            'task_id'   => $taskId,
            'platform'  => $platform,
            'is_force'  => $type
        ]);
        $files           = File::get_php_files($langPathFrom);

        $taskNumber                    = count($files);
        $updateData['status']          = 1;
        $updateData['running']         = '';
        $updateData['errors']          = '';
        $updateData['success']         = '';
        $updateData['run_task_number'] = 0;
        $updateData['end_time']        = 0;
        $updateData['start_time']      = time();
        $updateData['thread_id']       = $threadId;
        $updateData['files']           = $files;
        $updateData['task_number']     = $taskNumber;

        if ($updateData) {
            $status = LangPackGenerator::query()
                                       ->where('id', $id)
                                       ->update($updateData);
            if (!$status) {
                throw new \Exception(trans('LangPackGenerator::common.system internal error'));
            }
        }

        $result = $object->start($row->from_code, $row->to_code, $files);
        if (!$result['status'] ?? 0) {
            throw new \Exception($result['message'] ?? trans('LangPackGenerator::common.system internal error'));
        }
        return ['status' => 1];
    }

    /**
     * 获取生成记录
     * @return array|\Illuminate\Database\Eloquent\Builder
     */
    public function records(int $perPage = 20)
    {
        return LangPackGenerator::query()
                                ->select('*')
                                ->orderByDesc('created_at')
                                ->paginate($perPage)
                                ->jsonSerialize();

    }

    /**
     * 保存生成记录
     *
     * @param array $params
     * @return mixed
     * @throws \Throwable
     */
    public function add($params)
    {
        $languages = LanguageService::all();
        foreach ($languages as $k => $v) {
            if ($v['code'] == $params['from_code']) {
                $params['from_name'] = $v['name'];
                break;
            }
        }

        $type = $params['type'];

        if ($type) {
            $data = [
                'from_code'   => $params['from_code'],
                'to_code'     => $params['to_code'],
                'type'        => $params['type'],
                'plugin_code' => $params['plugin_code'],
            ];
        } else {
            $data = [
                'from_code'   => $params['from_code'],
                'to_code'     => $params['to_code'],
                'type'        => $type,
                'custom_name' => $params['custom_name'] ?? '',
            ];
        }
        $exists = LangPackGenerator::query()
                                   ->where($data)
                                   ->exists();
        if ($exists) {
            throw new \Exception(trans('LangPackGenerator::common.list_already'));
        }
        $data['from_name'] = $params['from_name'];
        $data['to_name']   = $params['to_name']??$params['to_code'];
        $data['status']    = 0;
        $data['running']   = '';
        // 类型检测
        $type       = $params['type'];
        $pluginCode = '';
        if ($type) {
            $isPlugin    = false;
            $plugins     = app('plugin')->getPlugins();
            $pluginsList = array_values(PluginResource::collection($plugins)
                                                      ->jsonSerialize());
            $pluginCode  = $params['plugin_code'];

            foreach ($pluginsList as $item) {
                if ($item['code'] == $pluginCode) {
                    $isPlugin = true;
                }
            }
            if (!$isPlugin) {
                throw new \Exception(trans('LangPackGenerator::common.plugin_does_not_exist'));
            }
            $data['type']        = $type;
            $data['plugin_code'] = $pluginCode;
        }
        $model = new LangPackGenerator($data);
        $model->saveOrFail();
        return $model;
    }

    public function import(array|string|null $params)
    {
        $id  = $params['id'];
        $row = LangPackGenerator::query()
                                ->findOrFail($id);

        $toCode = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::toSystemLangCode($row->to_code);
        if ($row['type']) {
            $toPath   = plugin($row->plugin_code)->getPath() . '/Lang/' . $toCode;
            $fromPath = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getBuildPath('plugins/' . $row->plugin_code . '/' . $row['to_code']);
        } else {
            $toPath   = lang_path() . '/' . $toCode;
            $fromPath = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getBuildPath($row['to_code']);
        }

        if (!is_dir($fromPath)) {
            throw new FileNotFoundException(trans('LangPackGenerator::common.directory does not exist', ['lang' => $toCode,
                                                                                                         'path' => $fromPath]));
        }
        // 先手动备份
        if ($row['type']) {
            $typeName = 'plugins';
        } else {
            $typeName = 'systems';
        }
        if (is_dir($toPath)) {
            $backupsDir  = 'Lang/' . $typeName . '/' . $typeName . '_' . $toCode . '_' . date('YmdHis');
            $backupsPath = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getBackupsPath($backupsDir);
            File::mvdir($toPath, $backupsPath);
        }

        // 在移动
        File::mvdir($fromPath, $toPath);
        return $row;
    }

    /**
     * 编辑
     * @param array|string|null $params
     * @return int|LangPackGenerator
     * @throws \Throwable
     */
    public function edit(array|string|null $params): LangPackGenerator|int
    {
        $id  = $params['id'];
        $row = LangPackGenerator::query()
                                ->findOrFail($id);

        // 将主文件命名
        if ($params['custom_name'] && $row['custom_name'] !== $params['custom_name']) {
            $baseFile = 'admin/base.php';
            $to       = $row->to_code;
            $from     = $row->form_code;

            $baseFile = lang_path($row->to_code) . '/' . $baseFile;
            if (is_file($baseFile)) {

                $baseData         = include $baseFile;
                $toTextsStr       = $params['custom_name'];
                $baseData['name'] = $toTextsStr;
                $var              = var_export($baseData, true);
                $plugin           = plugin('lang_pack_generator');

                $toName     = $row->to_name;
                $toCode     = $row->to_code;
                $time       = date('Y-m-d H:i:s');
                $pluginName = $plugin->getName()['zh_cn'];

                $template = <<<FILEBODY
<?php
/**
 * {$toName}({$toCode})语言包
 * @author {$pluginName} - 1cli
 * @version v1.0.0
 * @date {$time}
 */

return {$var};
FILEBODY;

                file_put_contents($baseFile, $template);

                // 更新系统语言名称
                $item = Language::query()
                                ->where('code', $row->to_code)
                                ->first();
                if ($item) {
                    LanguageRepo::update($item->id, ['name' => $toTextsStr]);
                }

            }

            return LangPackGenerator::query()
                                    ->where(['id' => $id])
                                    ->update(['custom_name' => $params['custom_name']]);
        }


        if (!in_array($row->status, [0])) {
            throw new \LogicException(trans('LangPackGenerator::common.the script is already running'));
        }
        $languages = LanguageService::all();
        foreach ($languages as $k => $v) {
            if ($v['code'] == $params['from_code']) {
                $params['from_name'] = $v['name'];
                break;
            }
        }
        $type = $params['type'];


        $params = [
            'from_name'   => $params['from_name'],
            'from_code'   => $params['from_code'],
            'to_name'     => $params['to_name'],
            'custom_name' => $params['custom_name'] ?? '',
        ];
        return LangPackGenerator::query()
                                ->where(['id' => $id])
                                ->update($params);
    }

    /**
     * 删除
     * @param array|string|null $params
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null
     */
    public function delete(array|string|null $params): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null
    {
        $id  = $params['id'];
        $row = LangPackGenerator::query()
                                ->findOrFail($id);
        $row->delete();
        return $row;
    }

    /**
     * 运行中的任务
     * @return int|mixed
     */
    public function runTaskCount()
    {
        return LangPackGenerator::query()
                                ->where('status', 1)
                                ->sum('run_task_number');
    }

    public function isRun()
    {
        $count = LangPackGenerator::query()
                                  ->where('status', 1)
                                  ->count();
        return (bool)$count;
    }

    public function logBackups($params)
    {
        $id  = $params['id'];
        $row = LangPackGenerator::query()
                                ->findOrFail($id);
        if (!$row) {
            throw new \LogicException(trans('LangPackGenerator::common.data_not_exits'));
        }
        // 先手动备份
        if ($row['type']) {
            $typeName = 'plugins';
        } else {
            $typeName = 'systems';
        }

        $list        = [];
        $backupsDir  = 'Lang/' . $typeName . '/';
        $backupsPath = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::getBackupsPath($backupsDir);
        if (!is_dir($backupsPath)) {
            return [];
        }
        $dirs = scandir($backupsPath);

        // 遍历文件和文件夹
        foreach ($dirs as $item) {
            // 排除当前目录和上级目录
            if ($item === '.' || $item === '..') {
                continue;
            }
            if ($row['type']) {
                $typeName = 'plugins';
            } else {
                $typeName = 'systems';
            }
            // 判断是否为文件夹
            $itemPath = $backupsPath . $item;
            // 使用 strncmp() 函数进行比较
            $prefix = $typeName . '_' . \Plugin\LangPackGenerator\Libraries\LangPackGenerator::toSystemLangCode($row->to_code) . '_';
            if (is_dir($itemPath) && (strncmp($item, $prefix, strlen($prefix)) === 0)) {
                $list[] = [
                    'path' => $itemPath,
                    'name' => $item,
                ];
            }
        }
        return $list;
    }

    /**
     * 恢复备份
     * @param array $params
     * @return true|void
     */
    public function restoreBackups(array $params)
    {
        $file = $params['file'];
        $id   = $params['id'];
        $row  = LangPackGenerator::query()
                                 ->findOrFail($id);
        if (!$row) {
            throw new \LogicException(trans('LangPackGenerator::common.the script is already running'));
        }
        $list = (new LangPackGeneratorLogic())->logBackups(['id' => $id]);
        if (is_string($list)) {
            throw new \Exception($list);
        }
        $toCode = \Plugin\LangPackGenerator\Libraries\LangPackGenerator::toSystemLangCode($row->to_code);
        foreach ($list as $k => $v) {
            if ($file == $v['name']) {

                if ($row['type']) {
                    $toPath = plugin($row->plugin_code)->getPath() . '/Lang/' . $toCode;
                } else {
                    $toPath = lang_path() . '/' . $toCode;
                }
                if (is_dir($toPath)) {
                    File::mvdir($v['path'], $toPath);
                }
                return true;
            }
        }
        return true;
    }

    public static function checkInstalled()
    {
        $plugin = plugin('lang_pack_generator');
        $path   = $plugin->getPath();
        $file   = $path . '/Install/install.lock';

        $editFile150md5 = md5_file($path.'/Install/files/v1.5.0/resources/beike/admin/views/pages/pages/form.blade.php');
        $editFile140md5 = md5_file($path.'/Install/files/v1.4.0/resources/beike/admin/views/pages/pages/form.blade.php');
        $editFile155md5 = md5_file($path.'/Install/files/v1.5.5/resources/beike/admin/views/pages/pages/form.blade.php');

        $sourceFileMd5 = md5_file(resource_path('beike/admin/views/pages/pages/form.blade.php'));
        if (file_exists($file) && in_array(file_get_contents($file), [1,2])  && $sourceFileMd5 != $editFile150md5 && $sourceFileMd5 != $editFile140md5 && $sourceFileMd5 != $editFile155md5) {
            LangPackGeneratorLogic::setInstalled(3);
            $status = 3;
        }else if (file_exists($file)) {
            $status = file_get_contents($file);
        } else {
            $status = 0;
        }
        return $status;
    }

    public static function setInstalled($status)
    {
        $plugin = plugin('lang_pack_generator');
        $path   = $plugin->getPath();
        $file   = $path . '/Install/install.lock';
        file_put_contents($file, $status);
        return $status;
    }

    public static function checkManualInstalled()
    {
        $status = LangPackGeneratorLogic::checkInstalled();
        if (!$status) {
            LangPackGeneratorLogic::setInstalled(2);
        }
        return 2;
    }

    public static function getVersion($value = '')
    {
        if ($value) {
            return $value;
        }
        $plugin       = plugin('lang_pack_generator');
        $path         = $plugin->getPath();
        $version      = config('beike.version');
        $versionSplit = explode('.', $version);
        $versionInt   = str_replace('.', '', $version);

        if (intval($versionInt) > 1700 or intval($versionInt) < 1400) {
            throw new \Exception('系统只支持 >=v1.4.0 and <=v1.5.* 版本, 高于或小于版本请使用指定版本或联系开发者升级');
        }

        $version    = $versionSplit[0] . '.' . $versionSplit[1] . '.'.$versionSplit[2];
        $versionDir = $path . '/Install/files/v' . $version;
        if (!is_dir($versionDir)) {
            $version    = $versionSplit[0] . '.' . $versionSplit[1] . '.0';
        }
        return $version;
    }

    public static function switchPluginType($value): bool
    {

        $plugin = plugin('lang_pack_generator');
        $type   = $plugin->getType();
        $path   = $plugin->getPath();
        $config = file_get_contents($path . '/config.json');
        $config = str_replace('"type": "' . $type . '"', '"type": "' . $value . '"', $config);
        file_put_contents($path . '/config.json', $config);
        // 更新数据库
        $plugin->setType($value);
        Plugin::query()
              ->where('code', 'lang_pack_generator')
              ->update(['type' => $value]);
        return true;
    }

    public static function getMasterPlatform($masterPlatform=null)
    {
        if (!$masterPlatform){
            $masterPlatform = self::getOptimalService()['id']??'';
        }
        // 开始前检查
        if (!$masterPlatform) {
//            throw new \Exception(trans('LangPackGenerator::common.go_to_config'));
            $masterPlatform = 'OfficialService';
        }
        // 检查配置是否正常
        $className = str_replace('Service', '', $masterPlatform);

        $name  = ucfirst($className);
        $class = '\\Plugin\\LangPackGenerator\\Services\\' . $name . 'Service';
        if (!class_exists($class)) {
            throw new \Exception("class not found:{$class}");
        }
        return $class;
    }

    public static function getOptimalService()
    {

        $list = self::getAvailableServices();

        if (!$list){
            throw new \Exception(trans('LangPackGenerator::common.go_to_config'));
        }

        return $list[0];
    }

    public static function getAvailableServices()
    {
        $list = self::getServiceList();

        $filterPlatform = plugin_setting('lang_pack_generator.filter_platform', '');
        $appId = plugin_setting('lang_pack_generator.app_id', '');
        $baiduAppId = plugin_setting('lang_pack_generator.baidu_app_id', '');
        $deepSeekAppId = plugin_setting('lang_pack_generator.deepseek_app_key', '');
        // 如果存在自动,那么不做配置
        if ($filterPlatform &&  in_array('autoModel', array_keys($filterPlatform)) ){
            unset($filterPlatform['autoModel']);
        }


        foreach ($list as $i => $v) {
            if ($v['id'] == 'baidu' && !$baiduAppId){
                unset($list[$i]);
                continue;
            }
            if ($v['id'] == 'youdao' && !$appId){
                unset($list[$i]);
                continue;
            }
            if ($v['id'] == 'deepSeek' && !$deepSeekAppId){
                unset($list[$i]);
                continue;
            }
            if ($v['id'] == 'autoModel'){
                unset($list[$i]);
                continue;
            }

            if ($filterPlatform && !in_array($v['id'], array_keys($filterPlatform) )) {
                unset($list[$i]);
            }
            $platform_key = $v['id'];
            $errorPlatformName = $platform_key."_error";
            $platformErrorNumber = Cache::get($errorPlatformName,0);
            if ($platformErrorNumber>5){
                unset($list[$i]);
            }

        }
        $ret = array_values($list);
        $platformStatus = plugin_setting('lang_pack_generator.platform_status', '');
        $newData = [];

        if ($platformStatus){
            $sortList = [];

            $id2KeyRet = [];
            foreach ($ret as $item) {
                $retId = ucfirst($item['id']);
                $ms = $platformStatus[$retId]['ms']??null;
                $id2KeyRet[$retId] = $item;
                $sortList[$retId] = $ms;

            }
            asort($sortList);

            foreach ($sortList as $k1 => $item) {
                $newDataItem = $id2KeyRet[$k1]??null;
                if (!$newDataItem){
                    continue;
                }
                $newDataItem['ms'] = $item;
                $newData[] = $newDataItem;
             }
        }
        return $newData?:$ret;
    }

    public static function getServiceList()
    {
        $plugin    = plugin('lang_pack_generator');
        $servicePath  = $plugin->getPath() . '/Services';
        $serviceFiles = File::dir_class_files($servicePath);
        $platforms    = [];

        foreach ($serviceFiles as $k => $v) {
            if ($v['class']=='BaseService'){
                continue;
            }
            $objectName = '\\' . $v['namespace'] . '\\' . $v['class'];

            $object     = new $objectName();

            if (in_array('getConfig', get_class_methods($object))) {
                $config      = $object->getConfig();
                $isNew       = $config['is_new']??0;
                $status = $config['status']??0;
                if (!$status){
                    continue;
                }
                $platformConfig = [
                    'id'    => $config['key'],
                    'label' => $config['label'],
                    'is_new' => $isNew
                ];
                if ($isNew){
                    array_unshift($platforms, $platformConfig);
                }else{
                    $platforms[] = $platformConfig;
                }
            } else {
                $className   = str_replace('Service', '', $v['class']);
                $platforms[] = [
                    'id'    => $className,
                    'label' => $className,
                ];
            }

        }

        return $platforms;
    }
}
