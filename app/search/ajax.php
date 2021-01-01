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
	public function setup()
	{
		H::no_cache_header();
	}

	public function search_action()
	{
		$limit = H::GET_I('limit');
		if (!$limit OR $limit > 20)
		{
			$limit = 20;
		}
		$result = $this->model('search')->search(iconv_substr(H::GET_S('q'), 0, 64), H::GET('type'), 1, $limit);

		if (!$result)
		{
			$result = array();
		}

		H::ajax_json_output($result);
	}
}