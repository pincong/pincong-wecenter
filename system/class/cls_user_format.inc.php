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
	// 获取头像网址
	// 举个例子：$uid=12345，那么头像网址很可能(根据您部署的上传文件夹而定)为 /uploads/000/01/23/45_avatar_min.jpg?random_string
	public static function avatar(&$user_info, $size = 'min', $show_forbidden = false)
	{
		$all_size = array('min', 'mid', 'max');
		$size = in_array($size, $all_size) ? $size : $all_size[0];

		$default = G_STATIC_URL . '/common/avatar-' . $size . '-img.png';

		if (!$user_info OR is_null($user_info['avatar_file']) OR (!$show_forbidden AND ($user_info['forbidden'] OR $user_info['flagged'] > 1)))
		{
			return $default;
		}

		$uid = sprintf("%09d", $user_info['uid']);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);

		$filename = '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . '_avatar_' . $size . '.jpg';
		return get_setting('upload_url') . $filename . '?' . $user_info['avatar_file']; // $user_info['avatar_file'] 随机字符串用于避免 CDN 缓存

		//$path = get_setting('upload_dir') . $filename;

		//if (file_exists($path))
		//{
		//	return get_setting('upload_url') . $filename . '?' . $user_info['avatar_file']; // $user_info['avatar_file'] 随机字符串用于避免 CDN 缓存
		//}
		//else
		//{
		//	return $default;
		//}
	}

	public static function signature(&$user_info, $show_forbidden = false)
	{
		if (!$user_info OR (!$show_forbidden AND ($user_info['forbidden'] OR $user_info['flagged'] > 1)))
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