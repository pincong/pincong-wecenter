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
	private $themes;
	private $current_theme;

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

	public function get_current_theme()
	{
		if (!isset($this->current_theme))
		{
			$id = H::get_cookie('theme');
			if (!$id)
			{
				$this->current_theme = $this->get_default_theme();
			}
			else
			{
				$this->current_theme = $this->themes[$id] ?? $this->get_default_theme();
			}
		}
		return $this->current_theme;
	}

	private function get_default_theme()
	{
		$id = S::get('default_theme');
		if (!$id)
		{
			$id = 'default';
		}
		return $this->themes[$id] ?? ($this->themes['default'] ?? []);
	}
}
