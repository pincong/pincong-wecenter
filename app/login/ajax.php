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
		HTTP::no_cache_header();
	}

	public function login_process_action()
	{
		if ($this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/')
			), 1, null));
		}

		if (!$_POST['user_name'] OR !$_POST['password'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}

		// 检查验证码
		if ($this->model('login')->is_captcha_required())
		{
			if ($_POST['captcha_enabled'] == '0')
			{
				//H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请刷新页面重试')));
				H::ajax_json_output(AWS_APP::RSM(null, 1, null));
			}
			if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请填写正确的验证码')));
			}
		}


		$user_info = $this->model('login')->check_login($_POST['user_name'], $_POST['password']);

		if (is_null($user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('该账号已经连续多次尝试登录失败, 为了安全起见, 该账号 %s 分钟内禁止登录', get_setting('limit_login_attempts_interval'))));
		}
		elseif (!$user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入正确的帐号或密码')));
		}
		
		{
			if ($user_info['forbidden'])
			{
				//H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('抱歉, 你的账号已经被禁止登录')));
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/people/') . $user_info['url_token']
				), 1, null));
			}

			if ($user_info['flagged'] > 0)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					//'url' => get_js_url('/')
					'url' => get_js_url('/people/') . $user_info['url_token']
				), 1, null));
			}

			// 记住我
			if ($_POST['net_auto_login'])
			{
				$expire = 60 * 60 * 24 * 360;
			}

			//$this->model('account')->update_user_last_login($user_info['uid']);
			$this->model('account')->setcookie_logout();

			$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);

			if ($_POST['return_url'])
			{
				//$url = get_js_url($_POST['return_url']);
				// TODO: 检查 $_POST['return_url']
				$url = get_js_url('/');
			}
			else
			{
				$url = get_js_url('/');
			}

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
	}

}
