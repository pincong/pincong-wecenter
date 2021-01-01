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
		H::no_cache_header();
	}

	public function user_info_action()
	{
		if ($this->user_id == H::GET('uid'))
		{
			$user_info = $this->user_info;
		}
		else if (!$user_info = $this->model('account')->get_user_info_by_uid(H::GET('uid')))
		{
			H::ajax_json_output(array(
				'uid' => null
			));
		}

		if ($this->user_id != $user_info['uid'])
		{
			$user_follow_check = $this->model('follow')->user_follow_check($this->user_id, $user_info['uid']);

			$pm_disabled = !$this->model('message')->test_permission($this->user_info, $user_info);
		}
		else
		{
			$pm_disabled = true;
		}

		H::ajax_json_output(array(
			'reputation' => UF::reputation($user_info),
			'agree_count' => $user_info['agree_count'],
			'type' => 'people',
			'uid' => $user_info['uid'],
			'user_name' => $user_info['user_name'],
			'avatar_file' => UF::avatar($user_info, 'mid'),
			'signature' => UF::signature($user_info),
			'focus' => ($user_follow_check ? true : false),
			'is_me' => (($this->user_id == $user_info['uid']) ? true : false),
			'url' => UF::url($user_info),
			'verified' => $user_info['verified'],
			'fans_count' => $user_info['fans_count'],
			'pm_disabled' => $pm_disabled,
		));
	}

}