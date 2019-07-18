<?php
//配置文件
return [
    //OM 微信公众号
    'appid'=>'wx14dbecb0e9923aec',
    'appsecret' =>'974aec19d40a10f31c2e9b116d8bf99d',
    'default_return_type' => 'json',

    //静态路径
    '__static__' =>'public/static/',
    // redis缓存
        'redis'   =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'       => '127.0.0.1',
        ],
];