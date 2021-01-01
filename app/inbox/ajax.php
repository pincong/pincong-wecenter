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

	private function validate_user_pm_settings(&$recipient_user, $sender_uid)
	{
		$inbox_recv = $recipient_user['inbox_recv'];
		if ($inbox_recv != 1 AND $inbox_recv != 2 AND $inbox_recv != 3)
		{
			$inbox_recv = intval(get_setting('default_inbox_recv'));
		}

		if ($inbox_recv == 2) // 2为拒绝任何人
		{
			if ($this->user_id == $sender_uid)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了拒绝接收任何人的私信')));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你设置了拒绝接收任何人的私信')));
			}
		}
		else if ($inbox_recv == 3) // 3为任何人
		{
			return;
		}

		if (!$this->model('follow')->user_follow_check($recipient_user['uid'], $sender_uid))
		{
			if ($this->user_id == $sender_uid)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方未关注你, 你无法给 Ta 发送私信')));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你未关注对方, Ta 无法给你发送私信')));
			}
		}
	}

	public function send_action()
	{
		$message = $_POST['message'];
		if (trim($message) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入私信内容')));
		}

		$length_limit = get_setting('pm_length_limit');
		if (cjk_strlen($message) > $length_limit)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('私信字数不得多于 %s 字', $length_limit)));
		}

		if (!$recipient_user = $this->model('account')->get_user_info_by_username($_POST['recipient']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('接收私信的用户不存在')));
		}

		if ($recipient_user['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能给自己发私信')));
		}

		if (!$this->user_info['permission']['dispatch_pm']) // 对普通用户进行严格的权限检查
		{
			if ($recipient_user['forbidden'] OR $recipient_user['flagged'] > 0)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方的账号已经被禁止登录')));
			}

			$test_result = $this->model('message')->test_permission($this->user_info, $recipient_user);
			if ($test_result === false)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能给这位用户发送私信')));
			}
			else if ($test_result === 0)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('这位用户的声望还不能接收你的私信')));
			}

			$this->validate_user_pm_settings($recipient_user, $this->user_id);
			$this->validate_user_pm_settings($this->user_info, $recipient_user['uid']);
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		$this->model('message')->send_message($this->user_id, $recipient_user['uid'], $message);

		if ($_POST['dialog_id'])
		{
			$rsm = array(
				'url' => get_js_url('/inbox/read/' . intval($_POST['dialog_id']))
			);
		}
		else
		{
			$rsm = array(
				'url' => get_js_url('/inbox/')
			);
		}

		H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
	}
}