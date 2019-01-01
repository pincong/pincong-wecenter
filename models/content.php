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

class content_class extends AWS_MODEL
{
	public function check_item_type($type)
	{
		switch ($type)
		{
			case 'question':
			case 'answer':
			case 'article':
			case 'article_comment':
			case 'video':
			case 'video_comment':
				return true;
		}
		return false;
	}

	public function get_item_info_by_id($type, $item_id)
	{
		$item_id = intval($item_id);
		if (!$item_id OR !$this->check_item_type($type))
		{
			return false;
		}

		$where = 'id = ' . ($item_id);
		// TODO: question_id, answer_id 字段改为 id 以避免特殊处理
		if ($type == 'question')
		{
			$where = 'question_id = ' . ($item_id);
		}
		elseif ($type == 'answer')
		{
			$where = 'answer_id = ' . ($item_id);
		}

		$item_info = $this->fetch_row($type, $where);
		// TODO: published_uid 字段改为 uid 以避免特殊处理
		if ($item_info)
		{
			if ($type == 'question')
			{
				$item_info['id'] = $item_info['question_id'];
				$item_info['uid'] = $item_info['published_uid'];
			}
			elseif ($type == 'answer')
			{
				$item_info['id'] = $item_info['answer_id'];
			}
		}

		return $item_info;
	}

}