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

		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
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

		/*if ($_GET['column'] == 'log' AND !$this->user_id)
		{
			HTTP::redirect('/question/' . $_GET['id']);
		}*/

		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			HTTP::error_404();
		}

		if (! $_GET['sort'] or $_GET['sort'] != 'ASC')
		{
			$sort = 'DESC';
		}
		else
		{
			$sort = 'ASC';
		}

		if (get_setting('unfold_comments') == 'Y')
		{
			$_GET['comment_unfold'] = 'all';
		}

		$question_info['redirect'] = $this->model('question')->get_redirect($question_info['question_id']);

		if ($question_info['redirect']['target_id'])
		{
			$target_question = $this->model('question')->get_question_info_by_id($question_info['redirect']['target_id']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_question = $this->model('question')->get_question_info_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('从问题 %s 跳转而来', '<a href="' . get_js_url('/question/' . $_GET['rf'] . '?rf=false') . '">' . $from_question['question_content'] . '</a>');
			}
		}

		if ($question_info['redirect'] and ! $_GET['rf'])
		{
			if ($target_question)
			{
				HTTP::redirect('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']);
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}
		else if ($question_info['redirect'])
		{
			if ($target_question)
			{
				$message = AWS_APP::lang()->_t('此问题将跳转至') . ' <a href="' . get_js_url('/question/' . $question_info['redirect']['target_id'] . '?rf=' . $question_info['question_id']) . '">' . $target_question['question_content'] . '</a>';

				if ($this->user_id AND ($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
				{
					$message .= "&nbsp; (<a href=\"javascript:;\" onclick=\"AWS.ajax_request(G_BASE_URL + '/question/ajax/unredirect/', 'item_id=" . $question_info['question_id'] . "&target_id=" . $question_info['redirect']['target_id'] . "');\">" . AWS_APP::lang()->_t('撤消重定向') . "</a>)";
				}

				$redirect_message[] = $message;
			}
			else
			{
				$redirect_message[] = AWS_APP::lang()->_t('重定向目标问题已被删除, 将不再重定向问题');
			}
		}

		if ($question_info['category_id'] AND get_setting('category_enable') == 'Y')
		{
			$question_info['category_info'] = $this->model('system')->get_category_info($question_info['category_id']);
		}

		$question_info['user_info'] = $this->model('account')->get_user_info_by_uid($question_info['published_uid'], true);

		if ($_GET['column'] != 'log')
		{
			$this->model('content')->update_view_count('question', $question_info['question_id'], session_id());

			if (is_digits($_GET['uid']))
			{
				$answer_list_where[] = 'uid = ' . intval($_GET['uid']);
				$answer_count_where = 'uid = ' . intval($_GET['uid']);
			}
			else if ($_GET['uid'] == 'focus' and $this->user_id)
			{
				if ($friends = $this->model('follow')->get_user_friends($this->user_id, false))
				{
					foreach ($friends as $key => $val)
					{
						$follow_uids[] = $val['uid'];
					}
				}
				else
				{
					$follow_uids[] = 0;
				}

				$answer_list_where[] = 'uid IN(' . implode($follow_uids, ',') . ')';
				$answer_count_where = 'uid IN(' . implode($follow_uids, ',') . ')';
				//$answer_order_by = 'add_time ASC';
				$answer_order_by = 'answer_id ASC';
			}
			else if ($_GET['sort_key'] == 'add_time')
			{
				//$answer_order_by = "add_time " . $sort;
				$answer_order_by = "answer_id " . $sort;
			}
			else
			{
				//$answer_order_by = "agree_count " . $sort . ", add_time ASC";
				$answer_order_by = "agree_count " . $sort . ", answer_id ASC";
			}

			if ($answer_count_where)
			{
				$answer_count = $this->model('answer')->get_answer_count_by_question_id($question_info['question_id'], $answer_count_where);
			}
			else
			{
				$answer_count = $question_info['answer_count'];
			}

			if (isset($_GET['answer_id']) and (! $this->user_id OR $_GET['single']))
			{
				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($_GET['answer_id']));
			}
			else if (! $this->user_id AND !$this->user_info['permission']['answer_show'])
			{
				if ($question_info['best_answer'])
				{
					$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . intval($question_info['best_answer']));
				}
				else
				{
					$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, null, 'agree_count DESC');
				}
			}
			else
			{
				if ($answer_list_where)
				{
					$answer_list_where = implode(' AND ', $answer_list_where);
				}

				$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], calc_page_limit($_GET['page'], 100), $answer_list_where, $answer_order_by);
			}

			// 最佳回复预留
			$answers[0] = '';

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

			if (!in_array($question_info['best_answer'], $answer_ids) AND intval($_GET['page']) < 2)
			{
				$answer_list = array_merge($this->model('answer')->get_answer_list_by_question_id($question_info['question_id'], 1, 'answer_id = ' . $question_info['best_answer']), $answer_list);
			}

			if ($this->user_id)
			{
				$answer_vote_values = $this->model('vote')->get_user_vote_values_by_ids('answer', $answer_ids, $this->user_id);
			}

			foreach ($answer_list as $answer)
			{
				$answer['answer_content'] = $this->model('question')->parse_at_user($answer['answer_content']);

				if ($this->user_id)
				{
					$answer['vote_value'] = $answer_vote_values[$answer['answer_id']];
				}

				if ($question_info['best_answer'] == $answer['answer_id'] AND intval($_GET['page']) < 2)
				{
					$answers[0] = $answer;
				}
				else
				{
					$answers[] = $answer;
				}
			}

			if (! $answers[0])
			{
				unset($answers[0]);
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
			TPL::assign('answer_count', $answer_count);
		}

		if ($this->user_id)
		{
			TPL::assign('invite_users', $this->model('question')->get_invite_users($question_info['question_id']));

			TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $question_info['published_uid']));

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

		if ($this->user_id)
		{
			if ($question_topics)
			{
				foreach ($question_topics AS $key => $val)
				{
					$question_topic_ids[] = $val['topic_id'];
				}
			}

			if ($helpful_users = $this->model('topic')->get_helpful_users_by_topic_ids($question_topic_ids, 17))
			{
				foreach ($helpful_users AS $key => $val)
				{
					if ($val['user_info']['uid'] == $this->user_id)
					{
						unset($helpful_users[$key]);
					}
					else
					{
						$helpful_users[$key]['has_invite'] = $this->model('question')->has_question_invite($question_info['question_id'], $val['user_info']['uid'], $this->user_id);

						$helpful_users[$key]['experience'] = end($helpful_users[$key]['experience']);
					}
				}

				TPL::assign('helpful_users', $helpful_users);
			}
		}

		$this->crumb($question_info['question_content'], '/question/' . $question_info['question_id']);

		if ($_GET['column'] == 'log')
		{
			$this->crumb(AWS_APP::lang()->_t('日志'), '/question/id-' . $question_info['question_id'] . '__column-log');
		}
		else
		{
			if ($this->user_id)
			{
				TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
					'base_url' => get_js_url('/question/id-' . $question_info['question_id'] . '__sort_key-' . $_GET['sort_key'] . '__sort-' . $_GET['sort'] . '__uid-' . $_GET['uid']),
					'total_rows' => $answer_count,
					'per_page' => 100
				))->create_links());
			}
		}

		TPL::set_meta('keywords', implode(',', $this->model('system')->analysis_keyword($question_info['question_content'])));

		TPL::set_meta('description', $question_info['question_content'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($question_info['question_detail'])), 0, 128, 'UTF-8', '...'));

		if (get_setting('advanced_editor_enable') == 'Y')
		{
			import_editor_static_files();
		}

		TPL::assign('redirect_message', $redirect_message);

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
		if (TPL::is_output('block/content_nav_menu.tpl.htm', 'question/square'))
		{
			TPL::assign('content_nav_menu', $this->model('menu')->get_nav_menu_list('question'));
		}

		//边栏可能感兴趣的人
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_recommend_users_topics', $this->model('module')->recommend_users_topics($this->user_id));
		}
