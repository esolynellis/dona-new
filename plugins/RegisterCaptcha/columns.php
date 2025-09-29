<?php

/**
 * @author    村长<178277164@qq.com>
 */

return [
/**
    [
        'name'        => 'email_driver',
        'label'       => '邮箱引擎',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'value'       => 'SMTP',
        'placeholder' => '邮箱引擎',
    ],
    [
        'name'        => 'email_host',
        'label'       => '邮箱主机',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'placeholder' => '邮件引擎',
    ],
    [
        'name'        => 'email_port',
        'label'       => '邮箱端口',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'value'       => '465',
        'placeholder' => '邮箱端口',
    ],
    [
        'name'        => 'email_username',
        'label'       => '邮箱用户',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'placeholder' => '邮箱用户',
    ],
    [
        'name'        => 'email_password',
        'label'       => '邮箱密码',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'placeholder' => '邮箱密码',
    ],
    [
        'name'        => 'email_from_name',
        'label'       => '发送者名称',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'placeholder' => '发送者名称，邮箱会显示该发送者名字，如：Beikeshop',
    ],
    [
        'name'        => 'email_encryption',
        'label'       => '邮箱加密方式',
        'type'        => 'string',
        'required'    => true,
        'rules'       => 'required',
        'value'       => 'TLS',
        'placeholder' => '邮箱加密方式',
    ],**/

    [
        'name'        => 'captcha_type',
        'label'       => '图形验证码类型',
        'type'        => 'select',
        'options'     => [
            [
                'value' => '0',
                'label' => '关闭'
            ],
            [
                'value' => '1',
                'label' => '极验'
            ],
            [
                'value' => '2',
                'label' => '腾讯'
            ],
        ],
        'required'    => true,
        'description' => '开启后有效防止机器人注册',
    ],
    [
        'name'        => 'captcha_id',
        'label'       => 'Captcha ID',
        'type'        => 'string',
        'required'    => false,
        'placeholder' => '图形验证码平台的Captcha ID',
    ],
    [
        'name'        => 'captcha_key',
        'label'       => 'Captcha Key',
        'type'        => 'string',
        'required'    => false,
        'placeholder' => '图形验证码平台的Captcha Key',
    ],
    [
        'name'        => 'tencent_secret_id',
        'label'       => '腾讯云 Secret ID',
        'type'        => 'string',
        'required'    => false,
        'placeholder' => '腾讯云 SecretId ID',

    ],
    [
        'name'        => 'tencent_secret_key',
        'label'       => '腾讯云 Secret Key',
        'type'        => 'string',
        'required'    => false,
        'placeholder' => '腾讯云 SecretId Key',
    ],


];

