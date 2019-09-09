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
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'user_info'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function user_info_action()
	{
		if ($this->user_id == $_GET['uid'])
		{
			$user_info = $this->user_info;
		}
		else if (!$user_info = $this->model('account')->get_user_info_by_uid($_GET['uid']))
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}

		if ($this->user_id != $user_info['uid'])
		{
			$user_follow_check = $this->model('follow')->user_follow_check($this->user_id, $user_info['uid']);
		}

		H::ajax_json_output(array(
			'reputation' => $user_info['reputation'],
			'agree_count' => $user_info['agree_count'],
			'type' => 'people',
			'uid' => $user_info['uid'],
			'user_name' => $user_info['user_name'],
			'avatar_file' => UF::avatar($user_info, 'mid'),
			'signature' => $user_info['signature'],
			'focus' => ($user_follow_check ? true : false),
			'is_me' => (($this->user_id == $user_info['uid']) ? true : false),
			'url' => get_js_url('/people/' . $user_info['url_token']),
			'verified' => $user_info['verified'],
			'fans_count' => $user_info['fans_count']
		));
	}

}