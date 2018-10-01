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
	public function add_favorite($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$item_type)
		{
			return false;
		}

		if (!$this->fetch_one('favorite', 'id', "type = '" . $this->quote($item_type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid)))
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
		if (!$item_id OR !$item_type OR !$uid)
		{
			return false;
		}

		$this->delete('favorite', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
	}

	public function count_favorite_items($uid, $tag = null)
	{
		{
			return $this->count('favorite', 'uid = ' . intval($uid));
		}
	}

	public function get_item_list($tag, $uid, $limit)
	{
		if (!$uid)
		{
			return false;
		}

		{
			$favorite_items = $this->fetch_all('favorite', "uid = " . intval($uid), 'item_id DESC', $limit);
		}

		return $this->process_list_data($favorite_items);
	}

	public function process_list_data($favorite_items)
	{
		if (!$favorite_items)
		{
			return false;
		}

		foreach ($favorite_items as $key => $data)
		{
			switch ($data['type'])
			{
				case 'answer':
					$answer_ids[] = $data['item_id'];
				break;

				case 'article':
					$article_ids[] = $data['item_id'];
				break;
			}
		}

		if ($answer_ids)
		{
			if ($answer_infos = $this->model('answer')->get_answers_by_ids($answer_ids))
			{
				foreach ($answer_infos AS $key => $data)
				{
					$question_ids[$data['question_id']] = $data['question_id'];

					$favorite_uids[$data['uid']] = $data['uid'];
				}

				$answer_attachs = $this->model('publish')->get_attachs('answer', $answer_ids, 'min');

				$question_infos = $this->model('question')->get_question_info_by_ids($question_ids);
			}
		}

		if ($article_ids)
		{
			if ($article_infos = $this->model('article')->get_article_info_by_ids($article_ids))
			{
				foreach ($article_infos AS $key => $data)
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
				case 'answer':
					$favorite_list_data[$key]['title'] = $question_infos[$answer_infos[$data['item_id']]['question_id']]['question_content'];
					$favorite_list_data[$key]['link'] = get_js_url('/question/' . $answer_infos[$data['item_id']]['question_id'] . '?rf=false&item_id=' . $data['item_id'] . '#!answer_' . $data['item_id']);
					$favorite_list_data[$key]['add_time'] = $question_infos[$answer_infos[$data['item_id']]['question_id']]['add_time'];

					$favorite_list_data[$key]['answer_info'] = $answer_infos[$data['item_id']];

					if ($favorite_list_data[$key]['answer_info']['has_attach'])
					{
						$favorite_list_data[$key]['answer_info']['attachs'] = $answer_attachs[$data['item_id']];
					}

					$favorite_list_data[$key]['question_info'] = $question_infos[$answer_infos[$data['item_id']]['question_id']];
					$favorite_list_data[$key]['user_info'] = $users_info[$answer_infos[$data['item_id']]['uid']];
				break;

				case 'article':
					$favorite_list_data[$key]['title'] = $article_infos[$data['item_id']]['title'];
					$favorite_list_data[$key]['link'] = get_js_url('/article/' . $data['item_id']);
					$favorite_list_data[$key]['add_time'] = $article_infos[$data['item_id']]['add_time'];

					$favorite_list_data[$key]['article_info'] = $article_infos[$data['item_id']];

					$favorite_list_data[$key]['last_action_str'] = ACTION_LOG::format_action_data(ACTION_LOG::ADD_ARTICLE, $data['uid'], $users_info[$data['uid']]['user_name']);

					$favorite_list_data[$key]['user_info'] = $users_info[$article_infos[$data['item_id']]['uid']];
				break;
			}

			$favorite_list_data[$key]['item_id'] = $data['item_id'];
			$favorite_list_data[$key]['item_type'] = $data['type'];
		}

		return $favorite_list_data;
	}
}