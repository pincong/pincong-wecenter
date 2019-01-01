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

    public function notifications_crond()
    {
        $admin_notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$admin_notifications)
        {
            $admin_notifications = get_setting('admin_notifications');
        }

        $admin_notifications = array(
                                // 注册审核
                                'register_approval' => $this->count('users', 'group_id = 3')
                            );


        AWS_APP::cache()->set('admin_notifications', $admin_notifications, 1800);

        return $this->model('setting')->set_vars(array('admin_notifications' => $admin_notifications));
    }

    public function get_notifications_texts()
    {
        $notifications = AWS_APP::cache()->get('admin_notifications');

        if (!$notifications)
        {
            $notifications = get_setting('admin_notifications');
        }

        if (!$notifications)
        {
            return false;
        }

        if (get_setting('register_valid_type') == 'approval' AND $notifications['register_approval'])
        {
            $notifications_texts[] = array(
                                            'url' => 'admin/user/register_approval_list/',
                                            'text' => AWS_APP::lang()->_t('有 %s 个新用户待审核', $notifications['register_approval'])
                                        );
        }

        return $notifications_texts;
    }
}
