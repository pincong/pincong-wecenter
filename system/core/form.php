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

class core_form
{
	private $secret;

	public function __construct()
	{
		$this->secret = AWS_APP::token()->new_secret('form_csrf_passphrase_' . G_COOKIE_HASH_KEY);
	}

	public function create_csrf_token($expire)
	{
		return AWS_APP::token()->create(time(), $expire, $this->secret);
	}

	public function check_csrf_token($token)
	{
		return AWS_APP::token()->check($token, $this->secret);
	}
}
