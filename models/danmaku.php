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

class danmaku_class extends AWS_MODEL
{
	public function get_danmaku_list_by_video_id($video_id, $page, $per_page)
	{
		$order = 'stime DESC';
		$where = 'video_id = ' . intval($video_id);

		$danmaku_list = $this->fetch_page('video_danmaku', $where, $order, $page, $per_page);

		return $danmaku_list;
	}

	public function save_danmaku($video_id, $uid, $stime, $text, $mode, $size, $color)
	{
        $now = fake_time();

		$video_id = intval($video_id);

		$danmaku_id = $this->insert('video_danmaku', array(
			'video_id' => $video_id,
			'uid' => intval($uid),
			'stime' => intval($stime),
			'text' => htmlspecialchars($text),
			'mode' => intval($mode),
			'size' => intval($size),
			'color' => intval($color),
			'add_time' => $now
		));

		if (!$danmaku_id)
		{
			return false;
		}

		$this->update('video', array(
			'danmaku_count' => $this->count('video_danmaku', 'video_id = ' . $video_id),
		), 'id = ' . $video_id);

		return $danmaku_id;
	}

	public function remove_danmaku($danmaku_id)
	{

	}


	public function remove_danmaku_by_video_id($video_id)
	{

	}

}