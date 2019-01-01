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
        if (!$uid)
        {
            return false;
        }

        foreach(AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
        {
            @unlink(get_setting('upload_dir') . '/avatar/' . $this->get_avatar_path($uid, $key));
        }

        return $this->model('account')->update_users_fields(array('avatar_file' => null), $uid);
    }

}