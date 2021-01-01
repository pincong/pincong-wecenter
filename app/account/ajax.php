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

	public function modify_password_action()
	{
		if (!$_POST['old_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入当前密码')));
		}

		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入相同的确认密码')));
		}

		if (strlen($_POST['password']) < 6)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('密码长度不符合规则')));
		}

		if ($this->model('password')->update_user_password($_POST['password'], $this->user_id, $_POST['old_password'], $this->user_info['salt']))
		{
			$this->model('login')->logout();
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/account/password_updated/')
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入正确的当前密码')));
		}
	}

}
