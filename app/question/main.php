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

class main extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($this->user_id AND $_GET['notification_id'])
		{
			$this->model('notification')->mark_as_read($_GET['notification_id'], $this->user_id);
		}

		$item_id = intval($_GET['item_id']);
		if ($item_id)
		{
			if (!$reply = $this->model('question')->get_answer_by_id($item_id))
			{
				HTTP::error_404();
			}
			$_GET['id'] = $reply['question_id'];
		}

		if (!$question_info = $this->model('question')->get_question_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		$replies_per_page = intval(get_setting('replies_per_page'));
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		$url_param[] = 'id-' . $question_info['id'];

		if ($_GET['sort'] == 'ASC')
		{
			$sort = 'ASC';

			$url_param[] = 'sort-ASC';
		}
		else
		{
			$sort = 'DESC';
		}

		if ($_GET['fold'])
		{
			$order_by[] = "fold ASC";

			$url_param[] = 'fold-1';
		}

		if ($_GET['sort_key'] == 'add_time')
		{
			$order_by[] = "id " . $sort;

			$url_param[] = 'sort_key-add_time';
		}
		else
		{
			$order_by[] = "reputation " . $sort;
			$order_by[] = "agree_count " . $sort;
		}

		$reply_count = $question_info['answer_count'];
		$discussion_count = $question_info['comment_count'];
		// 判断是否已合并
		if ($redirect_posts = $this->model('content')->get_redirect_posts('question', $question_info['id']))
		{
			foreach ($redirect_posts AS $key => $val)
			{
				$post_ids[] = $val['id'];
				// 修复合并后回复数
				$reply_count += $val['answer_count'];
				$discussion_count += $val['comment_count'];
			}
		}
		$post_ids[] = $question_info['id'];

		if ($item_id)
		{
			$answer_list[] = $reply;
		}
		else
		{
			$answer_list = $this->model('question')->get_answers($post_ids, $_GET['page'], $replies_per_page, implode(', ', $order_by));
		}

		if (! is_array($answer_list))
		{
			$answer_list = array();
		}

		$answer_ids = array();
		$answer_uids = array();

		foreach ($answer_list as $answer)
		{
			$answer_ids[] = $answer['id'];
		}

		if ($this->user_id)
		{
			$answer_vote_values = $this->model('vote')->get_user_vote_values_by_ids('answer', $answer_ids, $this->user_id);
		}

		foreach ($answer_list as $answer)
		{
			$answer['message'] = $this->model('mention')->parse_at_user($answer['message']);

			if ($this->user_id)
			{
				$answer['vote_value'] = $answer_vote_values[$answer['id']];
			}

			$answers[] = $answer;
		}

		if (get_setting('answer_unique') == 'Y')
		{
			TPL::assign('user_answered', $this->model('content')->has_user_relpied_to_thread('question', $question_info['id'], $this->user_id));
		}

		TPL::assign('answers', $answers);
		TPL::assign('answer_count', $reply_count);
		TPL::assign('discussion_count', $discussion_count);


		if ($this->user_id)
		{
			TPL::assign('invite_users', $this->model('invite')->get_invite_users($question_info['id']));

			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $question_info['uid']));

			$question_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('question', $question_info['id'], $this->user_id);
		}

		TPL::assign('question_info', $question_info);
		if ($question_info['redirect_id'])
		{
			TPL::assign('redirect_info', $this->model('content')->get_post_by_id('question', $question_info['redirect_id']));
		}
		if ($_GET['rf'])
		{
			TPL::assign('redirected_from', $this->model('content')->get_post_by_id('question', $_GET['rf']));
		}

		$question_topics = $this->model('topic')->get_topics_by_item_id($question_info['id'], 'question');

		if (sizeof($question_topics) == 0 AND $this->user_id)
		{
			$related_topics = $this->model('question')->get_related_topics($question_info['title']);

			TPL::assign('related_topics', $related_topics);
		}

		TPL::assign('question_topics', $question_topics);

		TPL::assign('question_related_list', $this->model('question')->get_related_question_list($question_info['id'], $question_info['title']));

		if ($question_topics)
		{
			foreach ($question_topics AS $key => $val)
			{
				$question_topic_ids[] = $val['topic_id'];
			}
		}

		$this->model('content')->update_view_count('question', $question_info['id'], session_id());

		$page_title = CF::page_title($question_info['user_info'], 'question_' . $question_info['id'], $question_info['title']);
		$this->crumb($page_title);

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => url_rewrite('/question/') . implode('__', $url_param),
			'total_rows' => $reply_count,
			'per_page' => $replies_per_page
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($question_info['title'])));

		TPL::set_meta('description', $question_info['title'] . ' - ' . truncate_text(str_replace("\r\n", ' ', $question_info['message']), 128));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		$recommend_posts = $this->model('posts')->get_recommend_posts_by_topic_ids($question_topic_ids);

		if ($recommend_posts)
		{
			foreach ($recommend_posts as $key => $value)
			{
				if ($value['post_type'] == 'question' AND $value['id'] == $question_info['id'])
				{
					unset($recommend_posts[$key]);

					break;
				}
			}

			TPL::assign('recommend_posts', $recommend_posts);
		}

		if ($this->user_id)
		{
			TPL::assign('following', $this->model('postfollow')->is_following('question', $question_info['id'], $this->user_id));
		}

		TPL::output('question/index');
	}

}
