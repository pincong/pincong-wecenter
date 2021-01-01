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
		if ($this->user_id AND H::GET('notification_id'))
		{
			$this->model('notification')->mark_as_read(H::GET('notification_id'), $this->user_id);
		}

		$item_id = H::GET_I('item_id');
		if ($item_id)
		{
			if (!$reply = $this->model('question')->get_answer_by_id($item_id))
			{
				H::error_404();
			}
			$thread_id = $reply['parent_id'];
		}
		else
		{
			$thread_id = H::GET('id');
		}

		if (!$thread_info = $this->model('question')->get_question_by_id($thread_id))
		{
			H::error_404();
		}

		$replies_per_page = S::get_int('replies_per_page');
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		$url_param[] = 'id-' . $thread_info['id'];

		if (H::GET('sort') == 'ASC')
		{
			$sort = 'ASC';

			$url_param[] = 'sort-ASC';
		}
		else
		{
			$sort = 'DESC';
		}

		if (H::GET('fold'))
		{
			$order_by[] = "fold ASC";

			$url_param[] = 'fold-1';
		}

		if (H::GET('sort_key') == 'add_time')
		{
			$order_by[] = "id " . $sort;

			$url_param[] = 'sort_key-add_time';
		}
		else
		{
			$order_by[] = "reputation " . $sort;
			$order_by[] = "agree_count " . $sort;
		}

		$reply_count = $thread_info['reply_count'];
		$discussion_count = $thread_info['comment_count'];
		// 判断是否已合并
		if ($redirect_posts = $this->model('post')->get_redirect_threads('question', $thread_info['id']))
		{
			foreach ($redirect_posts AS $key => $val)
			{
				$post_ids[] = $val['id'];
				// 修复合并后回复数
				$reply_count += $val['reply_count'];
				$discussion_count += $val['comment_count'];
			}
		}
		$post_ids[] = $thread_info['id'];

		if ($item_id)
		{
			$answer_list[] = $reply;
		}
		else
		{
			$answer_list = $this->model('question')->get_answers($post_ids, H::GET('page'), $replies_per_page, implode(', ', $order_by));
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
			$answer_vote_values = $this->model('vote')->get_user_vote_values_by_ids('question_reply', $answer_ids, $this->user_id);
		}

		foreach ($answer_list as $answer)
		{
			if ($this->user_id)
			{
				$answer['vote_value'] = $answer_vote_values[$answer['id']];
			}

			$answers[] = $answer;
		}

		if (S::get('answer_unique') == 'Y')
		{
			TPL::assign('user_answered', $this->model('post')->has_user_relpied_to_thread('question', $thread_info['id'], $this->user_id));
		}

		TPL::assign('answers', $answers);
		TPL::assign('answer_count', $reply_count);
		TPL::assign('discussion_count', $discussion_count);


		if ($this->user_id)
		{
			TPL::assign('invite_users', $this->model('invite')->get_invite_users($thread_info['id']));

			$thread_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('question', $thread_info['id'], $this->user_id);
		}

		TPL::assign('question_info', $thread_info);
		if ($thread_info['redirect_id'])
		{
			TPL::assign('redirect_info', $this->model('post')->get_post_by_id('question', $thread_info['redirect_id']));
		}
		if (H::GET('rf'))
		{
			TPL::assign('redirected_from', $this->model('post')->get_post_by_id('question', H::GET('rf')));
		}

		$this->model('post')->update_view_count('question', $thread_info['id']);

		$page_title = CF::page_title($thread_info);
		$this->crumb($page_title);

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/question/') . implode('__', $url_param),
			'total_rows' => $reply_count,
			'per_page' => $replies_per_page
		)));

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($thread_info['title'])));

		TPL::set_meta('description', $thread_info['title'] . ' - ' . truncate_text(str_replace("\r\n", ' ', $thread_info['message']), 128));

		$topic_ids = $this->model('topic')->get_topic_ids_by_item_id($thread_info['id'], 'question');
		if ($topic_ids)
		{
			TPL::assign('topics', $this->model('topic')->get_topics_by_ids($topic_ids));
		}

		TPL::assign('related_posts', $this->model('threadindex')->get_related_posts_by_topic_ids('question', $topic_ids, $thread_info['id']));
		TPL::assign('recommended_posts', $this->model('threadindex')->get_recommended_posts('question', $thread_info['id']));

		if ($this->user_id)
		{
			TPL::assign('following', $this->model('postfollow')->is_following('question', $thread_info['id'], $this->user_id));
		}

		TPL::output('question/index');
	}

}
