<?php

namespace Plugin\LangPackGenerator\Models;

use Illuminate\Database\Eloquent\Model;

class LangPackGeneratorLogs  extends Model
{
    public $timestamps = true;

    protected $table = 'lang_pack_generator_logs';

    protected $fillable = [
        'thread_id', 'file', 'result', 'status', 'type','from_text', 'to_text','task_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [

    ];

    protected $appends = ['created_format','file_name'];

    public function getCreatedFormatAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * 获取用户的名字。
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute|string
     */
    protected function getFileNameAttribute()
    {
        return  basename($this->file);
    }



}
