<?php

namespace Plugin\LangPackGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class LangPackGenerator extends Model
{
    public $timestamps = true;

    protected $table = 'lang_pack_generator';

    protected $fillable = [
      'custom_name',  'type','plugin_code','from_name', 'from_code', 'to_name', 'to_code', 'status', 'file', 'result','running','run_task_number','task_number','errors','success','files'
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'result' => 'array',
        'success' => 'array',
        'errors' => 'array',
        'files' => 'array'
    ];
    protected $appends = ['created_format',   'status_text', 'start_time_format', 'end_time_format','timeout'];

    public function getTimeoutAttribute(){

        if (intval($this->status) === 1){
            $endTime = time();
        }else {
            $endTime = $this->end_time?:$this->start_time;
        }
        return $endTime - $this->start_time;
    }
    public function getStartTimeFormatAttribute(){
        return date('Y-m-d H:i:s', $this->start_time);
    }
    public function getEndTimeFormatAttribute(){
        return date('Y-m-d H:i:s', $this->end_time);
    }
    public function getCreatedFormatAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getStatusTextAttribute()
    {
        $eum = [
            0 => trans('LangPackGenerator::common.wait'),
            1 => trans('LangPackGenerator::common.running') ,
            2 => trans('LangPackGenerator::common.success') ,
            3 => trans('LangPackGenerator::common.stop') ,
            4 => trans('LangPackGenerator::common.errors') ,
        ];
        return $eum[$this->status]??trans('unknown');
    }
}

