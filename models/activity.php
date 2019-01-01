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

class activity_class extends AWS_MODEL
{
	/**
	 * 记录用户动态
	 * @param string $item_type question|question_discussion|answer|answer_discussion|article|article_comment|video|video_danmaku|video_comment
	 * @param int $item_id
	 * @param string $note
	 * @param int $uid
	 */
	public function log($item_type, $item_id, $note, $uid = 0)
	{
		$this->insert('activity', array(
			'item_type' => $item_type,
			'item_id' => intval($item_id),
			'note' => $note,
			'uid' => intval($uid),
			'time' => fake_time()
		));
	}

	public function get_activities_by_uids($uids)
	{
	}


}