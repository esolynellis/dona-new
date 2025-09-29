<?php

namespace Plugin\LangPackGenerator\Libraries;

use Beike\Repositories\SettingRepo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogic;
use Plugin\LangPackGenerator\Logic\LangPackGeneratorLogsLogic;
use Plugin\LangPackGenerator\Services\AutoModelService;
use Plugin\LangPackGenerator\Services\factory\TranslateFactory;

ignore_user_abort(true);
set_time_limit(0);
ini_set('max_execution_time', '0');
$plugin = plugin('lang_pack_generator');
define('PLUGIN_NAME', $plugin->getLocaleName());
define('LPG_ROOT_PATH', $plugin->getPath());
define('LPG_RUNTIME_PATH', LPG_ROOT_PATH . '/runtime');
define('LPG_BUILD_PATH', LPG_ROOT_PATH . '/build');

class LangPackGenerator {
    /**
     * 线程id
     * @var mixed
     */
    private $threadId = 0;
    /**
     * 任务id
     * @var mixed
     */
    private $taskId = 0;
    /**
     * 任务信息
     * @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    private $task = [];
    /**
     * 翻译平台
     * @var mixed
     */
    private $platform = null;
    /**
     * 强制刷新
     * @var false|mixed
     */
    private $isForce = false;
    /**
     * 基础文件
     * 用于定义名称
     * @var string
     */
    private       $baseFile     = 'admin/base.php';
    private array $openPlatform = [
        'OfficialService'
    ];
    // 分隔符 ['`',":s","s1=s2"]
    private $separator = [
        "\n\n",
    ];