/*
		//边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_hot_users', $this->model('module')->sidebar_hot_users($this->user_id, 5));
		}
*/
		//边栏热门话题
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'question/square'))
		{
			TPL::assign('sidebar_hot_topics', $this->model('module')->sidebar_hot_topics($_GET['category']));
		}

		//边栏功能
		if (TPL::is_output('block/sidebar_feature.tpl.htm', 'question/square'))
		{
			TPL::assign('feature_list', $this->model('feature')->get_enabled_feature_list());
		}

		if ($_GET['category'])
		{
			if (is_digits($_GET['category']))
			{
				$category_info = $this->model('system')->get_category_info($_GET['category']);
			}
			else
			{
				$category_info = $this->model('system')->get_category_info_by_url_token($_GET['category']);
			}
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

		$topic_ids = null;

		if (! $_GET['sort_type'])
		{
			$_GET['sort_type'] = 'new';
		}

		if ($_GET['sort_type'] == 'hot')
		{
			$question_list = $this->model('posts')->get_hot_posts('question', $category_info['id'], $topic_ids, $_GET['day'], $_GET['page'], get_setting('contents_per_page'));
		}
		else
		{
			$question_list = $this->model('posts')->get_posts_list('question', $_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $topic_ids, $category_info['id'], $_GET['answer_count'], $_GET['day'], $_GET['recommend']);
		}

		if ($question_list)
		{
			foreach ($question_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$question_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['published_uid']);
				}
			}
		}

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/question/sort_type-' . preg_replace("/[\(\)\.;']/", '', $_GET['sort_type']) . '__category-' . $category_info['id'] . '__day-' . intval($_GET['day']) . '__recommend-' . $_GET['recommend']),
			'total_rows' => $this->model('posts')->get_posts_list_total(),
			'per_page' => get_setting('contents_per_page')
		))->create_links());

		TPL::assign('posts_list', $question_list);
		TPL::assign('question_list_bit', TPL::output('explore/ajax/list', false));

		TPL::output('question/square');
	}

}
