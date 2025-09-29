<?php

namespace Plugin\LangPackGenerator\Controllers;

use Beike\Admin\Http\Controllers\Controller;
use Beike\Admin\Http\Resources\PluginResource;
use Beike\Admin\Services\LanguageService;
use Beike\Repositories\SettingRepo;
use FilesystemIterator;
use Illuminate\Http\Request;
use Plugin\LangPackGenerator\Libraries\File;
use Plugin\LangPackGenerator\Libraries\LangPackGenerator;
use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogsLogic;
use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic;
use Plugin\LangPackGenerator\Requests\LpgAddRequest;
use Plugin\LangPackGenerator\Requests\LpgSaveRequest;
use Plugin\LangPackGenerator\Services\factory\TranslateFactory;

class LangPackGeneratorController extends Controller {

    public function decodeUnescapeHtml(Request $request)
    {
        $escapedString   = $request->post('html');
        $unescapedString = stripslashes($escapedString);
        return str_replace('\'', '’', $unescapedString);
    }

    public function component(Request $request, $component)
    {
        //        dd(locales());
        //"name" => "繁体中文"
        //    "code" => "zh_hk"
        $data = [
            'component' => $component ?? 'common'
        ];
        return match (($component ?? 'common')) {
            'article', 'page_category' => view('LangPackGenerator::admin.page_script', $data),
            'menu'                     => view('LangPackGenerator::admin.menu_script', $data),
            'product'                  => view('LangPackGenerator::admin.product_script', $data),
            default                    => view('LangPackGenerator::admin.script', $data),
        };

    }

    public function syncContent()
    {
        //        dd(232);

        $platforms = $this->check();
        $this->background();
        // 同步首页设置
        $desin   = system_setting('base.design_setting');
        $newData = $this->queryDesignSettingLanguages($desin['modules'], 'zh_cn', $platforms, true);
        $newData = ['modules' => $newData];
        SettingRepo::storeValue('design_setting', $newData);
        // 同步菜单
        $desin   = system_setting('base.menu_setting');
        $newData = $this->queryDesignSettingLanguages($desin['menus'], 'zh_cn', $platforms, true);
        $newData = ['menus' => $newData];
        SettingRepo::storeValue('menu_setting', $newData);
        // 同步页脚
        $desin   = system_setting('base.footer_setting');
        $newData = $this->queryDesignSettingLanguages($desin, 'zh_cn', $platforms, true);
        SettingRepo::storeValue('footer_setting', $newData);

    }

    /**
     * 查询语言并同步
     * @param $data
     * @param $query
     * @return mixed
     * @throws \Exception
     */
    public function queryDesignSettingLanguages(&$data, $query = 'zh_cn', $platforms = [], $isForce = false)
    {

        foreach ($data as $key => &$item) {
            if (isset($item[$query])) {
                $all = LanguageService::all();


                foreach ($all as $lang) {
                    $transText = $item[$query];
                    if (!isset($item[$lang['code']]) || $item[$lang['code']] == '' || $isForce) {
                        if ($transText && preg_match("/[\x7f-\xff]/", $transText)) {
                            // 检查配置是否正常
                            foreach ($platforms as $k => $name) {
                                $className = str_replace('Service', '', $name);
                                $instance  = TranslateFactory::instance($className);
                                $check     = $instance->translate('zh_cn', $lang['code'], $transText);
                                $transText = $check;
                                sleep(1);
                            }
                        }
                    }
                    $item[$lang['code']] = $transText;
                }
                //                dd($data,$key, $item);
            } else {
                if (is_array($item)) {
                    $this->queryDesignSettingLanguages($item, $query, $platforms, $isForce);
                }
            }
        }
        return $data;
    }

    /**
     * 后台运行
     * @return void
     */
    protected function background()
    {
        //非php-fpm  一般是apache
        set_time_limit(0);
        ignore_user_abort(true);
        @ob_end_flush();
        ob_start();
        //巴拉巴拉这里处理了一些事情
        echo json_encode($this->success([], trans('LangPackGenerator::common.start_run')), JSON_UNESCAPED_UNICODE);
        header("Content-Type: text/html;charset=utf-8");
        header("Connection: close");                 //告诉浏览器不需要保持长连接
        header('Content-Length: ' . ob_get_length());//告诉浏览器本次响应的数据大小只有上面的echo那么多
        ob_flush();
        flush();
        fastcgi_finish_request();

    }

