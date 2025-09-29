<?php

namespace Plugin\LangPackGenerator\Logic;

use Plugin\LangPackGenerator\Libraries\LangPackGenerator;
use Plugin\LangPackGenerator\Models\LangPackGeneratorLogs;
use Throwable;

class LangPackGeneratorLogsLogic
{
    /**
     * 获取生成记录
     * @return array|\Illuminate\Database\Eloquent\Builder|string
     */
    public static function records(int $perPage = 999, $params=[])
    {

        $id = $params['task_id']??'';
        if (!$id){
            return '';
        }

        $log =  LangPackGenerator::getLogTask($id);

        // $log = explode("\n[", $log);
        // $log = array_reverse($log);
        // $log =  implode("\n[", $log);
        return $log?:'';
    }

    /**
     * 保存生成记录
     *
     * @param $threadId
     * @param $file
     * @param $type
     * @param array|string $result
     * @param $from
     * @param $to
     * @param int $status
     * @param array $result
     * @return LangPackGeneratorLogs
     * @throws Throwable
     */
    public static function write($taskId, $threadId, $file, $type, $from, $to, int $status = 0, array|string $result = ''): LangPackGeneratorLogs
    {
        $params = [
            'task_id'   => $taskId,
            'thread_id' => $threadId,
            'file'      => $file,
            'type'      => $type,
            'result'    => $result,
            'status'    => $status,
            'from_text' => $from,
            'to_text'   => $to,
        ];

        $model = new LangPackGeneratorLogs($params);
        $model->saveOrFail();
        return $model;
    }
}
