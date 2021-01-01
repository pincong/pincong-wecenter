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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'topic_info'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		H::no_cache_header();
	}

	public function edit_topic_action()
	{
		if (!check_user_operation_interval('edit_topic', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}

			if ($this->model('topic')->has_lock_topic(H::POST('topic_id')))
			{
				H::ajax_error((_t('锁定的话题不能编辑')));
			}
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id(H::POST('topic_id')))
		{
			H::ajax_error((_t('话题不存在')));
		}

		$this->model('topic')->update_topic($this->user_id, H::POST('topic_id'), null, H::POST_S('topic_description'));

		set_user_operation_last_time('edit_topic', $this->user_id);

		H::ajax_location(url_rewrite('/topic/topic_id-' . H::POST('topic_id')));
	}

	public function save_related_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic(H::GET('topic_id')))
			{
				H::ajax_error((_t('锁定的话题不能编辑')));
			}
		}

		if (!$this->model('topic')->get_topic_by_id(H::GET('topic_id')))
		{
			H::ajax_error((_t('话题不存在')));
		}

		if (!$topic_title = H::POST_S('topic_title'))
		{
			H::ajax_error((_t('请输入话题标题')));
		}

		if (S::get('topic_title_limit') AND strlen($topic_title) > S::get('topic_title_limit'))
		{
			H::ajax_error((_t('话题标题字数超出限制')));
		}

		if (!$related_id = $this->model('topic')->save_topic($topic_title, $this->user_id, $this->user_info['permission']['create_topic']))
		{
			H::ajax_error((_t('话题已锁定或没有创建话题权限')));
		}

		if (!$this->model('topic')->save_related_topic(H::GET('topic_id'), $related_id))
		{
			H::ajax_error((_t('已经存在相同推荐话题')));
		}

		H::ajax_response(array(
			'related_id' => $related_id,
		));
	}

	public function remove_related_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic(H::GET('topic_id')))
			{
				H::ajax_error((_t('锁定的话题不能编辑')));
			}
		}

		$this->model('topic')->remove_related_topic(H::GET('topic_id'), H::GET('related_id'));

		H::ajax_success();
	}

	public function upload_topic_pic_action()
	{
		if (S::get('upload_enable') == 'N')
		{
			H::ajax_error((_t('本站未开启上传功能')));
		}

		if (!($this->user_info['permission']['manage_topic']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
			else if ($this->model('topic')->has_lock_topic(H::GET('topic_id')))
			{
				H::ajax_error((_t('锁定的话题不能编辑')));
			}
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id(H::GET('topic_id')))
		{
			H::ajax_error((_t('话题不存在')));
		}

		if (!$this->model('topic')->upload_image('aws_upload_file', H::GET('topic_id'), $error))
		{
			H::ajax_error($error);
		}

		H::ajax_response(array(
			'thumb' => S::get('upload_url') . '/topic/' . $this->model('topic')->get_image_path(H::GET('topic_id'), 'mid') . '?' . rand(1, 999)
		));
	}

	public function focus_topic_action()
	{
		H::ajax_response(array(
			'type' => $this->model('topic')->add_focus_topic($this->user_id, H::POST_I('topic_id'))
		));
	}

/*
	public function lock_topic_action()
	{
		if (!$this->user_info['permission']['manage_topic'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		$this->model('topic')->lock_topic_by_id(H::GET('topic_id'), $this->model('topic')->has_lock_topic(H::GET('topic_id')));

		H::ajax_success();
	}
*/

	public function lock_action()
	{
		if (!$this->user_info['permission']['manage_topic'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id(H::POST('topic_id')))
		{
			H::ajax_error((_t('话题不存在')));
		}

		$this->model('topic')->lock_topic_by_id(H::POST('topic_id'), !$topic_info['topic_lock']);

		H::ajax_success();
	}

	public function remove_action()
	{
		if (!$this->user_info['permission']['manage_topic'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		$this->model('topic')->remove_topic_by_ids(H::POST('topic_id'));

		H::ajax_location(url_rewrite('/topic/'));
	}

	public function merge_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_error((_t('锁定的话题不能编辑')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_title(H::POST_S('topic_title')))
		{
			H::ajax_error((_t('话题不存在')));
		}

		if ($topic_info['topic_id'] == H::POST('target_id'))
		{
			H::ajax_error((_t('话题合并不能与自己合并')));
		}

		if ($topic_info['merged_id'])
		{
			$merged_topic_info = $this->model('topic')->get_topic_by_id($topic_info['merged_id']);

			H::ajax_error((_t('该话题已经与 %s 合并', $merged_topic_info['topic_title'])));
		}

		$this->model('topic')->merge_topic($topic_info['topic_id'], H::POST('target_id'), $this->user_id);

		H::ajax_success();
	}

	public function remove_merge_topic_action()
	{
		if (!($this->user_info['permission']['manage_topic']))
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		$this->model('topic')->remove_merge_topic(H::POST('source_id'), H::POST('target_id'));

		H::ajax_success();
	}

	public function remove_topic_relation_action()
	{
		if (!H::POST('topic_id') OR !H::POST('item_id') OR !H::POST('type'))
		{
			H::ajax_error((_t('指定的项目不存在')));
		}

		if (!check_user_operation_interval('edit_topic', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$thread_info = $this->model('post')->get_thread_info_by_id(H::POST('type'), H::POST('item_id')))
		{
			H::ajax_error((_t('指定的项目不存在')));
		}

		if ($thread_info['lock'])
		{
			H::ajax_error((_t('锁定的主题不能编辑话题')));
		}

		if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $thread_info['uid'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!$topic_info = $this->model('topic')->get_topic_by_id(H::POST('topic_id')))
		{
			H::ajax_error((_t('指定的项目不存在')));
		}

		$this->model('topic')->remove_thread_topic(
			H::POST('type'),
			H::POST('item_id'),
			H::POST('topic_id'),
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		set_user_operation_last_time('edit_topic', $this->user_id);

		H::ajax_success();
	}

	public function save_topic_relation_action()
	{
		if (!H::POST('item_id') OR !H::POST('type'))
		{
			H::ajax_error((_t('指定的项目不存在')));
		}

		if (!check_user_operation_interval('edit_topic', $this->user_id, $this->user_info['permission']['interval_modify']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$topic_title = H::POST_S('topic_title'))
		{
			H::ajax_error((_t('请输入话题标题')));
		}

		if (S::get('topic_title_limit') AND strlen($topic_title) > S::get('topic_title_limit') AND !$this->model('topic')->get_topic_id_by_title($topic_title))
		{
			H::ajax_error((_t('话题标题字数超出限制')));
		}

		if (!$thread_info = $this->model('post')->get_thread_info_by_id(H::POST('type'), H::POST('item_id')))
		{
			H::ajax_error((_t('指定的项目不存在')));
		}

		if (!$this->user_info['permission']['edit_question_topic'] AND $this->user_id != $thread_info['uid'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if ($thread_info['lock'])
		{
			H::ajax_error((_t('锁定的主题不能编辑话题')));
		}

		$topics_limit_max = S::get_int('topics_limit_max');
		if ($topics_limit_max)
		{
			$num_topics = 0;
			if ($topic_ids = $this->model('topic')->get_topic_ids_by_item_id(H::POST('item_id'), H::POST('type')))
			{
				$num_topics = sizeof($topic_ids);
			}
			if ($num_topics >= $topics_limit_max)
			{
				H::ajax_error((_t('话题数量最多 %s 个, 请调整话题数量', $topics_limit_max)));
			}
		}

		if (!$topic_id = $this->model('topic')->save_topic($topic_title, $this->user_id, $this->user_info['permission']['create_topic']))
		{
			H::ajax_error((_t('话题已锁定或没有创建话题权限, 不能添加话题')));
		}

		$this->model('topic')->add_thread_topic(
			H::POST('type'),
			H::POST('item_id'),
			$topic_id,
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		set_user_operation_last_time('edit_topic', $this->user_id);

		H::ajax_response(array(
			'topic_id' => $topic_id,
			'topic_url' => url_rewrite('topic/topic_id-' . $topic_id)
		));
	}

}