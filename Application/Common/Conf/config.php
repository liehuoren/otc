<?php
return array(
    //'配置项'=>'配置值'
    'TMPL_PARSE_STRING' => array(
        '__HOME__' => __ROOT__.'/Public' .'/Home',
        '__ADMIN__' => __ROOT__.'/Public' .'/Admin',
    ),

    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'rm-j6c5zlcc582dy40sbzo.mysql.rds.aliyuncs.com', // 服务器地址
//    'DB_HOST'               =>  '192.168.1.113', // 服务器地址
    'DB_NAME'               =>  'speetotc',          // 数据库名
    'DB_USER'               =>  'chuangkeroot',      // 用户名
    'DB_PWD'                =>  '$Chuangke542397',          // 密码
//    'DB_USER'               =>  'root',      // 用户名
//    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'trade_',    // 数据库表前缀
    

);