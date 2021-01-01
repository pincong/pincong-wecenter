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
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/')
			), 1, null));
		}

		if (!check_http_referer())
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}
	}

	public function process_action()
	{
		if (!$_POST['scrambled_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的用户名和密码')));
		}

		if (!AWS_APP::form()->check_csrf_token($_POST['token'], 'login_next', false))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('页面停留时间过长, 请<a href="%s">刷新页面</a>重试', url_rewrite() . '/login/')));
		}

		// 检查验证码
		if ($this->model('login')->is_captcha_required())
		{
			if ($_POST['captcha_enabled'] == '0')
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请<a href="%s">刷新页面</a>重试', url_rewrite() . '/login/')));
				//H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			}
			if (!AWS_APP::captcha()->is_valid($_POST['captcha'], H::get_cookie('captcha')))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
			}
		}

		$user_info = $this->model('login')->verify($_POST['uid'], $_POST['scrambled_password']);

		if (is_null($user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该账号已经连续多次尝试登录失败, 为了安全起见, 该账号 %s 分钟内禁止登录', S::get('limit_login_attempts_interval'))));
		}
		elseif (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的用户名和密码')));
		}

		if ($user_info['password_version'] < 2)
		{
			if (!$this->model('password')->check_structure($_POST['new_scrambled_password'], $_POST['client_salt']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('登录失败')));
			}
			if (!$this->model('password')->update_password($user_info['uid'], $_POST['new_scrambled_password'], $_POST['client_salt']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('登录失败')));
			}
			$scrambled_password = $_POST['new_scrambled_password'];
		}
		else
		{
			$scrambled_password = $_POST['scrambled_password'];
		}

		AWS_APP::form()->revoke_csrf_token($_POST['token']);

		if ($user_info['forbidden'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => UF::url($user_info)
			), 1, null));
		}

		//$this->model('account')->update_user_last_login($user_info['uid']);

		// 记住我
		if ($_POST['remember_me'])
		{
			$expire = 60 * 60 * 24 * 360;
		}

		$this->model('login')->cookie_logout();
		$this->model('login')->cookie_login($user_info['uid'], $scrambled_password, $expire);

		$url = url_rewrite('/');

		if ($_POST['return_url'] AND is_inside_url($_POST['return_url']))
		{
			$url = $_POST['return_url'];
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => $url
		), 1, null));
	}

}
