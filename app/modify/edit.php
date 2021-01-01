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

class edit extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['redirect'] = false; // 不跳转到登录页面直接输出403
		return $rule_action;
	}

	private function check_permission($post_uid)
	{
		if ($post_uid != $this->user_id AND !$this->user_info['permission']['edit_any_post'])
		{
			if (!$this->user_info['permission']['edit_specific_post'] OR !in_array($post_uid, get_setting_array('specific_post_uids')))
			{
				return false;
			}
		}
		return true;
	}

	public function answer_action()
	{
		$id = intval($_GET['id']);
		if (!$id)
		{
			HTTP::error_403();
		}
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('answer', $id))
		{
			HTTP::error_403();
		}

		if (!$this->check_permission($reply_info['uid']))
		{
			TPL::assign('dialog_message', AWS_APP::lang()->_t('你没有权限编辑此内容'));
			TPL::output("dialog/alert_template");
		}
		else
		{
			TPL::assign('reply_info', $reply_info);
			TPL::output("modify/edit_answer_template");
		}
	}

	public function article_comment_action()
	{
		$id = intval($_GET['id']);
		if (!$id)
		{
			HTTP::error_403();
		}
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('article_comment', $id))
		{
			HTTP::error_403();
		}

		if (!$this->check_permission($reply_info['uid']))
		{
			TPL::assign('dialog_message', AWS_APP::lang()->_t('你没有权限编辑此内容'));
			TPL::output("dialog/alert_template");
		}
		else
		{
			TPL::assign('reply_info', $reply_info);
			TPL::output("modify/edit_article_comment_template");
		}
	}

	public function video_comment_action()
	{
		$id = intval($_GET['id']);
		if (!$id)
		{
			HTTP::error_403();
		}
		if (!$reply_info = $this->model('content')->get_reply_info_by_id('video_comment', $id))
		{
			HTTP::error_403();
		}

		if (!$this->check_permission($reply_info['uid']))
		{
			TPL::assign('dialog_message', AWS_APP::lang()->_t('你没有权限编辑此内容'));
			TPL::output("dialog/alert_template");
		}
		else
		{
			TPL::assign('reply_info', $reply_info);
			TPL::output("modify/edit_video_comment_template");
		}
	}

}
