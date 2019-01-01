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
		$rule_action['rule_type'] = 'white'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function notifications_action()
	{
		H::ajax_json_output(AWS_APP::RSM(array(
			'inbox_num' => $this->user_info['inbox_unread'],
			'notifications_num' => $this->user_info['notification_unread']
		), '1', null));
	}

}