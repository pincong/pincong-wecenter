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

class core_theme
{
	private $themes = [];

	public function __construct()
	{
		$this->themes = load_class('core_config')->get('theme')->themes;
		foreach($this->themes as $key => &$val)
		{
			if ($key == 'default')
			{
				$val['css'] = '';
			}
			else
			{
				$val['css'] = G_STATIC_URL . '/themes/' . $key . '.css';
			}
		}
		unset($val);
	}

	public function list_themes()
	{
		return $this->themes;
	}

	public function current_theme()
	{
		$id = H::get_cookie('theme', 'default');
		return $this->themes[$id] ?? ($this->themes['default'] ?? []);
	}
}
