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
		HTTP::no_cache_header();
	}

	private function get_user_info($uid, &$user_info)
	{
		if ($uid == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你不能对自己进行此操作')));
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if (!$this->user_info['permission']['ignore_reputation'])
		{
			// 普通用户不能处理比自己声望高的用户
			if (intval($this->user_info['reputation']) <= intval($user_info['reputation']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
		}
	}

	private function get_reason_and_detail($status, &$reason, &$detail, &$log_detail)
	{
		$reason = trim($_POST['reason']);
		$detail = trim($_POST['detail']);
		// TODO: 字数选项
		if (cjk_strlen($reason) > 300 OR cjk_strlen($detail) > 300)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('理由太长')));
		}

		if (!$this->user_info['permission']['is_moderator'])
		{
			if ($status)
			{
				if (!$reason)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写理由')));
				}
			}
			else
			{
				// 取消时是没有选项列表的
				if (!$reason AND !$detail)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写理由')));
				}
			}
		}

		$log_detail = trim($reason . ' ' . $detail);
	}

	public function forbid_user_action()
	{
		if (!$this->user_info['permission']['forbid_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$status = intval($_POST['status']);
		$this->get_reason_and_detail($status, $reason, $detail, $log_detail);

		set_user_operation_last_time('manage', $this->user_id);

		$uid = intval($_POST['uid']);
		$this->get_user_info($uid, $user_info);

		if ($status)
		{
			if ($user_info['forbidden'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已经被封禁')));
			}

			$user_group = $this->model('usergroup')->get_user_group_by_user_info($user_info);
			if ($user_group)
			{
				$banning_type = $user_group['permission']['banning_type'];
			}

			if ($banning_type == 'protected')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('操作失败')));
			}
			elseif ($banning_type == 'permanent')
			{
				$status = 3;
			}
			elseif ($banning_type == 'temporary')
			{
				$status = 4;
			}
			else
			{
				$status = 1;
			}
		}
		else
		{
			if (!$user_info['forbidden'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户没有被封禁')));
			}
		}

		$this->model('user')->forbid_user_by_uid($uid, $status, $this->user_id, $reason, $detail);
		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($uid, $this->user_id, 'forbid_user', $status, $log_detail);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function flag_user_action()
	{
		if (!$this->user_info['permission']['flag_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$status = intval($_POST['status']);
		if (!in_array($status, array(-1, 0, 1, 2, 3)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('操作失败')));
		}

		$this->get_reason_and_detail($status, $reason, $detail, $log_detail);

		set_user_operation_last_time('manage', $this->user_id);

		$uid = intval($_POST['uid']);
		$this->get_user_info($uid, $user_info);

		if ($status > 0 AND !$this->user_info['permission']['is_moderator'])
		{
			$reputation_formal_user = get_setting('reputation_formal_user');
			if (is_numeric($reputation_formal_user) AND $user_info['reputation'] >= $reputation_formal_user)
			{
				if ($user_info['flagged'] == 0)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正式用户请先标记为「观察」')));
				}
				else if ($user_info['flagged'] < 0 AND $this->model('account')->get_user_extra_data($uid)['flagged_by'] == $this->user_id)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('只有第二位管理员可以执行此操作')));
				}
			}
		}

		$this->model('user')->flag_user_by_uid($uid, $status, $this->user_id, $reason, $detail);
		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($uid, $this->user_id, 'flag_user', $status, $log_detail);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function edit_verified_title_action()
	{
		if (!$this->user_info['permission']['edit_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$text = trim($_POST['text']);
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
		), $_POST['uid']);

		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($_POST['uid'], $this->user_id, 'edit_title', 0, $text);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function edit_signature_action()
	{
		if (!$this->user_info['permission']['edit_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$text = trim($_POST['text']);
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
		), $_POST['uid']);

		if (!$this->user_info['permission']['is_moderator'])
		{
			$this->model('user')->insert_admin_log($_POST['uid'], $this->user_id, 'edit_signature', 0, $text);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function change_group_action()
	{
		if (!$this->user_info['permission']['change_user_group'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!check_user_operation_interval('manage', $this->user_id, $this->user_info['permission']['interval_manage']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		set_user_operation_last_time('manage', $this->user_id);

		$uid = intval($_POST['uid']);
		$this->get_user_info($uid, $user_info);

		$input_group_id = intval($_POST['group_id']);
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
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户组不存在')));
		}

		if ($group_id != $user_info['group_id'])
		{
			$this->model('account')->update_user_fields(array(
				'group_id' => ($group_id),
				'user_update_time' => fake_time()
			), $uid);

			if (!$this->user_info['permission']['is_moderator'])
			{
				$this->model('user')->insert_admin_log($uid, $this->user_id, 'change_group', $group_id, $group_name);
			}
		}
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}
