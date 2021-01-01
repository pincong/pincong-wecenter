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

	private function validate_title_length()
	{
		$length_min = intval(S::get('title_length_min'));
		$length_max = intval(S::get('title_length_max'));
		$length = iconv_strlen($_POST['title']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_body_length()
	{
		$length_min = intval(S::get('kb_body_length_min'));
		$length_max = intval(S::get('kb_body_length_max'));
		$length = iconv_strlen($_POST['message']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得大于 %s 字', $length_max)));
		}
	}

	private function do_validate()
	{
		$_POST['title'] = trim($_POST['title']);
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入标题')));
		}
		if (!$this->user_info['permission']['kb_manage'])
		{
			$this->validate_title_length();
		}

		$_POST['message'] = trim($_POST['message']);
		if (!$_POST['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入内容')));
		}
		if (!$this->user_info['permission']['kb_manage'])
		{
			$this->validate_body_length();
		}
	}

	public function publish_action()
	{
		if (!$this->user_info['permission']['kb_add'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('没有权限')));
		}

		$this->do_validate();

		if (!check_repeat_submission($this->user_id, $_POST['title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}
		set_repeat_submission_digest($this->user_id, $_POST['title']);

		if ($_POST['anonymous'])
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

		$this->model('kb')->add($_POST['title'], $_POST['message'], $uid, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function modify_action()
	{
		if (!$item_info = $this->model('kb')->get($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id AND $item_info['last_uid'] != $this->user_id AND !$this->user_info['permission']['kb_manage'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('没有权限')));
		}

		$this->do_validate();

		if ($_POST['anonymous'])
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

		$this->model('kb')->edit($item_info['id'], $_POST['title'], $_POST['message'], $uid, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remark_action()
	{
		if (!$item_info = $this->model('kb')->get($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] != $this->user_id AND $item_info['last_uid'] != $this->user_id AND !$this->user_info['permission']['kb_manage'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('没有权限')));
		}

		$this->model('kb')->remark($item_info['id'], $_POST['remarks']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}