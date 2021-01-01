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
		H::no_cache_header();
	}

	private function validate_permission($permission_name)
	{
		if (!$this->user_info['permission'][$permission_name])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}
	}

	private function validate_interval($interval_name)
	{
		if (!check_user_operation_interval($interval_name, $this->user_id, $this->user_info['permission']['interval_' . $interval_name]))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}
	}

	private function validate_thread($permission_name, $interval_name, $item_type, $item_id, &$item_info_out)
	{
		$this->validate_permission($permission_name);
		$this->validate_interval($interval_name);

		if (!$item_info_out = $this->model('post')->get_thread_info_by_id($item_type, $item_id))
		{
			H::ajax_error((_t('内容不存在')));
		}

		set_user_operation_last_time($interval_name, $this->user_id);
	}

	private function validate_reply($permission_name, $interval_name, $item_type, $item_id, &$item_info_out)
	{
		$this->validate_permission($permission_name);
		$this->validate_interval($interval_name);

		if (!$item_info_out = $this->model('post')->get_reply_info_by_id($item_type, $item_id))
		{
			H::ajax_error((_t('内容不存在')));
		}

		set_user_operation_last_time($interval_name, $this->user_id);
	}


	public function change_uid_action()
	{
		$this->validate_thread('is_moderator', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		$this->model('content')->change_uid(
			H::POST('item_type'),
			H::POST('item_id'),
			H::POST('uid'),
			$item_info['uid'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_success();
	}

	public function redirect_action()
	{
		$this->validate_thread('redirect_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		$redirect_id = H::POST_I('redirect_id');
		if ($redirect_id == $item_info['id'] OR $redirect_id == $item_info['redirect_id'])
		{
			H::ajax_error((_t('不能合并到同一个主题')));
		}

		if (!$redirect_item_info = $this->model('post')->get_thread_info_by_id(H::POST('item_type'), $redirect_id))
		{
			H::ajax_error((_t('合并内容不存在')));
		}

		if ($redirect_item_info['redirect_id'])
		{
			H::ajax_error((_t('不能合并到被合并的主题')));
		}

		$this->model('content')->redirect(
			H::POST('item_type'),
			H::POST('item_id'),
			$redirect_id,
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_success();
	}

	public function unredirect_action()
	{
		$this->validate_thread('redirect_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if (!$item_info['redirect_id'])
		{
			H::ajax_error((_t('内容没有被合并')));
		}

		$this->model('content')->unredirect(
			H::POST('item_type'),
			H::POST('item_id'),
			$item_info['redirect_id'],
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_success();
	}

	public function change_category_action()
	{
		$this->validate_thread('change_category', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if (!$new_category_id = H::POST_I('category_id'))
		{
			H::ajax_error((_t('分类不存在')));
		}

		if (!$this->model('category')->check_change_category_permission($new_category_id, $item_info['category_id'], $this->user_info))
		{
			H::ajax_error((_t('不能变更到这个分类')));
		}

		if (!$this->model('category')->category_exists($new_category_id))
		{
			H::ajax_error((_t('分类不存在')));
		}

		if ($item_info['category_id'] != $new_category_id)
		{
			$this->model('content')->change_category(
				H::POST('item_type'),
				H::POST('item_id'),
				$new_category_id,
				$item_info['category_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function lock_action()
	{
		$this->validate_thread('lock_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if (!$item_info['lock'])
		{
			$this->model('content')->lock(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function unlock_action()
	{
		$this->validate_thread('lock_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if ($item_info['lock'])
		{
			$this->model('content')->unlock(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function recommend_action()
	{
		$this->validate_thread('recommend_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if (!$item_info['recommend'])
		{
			$this->model('content')->recommend(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function unrecommend_action()
	{
		$this->validate_thread('recommend_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if ($item_info['recommend'])
		{
			$this->model('content')->unrecommend(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function bump_action()
	{
		$this->validate_thread('bump_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		$this->model('content')->bump(
			H::POST('item_type'),
			H::POST('item_id'),
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_success();
	}

	public function sink_action()
	{
		$this->validate_thread('sink_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		$this->model('content')->sink(
			H::POST('item_type'),
			H::POST('item_id'),
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
		);

		H::ajax_success();
	}


	// 置顶
	public function pin_action()
	{
		$this->validate_thread('pin_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if (!$item_info['sort'])
		{
			$this->model('content')->pin(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	// 取消置顶
	public function unpin_action()
	{
		$this->validate_thread('pin_post', 'manage', H::POST('item_type'), H::POST('item_id'), $item_info);

		if ($item_info['sort'])
		{
			$this->model('content')->unpin(
				H::POST('item_type'),
				H::POST('item_id'),
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	// TODO: 刪
	public function fold_reply_action()
	{
		$this->validate_interval('manage');
		if (!$item_info = $this->model('post')->get_reply_info_by_id(H::POST('item_type'), H::POST('item_id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if (!$item_info['fold'])
		{
			switch (H::POST('item_type'))
			{
				case 'question_reply':
					$parent_type = 'question';
					break;
				case 'article_reply':
					$parent_type = 'article';
					break;
				case 'video_reply':
					$parent_type = 'video';
					break;
			}

			$parent_id = $item_info['parent_id'];

			$parent_info = $this->model('post')->get_thread_info_by_id($parent_type, $parent_id);
			if (!$parent_info OR $parent_info['uid'] != $this->user_id)
			{
				$this->validate_permission('fold_post');
			}
			else
			{
				$this->validate_permission('fold_post_own_thread');
			}

			set_user_operation_last_time('manage', $this->user_id);

			$this->model('content')->fold(
				H::POST('item_type'),
				H::POST('item_id'),
				$parent_type,
				$parent_id,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	// TODO: 刪
	public function unfold_reply_action()
	{
		$this->validate_interval('manage');
		if (!$item_info = $this->model('post')->get_reply_info_by_id(H::POST('item_type'), H::POST('item_id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['fold'])
		{
			switch (H::POST('item_type'))
			{
				case 'question_reply':
					$parent_type = 'question';
					break;
				case 'article_reply':
					$parent_type = 'article';
					break;
				case 'video_reply':
					$parent_type = 'video';
					break;
			}

			$parent_id = $item_info['parent_id'];

			$parent_info = $this->model('post')->get_thread_info_by_id($parent_type, $parent_id);
			if (!$parent_info OR $parent_info['uid'] != $this->user_id)
			{
				$this->validate_permission('fold_post');
			}
			else
			{
				$this->validate_permission('fold_post_own_thread');
			}

			set_user_operation_last_time('manage', $this->user_id);

			$this->model('content')->unfold(
				H::POST('item_type'),
				H::POST('item_id'),
				$parent_type,
				$parent_id,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}


	// 关注主题
	public function follow_action()
	{
		$this->validate_interval('follow');

		if (!$item_info = $this->model('post')->get_thread_info_by_id(H::POST('item_type'), H::POST('item_id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id)
		{
			$this->validate_permission('follow_thread');
		}

		set_user_operation_last_time('follow', $this->user_id);

		$this->model('postfollow')->follow(
			H::POST('item_type'),
			H::POST('item_id'),
			$this->user_id
		);

		H::ajax_success();
	}

	// 取消关注主题
	public function unfollow_action()
	{
		$this->validate_interval('follow');

		if (!$item_info = $this->model('post')->get_thread_info_by_id(H::POST('item_type'), H::POST('item_id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		set_user_operation_last_time('follow', $this->user_id);

		$this->model('postfollow')->unfollow(
			H::POST('item_type'),
			H::POST('item_id'),
			$this->user_id
		);

		H::ajax_success();
	}

	public function fold_action()
	{
		$this->validate_interval('manage');
		$item_type = H::POST('item_type');
		$item_id = H::POST('item_id');
		if ($this->model('post')->check_thread_type($item_type) OR
			!$item_info = $this->model('post')->get_post_info_by_id($item_type, $item_id))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if (!$item_info['fold'])
		{
			$thread_info = $this->model('post')->get_post_thread_info_by_id($item_type, $item_id);
			if (!$thread_info)
			{
				H::ajax_error((_t('内容不存在')));
			}
			if ($thread_info['uid'] != $this->user_id)
			{
				$this->validate_permission('fold_post');
			}
			else
			{
				$this->validate_permission('fold_post_own_thread');
			}

			set_user_operation_last_time('manage', $this->user_id);

			$this->model('content')->fold(
				$item_type,
				$item_id,
				$thread_info['thread_type'],
				$thread_info['thread_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

	public function unfold_action()
	{
		$this->validate_interval('manage');
		$item_type = H::POST('item_type');
		$item_id = H::POST('item_id');
		if ($this->model('post')->check_thread_type($item_type) OR
			!$item_info = $this->model('post')->get_post_info_by_id($item_type, $item_id))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['fold'])
		{
			$thread_info = $this->model('post')->get_post_thread_info_by_id($item_type, $item_id);
			if (!$thread_info)
			{
				H::ajax_error((_t('内容不存在')));
			}
			if ($thread_info['uid'] != $this->user_id)
			{
				$this->validate_permission('fold_post');
			}
			else
			{
				$this->validate_permission('fold_post_own_thread');
			}

			set_user_operation_last_time('manage', $this->user_id);

			$this->model('content')->unfold(
				$item_type,
				$item_id,
				$thread_info['thread_type'],
				$thread_info['thread_id'],
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}

}
