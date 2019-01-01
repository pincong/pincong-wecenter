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

class people_class extends AWS_MODEL
{
	public function update_view_count($uid)
	{
		if (AWS_APP::cache()->get('update_view_count_people_' . md5(session_id()) . '_' . intval($uid)))
		{
			return false;
		}

		AWS_APP::cache()->set('update_view_count_people_' . md5(session_id()) . '_' . intval($uid), time(), get_setting('cache_level_normal'));

		return $this->query('UPDATE ' . $this->get_table('users') . ' SET views_count = views_count + 1 WHERE uid = ' . intval($uid));
	}

}