<?php
/**
 * Created by PhpStorm.
 * User: PHP
 * Date: 2019/6/3
 * Time: 14:50
 */

namespace app\api\controller;

use think\Request;
use think\response\Json;
use think\Validate;
use think\Controller;
use think\Image;

class Common extends Controller
{
    protected $request; //接收参数

    protected $validater; //验证数据/参数

    protected $params; //过滤后符合要求的参数

    protected $rules = array(
        'User' => array(
            'login'           => array(
                'user_name' => 'require',
                'user_pwd'  => 'require|length:32'
            ),
            'register'        => array(
                'user_name' => 'require',
                'user_pwd'  => 'require|length:32',
                'code'      => 'require|length:6|number'
            ),
            'upload_head_img' => array(
                'user_id|用户ID'   => 'require|number',
                'user_icon|用户头像' => 'require|image|fileSize:2000000|fileExt:jpg,jpeg,png,bmp',
            ),
            'change_pwd'      => array(
                'user_name|用户名'      => 'require',
                'user_ini_pwd|用户原密码' => 'require|length:32',
                'user_pwd|用户密码'      => 'require|length:32',
            ),
            'find_pwd'        => array(
                'user_name' => 'require',
                'user_pwd'  => 'require|length:32',
                'code'      => 'require|length:6|number'
            ),
            'bind_phone'      => array(
                'user_id|用户ID'   => 'require|number',
                'user_phone|手机号' => ['require', 'regex' => '/^1[34578]\d{9}$/'],
                'code'           => 'require|length:6|number'
            ),
            'bind_email'      => array(
                'user_id|用户ID'  => 'require|number',
                'user_email|邮箱' => 'require|email',
                'code'          => 'require|length:6|number'
            ),
            'bind_username'   => array(
                'user_id|用户ID'   => 'require|number',
                'user_name' => 'require',
                'code'           => 'require|length:6|number'
            ),
            'set_nickname'   => array(
                'user_id|用户ID'   => 'require|number',
                'user_nickname|用户名' => 'require|chsDash',
            ),
        ),
        'Code' => array(
            'get_code' => array(
                'username' => 'require',
                'is_exist' => 'require|number|length:1'
            ),
        ),
        'Article' => array(
            'article_add' => array(
                'article_uid' => 'require|number',
                'article_title' => 'require|chsDash'
            ),
            'article_list' => array(
                'user_id' => 'require|number',
                'num' => 'number',
                'page' => 'number'
            ),
            'article_detail' => array(
                'article_id' => 'require|number',
            ),
            'update_article' => array(
                'article_id|文章ID' => 'require|number',
                'article_title|文章标题' => 'chsDash'
            ),
            'del_article' => array(
                'article_id|文章ID' => 'require|number',
            ),
        )
    );

    protected function _initialize()
    {
        parent::_initialize();//继承父类的构造方法
        $this->request = Request::instance(); // TODO: Change the autogenerated stub
        //$this->check_time($this->request->only(['time']));
        //$this->check_token($this->request->param());
        $this->params = $this->check_params($this->request->param(true));
    }

    /**
     * @验证请求是否超时
     * [$arr] [包含时间戳的参数组]
     * [json] [检测结果]
     */
    public function check_time($arr)
    {
        if (!isset($arr['time']) || intval($arr['time'] <= 1)) {
            $this->return_msg(400, '时间戳不正确！');
        }
        if (time() - intval($arr['time']) > 60) {
            $this->return_msg(400, '请求时间超时！');
        }
    }

    /**
     * 验证token（防止数据篡改）
     * @param $arr [全部请求数据]
     * @retuen [json] [token验证结果]
     */
    public function check_token($arr)
    {
        //api传过来的token判断
        if (!isset($arr['token']) || empty($arr['token'])) {
            $this->return_msg(400, 'token不能为空');
        }
        //api传过来的token
        $app_token = $arr['token'];
        unset($arr['token']); //删除api传过来的token
        //服务端生成token
        $service_token = '';
        foreach ($arr as $key => $value) {
            $service_token .= md5($value);
        }
        //服务端生成即使token
        $service_token = md5('api_' . $service_token . '_api');
        //对比token,返回结果
        if ($app_token !== $service_token) {
            $this->return_msg(400, 'token不正确');
        }
    }