    /**
     * 监听进度
     * @return array|\Illuminate\Database\Eloquent\Builder
     */
    public function progress(): \Illuminate\Database\Eloquent\Builder|array
    {
        $lists    = [];
        $oldCount = (new LangPackGeneratorLogic())->runTaskCount();
        do {
            $count = (new LangPackGeneratorLogic())->runTaskCount();

            if ($count > $oldCount) {
                $lists = $this->lists();

                break;
            }
            sleep(1);
        } while ((new LangPackGeneratorLogic())->isRun());
        return $lists ? : $this->lists();
    }

    /**
     * 首页
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Exception
     */
    public function index()
    {
        //         $baseDemo = new AezhushouService();
        //         echo "<br>愿文本:<br>";
        //         $htmlContent =   <<<HTML
        // <html>
        // <head>
        //     <title>示例页面</title>
        // </head>
        // <body>
        //     <p>这是一个示例文本。</p>
        //     <div>包含<span>中文</span>和英文。</div>
        //     <script>
        //         var text = "这段文本不会被替换";
        //     </script>
        // </body>
        // </html>
        // HTML;
        //         echo $htmlContent;
        //         echo "<br>翻译后文本:<br>";
        //         $rs = $baseDemo->richTextTranslate('zh-cn', 'en', $htmlContent);
        //         echo ($rs);die;
        $plugins     = app('plugin')->getPlugins();
        $pluginsList = array_values(PluginResource::collection($plugins)->jsonSerialize());

        $plugin    = plugin('lang_pack_generator');
        $records   = $this->lists();
        $languages = LanguageService::all();
        $support   = LangPackGenerator::language_code('default');

        $servicePath  = $plugin->getPath() . '/Services';
        $serviceFiles = File::dir_class_files($servicePath);
        $platforms    = LangPackGeneratorLogic::getServiceList();

        $config  = [];
        $columns = SettingRepo::getPluginColumns('lang_pack_generator');
        foreach ($columns as $item) {
            $config[$item['name']] = $item->toArray();
        }
        $configPlatForm = $config['platform']['value'] ?? [];


        $data = [
            'config'            => [
                'sleep'            => $config['sleep']['value'] ?? '',
                'app_id'           => $config['app_id']['value'] ?? '',
                'app_secret'       => $config['app_secret']['value'] ?? '',
                'baidu_app_id'     => $config['baidu_app_id']['value'] ?? '',
                'baidu_app_secret' => $config['baidu_app_secret']['value'] ?? '',
                'platform'         => $configPlatForm ? explode(",", $configPlatForm) : [],
            ],
            'open_platform'     => $platforms,
            'plugins'           => $pluginsList,
            'support_languages' => $support,
            'languages'         => $languages,
            'records'           => $records,
            'name'              => $plugin->getLocaleName(),
            'description'       => $plugin->getLocaleDescription(),
        ];
        return view('LangPackGenerator::admin.lang_pack_generator', $data);
    }

    /**
     * 数据列表
     * @return \Illuminate\Database\Eloquent\Builder|array
     */
    public function lists(): \Illuminate\Database\Eloquent\Builder|array
    {
        $plugins     = app('plugin')->getPlugins();
        $pluginsList = array_values(PluginResource::collection($plugins)->jsonSerialize());
        $result      = (new LangPackGeneratorLogic())->records();
        foreach ($result['data'] as &$item) {
            $runTaskNumber = $item['run_task_number'];
            if ($item['task_number']) {
                $item['progress'] = round(($runTaskNumber / $item['task_number']) * 100);

            } else {
                $item['progress'] = 0;
            }


            $pluginCode = $item['plugin_code'];
            foreach ($pluginsList as $plugin) {
                if ($plugin['code'] == $pluginCode) {
                    $item['plugin'] = $plugin;
                }
            }
        }
        return $result;
    }

