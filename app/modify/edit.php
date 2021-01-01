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

	public function answer_action()
	{
		$id = H::GET_I('id');
		if (!$id)
		{
			H::error_403();
		}
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('question_reply', $id))
		{
			H::error_403();
		}

		if (!can_edit_post($reply_info['uid'], $this->user_info))
		{
			TPL::assign('dialog_message', _t('你没有权限编辑此内容'));
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
		$id = H::GET_I('id');
		if (!$id)
		{
			H::error_403();
		}
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('article_reply', $id))
		{
			H::error_403();
		}

		if (!can_edit_post($reply_info['uid'], $this->user_info))
		{
			TPL::assign('dialog_message', _t('你没有权限编辑此内容'));
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
		$id = H::GET_I('id');
		if (!$id)
		{
			H::error_403();
		}
		if (!$reply_info = $this->model('post')->get_reply_info_by_id('video_reply', $id))
		{
			H::error_403();
		}

		if (!can_edit_post($reply_info['uid'], $this->user_info))
		{
			TPL::assign('dialog_message', _t('你没有权限编辑此内容'));
			TPL::output("dialog/alert_template");
		}
		else
		{
			TPL::assign('reply_info', $reply_info);
			TPL::output("modify/edit_video_comment_template");
		}
	}

}
