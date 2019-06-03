<?php

 use think\Route;

 //检测域名是否是api 如果是则访问api模块
 Route::domain('api','api');
 //用户登陆
 Route::post('user','user/login');
 //获取验证码
 Route::get('code/:time/:token/:username/:is_exist','code/get_code');