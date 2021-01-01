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
		$rule_action['rule_type'] = "white";

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function index_action()
	{
		if (is_digits($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if (!$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}

		if (!$topic_info)
		{
			HTTP::error_404();
		}

		if ($topic_info['merged_id'] AND $topic_info['merged_id'] != $topic_info['topic_id'])
		{
			if ($this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				HTTP::redirect('/topic/' . $topic_info['merged_id'] . '?rf=' . $topic_info['topic_id']);
			}
			else
			{
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}

		if (urldecode($topic_info['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/topic/' . $topic_info['url_token'] . '?rf=' . $_GET['rf']);
		}

		if (is_digits($_GET['rf']) and $_GET['rf'])
		{
			if ($from_topic = $this->model('topic')->get_topic_by_id($_GET['rf']))
			{
				$redirect_message[] = AWS_APP::lang()->_t('话题 (%s) 已与当前话题合并', $from_topic['topic_title']);
			}
		}

		if ($topic_info['seo_title'])
		{
			TPL::assign('page_title', $topic_info['seo_title']);
		}
		else
		{
			$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['url_token']);
		}

		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}

		if ($topic_info['topic_description'])
		{
			TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));
		}

		TPL::assign('topic_info', $topic_info);

		$related_topics_ids = array();

		$page_keywords[] = $topic_info['topic_title'];

		if ($related_topics = $this->model('topic')->related_topics($topic_info['topic_id']))
		{
			foreach ($related_topics AS $key => $val)
			{
				$related_topics_ids[$val['topic_id']] = $val['topic_id'];

				$page_keywords[] = $val['topic_title'];
			}
		}

		TPL::set_meta('keywords', implode(',', $page_keywords));
		TPL::set_meta('description', cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));

		TPL::assign('related_topics', $related_topics);

		$topic_ids[] = $topic_info['topic_id'];

		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$topic_ids[] = $val['source_id'];
			}
		}

		if ($posts_list = $this->model('posts')->get_posts_list_by_topic_ids(null, $topic_ids, 1, get_setting('contents_per_page')))
		{
			foreach ($posts_list AS $key => $val)
			{
				// TODO: if ($val['post_type'] == 'question')
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['uid']);
				}
			}
		}

		TPL::assign('posts_list', $posts_list);
		TPL::assign('all_list_bit', TPL::render('explore/ajax/list'));

		TPL::assign('topic_ids', implode(',', $topic_ids));

		TPL::assign('redirect_message', $redirect_message);

		TPL::output('topic/index');
	}

	public function index_square_action()
	{
		switch ($_GET['channel'])
		{
			case 'focus':
				if ($topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, calc_page_limit($_GET['page'], 20)))
				{
					$topics_list_total_rows = $this->user_info['topic_focus_count'];
				}

				TPL::assign('topics_list', $topics_list);
			break;

			case 'hot':
			default:
				switch ($_GET['day'])
				{
					case 'month':
						$order = 'discuss_count_last_month DESC';
					break;

					case 'week':
						$order = 'discuss_count_last_week DESC';
					break;

					default:
						$order = 'discuss_count DESC';
					break;
				}

				$cache_key = 'square_hot_topic_list' . md5($order) . '_' . intval($_GET['page']);

				if (!$topics_list = AWS_APP::cache()->get($cache_key))
				{
					if ($topics_list = $this->model('topic')->get_topic_list(null, $order, 20, $_GET['page']))
					{
						$topics_list_total_rows = $this->model('topic')->found_rows();

						AWS_APP::cache()->set('square_hot_topic_list_total_rows', $topics_list_total_rows, get_setting('cache_level_low'));
					}

					AWS_APP::cache()->set($cache_key, $topics_list, get_setting('cache_level_low'));
				}
				else
				{
					$topics_list_total_rows = AWS_APP::cache()->get('square_hot_topic_list_total_rows');
				}

				TPL::assign('topics_list', $topics_list);
			break;
		}

		TPL::assign('new_topics', $this->model('topic')->get_topic_list(null, 'topic_id DESC', 10));

		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/topic/channel-' . $_GET['channel'] . '__topic_id-' . $_GET['topic_id'] . '__day-' . $_GET['day']),
			'total_rows' => $topics_list_total_rows,
			'per_page' => 20
		))->create_links());

		$this->crumb(AWS_APP::lang()->_t('话题广场'), '/topic/');

		TPL::output('topic/square');
	}

	public function edit_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['id']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('已锁定的话题不能编辑'));
			}
		}

		$this->crumb(AWS_APP::lang()->_t('话题编辑'), '/topic/edit/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);

		TPL::assign('topic_info', $topic_info);
		TPL::assign('related_topics', $this->model('topic')->related_topics($_GET['id']));

		TPL::output('topic/edit');
	}

	public function manage_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('话题不存在'), '/');
		}

		$this->crumb(AWS_APP::lang()->_t('话题管理'), '/topic/manage/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);

		if (!($this->user_info['permission']['manage_topic']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('你没有权限进行此操作'));
		}

		if ($merged_topics = $this->model('topic')->get_merged_topic_ids($topic_info['topic_id']))
		{
			foreach ($merged_topics AS $key => $val)
			{
				$merged_topic_ids[] = $val['source_id'];
			}

			$merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids);
		}

		TPL::assign('merged_topics_info', $merged_topics_info);

		TPL::assign('topic_info', $topic_info);

		TPL::output('topic/manage');
	}
}
