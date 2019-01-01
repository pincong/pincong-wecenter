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

	public function send_action()
	{
		if (!$this->user_info['permission']['send_pm'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能发送私信')));
		}

		$message = $_POST['message'];
		if (trim($message) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入私信内容')));
		}

		// TODO: 在管理后台添加字数选项
		if (cjk_strlen($message) > 500)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('私信字数不得多于 500 字')));
		}

		if (!$recipient_user = $this->model('account')->get_user_info_by_username($_POST['recipient']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('接收私信的用户不存在')));
		}

		if ($recipient_user['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能给自己发私信')));
		}

		if ($recipient_user['forbidden'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方的账号已经被禁止登录')));
		}

		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			$recipient_user_group = $this->model('account')->get_user_group_by_user_info($recipient_user);
			if (!$recipient_user_group['permission']['receive_pm'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方的等级还不能接收私信')));
			}
		}

		if (!$this->user_info['permission']['is_administrator'])
		{
			$inbox_recv = $recipient_user['inbox_recv'];
			if ($inbox_recv != 1 AND $inbox_recv != 2 AND $inbox_recv != 3)
			{
				$inbox_recv = intval(get_setting('default_inbox_recv'));
			}

			if ($inbox_recv == 2) // 2为拒绝任何人
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了拒绝任何人给 Ta 发送私信')));
			}

			else if ($inbox_recv != 3) // 3为任何人
			{
				if (!$this->model('follow')->user_follow_check($recipient_user['uid'], $this->user_id))
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了只有 Ta 关注的人才能给 Ta 发送私信')));
				}
			}

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