    /**
     * 验证参数 参数过滤
     * @param $arr [除time和token的所有参数]
     * @return mixed [过滤后的参数组]
     */
    public function check_params($arr)
    {
        //获取参数验证规则
        $rule = $this->rules[$this->request->controller()][$this->request->action()];
        //验证参数并返回错误
        $this->validater = new Validate($rule);
        if (!$this->validater->check($arr)) {
            $this->return_msg(400, $this->validater->getError());
        }
        return $arr;
    }

    /**
     * 检测用户名并返回用户名类别
     * @param $username [用户名可能是邮箱或者是手机]
     * @return string [检测结果]
     */
    public function check_username($username)
    {
        //判断是否为邮箱
        $is_email = Validate::is($username, 'email') ? 1 : 0;
        //判断是否为手机
        $is_phone = preg_match('/^1[34578]\d{9}$/', $username) ? 4 : 2;
        //最终结果
        $flag = $is_email + $is_phone;
        switch ($flag) {
            //not email not phone
            case 2:
                $this->return_msg(400, '邮箱或手机号不正确');
                break;
            //is email not phone
            case 3:
                return 'email';
                break;
            //is phone not email
            case 4:
                return 'phone';
                break;
        }
    }

    /**
     * @param $value [传递过来的账号]
     * @param $type [邮箱或手机]
     * @param $exist [是否纯在]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return Json [返回接口的json数据]
     */
    public function check_exist($value, $type, $exist)
    {
        $type_num  = $type == 'phone' ? 2 : 4;
        $flag      = $type_num + $exist;
        $phone_res = db('user')->where('user_phone', $value)->find();
        $email_res = db('user')->where('user_email', $value)->find();
        switch ($flag) {
            case 2:
                if ($phone_res) {
                    $this->return_msg(400, '此手机号已被占用');
                }
                break;
            case 3:
                if (!$phone_res) {
                    $this->return_msg(400, '此手机号码不存在');
                }
                break;
            case 4:
                if ($email_res) {
                    $this->return_msg(400, '此邮箱已被占用');
                }
                break;
            case 5:
                if (!$email_res) {
                    $this->return_msg(400, '此邮箱不存在');
                }
                break;
        }
    }

    /**
     * @param $user_name [用户名]
     * @param $code [验证码]
     * return json [api返回的json数据]
     */
    public function check_code($user_name, $code)
    {
        //检测是否超时
        $last_time = session($user_name . '_last_send_time');
        if (time() - $last_time > 600) {
            $this->return_msg(400, '验证超时，请在一分钟内验证');
        }
        //检测验证码是否正确
        $md5_code = md5($user_name . '_' . md5($code));
        if (session($user_name . '_code') !== $md5_code) {
            $this->return_msg(400, '验证码不正确');
        }
        //不管正确与否，每个验证码只验证一次
        session($user_name . '_code', null);
    }

    public function upload_file($file, $type = '')
    {
        //把上传文件移动到根目录的uploads下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $path = '/uploads/' . $info->getSaveName();
            //裁剪图片
            if (!empty($type)) {
                $this->image_edit($path, $type);
            }
            return str_replace('\\', '/', $path);
        } else {
            $this->return_msg(400, $file->getError());
        }
    }

    public function image_edit($path, $type)
    {
        //获取到图片
        $image = Image::open(ROOT_PATH . 'public' . $path);
        switch ($type) {
            case 'head_img':
                $image->thumb(200, 200, Image::THUMB_CENTER)->save(ROOT_PATH . 'public' . $path);
                break;
        }
    }

    /**
     * api 返回的数据
     * @param $code [结果码 200：正常/400：数据问题/500：服务器问题]
     * @param string $msg [接口返回的提示信息]
     * @param array $data [接口返回数据]
     * @return [最终的json数据]
     */
    public function return_msg($code, $msg = '', $data = [])
    {
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        echo json_encode($return_data);
        die;
    }
}