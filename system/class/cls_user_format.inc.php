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
	private static $permissions;

	public static function set_permissions($val)
	{
		self::$permissions = $val;
	}

	// 获取头像网址
	// 举个例子：$uid=12345，那么头像网址很可能(根据您部署的上传文件夹而定)为 /uploads/000/01/23/45_avatar_min.jpg?random_string
	public static function avatar($user_info, $size = 'min')
	{
		$all_size = array('min', 'mid', 'max');
		$size = in_array($size, $all_size) ? $size : $all_size[0];

		$default = G_STATIC_URL . '/common/avatar-' . $size . '-img.png';

		if (!$user_info OR is_null($user_info['avatar_file']) OR (!self::$permissions['is_moderator'] AND $user_info['forbidden'] > 1))
		{
			return $default;
		}

		$uid = sprintf("%09d", $user_info['uid']);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);

		$filename = '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg';
		return S::get('upload_url') . $filename . '?' . $user_info['avatar_file']; // $user_info['avatar_file'] 随机字符串用于避免 CDN 缓存

		//$path = S::get('upload_dir') . $filename;

		//if (file_exists($path))
		//{
		//	return S::get('upload_url') . $filename . '?' . $user_info['avatar_file']; // $user_info['avatar_file'] 随机字符串用于避免 CDN 缓存
		//}
		//else
		//{
		//	return $default;
		//}
	}

	public static function signature($user_info)
	{
		if (!$user_info OR (!self::$permissions['is_moderator'] AND $user_info['forbidden'] > 1))
		{
			return '';
		}
		return FORMAT::text($user_info['signature'], true);
	}

	public static function name($user_info)
	{
		if (!$user_info)
		{
			return _t('[已注销]');
		}
		return FORMAT::text($user_info['user_name']);
	}

	public static function url($user_info)
	{
		if (!$user_info)
		{
			return 'javascript:;';
		}
		return url_rewrite('/people/') . safe_url_encode($user_info['user_name']);
	}

	public static function reputation($user_info)
	{
		if (!$user_info)
		{
			return 0;
		}
		if (self::$permissions['is_moderator'])
		{
			return $user_info['reputation'];
		}
		return intval($user_info['reputation']);
	}

	public static function flagged($user_info)
	{
		if (!$user_info OR !$user_info['flagged'])
		{
			return '';
		}
		return get_user_group_name_flagged($user_info['flagged']);
	}

}