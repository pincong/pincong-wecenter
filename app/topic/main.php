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
			$rule_action['actions'] = array(
				'index'
			);
		}

		return $rule_action;
	}

	private function index_square()
	{
		$per_page = S::get_int('contents_per_page');

		$url_param = [];
		switch (H::GET('channel'))
		{
			case 'focus':
				$url_param[] = 'channel-focus';
				$topics_list = $this->model('topic')->get_focus_topic_list($this->user_id, H::GET('page'), $per_page);
			break;

			case 'hot':
			default:
				switch (H::GET('day'))
				{
					case 'month':
						$order = 'discuss_count_last_month DESC';
						$url_param[] = 'day-month';
					break;

					case 'week':
						$order = 'discuss_count_last_week DESC';
						$url_param[] = 'day-week';
					break;

					default:
						$order = 'discuss_count DESC';
					break;
				}
				$topics_list = $this->model('topic')->get_topic_list(null, $order, H::GET('page'), $per_page);
			break;
		}

		$topics_list_total_rows = $this->model('topic')->total_rows();
		TPL::assign('topics_list', $topics_list);

		TPL::assign('new_topics', $this->model('topic')->get_topic_list(null, 'topic_id DESC', 1, 10));

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/topic/') . implode('__', $url_param),
			'total_rows' => $topics_list_total_rows,
			'per_page' => $per_page
		)));

		$this->crumb(_t('话题广场'));

		TPL::output('topic/square');
	}

	public function index_action()
	{
		if (!H::GET('id') AND !H::GET('topic_id'))
		{
			$this->index_square();
			return;
		}

		if (H::GET('topic_id'))
		{
			$topic_info = $this->model('topic')->get_topic_by_id(H::GET('topic_id'));
		}
		else
		{
			$topic_info = $this->model('topic')->get_topic_by_title(H::GET('id'));
		}

		if (!$topic_info)
		{
			H::error_404();
		}

		if ($topic_info['merged_id'])
		{
			if ($topic_info['merged_id'] != $topic_info['topic_id'] AND $redirect_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']))
			{
				if (!H::GET('topic_id'))
				{
					// 如果未指定H::GET('topic_id')才自动跳转
					H::redirect('/topic/topic_id-' . $topic_info['merged_id'] . '__rf-' . $topic_info['topic_id']);
					return;
				}
				TPL::assign('redirect_info', $redirect_info);
			}
			else
			{
				// 删除无效合并
				$this->model('topic')->remove_merge_topic($topic_info['topic_id'], $topic_info['merged_id']);
			}
		}

		if (H::GET('rf'))
		{
			TPL::assign('redirected_from', $this->model('topic')->get_topic_by_id(H::GET('rf')));
		}

		$url_param[] = 'topic_id-' . $topic_info['topic_id'];
		$type = H::GET('type');
		if ($type AND $this->model('post')->check_thread_type($type))
		{
			$url_param[] = 'type-' . $type;
		}
		else
		{
			$type = null;
		}

		$this->crumb($topic_info['topic_title']);

		if ($this->user_id)
		{
			$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		}

		if ($topic_info['topic_description'])
		{
			TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . truncate_text(str_replace("\r\n", ' ', $topic_info['topic_description']), 128));
		}

		TPL::assign('topic_info', $topic_info);

		$related_topics_ids = array();

		$page_keywords[] = $topic_info['topic_title'];

		if ($related_topics = $this->model('topic')->get_related_topics($topic_info['topic_id']))
		{
			foreach ($related_topics AS $key => $val)
			{
				$related_topics_ids[$val['topic_id']] = $val['topic_id'];

				$page_keywords[] = $val['topic_title'];
			}
		}

		TPL::set_meta('keywords', implode(',', $page_keywords));
		TPL::set_meta('description', truncate_text(str_replace("\r\n", ' ', $topic_info['topic_description']), 128));

		TPL::assign('related_topics', $related_topics);

		$topic_ids = $this->model('topic')->get_merged_topic_ids_by_id($topic_info['topic_id']);
		$topic_ids[] = $topic_info['topic_id'];

		if ($posts_list = $this->model('threadindex')->get_posts_list_by_topic_ids($type, $topic_ids, H::GET('page'), S::get_int('contents_per_page')))
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['post_type'] == 'question')
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['id'], 2, $val['uid']);
				}
			}
		}

		TPL::assign('posts_list', $posts_list);
		TPL::assign('all_list_bit', TPL::render('explore/list_template'));

		TPL::assign('pagination', AWS_APP::pagination()->create(array(
			'base_url' => url_rewrite('/topic/') . implode('__', $url_param),
			'total_rows' => $this->model('threadindex')->get_posts_list_total(),
			'per_page' => S::get_int('contents_per_page')
		)));

		TPL::output('topic/index');
	}

	public function edit_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id(H::GET('id')))
		{
			H::redirect_msg(_t('话题不存在'), '/');
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::redirect_msg(_t('你没有权限进行此操作'));
			}
			else if ($this->model('topic')->has_lock_topic(H::GET('id')))
			{
				H::redirect_msg(_t('已锁定的话题不能编辑'));
			}
		}

		$this->crumb(_t('话题编辑'));
		$this->crumb($topic_info['topic_title']);

		TPL::assign('topic_info', $topic_info);
		TPL::assign('related_topics', $this->model('topic')->get_related_topics(H::GET('id')));

		TPL::output('topic/edit');
	}

	public function manage_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id(H::GET('id')))
		{
			H::redirect_msg(_t('话题不存在'), '/');
		}

		$this->crumb(_t('话题管理'));
		$this->crumb($topic_info['topic_title']);

		if (!($this->user_info['permission']['manage_topic']))
		{
			H::redirect_msg(_t('你没有权限进行此操作'));
		}

		if ($merged_topic_ids = $this->model('topic')->get_merged_topic_ids_by_id($topic_info['topic_id']))
		{
			$merged_topics_info = $this->model('topic')->get_topics_by_ids($merged_topic_ids);
		}

		TPL::assign('merged_topics_info', $merged_topics_info);

		TPL::assign('topic_info', $topic_info);

		TPL::output('topic/manage');
	}
}