    /**
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        $this->threadId = $options['thread_id'];
        $this->taskId   = $options['task_id'];
        // 清除日志
        file_put_contents(storage_path('logs/lang_pack_generator/'.$this->taskId.'.log'),'');
        $fields         = [
            'status',
            'type',
            'plugin_code',
            'custom_name',
            'from_name',
            'from_code',
            'to_code',
            'to_name',
            'id',
            'thread_id'
        ];
        $this->task     =
            \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->findOrFail($this->taskId, $fields);

        $this->isForce = $options['is_force'] ?? false;
        if ($options['platform']??null) {
            $this->openPlatform = $options['platform'];
        }


    }


    /**
     * 开始运行
     * @param $threadId
     * @param $from
     * @param $to
     * @param $files
     * @return int[]
     */
    function start($from, $to, $files)
    {
        try {

            $threadStatus = $this->task->status;


            $plugin = plugin('lang_pack_generator');

            $rootPath     = $plugin->getPath();
            $builds       = [];
            $key          = 0;
            $serviceFiles = $plugin->getPath() . '/Services';
            $platforms    = File::dir_class_files($serviceFiles);
            if (intval($this->task->type) === 1) {
                $plugin = plugin($this->task->plugin_code);

            }
            do {
                $t1     = time();
                $error  = '';
                $builds = [];
                if (!$this->openPlatform) {
                    $files = [];
                    throw new \Exception(trans('LangPackGenerator::common.no_lines_available'));
                }
                $isBuild  = 0;
                foreach ($this->openPlatform as $k => $name) {

                    $className      = str_replace('Service', '', $name);

                    $instance       = TranslateFactory::instance($className);
                    $instance->type = 'text';
                    $this->platform = $instance;
                    $file           = $files[$key];


                    $from_match = $this->mapCode($from);
                    $to_match = $this->mapCode($to);

                    $run_task_number =
                        \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where('id', $this->taskId)
                                                                          ->value('run_task_number');

                    $sourcePath = lang_path();
                    $toDir      = LPG_BUILD_PATH . '/' . $this->task->to_code;
                    if (intval($this->task->type) === 1) {
                        $sourcePath = $plugin->getPath() . '/Lang';
                        $toDir      =
                            LPG_BUILD_PATH . '/plugins/' . $this->task->plugin_code . '/' . $this->task->to_code;
                    }
                    $sourceDir = $sourcePath . '/' . $this->task->from_code;
                    $buildFile = str_replace($sourceDir, $toDir, $file);

                    if (!$this->isForce) {
                        if (is_file($buildFile)) {
                            $r =
                                \Plugin\LangPackGenerator\Models\LangPackGenerator::query()
                                                                                  ->where(['id' => $this->taskId])
                                                                                  ->update([
                                                                                               'run_task_number' => $run_task_number + 1,
                                                                                               'running'         => $file
                                                                                           ]);

                            $this->output(trans('LangPackGenerator::common.file already exists', ['fileName' => $buildFile]), 'error', $file);
                            unset($files[$key]);
                            $key++;
                            continue;
                        }
                    }

                    $this->output(trans('LangPackGenerator::common.start generator language file', ['fileName' => $buildFile]), 'start', $file);

                    $data = include $file;
                    if (!is_array($data)) {
                        continue;
                    }
                    $this->logTask("语言包文件:{$file}:");



                    $maxCount = 30;
                    $chunk_data = array_chunk($data,$maxCount,true);
                    $chunk_data_all  = [];
                    foreach ($chunk_data as $chunk_data_index => $chunk_data_item){
                        $texts = [];
                        $_tempData = $chunk_data_item;
                        $this->logTask("{$chunk_data_index}.翻译{$from_match}->{$to_match}:");
                        $this->logTask($chunk_data_item);

                        $this->handle($from, $to, $_tempData, $texts);
                        if (!$texts) {
                            continue;
                        }

                        $textsStr                   = implode($this->separator[0], $texts);
                        $msT1                       = self::getMillisecond();
                        $toTextsStr                 = $this->platform->translate($from_match, $to_match, $textsStr);
                        $msT2                       = self::getMillisecond();
                        $delayTime                  = $msT2 - $msT1;
                        $className                  = ucfirst($className);
                        // 转换成数组
                        $toTexts = explode($this->separator[0], $toTextsStr);
                        $this->logTask("响应内容:");
                        $this->logTask($toTextsStr);


                        $handleStatus = $this->toHandle($_tempData, $toTexts);
                        // 翻译失败重新翻译
                        if (!$handleStatus){
                            continue 2;
                        }
                        if ($chunk_data_all){
                            $chunk_data_all = array_merge($chunk_data_all,  $_tempData);
                        }else{
                            $chunk_data_all   = $_tempData;
                        }

                    }
                    $data = $chunk_data_all;
                    $builds[$to][$file] = $data;

                    $this->build($builds, $to);

                    \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where(['id' => $this->taskId])
                                                                      ->update([
                                                                                   'run_task_number' => $run_task_number + 1,
                                                                                   'running'         => $file
                                                                               ]);

                    $this->output(trans('LangPackGenerator::common.generator success'), 'success', $file);
                    unset($files[$key]);
                    if (!count($files)) {
                        break;
                    }
                    $key++;
                    $isBuild++;
                    sleep(plugin_setting('lang_pack_generator.sleep', 1));
                }
                if ( $isBuild > 10 ) {
                    throw new \Exception($error ? : trans('LangPackGenerator::common.system internal error'));
                }
                LangPackGeneratorLogic::getAvailableServices();
            } while (count($files));

            \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where(['id' => $this->taskId])->update([
                                                                                                                    'status'   => 2,
                                                                                                                    'result'   => $builds,
                                                                                                                    'end_time' => time()
                                                                                                                ]);
            // 将主文件命名
            $baseFile = LPG_BUILD_PATH . '/' . $to . '/' . $this->baseFile;

            if (is_file($baseFile)) {
                $baseName = $this->task->to_name;
                $baseData = include $baseFile;
                if ($this->task->custom_name) {
                    $toTextsStr = $this->task->custom_name;
                } else {
                    $toTextsStr = $this->platform->translate($from, $to, $baseName);
                }

                $baseData['name'] = $toTextsStr;
                $var              = var_export($baseData, true);
                $template         = $this->template($var);
                file_put_contents($baseFile, $template);
                // 记录日志
                $this->logTask("生成文件内容：".$baseFile);

            }
            return ['status' => 1];
        } catch (\ErrorException|\Error|\Exception|\Throwable  $e) {

            //            sleep(1);
            $errors   = $this->task->errors;
            $errors[] = [
                'title' => $e->getMessage() . ' in ' . $e->getFile() . ' - ' . $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where('id', $this->taskId)->update([
                                                                                                                'errors'   => $errors,
                                                                                                                'status'   => 4,
                                                                                                                'end_time' => time()
                                                                                                            ]);
            return [
                'status'  => 0,
                'message' => $e->getMessage()
            ];
        }

    }

    static function getMillisecond()
    {
        [
            $microSeconds,
            $seconds
        ] = explode(' ', microtime());
        $microSeconds = (float)$microSeconds;
        $seconds      = (float)$seconds;

        // 将微秒转换为毫秒
        $milliseconds = ($seconds * 1000) + ($microSeconds * 1000);

        return floor($milliseconds);
    }

    /**
     * 内容输出
     * @param        $msg
     * @param string $type
     * @param string $file
     * @return void
     * @throws \Throwable
     */
    public function output($msg, string $type = 'info', $file = '')
    {

        //        LangPackGeneratorLogsLogic::write($this->taskId, $this->threadId, $file, $type, $this->task->from_code, $this->task->to_code, 1, $msg);

    }

    /**
     * 操作数据
     * @param $file
     * @param $from
     * @param $to
     * @param $data
     * @return mixed|void
     * @throws \Throwable
     */
    function handle($from, $to, $data, &$newData = [])
    {

        foreach ($data as $fromDict => &$toDict) {
            if (is_array($toDict)) {
                $data[$fromDict] = $this->handle($from, $to, $toDict, $newData);
            } else {
                $status =
                    \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where('id', $this->taskId)
                                                                      ->value('status');
                if (intval($status) !== 1) {
                    throw new \Exception(trans('LangPackGenerator::common.manual pause'));
                }
                $k         = count($newData);
                $newData[] = $toDict;
            }
        }
        return $newData;
    }


    public function toHandle(&$data, $toData, $i = 0)
    {
        foreach ($data as $fromDict => &$toDict) {
            if (is_array($toDict)) {
                $data[$fromDict] = $this->toHandle($toDict, $toData, $i);
            } else {
                $status =
                    \Plugin\LangPackGenerator\Models\LangPackGenerator::query()->where('id', $this->taskId)
                                                                      ->value('status');
                if (intval($status) !== 1) {
                    throw new \Exception(trans('LangPackGenerator::common.manual pause'));
                }
                if (!isset($toData[$i])){
                    $this->logTask("翻译失败({$i}), \n翻译生成数据：");
                    $this->logTask($toData);


                    $platform_key = $this->getPlatformConfig('key');
                    $errorPlatformName = $platform_key."_error";
                    $platformErrorNumber = Cache::get($errorPlatformName,0);
                    Cache::set($errorPlatformName, intval($platformErrorNumber)+1,1);

                    return false;
                }
                $translation = $toData[$i];
                $translation = str_replace("\'", "", $translation);
                $translation = str_replace('<ahref', '<a href', $translation);
                $translation = str_replace('ahref', '<a href', $translation);
                $toDict      = $translation;
            }
            $i++;

        }
        return $data;
    }


    /**
     * 打包文件
     * @param $data
     * @param $lang
     * @return void
     */
    function build($data, $lang = 'zh')
    {
        foreach ($data[$lang] as $file => $item) {
            $body     = var_export($item, true);
            $fileBody = $this->template($body);

            $fileName = basename($file);
            $buildDir = LPG_ROOT_PATH . '/build/' . $lang;
            $newFile  = str_replace(lang_path() . '/' . $this->task->from_code, $buildDir, $file);
            if (intval($this->task->type) === 1) {

                $buildDir = LPG_ROOT_PATH . '/build/plugins/' . $this->task->plugin_code . '/' . $lang;

                $toPlugin     = plugin($this->task->plugin_code);
                $toSourcePath = $toPlugin->getPath() . '/Lang';

                $newFile = str_replace($toSourcePath . '/' . $this->task->from_code, $buildDir, $file);

            }

            is_dir($buildDir) or mkdir($buildDir, 0775, true);
            $dirname = dirname($newFile);
            is_dir($dirname) or mkdir($dirname, 0775, true);
            $this->logTask("语言包文件生成完成");
            $this->logTask($fileBody);
            file_put_contents($newFile, $fileBody);
            // 记录日志
            $this->logTask("生成文件内容：".$newFile);

        }
    }

    /**
     * 获取备份目录
     * @param $dir
     * @return string
     */
    public static function getBackupsPath($dir = '')
    {
        return LPG_ROOT_PATH . '/backups/' . $dir;
    }

    /**
     * 获取打包文件
     * @param $dir
     * @return string
     */
    public static function getBuildPath($dir = '')
    {
        return LPG_BUILD_PATH . '/' . $dir;
    }

    /**
     * 获取根目录
     * @param $dir
     * @return string
     */
    public static function getRootPath($dir = '')
    {
        return LPG_ROOT_PATH . '/' . $dir;
    }

    /**
     * 获取运行日志路径
     * @param $dir
     * @return string
     */
    public static function getRuntimePath($dir = '')
    {
        return LPG_RUNTIME_PATH . '/' . $dir;
    }

    public function getPlatformConfig($name='', $default=null)
    {
        $platform_config = $this->platform->getConfig();
        if ($name){
            return $platform_config[$name]??$default;
        }
        return $platform_config;
    }

    /**
     * 模板
     * @param $body
     * @return string
     */
    public function template($body)
    {
        $platform_config = $this->getPlatformConfig();
        $platform_name = $platform_config['label'];
        $platform_key = $platform_config['key'];
        $toName     = $this->task->to_name;
        $toCode     = $this->task->to_code;
        $time       = date('Y-m-d H:i:s');
        $pluginName = PLUGIN_NAME;
        $version = plugin('lang_pack_generator')->getVersion();
        $lang_file_content = <<<FILEBODY
<?php
/**
 * {$toName}({$toCode})语言包
 * @author {$pluginName} - 1cli
 * @version {$version}
 * @platform {$platform_name}($platform_key)
 * @date {$time}
 */
return {$body};
FILEBODY;
        // 针对部分进行 unicode 解码
        $lang_file_content = html_entity_decode( $lang_file_content,ENT_QUOTES,'UTF-8');
        // 处理标签
        $lang_file_content = str_replace('<p style="">', '', $lang_file_content);

        return $lang_file_content;
    }

    public   function logTask($message)
    {
        $config = $this->getPlatformConfig();
        $name = "[".($config['label']??($config['key']??'-'))."]:";
        $message = is_array($message)?var_export($message, true):$message;
        $message = $name.$message;
        Log::build([
            'name' => 'lang_pack_generator',
            'path' => storage_path('logs/lang_pack_generator/'.$this->taskId.'.log'),
            'driver' => 'single',
            'channels' => ['single', 'slack'],
        ])->debug($message);
    }

    public static function getLogTask($taskId)
    {
        return @file_get_contents(storage_path('logs/lang_pack_generator/'.$taskId.'.log'));
    }

    public static function getCurrentPlatformInfo($name=null)
    {
        $currentPlatform = 'default';
        $filterPlatform  = plugin_setting('lang_pack_generator.filter_platform', ["AutoModel"=>'no']);
        foreach ($filterPlatform as $platform => $status) {
            $currentPlatform = $platform;
            break;
        }
        $factory        = TranslateFactory::instance($currentPlatform);
        $config         = $factory->getConfig();
        $config['name'] = strtolower($config['key']);
        if ($name){
            return $config[$name]??'';
        }
        return $config;
    }

    public static function getLangCodeList($platform='default')
    {
        $file = LangPackGenerator::getRuntimePath() . "/{$platform}.json";
        if (!file_exists($file)) {
            $file = LangPackGenerator::getRuntimePath() . '/default.json';
        }
        $langCode  = file_get_contents($file);
        return json_decode($langCode, true);
    }

    public static function getSystemLangCodeList()
    {
        $file = LangPackGenerator::getRuntimePath() . '/default.json';
        $langCode  = file_get_contents($file);
        return json_decode($langCode, true);
    }

    /**
     * 支持语言
     * @param $code
     * @return array
     */
    public static function language_code($platform_name = '')
    {
        if (!$platform_name){
            $platform_name = strtolower(self::getCurrentPlatformInfo('key'));
        }
        $file            = LangPackGenerator::getRuntimePath() . "/{$platform_name}.json";
        if (!file_exists($file)) {
            $file = LangPackGenerator::getRuntimePath() . "/default.json";
        }
        $langCode  = file_get_contents($file);
        $langCodes = json_decode($langCode, true);
        $languages = [];
        foreach ($langCodes as $code => $name) {

            $item = [
                'en'   => $name,
                'zh'   => $name,
                'code' => $code
            ];

            $languages[] = $item;
        }
        return $languages;
    }

    public static function toSystemLangCode($code)
    {
        $codes = [
            'zh-CHT' => 'zh_hk',
            'zh-CHS' => 'zh_cn',
            'ru'     => 'ru',
            'ko'     => 'ko',
            'ja'     => 'ja',
            'it'     => 'it',
            'id'     => 'id',
            'fr'     => 'fr',
            'es'     => 'es',
            'en'     => 'en',
            'de'     => 'de',
        ];
        return $codes[$code] ?? $code;
    }

    public  function mapCode($code)
    {
        $platform_config = $this->getPlatformConfig();
        $platform_id = $platform_config['key'];
        $lang_conversion_list = plugin_setting('lang_pack_generator.lang_conversion_list', []);
        if ($lang_conversion_list){
            foreach ($lang_conversion_list as $item){
                if ($item['master'] == $code){
                    $code =  $item[$platform_id]??$code;
                    break;
                }
            }

        }

        return  $code;
    }


}
