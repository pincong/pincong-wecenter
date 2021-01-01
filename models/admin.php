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

    public function admin_login()
    {
        AWS_APP::auth()->admin_authorize();
    }

    public function admin_logout()
    {
        AWS_APP::auth()->wipe_token();
    }

    public function get_notifications_texts()
    {
/*
        $notifications_texts[] = array(
            'url' => 'url',
            'text' => 'text'
        );

        return $notifications_texts;
*/
    }
}
