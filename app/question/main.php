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
				'index',
				'square'
			);
		}

		return $rule_action;
	}

	public function index_action()
	{
		if ($_GET['notification_id'])
		{
			$this->model('notify')->read_notification($_GET['notification_id'], $this->user_id);
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		$replies_per_page = intval(get_setting('replies_per_page'));
		if (!$replies_per_page)
		{
			$replies_per_page = 100;
		}

		if (! $_GET['sort'] OR $_GET['sort'] != 'ASC')
		{
			$sort = 'DESC';
		}
		else
		{
			$sort = 'ASC';
		}

		$question_info['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['uid']);

		$this->model('content')->update_view_count('question', $question_info['question_id'], session_id());

		if ($_GET['sort_key'] == 'add_time')
		{
			//$answer_order_by = "add_time " . $sort;
			$answer_order_by = "answer_id " . $sort;
		}
		else
		{
			//$answer_order_by = "agree_count " . $sort . ", add_time ASC";
			$answer_order_by = "reputation " . $sort . ", agree_count " . $sort . ", answer_id ASC";
		}

		$item_id = intval($_GET['item_id']);
		if ($item_id > 0)
		{
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . $item_id);
		}
		else
		{
			$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], $replies_per_page), null, $answer_order_by);
		}

		if (! is_array($answer_list))
		{
			$answer_list = array();
		}

		$answer_ids = array();
		$answer_uids = array();

		foreach ($answer_list as $answer)
		{
			$answer_ids[] = $answer['answer_id'];
			$answer_uids[] = $answer['uid'];
		}

		if ($this->user_id)
		{
			$answer_vote_values = $this->model('vote')->get_user_vote_values_by_ids('answer', $answer_ids, $this->user_id);
		}

		foreach ($answer_list as $answer)
		{
			$answer['answer_content'] = $this->model('mention')->parse_at_user($answer['answer_content']);

			if ($this->user_id)
			{
				$answer['vote_value'] = $answer_vote_values[$answer['answer_id']];
			}

			$answers[] = $answer;
		}

		if (get_setting('answer_unique') == 'Y')
		{
			if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
			{
				TPL::assign('user_answered', 1);
			}
			else
			{
				TPL::assign('user_answered', 0);
			}
		}

		TPL::assign('answers', $answers);
		TPL::assign('answer_count', $question_info['answer_count']);


		if ($this->user_id)
		{
			TPL::assign('invite_users', $this->model('question')->get_invite_users($question_info['question_id']));

			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $question_info['uid']));

			$question_info['vote_value'] = $this->model('vote')->get_user_vote_value_by_id('question', $question_info['question_id'], $this->user_id);
		}

		TPL::assign('question_info', $question_info);
		TPL::assign('question_focus', $this->model('question')->has_focus_question($question_info['question_id'], $this->user_id));

		$question_topics = $this->model('topic')->get_topics_by_item_id($question_info['question_id'], 'question');

		if (sizeof($question_topics) == 0 AND $this->user_id)
		{
			$related_topics = $this->model('question')->get_related_topics($question_info['question_content']);

			TPL::assign('related_topics', $related_topics);
		}

		TPL::assign('question_topics', $question_topics);

		TPL::assign('question_related_list', $this->model('question')->get_related_question_list($question_info['question_id'], $question_info['question_content']));

		if ($question_topics)
		{
			foreach ($question_topics AS $key => $val)
			{
				$question_topic_ids[] = $val['topic_id'];
			}
		}

		$page_title = CF::page_title($question_info['user_info'], 'question_' . $question_info['question_id'], $question_info['question_content']);
		$this->crumb($page_title, '/question/' . $question_info['question_id']);

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/question/id-' . $question_info['question_id'] . '__sort_key-' . $_GET['sort_key'] . '__sort-' . $_GET['sort']),
			'total_rows' => $question_info['answer_count'],
			'per_page' => $replies_per_page
		))->create_links());

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($question_info['question_content'])));

		TPL::set_meta('description', $question_info['question_content'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($question_info['question_detail'])), 0, 128, 'UTF-8', '...'));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		$recommend_posts = $this->model('posts')->get_recommend_posts_by_topic_ids($question_topic_ids);

		if ($recommend_posts)
		{
			foreach ($recommend_posts as $key => $value)
			{
				if ($value['question_id'] AND $value['question_id'] == $question_info['question_id'])
				{
					unset($recommend_posts[$key]);

					break;
				}
			}

			TPL::assign('recommend_posts', $recommend_posts);
		}

		TPL::output('question/index');
	}

	public function index_square_action()
	{
		$this->crumb(AWS_APP::lang()->_t('问题'), '/question/');

		// 导航
		TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('question'));

/*
		//边栏热门用户
		TPL::assign('sidebar_hot_users', $this->model('module')->sidebar_hot_users($this->user_id, 5));
*/
		//边栏热门话题
		TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($_GET['category']));

		//边栏功能
		TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());

		if ($_GET['category'])
		{
			$category_info = $this->model('category')->get_category_info($_GET['category']);
		}

		if ($category_info)
		{
			TPL::assign('category_info', $category_info);

			$this->crumb($category_info['title'], '/question/category-' . $category_info['id']);

			$meta_description = $category_info['title'];

			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}

			TPL::set_meta('description', $meta_description);
		}

		if (! $_GET['sort_type'])
		{
			$_GET['sort_type'] = 'new';
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$question_list = $this->model('posts')->get_hot_posts('question', $category_info['id'], $_GET['day'], $_GET['page'], get_setting('contents_per_page'));
		}
		else
		{
			$question_list = $this->model('posts')->get_posts_list('question', $_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['recommend']);
		}

		if ($question_list)
		{
			foreach ($question_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$question_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['uid']);
				}
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/question/sort_type-' . preg_replace("/[\(\)\.;']/", '', $_GET['sort_type']) . '__category-' . $category_info['id'] . '__day-' . intval($_GET['day']) . '__recommend-' . $_GET['recommend']),
			'total_rows' => $this->model('posts')->get_posts_list_total(),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::assign('posts_list', $question_list);
		TPL::assign('question_list_bit', TPL::render('explore/ajax/list'));

		TPL::output('question/square');
	}

}
