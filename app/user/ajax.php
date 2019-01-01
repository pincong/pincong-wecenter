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
		if ($status)
		{
			$reason = trim($_POST['reason']);
			if (!$reason)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写理由')));
			}
			// TODO: 字数选项
			if (cjk_strlen($reason) > 200)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('理由太长')));
			}
		}

		set_user_operation_last_time('manage', $this->user_id);

		$uid = intval($_POST['uid']);
		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($this->user_info['group_id'] != 1 AND $this->user_info['group_id'] != 2 AND $this->user_info['group_id'] != 3)
		{
			if ($user_info['group_id'] != 4 OR intval($this->user_info['reputation']) <= intval($user_info['reputation']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
		}

		if ($status)
		{
			if ($user_info['forbidden'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该用户已经被封禁')));
			}

			$user_group = $this->model('account')->get_user_group_by_user_info($user_info);
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

		$this->model('user')->forbid_user_by_uid($uid, $status, $this->user_id, $reason);

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
		if ($status)
		{
			$reason = trim($_POST['reason']);
			if (!$reason)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写理由')));
			}
			// TODO: 字数选项
			if (cjk_strlen($reason) > 200)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('理由太长')));
			}
		}

		set_user_operation_last_time('manage', $this->user_id);

		$uid = intval($_POST['uid']);
		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($this->user_info['group_id'] != 1 AND $this->user_info['group_id'] != 2 AND $this->user_info['group_id'] != 3)
		{
			if ($user_info['group_id'] != 4 OR intval($this->user_info['reputation']) <= intval($user_info['reputation']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
			}
		}

		$this->model('user')->flag_user_by_uid($uid, $status, $this->user_id, $reason);

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

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}
