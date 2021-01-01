<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);

if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function setup()
	{
		H::no_cache_header();
	}

	public function agree_action()
	{
		if (!$this->user_info['permission']['vote_agree'])
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if (!check_user_operation_interval('vote', $this->user_id, $this->user_info['permission']['interval_vote']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_agree'))
		{
			H::ajax_error((_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if (!$this->model('vote')->check_user_vote_rate_limit($this->user_id, $this->user_info['permission']))
		{
			H::ajax_error((_t('今日赞同/反对已经达到上限')));
		}

		$item_info = $this->model('post')->get_thread_or_reply_info_by_id(H::POST('type'), H::POST('item_id'));
		if (!$item_info)
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_error((_t('不能赞同/反对自己发表的内容')));
		}

		if (!$this->model('vote')->check_same_user_limit($this->user_id, $item_info['uid'], 1))
		{
			H::ajax_error((_t('不能连续赞同同一个用户, 请明天再试')));
		}

		// 恶意行为
		if ($this->model('vote')->get_user_vote_count($this->user_id, null, null, H::POST('type'), H::POST('item_id')) >= 4)
		{
			H::ajax_error((_t('不能反复赞同/反对')));
		}

		set_user_operation_last_time('vote', $this->user_id);

		$this->model('vote')->vote(H::POST('type'), H::POST('item_id'), $this->user_id, $item_info['uid'], 1);

		H::ajax_success();
	}

	public function disagree_action()
	{
		if (!$this->user_info['permission']['vote_disagree'])
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if (!check_user_operation_interval('vote', $this->user_id, $this->user_info['permission']['interval_vote']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_disagree'))
		{
			H::ajax_error((_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if (!$this->model('vote')->check_user_vote_rate_limit($this->user_id, $this->user_info['permission']))
		{
			H::ajax_error((_t('今日赞同/反对已经达到上限')));
		}

		$item_info = $this->model('post')->get_thread_or_reply_info_by_id(H::POST('type'), H::POST('item_id'));
		if (!$item_info)
		{
			H::ajax_error((_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_error((_t('不能赞同/反对自己发表的内容')));
		}

		if (!$this->model('vote')->check_same_user_limit($this->user_id, $item_info['uid'], -1))
		{
			H::ajax_error((_t('不能连续反对同一个用户, 请明天再试')));
		}

		// 恶意行为
		if ($this->model('vote')->get_user_vote_count($this->user_id, null, null, H::POST('type'), H::POST('item_id')) >= 4)
		{
			H::ajax_error((_t('不能反复赞同/反对')));
		}

		set_user_operation_last_time('vote', $this->user_id);

		$this->model('vote')->vote(H::POST('type'), H::POST('item_id'), $this->user_id, $item_info['uid'], -1);

		H::ajax_success();
	}

}