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

class crond_class extends AWS_MODEL
{
    public function start()
    {
        if (!AWS_APP::cache()->get('crond_timer_half_minute'))
        {
            $call_actions[] = 'half_minute';

            AWS_APP::cache()->set('crond_timer_half_minute', time(), 30, 'crond');
        }

        if (!AWS_APP::cache()->get('crond_timer_minute'))
        {
            $call_actions[] = 'minute';

            AWS_APP::cache()->set('crond_timer_minute', time(), 60, 'crond');
        }

        if (!AWS_APP::cache()->get('crond_timer_five_minutes'))
        {
            $call_actions[] = 'five_minutes';

            AWS_APP::cache()->set('crond_timer_five_minutes', time(), 300, 'crond');
        }

        if (!AWS_APP::cache()->get('crond_timer_ten_minutes'))
        {
            $call_actions[] = 'ten_minutes';

            AWS_APP::cache()->set('crond_timer_ten_minutes', time(), 600, 'crond');
        }

        if (gmdate('YW', AWS_APP::cache()->get('crond_timer_week')) != gmdate('YW', time()))
        {
            $call_actions[] = 'week';

            AWS_APP::cache()->set('crond_timer_week', time(), 259200, 'crond');
        }
        else if (gmdate('Y-m-d', AWS_APP::cache()->get('crond_timer_day')) != gmdate('Y-m-d', time()))
        {
            $call_actions[] = 'day';

            AWS_APP::cache()->set('crond_timer_day', time(), 86400, 'crond');
        }
        else if (!AWS_APP::cache()->get('crond_timer_hour'))
        {
            $call_actions[] = 'hour';

            AWS_APP::cache()->set('crond_timer_hour', time(), 3600, 'crond');
        }
        else if (!AWS_APP::cache()->get('crond_timer_half_hour'))
        {
            $call_actions[] = 'half_hour';

            AWS_APP::cache()->set('crond_timer_half_hour', time(), 1800, 'crond');
        }

        return $call_actions;
    }

    // 每半分钟执行
    public function half_minute()
    {

    }

    // 每分钟执行
    public function minute()
    {

    }

    // 每五分钟执行
    public function five_minutes()
    {
		$this->model('publish')->publish_scheduled_posts();
    }

    // 每十分钟执行
    public function ten_minutes()
    {

    }

    // 每半小时执行
    public function half_hour()
    {

    }

    // 每小时执行
    public function hour()
    {

    }

    // 每日时执行
    public function day()
    {

    }

    // 每周执行
    public function week()
    {

    }
}