<?php
/**
 * Created by PhpStorm.
 * User: PHP
 * Date: 2019/6/3
 * Time: 10:17
 */

namespace app\api\controller;

class User extends Common
{
    /**
     * 用户登陆
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * return [json] [api返回的json数据]
     */
    public function login()
    {
        //接受参数
        $data = $this->params;
        //检查用户名
        $user_name_type = $this->check_username($data['user_name']);
        switch ($user_name_type) {
            case 'phone':
                $this->check_exist($data['user_name'], 'phone', 1);
                $db_res = db('user')->where('user_phone', $data['user_name'])->find();
                break;
            case 'email':
                $this->check_exist($data['user_name'], 'email', 1);
                $db_res = db('user')->where('user_phone', $data['user_name'])->find();
                break;
        }
        if ($db_res['user_pwd'] !== $data['user_pwd']) {
            $this->return_msg(400, '用户名或密码不正确');
        } else {
            unset($db_res['user_pwd']); //密码永不返回
            $this->return_msg(200, '登陆成功', $db_res);
        }

    }

    /**
     * 用户注册
     * @return [json] [api接口返回的json数据]
     */
    public function register()
    {
        //接受参数
        $data = $this->params;
        //检查验证码
        $this->check_code($data['user_name'], $data['code']);
        //检测用户名
        $user_name_type = $this->check_username($data['user_name']);
        switch ($user_name_type) {
            case 'phone':
                $this->check_exist($data['user_name'], 'phone', 0);
                $data['user_phone'] = $data['user_name'];
                break;
            case 'email':
                $this->check_exist($data['user_name'], 'email', 0);
                $data['user_email'] = $data['user_name'];
                break;
        }
        //将用户信息写入数据库
        unset($data['user_name']);
        $data['user_rtime'] = time();
        $res                = db('user')->insert($data);
        if ($res) {
            $this->return_msg(200, '用户注册成功');
        } else {
            $this->return_msg(400, '用户注册失败');
        }
    }

    /**
     * 用户上传头像
     * return [json] [api返回的接口数据]
     */
    public function upload_head_img()
    {
        //接受参数
        $data = $this->params;
        //获取图片上传路径
        $head_img_path = $this->upload_file($data['user_icon'], 'head_img');
        //存入数据库
        $res = db('user')->where('user_id', $data['user_id'])->setField('user_icon', $head_img_path);
        if ($res) {
            $this->return_msg(200, '头像上传成功', $head_img_path);
        } else {
            $this->return_msg(400, '头像上传失败');
        }
    }

    /**
     * 用户修改密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * return [json] [api返回的接口数据]
     */
    public function change_pwd()
    {
        //接受参数
        $data = $this->params;
        //检查用户名并并取出数据库中的密码
        $user_name_type = $this->check_username($data['user_name']);
        switch ($user_name_type) {
            case 'phone':
                $this->check_exist($data['user_name'], 'phone', 1);
                $where['user_phone'] = $data['user_name'];
                break;
            case 'email':
                $this->check_exist($data['user_name'], 'email', 1);
                $where['user_email'] = $data['user_name'];
                break;
        }
        //判断原始密码是否正确
        $db_ini_pwd = db('user')->where($where)->value('user_pwd');
        if ($db_ini_pwd !== $data['user_ini_pwd']) {
            $this->return_msg(400, '原始密码错误');
        }
        //把新的密码写入数据库
        $res = db('user')->where($where)->setField('user_pwd', $data['user_pwd']);
        if ($res !== false) {
            $this->return_msg(200, '修改密码成功');
        } else {
            $this->return_msg(400, '修改密码失败');
        }
    }

    /**
     * 找回密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * return [json] [api返回的接口数据]
     */
    public function find_pwd()
    {
        //接受数据
        $data = $this->params;
        //检测验证码是否正确
        $this->check_code($data['user_name'], $data['code']);
        //检测用户名
        $user_name_type = $this->check_username($data['user_name']);
        switch ($user_name_type) {
            case 'phone':
                $this->check_exist($data['user_name'], 'phone', 1);
                $where['user_phone'] = $data['user_name'];
                break;
            case 'email':
                $this->check_exist($data['user_name'], 'email', 1);
                $where['user_email'] = $data['user_name'];
                break;
        }
        //修改数据密码
        $res = db('user')->where($where)->setField('user_pwd',$data['user_pwd']);
        if ($res !== false) {
            $this->return_msg(200, '修改密码成功！');
        } else {
            $this->return_msg(200, '修改密码失败！');
        }
    }

    /**
     * 绑定绑定手机号
     * return [json] [api返回的接口数据]
     */
    public function bind_phone()
    {
        //接收参数
        $data = $this->params;
        //检查验证码
        $this->check_code($data['user_phone'],$data['code']);
        //修改数据库
        $res = db('user')->where('user_id',$data['user_id'])->setField('user_phone',$data['user_phone']);
        if ($res !== false){
            $this->return_msg(200,'手机号绑定成功！');
        }else{
            $this->return_msg(400,'手机号绑定失败！');
        }
    }

    /**
     * 绑定绑定邮箱
     * return [json] [api返回的接口数据]
     */
    public function bind_email()
    {
        //接收参数
        $data = $this->params;
        //检查验证码
        $this->check_code($data['user_email'],$data['code']);
        //修改数据库
        $res = db('user')->where('user_id',$data['user_id'])->setField('user_email',$data['user_email']);
        if ($res !== false){
            $this->return_msg(200,'邮箱绑定成功！');
        }else{
            $this->return_msg(400,'邮箱绑定失败！');
        }
    }

    /**
     * 绑定绑定手机号或邮箱
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * return [json] [api返回的接口数据]
     */
    public function bind_username()
    {
        //接收参数
        $data = $this->params;
        //检查验证码
        $this->check_code($data['user_name'],$data['code']);
        //判断用户名
        $user_name_type = $this->check_username($data['user_name']);
        switch ($user_name_type){
            case 'phone':
                $type_text = '手机号';
                $update_data['user_phone'] = $data['user_name'];
                break;
            case 'email':
                $type_text = '邮箱';
                $update_data['user_email'] = $data['user_name'];
                break;
        }
        $res = db('user')->where('user_id',$data['user_id'])->update($update_data);
        if ($res !== false){
            $this->return_msg(200,$type_text.'绑定成功！');
        }else{
            $this->return_msg(400,$type_text.'绑定失败！');
        }
    }

    /**
     * 用户修改昵称
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * return [json] [api返回的接口数据]
     */
    public function set_nickname()
    {
        //接收参数
        $data = $this->params;
        //检测昵称是否存在
        $res = db('user')->where('user_nickname',$data['user_nickname'])->find();
        if ($res){
            $this->return_msg(400,'该昵称已被占用');
        }
        $res = db('user')->where('user_id',$data['user_id'])->setField('user_nickname',$data['user_nickname']);
        if (!$res){
            $this->return_msg(400,'修改昵称失败！');
        }else{
            $this->return_msg(400,'修改昵称成功！');
        }
    }

}