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

class register_class extends AWS_MODEL
{
    public function check_username_char($user_name)
    {
        if (is_digits($user_name))
        {
            return AWS_APP::lang()->_t('用户名不能为纯数字');
        }

        if (strstr($user_name, '-') OR strstr($user_name, '.') OR strstr($user_name, '/') OR strstr($user_name, '%') OR strstr($user_name, '__'))
        {
            return AWS_APP::lang()->_t('用户名不能包含 - / . % 与连续的下划线');
        }

        $length = strlen(convert_encoding($user_name, 'UTF-8', 'GB2312'));

        $length_min = intval(get_setting('username_length_min'));
        $length_max = intval(get_setting('username_length_max'));

        if ($length < $length_min || $length > $length_max)
        {
            $flag = true;
        }

        switch(get_setting('username_rule'))
        {
            default:

            break;

            case 1:
                if (!preg_match('/^[\x{4e00}-\x{9fa5}_a-zA-Z0-9]+$/u', $user_name) OR $flag)
                {
                    return AWS_APP::lang()->_t('请输入大于 %s 字节的用户名, 允许汉字、字母与数字', ($length_min . ' - ' . $length_max));
                }
            break;

            case 2:
                if (!preg_match("/^[a-zA-Z0-9_]+$/i", $user_name) OR $flag)
                {
                    return AWS_APP::lang()->_t('请输入 %s 个字母、数字或下划线', ($length_min . ' - ' . $length_max));
                }
            break;

            case 3:
                if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $user_name) OR $flag)
                {
                    return AWS_APP::lang()->_t('请输入 %s 个汉字', (ceil($length_min / 2) . ' - ' . floor($length_max / 2)));
                }
            break;
        }

        return false;
    }

    /**
     * 检查用户名中是否包含敏感词或用户信息保留字
     *
     * @param string
     * @return boolean
     */
    public function check_username_sensitive_words($user_name)
    {
        if (H::sensitive_word_exists($user_name))
        {
            return true;
        }

        if (!get_setting('censoruser'))
        {
            return false;
        }

        if ($censorusers = explode("\n", get_setting('censoruser')))
        {
            foreach ($censorusers as $name)
            {
                if (!$name = trim($name))
                {
                    continue;
                }

                if (preg_match('/(' . $name . ')/is', $user_name))
                {
                    return true;
                }
            }
        }

        return false;
    }

}