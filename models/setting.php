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

class setting_class extends AWS_MODEL
{
	public function get_settings()
	{
		if ($system_setting = $this->fetch_all('system_setting'))
		{
			foreach ($system_setting as $key => $val)
			{
				if ($val['value'])
				{
					$val['value'] = unserialize($val['value'], array('allowed_classes' => false));
				}

				$settings[$val['varname']] = $val['value'];
			}
		}

		return $settings;
	}

	public function set_vars($vars)
	{
		if (!is_array($vars))
		{
			return false;
		}

		foreach ($vars as $key => $val)
		{
            $key = trim($key);
            // 过滤掉不需要的 $key 如 '_post_type'
            if (substr($key, 0, 1) == "_")
            {
                continue;
            }

            $where = ['varname', 'eq', $key];
            if (!$this->count('system_setting', $where))
            {
                $this->insert('system_setting', array(
                    'value' => serialize($val),
                    'varname' => $key
                ));
            }
            else
            {
                $this->update('system_setting', array(
                    'value' => serialize($val)
                ), $where);
            }
		}

		return true;
	}

	public function get_ui_styles()
	{
		if ($handle = opendir(ROOT_PATH . 'views'))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (substr($file, 0, 1) != '.' AND is_dir(ROOT_PATH . 'views/' . $file))
				{
					$dirs[] = $file;
				}
			}

			closedir($handle);
		}

		$ui_style = array();

		foreach ($dirs as $key => $val)
		{
			$ui_style[] = array(
				'id' => $val,
				'title' => $val
			);
		}

		return $ui_style;
	}
}
