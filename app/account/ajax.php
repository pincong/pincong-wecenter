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

		if (!check_http_referer())
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}
	}

	public function change_password_action()
	{
		if (!$_POST['scrambled_password'] OR
			!$this->model('password')->check_structure($_POST['new_scrambled_password'], $_POST['client_salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的密码')));
		}

		if ($this->model('password')->change_password($this->user_id, $_POST['scrambled_password'], $_POST['new_scrambled_password'], $_POST['client_salt']))
		{
			// 记住我
			if ($_POST['remember_me'])
			{
				$expire = 60 * 60 * 24 * 360;
			}

			$this->model('login')->cookie_logout();
			$this->model('login')->cookie_login($this->user_id, $_POST['new_scrambled_password'], $expire);

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/account/password_updated/')
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的密码')));
		}
	}

}
