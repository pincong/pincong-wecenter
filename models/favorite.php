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

class favorite_class extends AWS_MODEL
{
	public function check_item_type($type)
	{
		switch ($type)
		{
			case 'question_reply':
			case 'question':
			case 'article':
			case 'video':
				return true;
		}
		return false;
	}

	public function add_favorite($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$uid OR !$this->check_item_type($item_type))
		{
			return false;
		}

		if (!$this->fetch_one('favorite', 'id', [['type', 'eq', $item_type], ['item_id', 'eq', $item_id, 'i'], ['uid', 'eq', $uid, 'i']]))
		{
			return $this->insert('favorite', array(
				'item_id' => intval($item_id),
				'type' => $item_type,
				'uid' => intval($uid),
				'time' => fake_time()
			));
		}
	}

	public function remove_favorite_item($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$uid OR !$this->check_item_type($item_type))
		{
			return false;
		}

		$this->delete('favorite', [['type', 'eq', $item_type], ['item_id', 'eq', $item_id, 'i'], ['uid', 'eq', $uid, 'i']]);
	}

	public function count_favorite_items($uid)
	{
		return $this->count('favorite', ['uid', 'eq', $uid, 'i']);
	}

	public function get_item_list($uid, $page, $per_page)
	{
		if (!$uid)
		{
			return false;
		}

		$favorite_items = $this->fetch_page('favorite', ['uid', 'eq', $uid, 'i'], 'item_id DESC', $page, $per_page);

		$this->process_list_data($favorite_items);
		return $favorite_items;
	}

	private function process_list_data(&$favorite_items)
	{
		if (!$favorite_items)
		{
			return false;
		}

		foreach ($favorite_items as $key => $data)
		{
			switch ($data['type'])
			{
				case 'question_reply':
					$answer_ids[] = $data['item_id'];
				break;

				case 'question':
					$question_ids[] = $data['item_id'];
				break;

				case 'article':
					$article_ids[] = $data['item_id'];
				break;

				case 'video':
					$video_ids[] = $data['item_id'];
				break;
			}
		}

		if ($answer_ids)
		{
			if ($answer_infos = $this->model('post')->get_posts_by_ids('question_reply', $answer_ids))
			{
				foreach ($answer_infos AS $key => $data)
				{
					$question_ids[$data['parent_id']] = $data['parent_id'];

					$favorite_uids[$data['uid']] = $data['uid'];
				}
			}
		}

		if ($question_ids)
		{
			if ($question_infos = $this->model('post')->get_posts_by_ids('question', $question_ids))
			{
				foreach ($question_infos AS $key => $data)
				{
					$favorite_uids[$data['uid']] = $data['uid'];
				}
			}
		}

		if ($article_ids)
		{
			if ($article_infos = $this->model('post')->get_posts_by_ids('article', $article_ids))
			{
				foreach ($article_infos AS $key => $data)
				{
					$favorite_uids[$data['uid']] = $data['uid'];
				}
			}
		}

		if ($video_ids)
		{
			if ($video_infos = $this->model('post')->get_posts_by_ids('video', $video_ids))
			{
				foreach ($video_infos AS $key => $data)
				{
					$favorite_uids[$data['uid']] = $data['uid'];
				}
			}
		}

		$users_info = $this->model('account')->get_user_info_by_uids($favorite_uids);

		foreach ($favorite_items as $key => $data)
		{
			switch ($data['type'])
			{
				case 'question_reply':
					$favorite_items[$key]['item'] = $answer_infos[$data['item_id']];
					$favorite_items[$key]['user_info'] = $users_info[$answer_infos[$data['item_id']]['uid']];
					$favorite_items[$key]['item']['question_info'] = $question_infos[$answer_infos[$data['item_id']]['parent_id']];
				break;

				case 'question':
					$favorite_items[$key]['item'] = $question_infos[$data['item_id']];
					$favorite_items[$key]['user_info'] = $users_info[$question_infos[$data['item_id']]['uid']];
				break;

				case 'article':
					$favorite_items[$key]['item'] = $article_infos[$data['item_id']];
					$favorite_items[$key]['user_info'] = $users_info[$article_infos[$data['item_id']]['uid']];
				break;

				case 'video':
					$favorite_items[$key]['item'] = $video_infos[$data['item_id']];
					$favorite_items[$key]['user_info'] = $users_info[$video_infos[$data['item_id']]['uid']];
				break;
			}
		}

		return true;
	}
}