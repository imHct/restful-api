<?php
/**
 * Created by PhpStorm.
 * User: PHP
 * Date: 2019/6/14
 * Time: 16:55
 */

namespace app\api\controller;


class Article extends Common
{
    public function article_add()
    {
        //接收数据
        $data = $this->params;
        $data['article_ctime'] = time();
        //写入数据库
        $res = db('article')->insertGetId($data);
        if ($res){
            $this->return_msg(200,'新增文章成功！',$res);
        }else{
            $this->return_msg(400,'新增文章失败！');
        }
    }

    public function article_list()
    {
        //接收参数
        $data = $this->params;
        if (!isset($data['num'])){
            $data['num'] = 10;
        }
        if (!isset($data['page'])){
            $data['page'] = 1;
        }
        //查询数据库
        $where['article_uid'] = $data['user_id'];
        $where['article_isdel'] = '0';
        //总数
        $count = db('article')->where($where)->count();
        //页数
        $page_num = ceil($count/$data['num']);
        $field = "article_id,article_ctime,article_title,user_nickname";
        $join = [['api_user u','u.user_id = a.article_uid']];
        $res = db('article')->alias('a')->field($field)->join($join)->where($where)->page($data['page'],$data['num'])->select();
        //判断并输出
        if ($res === false){
            $this->return_msg(400,'查询失败');
        }elseif(empty($res)){
            $this->return_msg(200,'暂无数据');
        }else{
            $return_data['articles'] = $res;
            $return_data['page_num'] = $page_num;
            $this->return_msg(200,'查询成功',$return_data);
        }
    }

    public function article_detail()
    {
        //接收参数
        $data = $this->params;
        //查询数据库
        $field = "article_id,article_ctime,article_title,article_content,user_nickname";
        $where['article_id'] = $data['article_id'];
        $join = [['api_user u','u.user_id = a.article_uid']];
        $res = db('article')->alias('a')->field($field)->join($join)->where($where)->find();
        $res['article_content'] = htmlspecialchars_decode($res['article_content']);
        //判断结果并输出
        if (!$res){
            $this->return_msg(400,'查询失败');
        }else{
            $this->return_msg(200,'查询成功',$res);
        }
    }

    public function update_article()
    {
        //接收参数
        $data = $this->params;
        //写入数据库
        $res = db('article')->where('article_id',$data['article_id'])->update($data);
        if ($res){
            $this->return_msg(200,'文章修改成功！');
        }else{
            $this->return_msg(400,'文章修改失败！');
        }
    }

    public function del_article()
    {
        $data = $this->params;
        //逻辑删除
        $res = db('article')->where('article_id',$data['article_id'])->setField('article_isdel',1);
        //物理删除
        //$res = db('article')->delete($data['article_id']);
        if ($res){
            $this->return_msg(200,'删除文章成功！');
        }else{
            $this->return_msg(400,'删除文章失败！');
        }
    }
}