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

define('G_DEBUG', FALSE);

// 定义 Cookies 作用域
define('G_COOKIE_DOMAIN', '');

// 定义 Cookies 前缀
define('G_COOKIE_PREFIX', 'aws');		// 建议修改此处

// 定义应用加密 KEY
define('G_SECUKEY','ABCDEFGHIJKLMNOP');				// 请修改此处
define('G_COOKIE_HASH_KEY', 'abcdefghijklmnop');	// 请修改此处

// GZIP 压缩输出页面
define('G_GZIP_COMPRESS', FALSE);

// Cache 命名空间
define('G_CACHE_NAMESPACE', 'aws');		// 建议修改此处

// Cache 类型 (File, Memcache, Memcached)
define('G_CACHE_TYPE', 'File');

// Cache options
define('G_CACHE_TYPE_MEMCACHED_HOST', '127.0.0.1');
define('G_CACHE_TYPE_MEMCACHED_PORT', 11211);

// 是否开启远程存储 (头像 话题图片)
define('G_REMOTE_STORAGE', FALSE);

// 远程存储配置 (详见 /system/Services/RemoteStorage.php)
define('G_REMOTE_STORAGE_REQUEST_URL', 'http://192.168.1.100/storage.php?path={$filename}');
define('G_REMOTE_STORAGE_REQUEST_HEADERS', array(
	"Authorization: abcdefghijklmnopqrstuvwxyz"
));
