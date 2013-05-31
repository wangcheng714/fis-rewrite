<?php

class Rewrite{

    private static $root = null;
    private static $rewriteRules = array();
    private static $MIME = array(
                    'bmp' => 'image/bmp',
                    'css' => 'text/css',
                    'doc' => 'application/msword',
                    'dtd' => 'text/xml',
                    'gif' => 'image/gif',
                    'hta' => 'application/hta',
                    'htc' => 'text/x-component',
                    'htm' => 'text/html',
                    'html' => 'text/html',
                    'xhtml' => 'text/html',
                    'ico' => 'image/x-icon',
                    'jpe' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'jpg' => 'image/jpeg',
                    'js' => 'text/javascript',
                    'json' => 'application/json',
                    'mocha' => 'text/javascript',
                    'mp3' => 'audio/mp3',
                    'mp4' => 'video/mpeg4',
                    'mpeg' => 'video/mpg',
                    'mpg' => 'video/mpg',
                    'manifest' => 'text/cache-manifest',
                    'pdf' => 'application/pdf',
                    'png' => 'image/png',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'rmvb' => 'application/vnd.rn-realmedia-vbr',
                    'rm' => 'application/vnd.rn-realmedia',
                    'rtf' => 'application/msword',
                    'svg' => 'image/svg+xml',
                    'swf' => 'application/x-shockwave-flash',
                    'tif' => 'image/tiff',
                    'tiff' => 'image/tiff',
                    'txt' => 'text/plain',
                    'vml' => 'text/xml',
                    'vxml' => 'text/xml',
                    'wav' => 'audio/wav',
                    'wma' => 'audio/x-ms-wma',
                    'wmv' => 'video/x-ms-wmv',
                    'woff' => 'image/woff',
                    'xml' => 'text/xml',
                    'xls' => 'application/vnd.ms-excel',
                    'xq' => 'text/xml',
                    'xql' => 'text/xml',
                    'xquery' => 'text/xml',
                    'xsd' => 'text/xml',
                    'xsl' => 'text/xml',
                    'xslt' => 'text/xml'
                );

    private static function initRewriteConf(){
        self::$root = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        $configFile = self::$root . 'server.conf';
        if(file_exists($configFile)){
            $configContent = file_get_contents($configFile);
            $rules = preg_split('/\r?\n/', $configContent);
            foreach($rules as $rule){
                $ruleTokens = preg_split('/\s+/', $rule);
                if($ruleTokens[0] == 'rewrite' || $ruleTokens[0] == 'redirect'){
                    self::$rewriteRules[] = array(
                        'rule' => $ruleTokens[1],
                        'rewrite' => $ruleTokens[2],
                        'type' => $ruleTokens[0]
                    );
                }
            }
        }
    }

    public static function addRewriteRule($reg, $rewrite){
        self::$rewriteRules[] = array(
            'rule' => $reg,
            'rewrite' => $rewrite
        );
    }

    private static function padString($str, array $replaceArr) {
        foreach ($replaceArr as $k => $v) {
            $replaceArr['$'.$k] = $v;
            unset($replaceArr[$k]);
        }
        return str_replace(array_keys($replaceArr), array_values($replaceArr), $str);
    }

    /**
     *  $url : 需要匹配的url
     *  $matches : 正则匹配的引用
     *  $statusCode : 匹配的状态码
     *      statuCode ： 200表示命中并执行
     *      statuCode ： 304表示在exts中，转发给用户自己处理
     *      statuCode :  404表示没有找到rewrite的文件
     *  $exts : 匹配exts里面的格式时会交给用户处理，返回状态码304
     *  返回值 ：
     *    true ： 表示命中正则
     *    false ： 表示没有命中
     */
    public static function match($url, &$matches = null, &$statusCode = null, $exts = null){
        self::initRewriteConf();
        $statusCode = false;
        foreach(self::$rewriteRules as $rules){
            if(preg_match('/' . $rules['rule'] . '/', $url, $matches)){
                $m = $matches;
                unset($m[0]);
                $rewrite = self::padString($rules['rewrite'], $m);
                if($rule['type'] == 'rewrite'){
                    if(file_exists(self::$root . $rewrite)){
                        $pos = strrpos($rewrite, '.');
                        if(false !== $pos){
                            $ext = substr($rewrite, $pos + 1);
                            if(in_array($ext, $exts)){
                                $statusCode = 304;
                            }else if($ext == 'php'){
                                $statusCode = 200;
                                self::includePhp(self::$root . $rewrite, $matches);
                            }else if(self::$MIME[$ext]){
                                $content_type = 'Content-Type: ' . $MIME[$ext];
                                header($content_type);
                                $statusCode = 200;
                                echo file_get_contents(self::$root . $rewrite);
                            }else{
                                $statusCode = 200;
                                $content_type = 'Content-Type: application/x-' . $ext;
                                header($content_type);
                                echo file_get_contents($file);
                            }
                        }
                    } else {
                        $statusCode = 404;
                    }
                }else if($rule['type'] == 'redirect'){
                        $statusCode = 302;
                        header('Location: ' . $rewrite);
                        exit();
                }
                return $statusCode;
            }
        }
        return false;
    }

    private static function includePhp($file, $matches){
        try{
            $fis_matches = $matches;
            include($file);
        }catch(Exception $e){
            throw new Exception("include php file " . $file . "failed");
        }
    }
}
