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
	public function process($uid, $action, $currency, $note = '', $item_id = null)
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
		$balance = intval($user_info['currency']) + $currency;

		$log_id = $this->insert('currency_log', array(
			'uid' => intval($uid),
			'action' => $action,
			'currency' => $currency,
			'balance' => $balance,
			'note' => $note,
			'item_id' => intval($item_id),
			'time' => fake_time()
		));

		$this->query('UPDATE ' . $this->get_table('users') . ' SET currency = currency + ' . $currency . ' WHERE uid = ' . intval($uid));

		return $log_id;
	}

	public function fetch_log($uid, $action, $item_id = null)
	{
        $where = 'uid = ' . intval($uid) . ' AND action = \'' . $this->quote($action) . '\'';
        if ($item_id !== null)
        {
            $where .= ' AND item_id = ' . intval($item_id);
        }
		return $this->fetch_row('currency_log', $where);
	}

	public function parse_log_item($parse_items)
	{
		if (!is_array($parse_items))
		{
			return false;
		}

		foreach ($parse_items AS $log_id => $item)
		{
			switch ($item['action'])
			{
				case 'NEW_QUESTION':
				case 'ANSWER_QUESTION':
				case 'QUESTION_ANSWER':
				case 'INVITE_ANSWER':
				case 'ANSWER_INVITE':
				case 'MOVE_UP_QUESTION':
				case 'QUESTION_MOVED_UP':
				case 'MOVE_DOWN_QUESTION':
				case 'QUESTION_MOVED_DOWN':
				case 'AGREE_QUESTION':
				case 'QUESTION_AGREED':
				case 'DISAGREE_QUESTION':
				case 'QUESTION_DISAGREED':
					$question_ids[] = $item['item_id'];
				break;

				case 'BEST_ANSWER':
				case 'AGREE_ANSWER':
				case 'ANSWER_AGREED':
				case 'DISAGREE_ANSWER':
				case 'ANSWER_DISAGREED':
					$answer_ids[] = $item['item_id'];
				break;

				case 'NEW_ARTICLE':
				case 'MOVE_UP_ARTICLE':
				case 'ARTICLE_MOVED_UP':
				case 'MOVE_DOWN_ARTICLE':
				case 'ARTICLE_MOVED_DOWN':
				case 'AGREE_ARTICLE':
				case 'ARTICLE_AGREED':
				case 'DISAGREE_ARTICLE':
				case 'ARTICLE_DISAGREED':
					$article_ids[] = $item['item_id'];
				break;

				case 'AGREE_ARTICLE_COMMENT':
				case 'ARTICLE_COMMENT_AGREED':
				case 'DISAGREE_ARTICLE_COMMENT':
				case 'ARTICLE_COMMENT_DISAGREED':
					$article_comment_ids[] = $item['item_id'];
				break;
			}
		}

		if ($question_ids)
		{
			$questions_info = $this->model('question')->get_question_info_by_ids($question_ids);
		}

		if ($answer_ids)
		{
			$answers_info = $this->model('answer')->get_answers_by_ids($answer_ids);
		}

		if ($article_ids)
		{
			$articles_info = $this->model('article')->get_article_info_by_ids($article_ids);
		}

		if ($article_comment_ids)
		{
			$article_comments_info = $this->model('article')->get_comments_by_ids($article_comment_ids);
		}

		foreach ($parse_items AS $log_id => $item)
		{
			if (!$item['item_id'])
			{
				continue;
			}

			switch ($item['action'])
			{
				case 'NEW_QUESTION':
				case 'ANSWER_INVITE':
				case 'ANSWER_QUESTION':
				case 'QUESTION_ANSWER':
				case 'INVITE_ANSWER':
				case 'MOVE_UP_QUESTION':
				case 'QUESTION_MOVED_UP':
				case 'MOVE_DOWN_QUESTION':
				case 'QUESTION_MOVED_DOWN':
				case 'AGREE_QUESTION':
				case 'QUESTION_AGREED':
				case 'DISAGREE_QUESTION':
				case 'QUESTION_DISAGREED':
					if ($questions_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '问题: ' . $questions_info[$item['item_id']]['question_content'],
							'url' => get_js_url('/question/' . $item['item_id'])
						);
					}

				break;

				case 'BEST_ANSWER':
				case 'AGREE_ANSWER':
				case 'ANSWER_AGREED':
				case 'DISAGREE_ANSWER':
				case 'ANSWER_DISAGREED':
				case 'AGREE_ANSWER':
				case 'ANSWER_AGREED':
				case 'DISAGREE_ANSWER':
				case 'ANSWER_DISAGREED':
					if ($answers_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '答案: ' . cjk_substr($answers_info[$item['item_id']]['answer_content'], 0, 24, 'UTF-8', '...'),
							'url' => get_js_url('/question/id-' . $answers_info[$item['item_id']]['question_id'] . '__answer_id-' . $item['item_id'] . '__single-TRUE')
						);
					}
				break;

				case 'NEW_ARTICLE':
				case 'COMMENT_ARTICLE':
				case 'ARTICLE_COMMENTED':
				case 'MOVE_UP_ARTICLE':
				case 'ARTICLE_MOVED_UP':
				case 'MOVE_DOWN_ARTICLE':
				case 'ARTICLE_MOVED_DOWN':
				case 'AGREE_ARTICLE':
				case 'ARTICLE_AGREED':
				case 'DISAGREE_ARTICLE':
				case 'ARTICLE_DISAGREED':
					if ($articles_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '文章: ' . $articles_info[$item['item_id']]['title'],
							'url' => get_js_url('/article/' . $item['item_id'])
						);
					}
				break;

				case 'AGREE_ARTICLE_COMMENT':
				case 'ARTICLE_COMMENT_AGREED':
				case 'DISAGREE_ARTICLE_COMMENT':
				case 'ARTICLE_COMMENT_DISAGREED':
					if ($article_comments_info[$item['item_id']])
					{
						$result[$log_id] = array(
							'title' => '文章评论: ' . cjk_substr($article_comments_info[$item['item_id']]['message'], 0, 24, 'UTF-8', '...'),
							'url' => get_js_url('/article/' . $article_comments_info[$item['item_id']]['article_id'])
						);
					}
				break;

			}
		}

		return $result;
	}

    public function check_balance_for_operation($currency, $key)
    {
        $reward = intval(get_setting($key));
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

	public function delete_expired_logs()
	{
		$days = intval(get_setting('expiration_currency_logs'));
		if (!$days)
		{
			return;
		}
		$seconds = $days * 24 * 3600;
		$time_before = real_time() - $seconds;
		if ($time_before < 0)
		{
			$time_before = 0;
		}
		$this->delete('currency_log', 'time < ' . $time_before);
	}

}