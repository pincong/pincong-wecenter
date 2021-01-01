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

	public function img_action()
	{
		if (!H::GET('id'))
		{
			die;
		}
		$url = safe_base64_decode(H::GET('id'));
		if (!$url OR !content_url_whitelist_check($url))
		{
			die;
		}
		TPL::assign('url', safe_text($url));
		TPL::output('url/img');
	}

	public function link_action()
	{
		if (!H::GET('id'))
		{
			die;
		}
		$url = safe_base64_decode(H::GET('id'));
		if (!$url OR is_inside_url($url))
		{
			die;
		}
		TPL::assign('url', safe_text($url));
		TPL::output('url/link');
	}
}