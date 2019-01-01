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

        $length_min = intval(get_setting('username_length_min'));
        $length_max = intval(get_setting('username_length_max'));
        $length = cjk_strlen($user_name);
        if ($length < $length_min OR $length > $length_max)
        {
            return AWS_APP::lang()->_t('用户名字数不符合规则');
        }

        $bytes_min = intval(get_setting('username_bytes_min'));
        $bytes_max = intval(get_setting('username_bytes_max'));
        $bytes = strlen($user_name);
        if ( ($bytes_min AND $bytes < $bytes_min) OR
            ($bytes_max AND $bytes > $bytes_max) )
        {
            return AWS_APP::lang()->_t('用户名长度不符合规则');
        }

        switch(get_setting('username_rule'))
        {
            case 1:
                if (!preg_match('/^[\x{4e00}-\x{9fa5}_a-zA-Z0-9]+$/u', $user_name))
                {
                    return AWS_APP::lang()->_t('用户名只允许出现汉字、字母、数字或下划线');
                }
                break;

            case 2:
                if (!preg_match("/^[a-zA-Z0-9_]+$/i", $user_name))
                {
                    return AWS_APP::lang()->_t('用户名只允许出现字母、数字或下划线');
                }
                break;

            case 3:
                if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $user_name))
                {
                    return AWS_APP::lang()->_t('用户名只允许出现汉字');
                }
                break;

            default:
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