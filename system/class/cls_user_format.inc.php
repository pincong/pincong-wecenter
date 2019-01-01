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

class UF
{
	// 获取头像地址
	// 举个例子：$uid=12345，那么头像路径很可能(根据您部署的上传文件夹而定)会被存储为/uploads/000/01/23/45_avatar_min.jpg
	public static function avatar(&$user_info, $size = 'min', $show_forbidden = true)
	{
		$all_size = array('min', 'mid', 'max');
		$size = in_array($size, $all_size) ? $size : $all_size[0];

		$default = G_STATIC_URL . '/common/avatar-' . $size . '-img.png';

		if (!$user_info OR (!$show_forbidden AND $user_info['forbidden']))
		{
			return $default;
		}

		$uid = sprintf("%09d", $user_info['uid']);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);

		$path = get_setting('upload_dir') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg';

		if (file_exists($path))
		{
			return $path;
		}
		else
		{
			return $default;
		}
	}

	public static function signature(&$user_info, $show_forbidden = true)
	{
		if (!$user_info OR (!$show_forbidden AND $user_info['forbidden']))
		{
			return '';
		}
		return $user_info['signature'];
	}

	public static function name(&$user_info)
	{
		if (!$user_info)
		{
			return AWS_APP::lang()->_t('[已注销]');
		}
		return $user_info['user_name'];
	}

	public static function url(&$user_info)
	{
		if (!$user_info)
		{
			return 'javascript:;';
		}
		return 'people/' . $user_info['url_token'];
	}

}