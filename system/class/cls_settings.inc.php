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

class S
{
	private static $settings;

	public static function init()
	{
		self::$settings = AWS_APP::model('setting')->get_settings();
	}

	public static function get_all()
	{
		return self::$settings;
	}

	public static function get($varname)
	{
		return self::$settings[$varname];
	}

	public static function get_int($varname)
	{
		return intval(self::$settings[$varname]);
	}

	public static function get_float($varname)
	{
		return floatval(self::$settings[$varname]);
	}

	public static function get_array($varname, $separator = ',')
	{
		return array_map('trim', explode($separator, self::$settings[$varname]));
	}

	/**
	 * 获取全局配置项 key-value pairs
	 *
	 * e.g. "Google google.com\nFacebook facebook.com"
	 * return array("Google" => "google.com", "Facebook" => "facebook.com")
	 *
	 * @param  string
	 * @return mixed
	 */
	public static function get_key_value_pairs($varname, $separator = ',', $allow_empty_separator = false)
	{
		$result = array();

		$rows = explode("\n", self::$settings[$varname]);
		foreach($rows as $row)
		{
			$row = trim($row);
			if (!$row AND $row !== '0')
			{
				continue;
			}

			if (!$separator AND $separator !== '0')
			{
				if ($allow_empty_separator)
				{
					$result[$row] = null;
				}
				continue;
			}

			$pos = strpos($row, $separator);
			if (!$pos)
			{
				if ($allow_empty_separator AND is_bool($pos))
				{
					$result[$row] = null;
				}
				continue;
			}
			else
			{
				$key = substr($row, 0, $pos);
				$value = substr($row, $pos + strlen($separator));
				$result[trim($key)] = trim($value);
			}
		}

		return $result;
	}


	/**
	 * 检查 $content 是否包含 self::$settings[$varname]
	 *
	 * 命中返回 true, 未命中返回 false
	 *
	 * @param  string
	 * @param  string
	 * @param  boolean    true: 可出现在 $content 的任意位置, false: 只能出现在 $content 的开头
	 * @param  boolean
	 * @return boolean
	 */
	public static function content_contains($varname, $content, $any_position = false, $case_sensitive = false)
	{
		if (!$content AND $content !== '0')
		{
			return false;
		}

		$rows = explode("\n", self::$settings[$varname]);
		foreach($rows AS $row)
		{
			$row = trim($row);

			if (!$row AND $row !== '0')
			{
				continue;
			}

			// 正则表达式
			if (substr($row, 0, 1) == '{' AND substr($row, -1, 1) == '}')
			{
				if (preg_match(substr($row, 1, -1), $content))
				{
					return true;
				}

				continue;
			}

			if ($case_sensitive)
			{
				$pos = strpos($content, $row);
			}
			else
			{
				$pos = stripos($content, $row);
			}

			if ($any_position AND $pos > 0)
			{
				return true;
			}

			if ($pos === 0)
			{
				return true;
			}
		}

		return false;
	}

}