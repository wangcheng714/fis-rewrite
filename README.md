请求转发模块说明 ： 

## Install

	$ fis server install rewrite

## Usage

1. 对外提供match方法，供其他调试模块调用，具体方法参考代码注释说明。

2. 默认读取根目录server.conf文件，书写方式是： 

	以RewriteRule开头的会被翻译成一条转发规则，自上而下的匹配。所有非RewriteRule开头的会被当做注释处理。


        RewriteRule ^\/news\?.*tn\=[a-zA-Z0-9]+.* app/data/news.php
        RewriteRule ^\/index\?.* app/data/index.json
        RewriteRule ^\/(.*)\?.*  app/data/$1.php
