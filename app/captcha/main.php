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
	}

	public function index_action()
	{
		$word = AWS_APP::captcha()->generateWord();
		$token = AWS_APP::captcha()->generateToken($word, 600);
		H::set_cookie('captcha', $token);
		echo AWS_APP::captcha()->generateImage($word);
		die;
	}
}