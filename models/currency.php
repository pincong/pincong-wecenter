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

class currency_class extends AWS_MODEL
{
	public function process($uid, $action, $currency, $note = '', $item_id = null, $item_type = null, $anonymous = false)
	{
		if (!$uid OR $uid == -1)
		{
			return false;
		}

		$currency = intval($currency);
		if ($currency == 0)
		{
			return false;
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		if (!$user_info)
		{
			return false;
		}

		$log_id = 0;
		if (!$anonymous)
		{
			$balance = intval($user_info['currency']) + $currency;

			$log_id = $this->insert('currency_log', array(
				'uid' => intval($uid),
				'action' => $action,
				'currency' => $currency,
				'balance' => $balance,
				'note' => $note,
				'item_id' => intval($item_id),
				'item_type' => $item_type,
				'time' => fake_time()
			));
		}

		$this->update('users', '`currency` = `currency` + ' . $currency, ['uid', 'eq', $uid, 'i']);

		return $log_id;
	}

	public function parse_log_items($parse_items)
	{
		if (!is_array($parse_items))
		{
			return false;
		}

		foreach ($parse_items AS $log_id => $item)
		{
			switch ($item['item_type'])
			{
				case 'question':
					$question_ids[] = $item['item_id'];
				break;

				case 'article':
					$article_ids[] = $item['item_id'];
				break;

				case 'video':
					$video_ids[] = $item['item_id'];
				break;

				case 'question_reply':
					$answer_ids[] = $item['item_id'];
				break;

				case 'article_reply':
					$article_comment_ids[] = $item['item_id'];
				break;

				case 'video_reply':
					$video_comment_ids[] = $item['item_id'];
				break;
			}
		}

		if ($question_ids)
		{
			$questions = $this->model('post')->get_posts_by_ids('question', $question_ids);
		}

		if ($article_ids)
		{
			$articles = $this->model('post')->get_posts_by_ids('article', $article_ids);
		}

		if ($video_ids)
		{
			$videos = $this->model('post')->get_posts_by_ids('video', $video_ids);
		}

		if ($answer_ids)
		{
			$answers = $this->model('post')->get_posts_by_ids('question_reply', $answer_ids);
		}

		if ($article_comment_ids)
		{
			$article_comments = $this->model('post')->get_posts_by_ids('article_reply', $article_comment_ids);
		}

		if ($video_comment_ids)
		{
			$video_comments = $this->model('post')->get_posts_by_ids('video_reply', $video_comment_ids);
		}

		foreach ($parse_items AS $log_id => $item)
		{
			switch ($item['item_type'])
			{
				case 'question':
					if ($questions[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => $questions[$item['item_id']]['title'],
							'url' => url_rewrite('/question/' . $item['item_id'])
						);
					}
				break;

				case 'article':
					if ($articles[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => $articles[$item['item_id']]['title'],
							'url' => url_rewrite('/article/' . $item['item_id'])
						);
					}
				break;

				case 'video':
					if ($videos[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => $videos[$item['item_id']]['title'],
							'url' => url_rewrite('/video/' . $item['item_id'])
						);
					}
				break;

				case 'question_reply':
					if ($answers[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => truncate_text($answers[$item['item_id']]['message'], 24),
							'url' => url_rewrite('/question/' . $answers[$item['item_id']]['parent_id'])
						);
					}
				break;

				case 'article_reply':
					if ($article_comments[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => truncate_text($article_comments[$item['item_id']]['message'], 24),
							'url' => url_rewrite('/article/' . $article_comments[$item['item_id']]['parent_id'])
						);
					}
				break;

				case 'video_reply':
					if ($video_comments[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => truncate_text($video_comments[$item['item_id']]['message'], 24),
							'url' => url_rewrite('/video/' . $video_comments[$item['item_id']]['parent_id'])
						);
					}
				break;

			}
		}

		return $result;
	}

    public function check_balance_for_operation($currency, $key)
    {
        $reward = S::get_int($key);
        if ($reward >= 0)
        {
            return true;
        }

        $currency += $reward;
        if ($currency < 0)
        {
            return false;
        }

        return true;
    }

}