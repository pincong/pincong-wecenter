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

class HTTP
{
	public static function no_cache_header($type = 'text/html', $charset = 'utf-8')
	{
		H::no_cache_header($type, $charset);
	}

	public static function error_403()
	{
		H::error_403();
	}

	public static function error_404()
	{
		H::error_404();
	}

	public static function redirect($url)
	{
		H::redirect($url);
	}

}