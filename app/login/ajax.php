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
		if (!H::POST('scrambled_password'))
		{
			H::ajax_error((_t('请输入正确的用户名和密码')));
		}

		if (!AWS_APP::form()->check_csrf_token(H::POST('token'), 'login_next', false))
		{
			H::ajax_error((_t('页面停留时间过长, 请<a href="%s">刷新页面</a>重试', url_rewrite() . '/login/')));
		}

		// 检查验证码
		if ($this->model('login')->is_captcha_required())
		{
			if (H::POST('captcha_enabled') == '0')
			{
				H::ajax_error((_t('请<a href="%s">刷新页面</a>重试', url_rewrite() . '/login/')));
			}
			if (!AWS_APP::captcha()->is_valid(H::POST('captcha'), H::get_cookie('captcha')))
			{
				H::ajax_error((_t('请填写正确的验证码')));
			}
		}

		$user_info = $this->model('login')->verify(H::POST('uid'), H::POST('scrambled_password'));

		if (is_null($user_info))
		{
			H::ajax_error((_t('该账号已经连续多次尝试登录失败, 为了安全起见, 该账号 %s 分钟内禁止登录', S::get('limit_login_attempts_interval'))));
		}
		elseif (!$user_info)
		{
			H::ajax_error((_t('请输入正确的用户名和密码')));
		}

		if ($user_info['password_version'] < 3)
		{
			if (!$this->model('password')->check_base64_string(H::POST('new_client_salt'), 60) OR
				!$this->model('password')->check_structure(H::POST('new_scrambled_password')))
			{
				H::ajax_error((_t('登录失败')));
			}

			$public_key = H::POST('new_public_key');
			$private_key = H::POST('new_private_key');

			if (!$this->model('password')->check_base64_string($public_key, 1000) OR
				!$this->model('password')->check_base64_string($private_key, 1000))
			{
				H::ajax_error((_t('密钥无效')));
			}

			if (!$this->model('password')->update_password($user_info['uid'], H::POST('new_scrambled_password'), H::POST('new_client_salt'), $public_key, $private_key))
			{
				H::ajax_error((_t('登录失败')));
			}
			$scrambled_password = H::POST('new_scrambled_password');
		}
		else
		{
			$private_key = $user_info['private_key'];

			$scrambled_password = H::POST('scrambled_password');
		}

		AWS_APP::form()->revoke_csrf_token(H::POST('token'));

		if ($user_info['forbidden'])
		{
			H::ajax_location(UF::url($user_info));
		}

		// 记住我
		if (H::POST_I('remember_me'))
		{
			$expire = 60 * 60 * 24 * 360;
		}

		$this->model('login')->cookie_logout();
		$this->model('login')->cookie_login($user_info['uid'], $scrambled_password, $expire);

		$url = url_rewrite('/');

		if ($return_url = H::POST_S('return_url') AND is_inside_url($return_url))
		{
			$url = $return_url;
		}

		H::ajax_response(array(
			'next' => $url,
			'private_key' => $private_key
		));
	}

}
