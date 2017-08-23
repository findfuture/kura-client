<?php

    /*
    * -----------------------------------------------
    * 云掌财经SOA微服务框架SERVICE客户端SDK
    * -----------------------------------------------
    * 版本：1.0.1
    * 开发人员：苏睿 / surui@123.com.cn
    * 最后更新日期：2017/06/21
    * -----------------------------------------------
    * SVN:
    * GIT:
    * -----------------------------------------------
    */

	namespace Kura;

    class kuraClient{
        
        //配置信息
        private $_config;
        //全局密钥
        private $_secret;
        //SOA配置
        private $_hash;
        //HEADER头信息
        private $_header;
        //调试信息
        private $_debug;
        //debug的KEY
        private $_debugKey;
        //SOA地址
        private $_soa;
        //自定义实例ID，用于调用非默认实例以外的其他实例
        private $_customEid;
        //自定义实例配置信息
        private $_custom;
        
        public function __construct()
        {
            $start = microtime(TRUE);
            date_default_timezone_set('PRC');
            //载入配置信息
            $this->_config = require 'conf.php';
            //校验配置信息
            if ( ! isset($this->_config['PRONAME']) || $this->_config['PRONAME'] == '')
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '项目名称未配置，请检查'
                ));
            }
            if ( ! isset($this->_config['PROURL']) || $this->_config['PROURL'] == '')
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '项目URL地址未配置，请检查'
                ));
            }
            if ( ! isset($this->_config['ONLINE']))
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '开发环境未配置，请检查'
                ));
            }
            if ( ! isset($this->_config['EXAMPLE']) || $this->_config['EXAMPLE'] < 1)
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '默认实例配置不正确，请检查'
                ));
            }
            if ( ! isset($this->_config['CLIENT']))
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '项目平台未配置，请检查'
                ));
            }
            if ( ! isset($this->_config['UNAME']) || $this->_config['UNAME'] == '')
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '开发者账号未配置，请检查'
                ));
            }
            if ( ! isset($this->_config['PWORD']) || $this->_config['PWORD'] == '')
            {
                $this->_error(array(
                    'code' => 500,
                    'msg'  => '开发者密码未配置，请检查'
                ));
            }
            $this->_soa = $this->_config['SOA'];
            //全局密钥
            $this->_secret = $this->_config['SOAKEY'];
            if ( ! $this->_checkService())
            {
                $this->_synchService();
            }
            $hash = require $this->_hash;
            $this->_config    = array_merge($this->_config, $hash);
            $auth             = require 'init/'.md5('auth').'.php';
            $this->_config    = array_merge($this->_config, $auth);
            $this->_header    = array();
            $this->_customEid = 0;
            $this->_custom    = array();
            $end = microtime(TRUE);
            $this->_debug['_time'] = round($end - $start, 5);
            $this->_debug['_allTime'] = 0;
        }
        
        //调用服务,GET方式
        public function get($url, $get = array(), $eid = 0)
        {
            $start = microtime(TRUE);
            $param = array();
            //拼接GET请求参数
            if (is_array($get) && ! empty($get))
            {
                $url .= $this->_createGetParam($get);
            }
            $param['url'] = $url;
            //实例ID
            $eid = (is_int($get)) ? $get : $eid;
            if ($eid > 0 && $eid != $this->_config['EXAMPLE'])
            {
                $this->_customEid = $eid;
            }
            else
            {
                $this->_customEid = 0;
            }
            $data = $this->_http($param);
            $return = array();
            $return['code'] = $data['result']['code'];
            $return['msg']  = $data['result']['msg'];
            if (isset($data['data']))
            $return['data'] = $data['data'];
            $end = microtime(TRUE);
            if (isset($this->_debug['_time']))
            {
                $this->_debug[$this->_debugKey]['_time'] = $this->_debug['_time'] + round($end - $start, 5);
                $this->_debug['_allTime'] +=  $this->_debug[$this->_debugKey]['_time'];
            }
            return $return;
        }
        
        //调用服务,POST方式
        public function post($url, $data, $eid = 0)
        {
            $start = microtime(TRUE);
            $param = array();
            $param['url']  = $url;
            $param['data'] = $data;
            if ($eid > 0 && $eid != $this->_config['EXAMPLE'])
            {
                $this->_customEid = $eid;
            }
            else
            {
                $this->_customEid = 0;
            }
            $data = $this->_http($param);
            $return = array();
            $return['code'] = $data['result']['code'];
            $return['msg']  = $data['result']['msg'];
            if (isset($data['data']))
            $return['data'] = $data['data']['list'];
            $end = microtime(TRUE);
            if (isset($this->_debug['_time']))
            {
                $this->_debug[$this->_debugKey]['_time'] = $this->_debug['_time'] + round($end - $start, 5);
                $this->_debug['_allTime'] +=  $this->_debug[$this->_debugKey]['_time'];
            }
            return $return;
        }
        
        //调试
        public function debug($noHTML = FALSE)
        {
            if ($noHTML)
            {
                unset($this->_debug['_time']);
                unset($this->_debug['_allTime']);
                $tmp = array();
                foreach ($this->_debug as $row)
                {
                    $tmp[] = json_decode($row['_return'], TRUE);
                }
                print_r($tmp);
                return TRUE;
            }
            unset($this->_debug['_time']);
            $allTime = $this->_debug['_allTime'];
            unset($this->_debug['_allTime']);
            //调试信息HTML代码
            $html  = '<link rel="stylesheet" type="text/css" href="'.$this->_soa.'skin/common/css/debug.css"/>';
            $html .= '<link rel="stylesheet" type="text/css" href="'.$this->_soa.'skin/common/css/jquery.jsonview.css"/>';
            $html .= '<script src="'.$this->_soa.'skin/common/js/jquery-1.7.2.min.js"></script>';
            $html .= '<script src="'.$this->_soa.'skin/common/js/jquery.jsonview.js"></script>';
            $html .= '<script>$(function(){';
            $html .= 'var _html = \'\';';
            $html .= '_html += \'<div id="kura-debug" style="max-height:\'+(window.innerHeight - 100)+\'px;">\';';
            $html .= '_html += \'<h3>KURA调试面板 - 共'.count($this->_debug).'个请求，总耗时：'.$allTime.'</h3>\';';
            foreach ($this->_debug as $row)
            {
                $html .= '_html += \'<div class="kura-debug-block">\';';
                $html .= '_html += \'<p><b>请求地址：</b>'.$row['_url'].'</p>\';';
                $html .= '_html += \'<p><b>请求方式：</b>'.$row['_method'].'</p>\';';
                $html .= '_html += \'<p><b>请求耗时：</b>'.$row['_time'].'</p>\';';
                $html .= '_html += \'<p><b>HEADER头数据</b> - <a href="javascript:;" class="kura-debug-close">展开+</a></p>\';';
                $html .= '_html += \'<div class="kura-debug-header">\';';
                foreach ($row['_header'] as $val)
                {
                    $val = str_replace(':', '：<span>', $val);
                    $html .= '_html += \'<p class="kura-debug-sub">|- '.$val.'</span><p>\';';
                }
                $html .= '_html += \'</div>\';';
                if (isset($row['_post']))
                {
                    $html .= '_html += \'<p><b>发送参数</b> - <a href="javascript:;" class="kura-debug-close">展开+</a></p>\';';
                    $html .= '_html += \'<div class="kura-debug-header">\';';
                    foreach ($row['_post'] as $key => $val)
                    $html .= '_html += \'<p class="kura-debug-sub">|- '.$key.'：<span>'.$val.'</span><p>\';';
                    $html .= '_html += \'</div>\';';
                }
                $html .= '_html += \'<p><b>返回数据</b> - <a href="javascript:;" class="kura-debug-close">展开+</a></p>\';';
                $return = $row['_return'];
                if ( ! is_numeric($return) &&
                    ! is_null(json_decode($return)))
                {
                    $return = str_replace('\r\n', '', $return);
                    $doc = 'json';
                }
                else
                {
                    $return = str_replace("\r\n", '', $return);
                    $return = str_replace("\n", '', $return);
                    $return = addslashes($return);
                    $doc = 'html';
                }
                $html .= '_html += \'<div class="kura-debug-result" data-doc="'.$doc.'">'.$return.'</div>\';';
                $html .= '_html += \'</div>\';';
            }
            $html .= '_html += \'</div>\';';
            $html .= '$("body").append(_html);';
            $html .= '$(".kura-debug-close").click(function(){';
            $html .= 'var _result = $(this).parent().next();';
            $html .= '_result.toggle();';
            $html .= '$(this).html(_result.is(":hidden") ? "展开+" : "关闭-");';
            $html .= '});';
            $html .= '$(".kura-debug-result").each(function(){';
            $html .= 'if ($(this).attr("data-doc") == \'json\'){';
            $html .= '$(this).JSONView($(this).html());}';
            $html .= '});';
            $html .= '});</script>';
            echo $html;        
        }
        
        //设置HEADER头信息
        public function header($header = array())
        {
            if (empty($header))
            {
                return TRUE;
            }
            foreach ($header as $key => $val)
            {
                $this->_header[] = strtoupper($key).':'.$val;
            }
        }
        
        //输出错误信息
        private function _error($return = '')
        {
            exit(json_encode($return, JSON_UNESCAPED_UNICODE));
        }
        
        //组装HEADER头信息
        private function _createHeader()
        {
            if ($this->_customEid > 0)
            {
                $appid  = $this->_custom[$this->_customEid]['APPID'][$this->_config['CLIENT']];
                $secret = $this->_custom[$this->_customEid]['SECRET'][$this->_config['CLIENT']];
            }
            else
            {
                $appid  = $this->_config['APPID'][$this->_config['CLIENT']];
                $secret = $this->_config['SECRET'][$this->_config['CLIENT']];
            }
            $nonce    = rand(100000, 999999);
            $curtime  = date('YmdHis', time());
            $header   = array();
            $header[] = 'CLIENT:'.$this->_config['CLIENT'];
            $header[] = 'APPID:'.$appid;
            $header[] = 'NONCE:'.$nonce;
            $header[] = 'CURTIME:'.$curtime;
            $header[] = 'OPENKEY:'.MD5($appid.$nonce.$curtime.$secret);
            $header[] = 'UNAME:'.$this->_config['UNAME'];
            $header[] = 'TOKEN:'.$this->_config['TOKEN'];
            return $header;
        }
        
        //验证SERVICE端配置信息是否存在
        private function _checkService()
        {
            $this->_hash = dirname(__FILE__).'/init/'.md5(($this->_customEid > 0 ? $this->_customEid : $this->_config['EXAMPLE']).$this->_config['ONLINE']).'.php';
            return is_file($this->_hash);
        }
        
        //拉取服务配置
        private function _synchService()
        {
            $url  = $this->_soa.'soa/system/synchAppid.html';
            $url .= '?token='.$this->_secret;
            $url .= '&id='.(($this->_customEid > 0) ? $this->_customEid : $this->_config['EXAMPLE']);
            $url .= '&online='.$this->_config['ONLINE'];
            $url .= '&uname='.$this->_config['UNAME'];
            $url .= '&pword='.$this->_config['PWORD'];
            $url .= '&proname='.$this->_config['PRONAME'];
            $url .= '&prourl='.urlencode($this->_config['PROURL']);
            $url .= '&client='.$this->_config['CLIENT'];
            $data = $this->_http(array(
                'url' => $url
            ), TRUE);
            if (is_null($data))
            {
                $this->_error(array(
                    'code' => 404,
                    'msg'  => 'SOA连接失败，请联系管理人员'
                ));
            }
            if ($data['code'] != 100)
            {
                $this->_error($data);
            }
            $data   = $data['msg'];
            $appid  = json_decode($data['appid'], TRUE);
            $secret = json_decode($data['secret'], TRUE);
            $init   = array();
            $init['APPID']  = $appid;
            $init['SECRET'] = $secret;
            $init['URL']    = $data['url'];
            $val = '<?PHP return '.var_export($init, TRUE).';';
            file_put_contents($this->_hash, $val);
            $auth = dirname(__FILE__).'/init/'.md5('auth').'.php';
            if ( ! is_file($auth))
            {
                $val = '<?PHP return '.var_export(array('TOKEN' => $data['token']), TRUE).';';
                file_put_contents($auth, $val);
            }
        }
        
        //组合GET请求的URL参数
        private function _createGetParam($param = array())
        {
            $url = '?';
            $doc = '';
            foreach ($param as $key => $val)
            {
                $url .= $doc.$key.'='.$val;
                $doc  = '&';
            }
            return $url;
        }
        
        //发送HTTP请求
        private function _http($param = array(), $soa = FALSE)
        {
            if ( ! isset($param['url']))
            {
                return FALSE;
            }
            //自定义实例
            if ($this->_customEid > 0 && ! $soa)
            {
                if ( ! $this->_checkService())
                {
                    $this->_synchService();
                }
                $this->_custom[$this->_customEid] = require $this->_hash;
            }
            //地址
            if ($soa)
            {
                $url = '';
            }
            else
            {
                $url = (isset($this->_config['URL'])) ? $this->_config['URL'] : '';
            }
            $url .= $param['url'];
            $this->_debugKey = md5($url);
            //发送的数据
            $data   = ( ! isset($param['data'])) ? array() : $param['data'];
            //请求模式
            $method = ( ! empty($data)) ? 'POST' : 'GET';
            if ( ! $soa)
            {
                //HEADER头
                $header = $this->_createHeader();
                if ( ! empty($this->_header))
                $header = array_merge($header, $this->_header);
                //保存调试信息
                $this->_debug[$this->_debugKey]['_url']    = $url;
                $this->_debug[$this->_debugKey]['_header'] = $header;
                if ( ! empty($data))
                $this->_debug[$this->_debugKey]['_post']   = $data;
                $this->_debug[$this->_debugKey]['_method'] = $method;
            }
            else
            {
                $header = array();
            }
            //超时时间
            $time = ( ! isset($param['time'])) ? 3 : $param['time'];
            //返回类型：html/json
            $type = ( ! isset($param['type'])) ? 'json' : $param['type'];
            $opts = array(
                CURLOPT_TIMEOUT        => $time,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_URL            => $url,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_POST           => TRUE, 
                CURLOPT_POSTFIELDS     => http_build_query($data)
            );
            $curl = curl_init();
            curl_setopt_array($curl, $opts);
            $return = curl_exec($curl);
            if ( ! $soa)
            $this->_debug[$this->_debugKey]['_return'] = $return;
            curl_close($curl);
            if ($type  == 'html')
            {
                return $return;
            }
            return json_decode($return, TRUE);
        }
        
    }