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

if (!defined('IN_ANWSION'))
{
	die;
}

class avatar_class extends AWS_MODEL
{
	// e.g. $uid=12345, return '000/01/23/'
    public function get_avatar_dir($uid)
    {
        $uid = abs(intval($uid));
        $uid = sprintf('%\'09d', $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);

        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
    }

	// e.g. $uid=12345, return '45_avatar_min.jpg'
    public function get_avatar_filename($uid, $size = 'min')
    {
        $uid = abs(intval($uid));
        $uid = sprintf('%\'09d', $uid);

        return substr($uid, -2) . '_avatar_' . $size . '.jpg';
    }

	// e.g. $uid=12345, return '000/01/23/45_avatar_min.jpg'
    public function get_avatar_path($uid, $size = 'min')
    {
        $uid = abs(intval($uid));
        $uid = sprintf('%\'09d', $uid);
        $dir1 = substr($uid, 0, 3);
        $dir2 = substr($uid, 3, 2);
        $dir3 = substr($uid, 5, 2);

        return $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, -2) . '_avatar_' . $size . '.jpg';
    }


    /**
     * 删除用户头像
     *
     * @param int
     * @return boolean
     */
	public function delete_avatar($uid)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		foreach(AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
		{
			@unlink(S::get('upload_dir') . '/avatar/' . $this->get_avatar_path($uid, $key));
		}

		return $this->model('account')->update_user_fields(array('avatar_file' => null), $uid);
	}


    /**
     * 上用户头像
     *
     * @param int
     * @param string reference
     * @return boolean
     */
	public function upload_avatar($field, $uid, &$error)
	{
		$uid = intval($uid);
		if ($uid <= 0)
		{
			return false;
		}

		$local_upload_dir = S::get('upload_dir');
		$save_dir = $local_upload_dir . '/avatar/' . $this->get_avatar_dir($uid);
		$filename = $this->get_avatar_filename($uid, 'real');

		AWS_APP::upload()->initialize(array(
			'allowed_types' => S::get('allowed_upload_types'),
			'upload_path' => $save_dir,
			'is_image' => TRUE,
			'max_size' => S::get('upload_size_limit'),
			'file_name' => $filename,
			'encrypt_name' => FALSE
		))->do_upload($field);

		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				case 'upload_invalid_filetype':
					$error = _t('文件类型无效');
					return false;

				case 'upload_invalid_filesize':
					$error = _t('文件尺寸过大, 最大允许尺寸为 %s KB', S::get('upload_size_limit'));
					return false;

				default:
					$error = _t('错误代码: %s', AWS_APP::upload()->get_error());
					return false;
			}
		}

		if (! $upload_data = AWS_APP::upload()->data())
		{
			$error = _t('上传失败');
			return false;
		}

		if ($upload_data['is_image'] != 1)
		{
			$error = _t('文件类型错误');
			return false;
		}

		foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
		{
			$result = AWS_APP::image()->initialize(array(
				'local_upload_dir' => $local_upload_dir,
				'quality' => 90,
				'source_image' => $save_dir . $filename,
				'new_image' => $save_dir . $this->get_avatar_filename($uid, $key),
				'width' => $val['w'],
				'height' => $val['h']
			))->resize();

			if ($result == false)
			{
				$error = _t('保存失败');
				return false;
			}
		}

		$update_data['avatar_file'] = random_string(4); // 生成随机字符串

		// 更新主表
		$this->model('account')->update_user_fields($update_data, $uid);

		return true;
	}

}