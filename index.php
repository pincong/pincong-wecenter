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

if (!file_exists(dirname(__FILE__) . '/system/config/database.php') AND !file_exists(dirname(__FILE__) . '/system/config/install.lock.php'))
{
	header('Location: ./install/');
	exit;
}

include('system/init.php');
