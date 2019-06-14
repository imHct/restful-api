<?php

 use think\Route;

 //检测域名是否是api 如果是则访问api模块
 Route::domain('api','api');
 //用户登陆
 Route::post('user','user/login');
 //获取验证码
 Route::get('code/:time/:token/:username/:is_exist','code/get_code');
 //用户注册
 Route::post('user/register','user/register');
 //用户登陆
 Route::post('user/login','user/login');
 //用户上传头像
 Route::post('user/icon','user/upload_head_img');
 //用户修改密码
 Route::post('user/change_pwd','user/change_pwd');
 //用户找回密码
 Route::post('user/find_pwd','user/find_pwd');
 //用户绑定手机号
 //Route::post('user/bind_phone','user/bind_phone');
 //用户绑定邮箱
 //Route::post('user/bind_email','user/bind_email');
 //用户绑定邮箱或手机号
 Route::post('user/bind_username','user/bind_username');
 //用户绑定邮箱或手机号
 Route::post('user/nickname','user/set_nickname');
 //新增文章
 Route::post('Article/article_add','Article/article_add');
 //文章列表
 Route::get('articles/:time/:token/:user_id/[:num]/[:page]','article/article_list');
 //获取单个文章信息
 Route::get('article/:time/:token/:article_id','article/article_detail');
 //修改/保存文章
 Route::put('article','article/update_article');
 //修改/保存文章
 Route::delete('article/:time/:token/:article_id','article/del_article');