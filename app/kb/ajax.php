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

	private function validate_title_length($title)
	{
		$length_min = S::get_int('title_length_min');
		$length_max = S::get_int('title_length_max');
		$length = iconv_strlen($title);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_error((_t('标题字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_error((_t('标题字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_body_length($message)
	{
		$length_min = S::get_int('kb_body_length_min');
		$length_max = S::get_int('kb_body_length_max');
		$length = iconv_strlen($message);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_error((_t('正文字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_error((_t('正文字数不得大于 %s 字', $length_max)));
		}
	}

	private function do_validate(&$title, &$message)
	{
		if (!$title = H::POST_S('title'))
		{
			H::ajax_error((_t('请输入标题')));
		}
		if (!$this->user_info['permission']['kb_manage'])
		{
			$this->validate_title_length($title);
		}

		if (!$message = H::POST_S('message'))
		{
			H::ajax_error((_t('请输入内容')));
		}
		if (!$this->user_info['permission']['kb_manage'])
		{
			$this->validate_body_length($message);
		}
	}

	public function publish_action()
	{
		if (!$this->user_info['permission']['kb_add'])
		{
			H::ajax_error((_t('没有权限')));
		}

		$this->do_validate($title, $message);

		if (!check_repeat_submission($this->user_id, $title))
		{
			H::ajax_error((_t('请不要重复提交')));
		}
		set_repeat_submission_digest($this->user_id, $title);

		if (H::POST_I('anonymous'))
		{
			if (!$uid = $this->model('anonymous')->get_anonymous_uid($this->user_info))
			{
				$uid = $this->user_id;
			}
		}
		else
		{
			$uid = $this->user_id;
		}

		$this->model('kb')->add($title, $message, $uid, $this->user_id);

		H::ajax_success();
	}

	public function modify_action()
	{
		if (!$item_info = $this->model('kb')->get(H::GET('id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id AND $item_info['last_uid'] != $this->user_id AND !$this->user_info['permission']['kb_manage'])
		{
			H::ajax_error((_t('没有权限')));
		}

		$this->do_validate($title, $message);

		if (H::POST_I('anonymous'))
		{
			if (!$uid = $this->model('anonymous')->get_anonymous_uid($this->user_info))
			{
				$uid = $item_info['uid'];
			}
		}
		else
		{
			$uid = $item_info['uid'];
		}

		$this->model('kb')->edit($item_info['id'], $title, $message, $uid, $this->user_id);

		H::ajax_success();
	}

	public function remark_action()
	{
		if (!$item_info = $this->model('kb')->get(H::GET('id')))
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id AND $item_info['last_uid'] != $this->user_id AND !$this->user_info['permission']['kb_manage'])
		{
			H::ajax_error((_t('没有权限')));
		}

		$this->model('kb')->remark($item_info['id'], H::POST_S('remarks'));

		H::ajax_success();
	}

}