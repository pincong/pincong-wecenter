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
	public function setup()
	{
		$this->crumb(AWS_APP::lang()->_t('设置'));

		TPL::import_css('css/user-setting.css');
	}

	public function index_action()
	{
		$this->crumb(AWS_APP::lang()->_t('基本资料'));

		TPL::output('profile/index');
	}

	public function privacy_action()
	{
		$this->crumb(AWS_APP::lang()->_t('隐私/提醒'));

		TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		TPL::assign('notify_actions', $this->model('notification')->notify_action_details);

		TPL::output('profile/privacy');
	}

	public function security_action()
	{
		$this->crumb(AWS_APP::lang()->_t('安全设置'));

		TPL::output('profile/security');
	}

	public function security1_action()
	{
		$this->crumb(AWS_APP::lang()->_t('安全设置'));

		TPL::output('profile/security1');
	}

}