<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

// 定义 Cookies 作用域
define('G_COOKIE_DOMAIN','');

// 定义 Cookies 前缀
define('G_COOKIE_PREFIX','aws_');

// 定义应用加密 KEY
define('G_SECUKEY','请修改此处');
define('G_COOKIE_HASH_KEY', '请修改此处');

define('G_INDEX_SCRIPT', '?/');

// GZIP 压缩输出页面
define('G_GZIP_COMPRESS', FALSE);

// Session 存储类型 (db, file)
define('G_SESSION_SAVE', 'db');

// Session 文件存储路径
define('G_SESSION_SAVE_PATH', '');

// Cache 类型 (File, Memcached)
define('G_CACHE_TYPE', 'File');

// Cache options
define('G_CACHE_TYPE_MEMCACHED_HOST', '127.0.0.1');
define('G_CACHE_TYPE_MEMCACHED_PORT', 11211);

// 是否开启远程存储 (头像 话题图片)
define('G_REMOTE_STORAGE', FALSE);

// 远程存储配置 (详见 system/Services/RemoteStorage.php)
define('G_REMOTE_STORAGE_REQUEST_URL', 'http://192.168.1.100/?{$filename}');
define('G_REMOTE_STORAGE_REQUEST_HEADERS', array(
	"Authorization: abcdefghijklmnopqrstuvwxyz"
));
