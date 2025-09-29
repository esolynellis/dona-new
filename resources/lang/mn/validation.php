<?php

return [
/*
|--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| The following language lines contain the default error messages used by
| the validator class. Some of these rules have multiple versions such
| as the size rules. Feel free to tweak each of these messages here.
|
*/

'accepted'             => ':attribute зөвшөөрөгдсөн байх ёстой.',
'accepted_if'          => ':other нь :value үед :attribute зөвшөөрөгдсөн байх ёстой.',
'active_url'           => ':attribute хүчинтэй URL биш байна.',
'after'                => ':attribute нь :date-с хойшхи огноо байх ёстой.',
'after_or_equal'       => ':attribute нь :date-с хойш эсвэл тэнцүү огноо байх ёстой.',
'alpha'                => ':attribute нь зөвхөн үсэг агуулсан байх ёстой.',
'alpha_dash'           => ':attribute нь зөвхөн үсэг, тоо, зураас, доогуур зураас агуулсан байх ёстой.',
'alpha_num'            => ':attribute нь зөвхөн үсэг, тоо агуулсан байх ёстой.',
'array'                => ':attribute нь массив байх ёстой.',
'before'               => ':attribute нь :date-с өмнөх огноо байх ёстой.',
'before_or_equal'      => ':attribute нь :date-с өмнө эсвэл тэнцүү огноо байх ёстой.',
'between'              => [
    'numeric' => ':attribute нь :min-:max хооронд байх ёстой.',
    'file'    => ':attribute нь :min-:max килобайт хооронд байх ёстой.',
    'string'  => ':attribute нь :min-:max тэмдэгт хооронд байх ёстой.',
    'array'   => ':attribute нь :min-:max элементийг агуулсан байх ёстой.',
],
'boolean'              => ':attribute нь үнэн эсвэл худал байх ёстой.',
'confirmed'            => ':attribute баталгаажуулалт тохирохгүй байна.',
'current_password'     => 'Нууц үг буруу байна.',
'date'                 => ':attribute хүчинтэй огноо биш байна.',
'date_equals'          => ':attribute нь :date-тай тэнцүү огноо байх ёстой.',
'date_format'          => ':attribute нь :format форматтай тохирохгүй байна.',
'declined'             => ':attribute нь татгалзсан байх ёстой.',
'declined_if'          => ':other нь :value үед :attribute нь татгалзсан байх ёстой.',
'different'            => ':attribute болон :other нь өөр байх ёстой.',
'digits'               => ':attribute нь :digits оронтой байх ёстой.',
'digits_between'       => ':attribute нь :min-:max орон хооронд байх ёстой.',
'dimensions'           => ':attribute зурагны хэмжээ буруу байна.',
'distinct'             => ':attribute талбарт давхардсан утга байна.',
'email'                => ':attribute хүчинтэй имэйл хаяг байх ёстой.',
'ends_with'            => ':attribute нь дараах утгуудын аль нэгээр төгсөх ёстой: :values.',
'exists'               => 'Сонгогдсон :attribute хүчингүй байна.',
'file'                 => ':attribute нь файл байх ёстой.',
'filled'               => ':attribute талбарт утга оруулсан байх ёстой.',
'gt'                   => [
    'numeric' => ':attribute нь :value-с их байх ёстой.',
    'file'    => ':attribute нь :value килобайтас их байх ёстой.',
    'string'  => ':attribute нь :value тэмдэгтээс их байх ёстой.',
    'array'   => ':attribute нь :value элементээс ихийг агуулсан байх ёстой.',
],
'gte'                  => [
    'numeric' => ':attribute нь :value-с их эсвэл тэнцүү байх ёстой.',
    'file'    => ':attribute нь :value килобайтас их эсвэл тэнцүү байх ёстой.',
    'string'  => ':attribute нь :value тэмдэгтээс их эсвэл тэнцүү байх ёстой.',
    'array'   => ':attribute нь :value элемент эсвэл түүнээс дээш агуулсан байх ёстой.',
],
'image'                => ':attribute нь зураг байх ёстой.',
'in'                   => 'Сонгогдсон :attribute хүчингүй байна.',
'in_array'             => ':attribute талбар :other-д байхгүй байна.',
'integer'              => ':attribute нь бүхэл тоо байх ёстой.',
'ip'                   => ':attribute нь хүчинтэй IP хаяг байх ёстой.',
'ipv4'                 => ':attribute нь хүчинтэй IPv4 хаяг байх ёстой.',
'ipv6'                 => ':attribute нь хүчинтэй IPv6 хаяг байх ёстой.',
'json'                 => ':attribute нь хүчинтэй JSON тэмдэгт мөр байх ёстой.',
'lt'                   => [
    'numeric' => ':attribute нь :value-с бага байх ёстой.',
    'file'    => ':attribute нь :value килобайтас бага байх ёстой.',
    'string'  => ':attribute нь :value тэмдэгтээс бага байх ёстой.',
    'array'   => ':attribute нь :value элементээс бага агуулсан байх ёстой.',
],
'lte'                  => [
    'numeric' => ':attribute нь :value-с бага эсвэл тэнцүү байх ёстой.',
    'file'    => ':attribute нь :value килобайтас бага эсвэл тэнцүү байх ёстой.',
    'string'  => ':attribute нь :value тэмдэгтээс бага эсвэл тэнцүү байх ёстой.',
    'array'   => ':attribute нь :value элементээс илүүгүй байх ёстой.',
],
'max'                  => [
    'numeric' => ':attribute нь :max-с ихгүй байх ёстой.',
    'file'    => ':attribute нь :max килобайтас ихгүй байх ёстой.',
    'string'  => ':attribute нь :max тэмдэгтээс ихгүй байх ёстой.',
    'array'   => ':attribute нь :max элементээс илүүгүй байх ёстой.',
],
'mimes'                => ':attribute нь дараах төрлийн файл байх ёстой: :values.',
'mimetypes'            => ':attribute нь дараах төрлийн файл байх ёстой: :values.',
'min'                  => [
    'numeric' => ':attribute нь хамгийн багадаа :min байх ёстой.',
    'file'    => ':attribute нь хамгийн багадаа :min килобайт байх ёстой.',
    'string'  => ':attribute нь хамгийн багадаа :min тэмдэгт байх ёстой.',
    'array'   => ':attribute нь хамгийн багадаа :min элемент агуулсан байх ёстой.',
],
'multiple_of'          => ':attribute нь :value-ийн үржвэр байх ёстой.',
'not_in'               => 'Сонгогдсон :attribute хүчингүй байна.',
'not_regex'            => ':attribute формат хүчингүй байна.',
'numeric'              => ':attribute нь тоо байх ёстой.',
'password'             => 'Нууц үг буруу байна.',
'present'              => ':attribute талбар байх ёстой.',
'prohibited'           => ':attribute талбар хориглосон байна.',
'prohibited_if'        => ':other нь :value үед :attribute талбар хориглосон байна.',
'prohibited_unless'    => ':other нь :values-д ороогүй л бол :attribute талбар хориглосон байна.',
'prohibits'            => ':attribute талбар :other-г оролцуулахыг хориглоно.',
'regex'                => ':attribute формат хүчингүй байна.',
'required'             => ':attribute талбар заавал бөглөх шаардлагатай.',
'required_if'          => ':other нь :value үед :attribute талбар заавал бөглөх шаардлагатай.',
'required_unless'      => ':other нь :values-д ороогүй л бол :attribute талбар заавал бөглөх шаардлагатай.',
'required_with'        => ':values байгаа үед :attribute талбар заавал бөглөх шаардлагатай.',
'required_with_all'    => ':values бүгд байгаа үед :attribute талбар заавал бөглөх шаардлагатай.',
'required_without'     => ':values байхгүй үед :attribute талбар заавал бөглөх шаардлагатай.',
'required_without_all' => ':values бүгд байхгүй үед :attribute талбар заавал бөглөх шаардлагатай.',
'same'                 => ':attribute болон :other ижил байх ёстой.',
'size'                 => [
    'numeric' => ':attribute нь :size байх ёстой.',
    'file'    => ':attribute нь :size килобайт байх ёстой.',
    'string'  => ':attribute нь :size тэмдэгт байх ёстой.',
    'array'   => ':attribute нь :size элемент агуулсан байх ёстой.',
],
'starts_with'          => ':attribute нь дараах утгуудын аль нэгээр эхлэх ёстой: :values.',
'string'               => ':attribute нь тэмдэгт мөр байх ёстой.',
'timezone'             => ':attribute нь хүчинтэй цагийн бүс байх ёстой.',
'unique'               => ':attribute аль хэдийн ашиглагдсан байна.',
'uploaded'             => ':attribute байршуулахад алдаа гарлаа.',
'url'                  => ':attribute нь хүчинтэй URL байх ёстой.',
'uuid'                 => ':attribute нь хүчинтэй UUID байх ёстой.',

/*
|--------------------------------------------------------------------------
| Custom Validation Language Lines
|--------------------------------------------------------------------------
|
| Here you may specify custom validation messages for attributes using the
| convention "attribute.rule" to name the lines. This makes it quick to
| specify a specific custom language line for a given attribute rule.
|
*/

'custom'               => [
    'attribute-name' => [
        'rule-name' => 'custom-message',
    ],
],

/*
|--------------------------------------------------------------------------
| Custom Validation Attributes
|--------------------------------------------------------------------------
|
| The following language lines are used to swap our attribute placeholder
| with something more reader friendly such as "E-Mail Address" instead
| of "email". This simply helps us make our message more expressive.
|
*/

'attributes'           => [
    'descriptions.en.title'    => 'Гарчиг',
    'descriptions.zh_cn.title' => 'Гарчиг',

    'tax_rate'                 => [
        'name' => 'Татварын хувь',
        'type' => 'Татварын төрөл',
        'rate' => 'Татварын хувь хэмжээ',
    ],
],

];
