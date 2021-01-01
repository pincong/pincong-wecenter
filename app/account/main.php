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

class main extends AWS_CONTROLLER
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

	public function index_action()
	{
		HTTP::redirect('/account/setting/');
	}

	public function captcha_action()
	{
		AWS_APP::captcha()->generate();
	}

	public function logout_action($return_url = null)
	{
		if ($_GET['return_url'])
		{
			$url = strip_tags(urldecode($_GET['return_url']));
		}
		else if (! $return_url)
		{
			$url = '/';
		}
		else
		{
			$url = $return_url;
		}

		if ($_GET['key'] != md5(session_id()))
		{
			H::redirect_msg(AWS_APP::lang()->_t('正在准备退出, 请稍候...'), '/account/logout/?return_url=' . urlencode($url) . '&key=' . md5(session_id()));
		}

		$this->model('account')->logout();

		$this->model('admin')->admin_logout();

		{
			HTTP::redirect($url);
		}
	}

	public function login_action()
	{
		if ($this->user_id)
		{
			HTTP::redirect('/');
		}

		$this->crumb(AWS_APP::lang()->_t('登录'));

		TPL::import_css('css/register.css');

		if ($_GET['url'])
		{
			$return_url = '/'; //htmlspecialchars(base64_decode($_GET['url']));
		}
		else
		{
			$return_url = '/'; //htmlspecialchars($_SERVER['HTTP_REFERER']);
		}

		TPL::assign('captcha_required', $this->model('login')->is_captcha_required());
		TPL::assign('return_url', $return_url);

		TPL::output("account/login");
	}

	public function register_action()
	{
		if ($this->user_id)
		{
			HTTP::redirect('/');
		}

		if (get_setting('register_type') == 'close')
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站目前关闭注册'), '/');
		}
		else if (get_setting('register_type') == 'invite' AND !$_GET['icode'])
		{
			H::redirect_msg(AWS_APP::lang()->_t('本站只接受邀请注册'), '/');
		}

		if ($_GET['icode'])
		{
			{
				H::redirect_msg(AWS_APP::lang()->_t('邀请码无效或已经使用, 请使用新的邀请码'), '/');
			}
		}

		$this->crumb(AWS_APP::lang()->_t('注册'));

		TPL::import_css('css/register.css');

		TPL::output('account/register');
	}

}