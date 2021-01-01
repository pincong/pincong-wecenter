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
		H::no_cache_header();

		if (!check_http_referer())
		{
			H::ajax_error((_t('错误的请求')));
		}
	}

	public function change_password_action()
	{
		$scrambled_password = H::POST('scrambled_password');
		$new_scrambled_password = H::POST('new_scrambled_password');
		$new_client_salt = H::POST('new_client_salt');

		if (!$scrambled_password OR
			!$this->model('password')->check_base64_string($new_client_salt, 60) OR
			!$this->model('password')->check_structure($new_scrambled_password))
		{
			H::ajax_error((_t('请输入正确的密码')));
		}

		$new_public_key = H::POST('new_public_key');
		$new_private_key = H::POST('new_private_key');

		if (!$this->model('password')->check_base64_string($new_public_key, 1000) OR
			!$this->model('password')->check_base64_string($new_private_key, 1000))
		{
			H::ajax_error((_t('密钥无效')));
		}

		if (!AWS_APP::form()->check_csrf_token(H::POST('token'), 'account_change_password', false))
		{
			H::ajax_error((_t('页面停留时间过长, 请刷新页面重试')));
		}

		if ($this->model('password')->change_password($this->user_id, $scrambled_password, $new_scrambled_password, $new_client_salt, $new_public_key, $new_private_key))
		{
			AWS_APP::form()->revoke_csrf_token(H::POST('token'));

			// 记住我
			if (H::POST_I('remember_me'))
			{
				$expire = 60 * 60 * 24 * 360;
			}

			$this->model('login')->cookie_logout();
			$this->model('login')->cookie_login($this->user_id, $new_scrambled_password, $expire);

			H::ajax_response(array(
				'next' => url_rewrite('/account/password_updated/')
			));
		}
		else
		{
			H::ajax_error((_t('请输入正确的密码')));
		}
	}

}
