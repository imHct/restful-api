<?php
/**
 * Created by PhpStorm.
 * User: PHP
 * Date: 2019/6/3
 * Time: 17:49
 */

namespace app\api\controller;

use phpmailer\PHPMailer;

class Code extends Common
{
    public function get_code()
    {
        $username      = $this->params['username'];
        $exist         = $this->params['is_exist'];
        $username_type = $this->check_username($username);
        switch ($username_type) {
            case 'phone':
                $this->get_code_by_username($username, 'phone', $exist);
                break;
            case 'email':
                $this->get_code_by_username($username, 'email', $exist);
                break;
        }
    }

    /**
     * 通过手机/邮箱获取验证码
     * @param $username [用户名]
     * @param $type [手机号或邮箱]
     * @param $exist [手机号/邮箱是否应该存在于数据库中 1：是 0：否]
     * return [json] [api返回的json数据]
     */
    public function get_code_by_username($username, $type, $exist)
    {
        if ($type == 'phone') {
            $type_name = '手机';
        } else {
            $type_name = '邮箱';
        }
        //判断手机号码是否存在
        $this->check_exist($username, $type, $exist);
        //检查验证码请求频率 30秒一次 ？判断session是否有值 有true 没有false
        if (session('?' . $username . '_last_send_time')) {
            if (time() - session($username . '_last_send_time') < 30) {
                $this->return_msg(400, $type_name . '验证码，每30秒只能发送一次');
            }
        }
        //生成验证码
        $code = $this->make_code(6);
        //使用session存储验证码，方便比对，MD5加密
        $md5_code = md5($username . '_' . md5($code));
        session($username . '_code', $md5_code);
        //使用session储存验证码发送的时间
        session($username.'_last_send_time', time());
        //发送验证码
        if ($type == 'phone') {
            $this->send_code_to_phone($username, $code);
        } else {
            $this->send_code_to_email($username, $code);
        }
    }

    /**
     * 生成验证码
     * @param $num [验证码的位数]
     * @return int [生成的验证码]
     */
    public function make_code($num)
    {
        $max = pow(10, $num) - 1;
        $min = pow(10, $num - 1);
        return rand($min, $max);
    }

    /**
     * 手机发送验证码[SDK]
     * @param $phone [手机号码]
     * @param $code  [验证码]
     * return [返回成功或错误的信息]
     */
    public function send_code_to_phone($phone, $code)
    {
        import('dysms.api_demo.SmsDemo', EXTEND_PATH);
        $send = new \SmsDemo();
        $info =  $send->sendSms($phone, $code);
        if ($info->Message != 'OK'){
            $this->return_msg(400,$info->Message);
        }else{
            $this->return_msg(200,'手机验证码已发送，请在一分钟内验证！');
        }
    }

    /**
     * 向邮箱发送验证码
     * @param $email [目标邮箱]
     * @param $code [生成的验证码]
     * @throws \phpmailer\phpmailerException
     * @return [json] [返回的json数据]
     */
    public function send_code_to_email($email, $code)
    {
        $toemail = $email;
        $mail    = new PHPMailer();
        //$mail -> CharSet = 'utf-8';		//设置发送内容的编码
        //$mail -> Host = 'smtp.qq.com';	//告诉我们的服务器使用163的smtp服务器发送
        //$mail -> SMTPAuth = true;		//开启SMTP授权
        //$mail -> Username = '1143698104@qq.com';	//登录到邮箱的用户名
        //$mail -> Password = 'nwaaxrcoswjyigaa';	//第三方登录的授权码，在邮箱里面设置
        //$mail -> IsSMTP();			//告诉服务器使用smtp协议发送
        ////$mail -> STMPSecure= 'ssl';
        //$mail -> prot= 995;
        //$mail -> SetFrom('1143698104@qq.com','接口测试');
        //$mail -> IsHTML(true);		    //发送的内容使用html编写
        //$mail -> Subject = '您有新的验证码！';//设置邮件的标题
        //$mail -> AddAddress($toemail,'test');    //收人的邮件地址
        //$mail -> MsgHTML("这是一个测试邮件，您的验证码是：$code ，验证码的有效期为1分钟，本邮件请勿回复");	//发送的邮件内容主体
        // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
        $mail->SMTPDebug = 1;
        // 使用smtp鉴权方式发送邮件
        $mail->isSMTP();
        // smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = true;
        // 链接qq域名
        $mail->Host = 'smtp.qq.com';
        // 设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = 'ssl';
        // 设置ssl连接smtp服务器的远程服务器端口号
        $mail->Port = 465;
        // 设置发送的邮件的编码
        $mail->CharSet = 'UTF-8';
        // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = 'api测试';
        // smtp登录的账号 QQ邮箱即可
        $mail->Username = '1143698104@qq.com';
        // smtp登录的密码 使用生成的授权码
        $mail->Password = 'nwaaxrcoswjyigaa';
        // 设置发件人邮箱地址 同登录账号
        $mail->From = '1143698104@qq.com';
        // 邮件正文是否为html编码 注意此处是一个方法
        $mail->isHTML(true);
        // 设置收件人邮箱地址
        $mail->addAddress($toemail);
        // 添加该邮件的主题
        $mail->Subject = '您有新的验证码！';
        $mail->Body    = "这是一个测试邮件，您的验证码是：$code ，验证码的有效期为1分钟，本邮件请勿回复";
        $result        = $mail->Send();
        if ($result) {
            $this->return_msg(200, '验证码已经发送成功，请注意查收！');
        } else {
            $this->return_msg(400, $mail->ErrorInfo);
        }
    }

}