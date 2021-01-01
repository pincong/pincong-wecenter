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

class admin_class extends AWS_MODEL
{
    public function fetch_menu_list($select_id)
    {
        $admin_menu = (array)AWS_APP::config()->get('admin_menu');

        if (!$admin_menu)
        {
            return false;
        }

        foreach($admin_menu as $m_id => $menu)
        {
            if ($menu['children'])
            {
                foreach($menu['children'] as $c_id => $c_menu)
                {
                    if ($select_id == $c_menu['id'])
                    {
                        $admin_menu[$m_id]['children'][$c_id]['select'] = true;
                        $admin_menu[$m_id]['select'] = true;
                    }
                }
            }
        }

        return $admin_menu;
    }

    public function set_admin_login($uid)
    {
        AWS_APP::session()->admin_login = AWS_APP::crypt()->encode(json_encode(array(
            'uid' => $uid
        )));
    }

    public function admin_logout()
    {
        if (isset(AWS_APP::session()->admin_login))
        {
            unset(AWS_APP::session()->admin_login);
        }
    }

    public function get_notifications_texts()
    {
/*
        $notifications_texts[] = array(
            'url' => 'url',
            'text' => AWS_APP::lang()->_t('text')
        );

        return $notifications_texts;
*/
    }
}
