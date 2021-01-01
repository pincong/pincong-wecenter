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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';

		return $rule_action;
	}

	public function setup()
	{
		H::no_cache_header();

		if ($this->user_id)
		{
			H::ajax_location(url_rewrite('/'));
		}

		if (!check_http_referer())
		{
			H::ajax_error((_t('错误的请求')));
		}
	}

	public function process_action()
	{
		$username = H::POST_S('username');
		$client_salt = H::POST('client_salt');
		$scrambled_password = H::POST('scrambled_password');

		if (!$username OR
			!$this->model('password')->check_base64_string($client_salt, 60) OR
			!$this->model('password')->check_structure($scrambled_password))
		{
			H::ajax_error((_t('请输入正确的用户名和密码')));
		}

		$public_key = H::POST('public_key');
		$private_key = H::POST('private_key');

		if (!$this->model('password')->check_base64_string($public_key, 1000) OR
			!$this->model('password')->check_base64_string($private_key, 1000))
		{
			H::ajax_error((_t('密钥无效')));
		}

		if (!AWS_APP::form()->check_csrf_token(H::POST('token'), 'register_next', false))
		{
			H::ajax_error((_t('页面停留时间过长, 请<a href="%s">刷新页面</a>重试', url_rewrite() . '/register/')));
		}

		// 检查验证码
		if ($this->model('register')->is_captcha_required())
		{
			if (!AWS_APP::captcha()->is_valid(H::POST('captcha'), H::get_cookie('captcha')))
			{
				H::ajax_error((_t('请填写正确的验证码')));
			}
		}

		$register_interval = rand_minmax(S::get('register_interval_min'), S::get('register_interval_max'), S::get('register_interval'));
		if (!check_user_operation_interval('register', 0, $register_interval, false))
		{
			H::ajax_error((_t('本站已开启注册频率限制, 请稍后再试')));
		}

		if ($check_result = $this->model('register')->check_username_char($username))
		{
			H::ajax_error($check_result);
		}

		if ($username != H::POST('username') OR
			$this->model('register')->check_username_sensitive_words($username) )
		{
			H::ajax_error((_t('用户名中包含敏感词或系统保留字')));
		}

		if ($this->model('account')->username_exists($username))
		{
			H::ajax_error((_t('用户名已经存在')));
		}

		$uid = $this->model('register')->register($username, $scrambled_password, $client_salt, $public_key, $private_key);
		if (!$uid)
		{
			H::ajax_error((_t('注册失败')));
		}

		AWS_APP::form()->revoke_csrf_token(H::POST('token'));
		set_user_operation_last_time('register', 0);

		$this->model('account')->welcome_message($uid, $username);

		$this->model('login')->logout();

		$this->model('login')->cookie_login($uid, $scrambled_password);

		H::ajax_response(array(
			'next' => url_rewrite('/home/first_login-TRUE')
		));

	}

}
