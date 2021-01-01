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
	public function setup()
	{
		HTTP::no_cache_header();
	}

	private function validate_thread($permission_name, $item_type, $item_id, &$item_info_out)
	{
		if (!$this->user_info['permission'][$permission_name])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$item_info_out = $this->model('content')->get_thread_info_by_id($item_type, $item_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		set_user_operation_last_time('manage', $this->user_id);
	}

	private function validate_reply($permission_name, $item_type, $item_id, &$item_info_out)
	{
		if (!$this->user_info['permission'][$permission_name])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$item_info_out = $this->model('content')->get_reply_info_by_id($item_type, $item_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		set_user_operation_last_time('manage', $this->user_id);
	}

	public function change_uid_action()
	{
		$this->validate_thread('is_moderator', $_POST['item_type'], $_POST['item_id'], $item_info);

		$this->model('content')->change_uid(
			$_POST['item_type'],
			$_POST['item_id'],
			$_POST['uid'],
			$item_info['uid'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function redirect_action()
	{
		$this->validate_thread('redirect_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$redirect_item_info = $this->model('content')->get_thread_info_by_id($_POST['item_type'], $_POST['redirect_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('合并内容不存在')));
		}

		if ($redirect_item_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能合并到被合并的主题')));
		}

		$this->model('content')->redirect(
			$_POST['item_type'],
			$_POST['item_id'],
			$_POST['redirect_id'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unredirect_action()
	{
		$this->validate_thread('redirect_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$item_info['redirect_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容没有被合并')));
		}

		$this->model('content')->unredirect(
			$_POST['item_type'],
			$_POST['item_id'],
			$item_info['redirect_id'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function change_category_action()
	{
		$this->validate_thread('change_category', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$category_id = intval($_POST['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}

		if (!$this->model('category')->check_user_permission($category_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的声望还不能在这个分类发言')));
		}

		if (!$this->model('category')->category_exists($category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}

		if ($item_info['category_id'] != $category_id)
		{
			$this->model('content')->change_category(
				$_POST['item_type'],
				$_POST['item_id'],
				$category_id,
				$item_info['category_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function lock_action()
	{
		$this->validate_thread('lock_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$item_info['lock'])
		{
			$this->model('content')->lock(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unlock_action()
	{
		$this->validate_thread('lock_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if ($item_info['lock'])
		{
			$this->model('content')->unlock(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function recommend_action()
	{
		$this->validate_thread('recommend_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$item_info['recommend'])
		{
			$this->model('content')->recommend(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unrecommend_action()
	{
		$this->validate_thread('recommend_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if ($item_info['recommend'])
		{
			$this->model('content')->unrecommend(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function bump_action()
	{
		$this->validate_thread('bump_sink', $_POST['item_type'], $_POST['item_id'], $item_info);

		$this->model('content')->bump(
			$_POST['item_type'],
			$_POST['item_id'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function sink_action()
	{
		$this->validate_thread('bump_sink', $_POST['item_type'], $_POST['item_id'], $item_info);

		$this->model('content')->sink(
			$_POST['item_type'],
			$_POST['item_id'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	// 置顶
	public function pin_action()
	{
		$this->validate_thread('pin_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$item_info['sort'])
		{
			$this->model('content')->pin(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	// 取消置顶
	public function unpin_action()
	{
		$this->validate_thread('pin_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if ($item_info['sort'])
		{
			$this->model('content')->unpin(
				$_POST['item_type'],
				$_POST['item_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


	public function fold_reply_action()
	{
		$this->validate_reply('fold_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if (!$item_info['fold'])
		{
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

			$this->model('content')->fold_reply(
				$_POST['item_type'],
				$_POST['item_id'],
				$parent_type,
				$parent_id,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function unfold_reply_action()
	{
		$this->validate_reply('fold_post', $_POST['item_type'], $_POST['item_id'], $item_info);

		if ($item_info['fold'])
		{
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

			$this->model('content')->unfold_reply(
				$_POST['item_type'],
				$_POST['item_id'],
				$parent_type,
				$parent_id,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}