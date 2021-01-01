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

		if ($this->user_id)
		{
			HTTP::redirect('/');
		}
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('登录'));

		TPL::import_css('css/register.css');

		TPL::assign('captcha_required', $this->model('login')->is_captcha_required());

		TPL::output("account/login");
	}

	public function next_action()
	{
		$captcha_required = $this->model('login')->is_captcha_required();

		// 检查验证码
		if ($captcha_required)
		{
			if ($_POST['captcha_enabled'] == '0')
			{
				H::redirect_msg(AWS_APP::lang()->_t('请填写验证码'), '/login/');
			}
			if (!AWS_APP::captcha()->is_validate($_POST['captcha']))
			{
				H::redirect_msg(AWS_APP::lang()->_t('请填写正确的验证码'), '/login/');
			}
		}

		if (!$user_info = $this->model('account')->get_user_info_by_username($_POST['username']))
		{
			H::redirect_msg(AWS_APP::lang()->_t('用户名不存在'), '/login/');
		}

		$this->crumb(AWS_APP::lang()->_t('登录'));

		TPL::import_css('css/register.css');

		if (1)
		{
			TPL::import_js('js/md5.js');
		}

		TPL::assign('captcha_required', $captcha_required);
		TPL::assign('user', $user_info);

		TPL::output("account/login_next");
	}

}