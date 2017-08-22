<?php

	namespace kura-client;

    define('CONF', TRUE);

    /*
     * 库拉客户端配置
     */

    $conf = array();
    
    //SOA地址
    $conf['SOA']     = 'xxx';
    //SOA密钥
    $conf['SOAKEY']  = 'xxx';
    //项目名称，例如：可来
    $conf['PRONAME'] = 'xxx';
    //项目地址，例如：http://kline.123.com.cn/
    $conf['PROURL']  = 'xxx';
    //开发环境,0：测试，1：生产
    $conf['ONLINE']  = 0;
    //默认实例ID
    $conf['EXAMPLE'] = 0;
    //平台 web/wap
    $conf['CLIENT']  = 'web';
    //开发者账号
    $conf['UNAME']   = 'xxx';
    //开发者密码
    $conf['PWORD']   = 'xxx';
    
    return $conf;