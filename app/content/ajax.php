<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
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

		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function change_category_action()
	{
		if (!$category_id = intval($_POST['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}

		if (!$this->user_info['permission']['change_category'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$this->model('category')->check_user_permission($category_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的等级还不能在这个分类发言')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('category')->category_exists($category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}

		if (!$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if ($item_info['category_id'] != $category_id)
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->change_category($_POST['item_type'], $_POST['item_id'], $category_id, $item_info['category_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function lock_action()
	{
		if (!$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if (!$item_info['lock'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->lock($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unlock_action()
	{
		if (!$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['lock'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->unlock($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function recommend_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if (!$item_info['recommend'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->recommend($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unrecommend_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['recommend'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->unrecommend($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function bump_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		$this->model('content')->bump($_POST['item_type'], $_POST['item_id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function sink_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		$this->model('content')->sink($_POST['item_type'], $_POST['item_id'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	// 置顶
	public function pin_action()
	{
		if (!$this->user_info['permission']['pin_post'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置置顶的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if (!$item_info['sort'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->pin($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	// 取消置顶
	public function unpin_action()
	{
		if (!$this->user_info['permission']['pin_post'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置置顶的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['sort'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);

			$this->model('content')->unpin($_POST['item_type'], $_POST['item_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	public function fold_reply_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有折叠的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_reply_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if (!$item_info['fold'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);
			switch ($_POST['item_type'])
			{
				case 'answer':
					$parent_type = 'question';
					$parent_id = $item_info['question_id'];
					break;
				case 'article_comment':
					$parent_type = 'article';
					$parent_id = $item_info['article_id'];
					break;
				case 'video_comment':
					$parent_type = 'video';
					$parent_id = $item_info['video_id'];
					break;
			}
			$this->model('content')->fold_reply($_POST['item_type'], $_POST['item_id'], $parent_type, $parent_id, $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unfold_reply_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有折叠的权限')));
		}

		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$item_info = $this->model('content')->get_reply_info_by_id($_POST['item_type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['fold'])
		{
			set_user_operation_last_time_by_uid('modify', $this->user_id);
			switch ($_POST['item_type'])
			{
				case 'answer':
					$parent_type = 'question';
					$parent_id = $item_info['question_id'];
					break;
				case 'article_comment':
					$parent_type = 'article';
					$parent_id = $item_info['article_id'];
					break;
				case 'video_comment':
					$parent_type = 'video';
					$parent_id = $item_info['video_id'];
					break;
			}
			$this->model('content')->unfold_reply($_POST['item_type'], $_POST['item_id'], $parent_type, $parent_id, $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


}