    public function check()
    {
        $platformSetting = plugin_setting('lang_pack_generator.platform', '');

        $platform = is_array($platformSetting) ? $platformSetting : explode(',', $platformSetting);
        // 开始前检查
        $platform = array_filter($platform);
        if (!$platform) {
            //            throw new \Exception(trans('LangPackGenerator::common.go_to_config'));
            $platform[] = 'OfficialService';
        }
        // 检查配置是否正常
        //        foreach ($platform as $k => $name) {
        //            $className = str_replace('Service', '', $name);
        //
        //            $instance  = TranslateFactory::instance($className);
        //            $check     = $instance->translate('zh_cn', 'en', 'ok');
        //            if (strtolower($check) != 'ok') {
        //                $config = $instance->getConfig();
        //                throw new \Exception(trans('LangPackGenerator::common.trans_error', ['label' => $className,
        //                                                                                     'trans' => $check]));
        //            }
        //        }
        return $platform;
    }

    /**
     * 运行
     * @throws \Exception
     */
    public function run(Request $request): array
    {

        try {
            $this->check();
            $this->background();

            $params = $request->post();
            $id     = $params['id'] ?? 0;
            if (!$id) {
                return $this->fail(trans('LangPackGenerator::common.params_error'));
            }
            $result = (new LangPackGeneratorLogic())->run($params);
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success($result, trans('LangPackGenerator::common.success'));
    }

    /**
     * 日志记录
     * @param Request $request
     */
    public function logs(Request $request)
    {
        $type = $request->get('type','');

        $id               = $request->get('id');
        $where['task_id'] = $id;
        $item = \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->findOrFail($id);

        $runTaskNumber = $item['run_task_number'];
        if ($item['task_number']) {
            $item['progress'] = round(($runTaskNumber / $item['task_number']) * 100);

        } else {
            $item['progress'] = 0;
        }


        $plugins     = app('plugin')->getPlugins();
        $pluginsList = array_values(PluginResource::collection($plugins)->jsonSerialize());
        $pluginCode = $item['plugin_code'];
        foreach ($pluginsList as $plugin) {
            if ($plugin['code'] == $pluginCode) {
                $item['plugin'] = $plugin;
            }
        }
        if ($type=='down'){

            $file = storage_path('logs/lang_pack_generator/'.$id.'.log');
            response()->download($file,'lang_pack_generator_'.$id.'_'.date('YmdHis'))->send();
            exit($file);
        }
        return [
            'content' => LangPackGeneratorLogsLogic::records(999, $where),
            'item' => $item
        ];
    }

    /**
     * 停止脚本
     * @param Request $request
     * @return array
     */
    public function stop(Request $request): array
    {
        $id     = $request->get('id');
        $result = LangPackGeneratorLogic::stop($id);

        return [
            'code'    => 1,
            'message' => trans('LangPackGenerator::common.stop_success')
        ];
    }

    /**
     * 新增
     * @param LpgAddRequest $request
     * @return array|mixed|\Plugin\LangPackGenerator\Models\LangPackGenerator|string[]
     */
    public function add(LpgAddRequest $request): mixed
    {
        try {
            $validated = $request->validated();

            $result = (new LangPackGeneratorLogic())->add($request->post());
            return $this->success($result, trans('LangPackGenerator::common.add_success'));
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }


    }

    /**
     * 编辑
     * @param LpgSaveRequest $request
     * @return array
     * @throws \Throwable
     */
    public function edit(LpgSaveRequest $request): array
    {
        try {
            $validated = $request->validated();
            $result    = (new LangPackGeneratorLogic())->edit($request->post());
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($result, trans('LangPackGenerator::common.update_success'));
    }

    /**
     * 导入系统
     * @param Request $request
     * @return array
     */
    public function import(Request $request): array
    {
        try {
            $result = (new LangPackGeneratorLogic())->import($request->post());
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($result, trans('LangPackGenerator::common.import_success'));
    }

    /**
     * 配置
     * @param Request $request
     * @return array
     */
    public function config(Request $request): array
    {
        try {
            $params = $request->post();

            $config = [
                'platform'         => is_array($params['open_platform']) ? implode(',', $params['open_platform']) : $params['open_platform'],
                'app_secret'       => $params['app_secret'],
                'app_id'           => $params['app_id'],
                'baidu_app_secret' => $params['baidu_app_secret'],
                'baidu_app_id'     => $params['baidu_app_id'],
                'sleep'            => $params['sleep'],
            ];
            SettingRepo::update('plugin', 'lang_pack_generator', $config);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($config, trans('LangPackGenerator::common.import_success'));
    }

    public function delete(Request $request)
    {
        try {
            $result = (new LangPackGeneratorLogic())->delete($request->post());
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($result, trans('LangPackGenerator::common.delete_success'));
    }

    /**
     * 备份记录
     * @param Request $request
     * @return array
     */
    public function logBackups(Request $request)
    {
        try {

            $result = (new LangPackGeneratorLogic())->logBackups(['id' => $request->get('id')]);
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($result, trans('LangPackGenerator::common.request_success'));
    }

    /**
     * 还原备份记录
     * @param Request $request
     * @return array
     */
    public function restoreBackups(Request $request)
    {
        try {
            $result = (new LangPackGeneratorLogic())->restoreBackups($request->post());
            if (is_string($result)) {
                return $this->fail($result);
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success([], trans('LangPackGenerator::common.restore_backups_sucdess'));
    }


    /**
     * 输出
     * @param $message
     * @param $data
     * @param $code
     * @return array
     */
    public function output($message, $data, $code)
    {
        return [
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ];
    }

    /**
     * 成功
     * @param $data
     * @param $message
     * @return array
     */
    public function success($data = [], $message = '')
    {
        if (!$message) {
            $message = trans('LangPackGenerator::common.request_success');
        }
        return $this->output($message, $data, 200);
    }

    /**
     * 失败
     * @param $message
     * @param $data
     * @return array
     */
    public function fail($message = '', $data = [])
    {
        if (!$message) {
            $message = trans('LangPackGenerator::common.request_fail');
        }
        return $this->output($message, $data, 500);
    }

    public function installPro(Request $request)
    {
        $status = LangPackGeneratorLogic::checkInstalled();
        if (intval($status) === 1) {
            return $this->fail('你已安装翻译增强功能, 请勿重复操作');
        }
        $plugin = plugin('lang_pack_generator');
        $path   = $plugin->getPath();
        // 获取配置安装
        $installFilesPath = $path . '/Install/config.php';
        $installConfig    = include_once $installFilesPath;
        $version          = LangPackGeneratorLogic::getVersion();

        foreach ($installConfig['install_files'] as $key => $value) {
            $file            = $path . '/Install/files/v' . $version . '/' . $value['path']; //旧目录
            $newFile         = app()->basePath() . '/' . $value['path'];                     //新目录
            $backupsBasePath = $path . '/Install/backups/v' . $version . '/';
            $backupsFile     = $backupsBasePath . $value['path'];
            // 检查目录是否存在，如果不存在则创建目录
            $dirname = pathinfo($backupsFile, PATHINFO_DIRNAME);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0755, true);
            }
            // 将内容写入文件
            file_put_contents($backupsFile, file_get_contents($newFile));
            copy($file, $newFile); //拷贝到新目录
        }
        file_put_contents($path . '/Install/install.lock', 1);
        // 将config.json文件修改
        LangPackGeneratorLogic::switchPluginType('translator');
        return $this->success([], '安装完成');
    }

    public function unInstallPro()
    {
        $status = LangPackGeneratorLogic::checkInstalled();
        if (intval($status) !== 1) {
            return $this->fail('你未安装增强功能或手动安装方式无法操作此功能');
        }
        $plugin = plugin('lang_pack_generator');
        $path   = $plugin->getPath();
        // 获取配置安装
        $installFilesPath = $path . '/Install/config.php';
        $installConfig    = include_once $installFilesPath;

        $version = LangPackGeneratorLogic::getVersion();
        foreach ($installConfig['install_files'] as $key => $value) {
            $file    = $path . '/Install/backups/v' . $version . '/' . $value['path']; //旧目录
            $newFile = app()->basePath() . '/' . $value['path'];                       //新目录
            copy($file, $newFile);                                                     //拷贝到新目录
        }
        unlink($path . '/Install/install.lock');
        LangPackGeneratorLogic::switchPluginType('feature');
        return $this->success([], '取消安装成功! 已还原文件');
    }

    public function replace(Request $request)
    {
        $keywords = $request->get('keywords');
        if ($keywords){
            $replace = $request->get('replace','');
            $lang = $request->get('lang',locale());
            $directory = app()->basePath() . '/resources/lang/'.$lang.'/';
            $list = $this->searchKeywordInPhpFiles($directory, $keywords, $replace);
            return ['data' => $list];
        }
        if ($files = $request->post()){
            foreach ($files as $post){
                $file = $post['file']??'';
                if (!$file){
                    return ['data' => [],'code' => 500,'msg' => '文件不能为空'];
                }
                if (!file_exists($file)){
                    return ['data' => [],'code' => 500,'msg' => '文件不存在'];
                }
                $data = include $file;
                $value = $data[$post['index']]??'';
                if (!$value){
                    return ['data' => [],'code' => 500,'msg' => '文件异常'];
                }
                $keywords = $post['keyword']??'';
                if (!$keywords){
                    return ['data' => [],'code' => 500,'msg' => '关键字不能为空'];
                }
                $replace = $post['replace']??'';
                if (!$replace){
                    return ['data' => [],'code' => 500,'msg' => '替换文本不能为空'];
                }
                $data[$post['index']] = str_replace($keywords, $replace,$value);
                $export = var_export($data,true);
                $content = <<<EOT
<?php

return $export;
EOT;
                file_put_contents($post['file'],$content);
                return ['data' => [],'code' => 200];
            }

        }
        $data = [
            'languages' => LanguageService::all()
        ];
        return view('LangPackGenerator::admin.replace', $data);
    }

    public function batch_replace(Request $request)
    {

        if ($files = $request->post()){

            foreach ($files as $post){
                $file = $post['file']??'';

                if (!$file){
                    continue;
                }

                if (!file_exists($file)){
                    continue;
                }
                $data = include $file;

                $value = $data[$post['index']]??'';
                if (!$value){
                    continue;
                }
                $keywords = $post['keyword']??'';
                if (!$keywords){
                    continue;
                }
                $replace = $post['replace']??'';
                if (!$replace){
                    continue;
                }


                $this->replaceHandle($value, $post['index'], $keywords, $replace, $data);

                $export = var_export($data,true);
                $content = <<<EOT
<?php

return $export;
EOT;
                file_put_contents($post['file'],$content);

            }
            return ['data' => [],'code' => 200];
        }

        return ['code' => 500,'msg' => '没有替换内容'];
    }
    private function replaceHandle($value,$index, $keywords, $replace, &$data){

        if (is_array($value)){
            $isMulti = false;
            foreach ($value as $k => $v){
                if (is_array($v)){
                    $isMulti = true;
                    $this->replaceHandle($v, $k, $keywords, $replace, $value);

                }
            }
            if (!$isMulti){
                $data[$index] = str_replace($keywords, $replace,$value);
            }else{
                $data[$index] =  $value;
            }
        }else{
            $data[$index] = str_replace($keywords, $replace,$value);
        }
    }
    private function searchKeywordInPhpFiles($directory, $keyword, $replace='') {

        $list = [];

        // 获取目录下的所有文件和子目录
        $items = scandir($directory);

        foreach ($items as $item) {
            // 忽略当前目录（.）和上级目录（..）
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            // 如果是目录，递归调用
            if (is_dir($path)) {
                $list =  array_merge($list, $this->searchKeywordInPhpFiles($path, $keyword, $replace));
            }  elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                // 引入文件
                $data = include $path;

                // 获取文件中定义的数组（假设数组名为 $data）
                if (!$data || !is_array($data)) {
                    continue;
                }
                foreach ($data as $index => $value) {
                    if (is_array($value)){
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    // 检查数组中是否包含关键字
                    if (!str_contains($value, $keyword)) {
                        continue;
                    }
                    $text = str_replace($keyword, '<span style="color: red">'.$keyword.'</span>', $value);
                    $list[] = [
                        'file' => $path,
                        'name' => basename($path),
                        'index' => $index,
                        'value' => $value,
                        'text' => $text,
                        'keyword'=>$keyword,
                        'replace' => $replace
                    ];
                }
            }
        }
        return $list;
    }
}
