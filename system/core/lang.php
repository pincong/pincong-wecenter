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

class core_lang
{
	private $messages = [];
	private $lang = null;

	public function __construct()
	{
		if (defined('G_SYSTEM_LANG') AND !!G_SYSTEM_LANG)
		{
			$this->init_language(G_SYSTEM_LANG);
			return;
		}

		$preferred = $this->parse_accept_language();
		foreach ($preferred as $lang)
		{
			if ($this->init_language($lang))
			{
				return;
			}
		}

		if (defined('G_DEFAULT_LANG') AND !!G_DEFAULT_LANG)
		{
			$this->init_language(G_DEFAULT_LANG);
			return;
		}
	}

	private function parse_accept_language($limit = 10)
	{
		$result = [];

		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
		if (!$accept_language)
		{
			return $result;
		}

		$i = 0;
		$locales = explode(',', $accept_language, $limit);
		foreach ($locales as $locale)
		{
			$i++;
			if (!$locale)
			{
				continue;
			}
			if ($i == $limit)
			{
				$locale = explode(',', $locale, 2)[0]; // 丢弃超出 $limit
			}
			$locale = explode(';', $locale, 2)[0]; // 偷懒不处理 q=
			$locale = trim($locale);
			if (!$locale)
			{
				continue;
			}
			$result[] = $locale;
		}

		return $result;
	}

	private function init_language($lang)
	{
		if (!is_string($lang))
		{
			return false;
		}

		$language_file = ROOT_PATH . 'language/' . $lang . '.php';
		if (!file_exists($language_file))
		{
			return false;
		}

		require $language_file;
		if (!is_array($language))
		{
			return false;
		}

		$this->messages = $language;
		$this->lang = $lang;
		return true;
	}

	public function get_language()
	{
		return $this->lang;
	}

	public function translate($string, $replace = null)
	{
		$search = '%s';

		if (is_array($replace))
		{
			$search = array();

			for ($i=0; $i<count($replace); $i++)
			{
				$search[] = '%s' . $i;
			};
		}

		if ($translate = $this->messages[trim($string)] ?? null)
		{
			if (isset($replace))
			{
				$translate = str_replace($search, $replace, $translate);
			}

			return $translate;
		}
		else
		{
			if (isset($replace))
			{
				$string = str_replace($search, $replace, $string);
			}

			return $string;
		}
	}

	public function _t($string, $replace = null)
	{
		return $this->translate($string, $replace);
	}

}