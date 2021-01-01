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
	public function setup()
	{
		H::no_cache_header();
	}

	private function get_user_info_and_check_permission($uid, &$user_info)
	{
		if ($uid == $this->user_id)
		{
			H::ajax_error((_t('你不能对自己进行此操作')));
		}

		$user_info = $this->model('account')->get_user_and_group_info_by_uid($uid);

		if (!$user_info)
		{
			H::ajax_error((_t('用户不存在')));
		}

		if (!$this->user_info['permission']['ignore_reputation'])
		{
			// 普通用户不能处理比自己声望高的用户
			if (intval($this->user_info['reputation']) <= intval($user_info['reputation']))
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
		}

		if (!$this->user_info['permission']['is_moderator'])
		{
			// 普通用户不能处理受保护的用户
			if ($user_info['permission']['protected'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
		}
	}

	private function get_reason_and_detail($status, &$reason, &$detail, &$log_detail)
	{
		$reason = H::POST_S('reason');
		$detail = H::POST_S('detail');
		$limit = S::get_int('reason_length_limit');
		if (!$limit)
		{
			$limit = 1000;
		}
		if (strlen($reason) > $limit OR strlen($detail) > $limit)
		{
			H::ajax_error((_t('理由太长')));
		}

		if (!$this->user_info['permission']['is_moderator'])
		{
			if ($status)
			{
				if (!$reason)
				{
					H::ajax_error((_t('请填写理由')));
				}
			}
			else
			{
				// 取消时是没有选项列表的
				if (!$reason AND !$detail)
				{
					H::ajax_error((_t('请填写理由')));
				}
			}
		}

		$log_detail = trim($reason . ' ' . $detail);
	}

	public function forbid_user_action()
	{
		$status = H::POST_I('status');

		if ($status)
		{
			if (!$this->user_info['permission']['forbid_user'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}

			if (!in_array($status, array(1, 2, 3)))
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
		}
		else
		{
			if (!$this->user_info['permission']['unforbid_user'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$this->get_reason_and_detail($status, $reason, $detail, $log_detail);

		set_user_operation_last_time('manage', $this->user_id);

		$uid = H::POST_I('uid');
		$this->get_user_info_and_check_permission($uid, $user_info);

		if ($status == $user_info['forbidden'])
		{
			H::ajax_error((_t('该用户已经处于此状态')));
		}

		if ($status AND !$this->user_info['permission']['is_moderator'])
		{
			if (!$user_info['permission']['informal_user'])
			{
				if (!$user_info['flagged'])
				{
					H::ajax_error((_t('对方是正式用户, 请先标记')));
				}
				else if ($this->model('account')->get_user_extra_data($uid)['flagged_by'] == $this->user_id)
				{
					H::ajax_error((_t('只有第二位管理员可以执行此操作')));
				}
			}
		}

		$this->model('user')->forbid_user_by_uid(
			$uid,
			$status,
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null),
			$reason,
			$detail
		);
		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($uid, $this->user_id, 'forbid_user', $status, $log_detail);
		}

		H::ajax_success();
	}

	public function flag_user_action()
	{
		$status = H::POST_I('status');

		if ($status)
		{
			if (!$this->user_info['permission']['flag_user'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}

			if ($this->user_info['permission']['flagged_ids'])
			{
				$group_ids = array_map('intval', explode(',', $this->user_info['permission']['flagged_ids']));
				if (!in_array($status, $group_ids))
				{
					H::ajax_error((_t('你没有权限进行此操作')));
				}
			}

			if (!$this->model('usergroup')->get_group_id_by_value_flagged($status))
			{
				H::ajax_error((_t('操作失败')));
			}
		}
		else
		{
			if (!$this->user_info['permission']['unflag_user'])
			{
				H::ajax_error((_t('你没有权限进行此操作')));
			}
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$this->get_reason_and_detail($status, $reason, $detail, $log_detail);

		set_user_operation_last_time('manage', $this->user_id);

		$uid = H::POST_I('uid');
		$this->get_user_info_and_check_permission($uid, $user_info);

		if ($status == $user_info['flagged'])
		{
			H::ajax_error((_t('该用户已经处于此状态')));
		}

		if (!$status)
		{
			if ($this->user_info['permission']['flagged_ids'])
			{
				$group_ids = array_map('intval', explode(',', $this->user_info['permission']['flagged_ids']));
				if (!in_array($user_info['flagged'], $group_ids))
				{
					H::ajax_error((_t('你没有权限进行此操作')));
				}
			}
		}

		$this->model('user')->flag_user_by_uid(
			$uid,
			$status,
			(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null),
			$reason,
			$detail
		);
		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($uid, $this->user_id, 'flag_user', $status, $log_detail);
		}

		H::ajax_success();
	}

	public function delete_user_action()
	{
		if (!$this->user_info['permission']['delete_user'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$this->get_reason_and_detail(0, $reason, $detail, $log_detail);

		set_user_operation_last_time('manage', $this->user_id);

		$uid = H::POST_I('uid');
		$this->get_user_info_and_check_permission($uid, $user_info);

		$this->model('user')->delete_user_by_uid($uid, false);
		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($uid, $this->user_id, 'delete_user', 0, $log_detail);
		}

		H::ajax_success();
	}


	public function edit_verified_title_action()
	{
		if (!$this->user_info['permission']['edit_user'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$text = H::POST_S('text');
		if (!$text)
		{
			$text = null;
		}
		else
		{
			$text = htmlspecialchars($text);
		}

		set_user_operation_last_time('manage', $this->user_id);

		$this->model('account')->update_user_fields(array(
			'verified' => $text
		), H::POST('uid'));

		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log(H::POST('uid'), $this->user_id, 'edit_title', 0, $text);
		}

		H::ajax_success();
	}

	public function edit_signature_action()
	{
		if (!$this->user_info['permission']['edit_user'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$text = H::POST_S('text');
		if (!$text)
		{
			$text = null;
		}
		else
		{
			$text = htmlspecialchars($text);
		}

		set_user_operation_last_time('manage', $this->user_id);

		$this->model('account')->update_user_fields(array(
			'signature' => $text
		), H::POST('uid'));

		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log(H::POST('uid'), $this->user_id, 'edit_signature', 0, $text);
		}

		H::ajax_success();
	}

	public function change_group_action()
	{
		if (!$this->user_info['permission']['change_user_group'])
		{
			H::ajax_error((_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		set_user_operation_last_time('manage', $this->user_id);

		$uid = H::POST_I('uid');
		$this->get_user_info_and_check_permission($uid, $user_info);

		$input_group_id = H::POST_I('group_id');
		$group_id = null;

		$user_group_list = $this->model('usergroup')->get_normal_group_list();
		if (!$this->user_info['permission']['is_administrator'])
		{
			foreach ($user_group_list as $key => $val)
			{
				if ($val['type'] != 2 AND $val['group_id'] != 0)
				{
					unset($user_group_list[$key]);
				}
			}
		}

		foreach ($user_group_list as $key => $val)
		{
			if ($input_group_id == $val['group_id'])
			{
				$group_id = $input_group_id;
				$group_name = $val['group_name'];
				break;
			}
		}

		if (!isset($group_id))
		{
			H::ajax_error((_t('用户组不存在')));
		}

		if ($group_id != $user_info['group_id'])
		{
			$this->model('account')->update_user_fields(array(
				'group_id' => ($group_id),
			), $uid);

			if (!$this->user_info['permission']['is_moderator'])
			{
				$this->model('user')->insert_admin_log($uid, $this->user_id, 'change_group', $group_id, $group_name);
			}
		}
		H::ajax_success();
	}

}
