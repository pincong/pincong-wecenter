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
		H::no_cache_header();

		if ($this->user_id)
		{
			H::redirect('/');
		}
	}

	public function index_action()
	{
		if (S::get('register_type') == 'close')
		{
			H::redirect_msg(_t('本站目前关闭注册'), '/');
		}
		else if (S::get('register_type') == 'custom')
		{
			$register_url = S::get('register_url');
			if (!$register_url)
			{
				$register_url = '/';
			}
			H::redirect($register_url);
		}

		$this->crumb(_t('注册'));

		TPL::assign('token', AWS_APP::form()->create_csrf_token(600, 'register_index'));
		TPL::assign('captcha_required', $this->model('register')->is_captcha_required());

		TPL::output('account/register');
	}

	public function next_action()
	{
		if (!check_http_referer())
		{
			H::redirect_msg(_t('错误的请求'), '/');
		}

		if (!H::POST_I('agree'))
		{
			H::redirect_msg(_t('你必需同意 %s 才能继续', S::get('user_agreement_name')), '/register/');
		}

		if (!AWS_APP::form()->check_csrf_token(H::POST('token'), 'register_index'))
		{
			H::redirect_msg(_t('页面停留时间过长, 请刷新页面重试'), '/register/');
		}

		$captcha_required = $this->model('register')->is_captcha_required();

		// 检查验证码
		if ($captcha_required)
		{
			if (!AWS_APP::captcha()->is_valid(H::POST('captcha'), H::get_cookie('captcha')))
			{
				H::redirect_msg(_t('请填写正确的验证码'), '/register/');
			}
		}

		$this->crumb(_t('注册'));

		TPL::assign('token', AWS_APP::form()->create_csrf_token(600, 'register_next'));
		TPL::assign('captcha_required', $captcha_required);

		TPL::output("account/register_next");
	}

}