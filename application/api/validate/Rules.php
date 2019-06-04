<?php
/**
 * Created by PhpStorm.
 * User: PHP
 * Date: 2019/6/4
 * Time: 9:23
 */

namespace app\api\validate;

use think\Validate;

class Rules extends Validate
{
    protected $rule = [
        'user_name' => 'require|chsDash|max:20',
        'user_pwd'  => 'require|length:32',
    ];

    protected $message = [
        'user_name.require' => '请填写用户名',
        'user_name.chsDash' => '用户名只能是汉字、字母、数字和下划线_及破折号-',
        'user_name.max'     => '用户名最大长度20位',
        'user_pwd.require'  => '请填写用户密码',
        'user_pwd.length'   => '请填写32位密码',
    ];
}