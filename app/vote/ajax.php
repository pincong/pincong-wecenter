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
		HTTP::no_cache_header();
	}

	public function agree_action()
	{
		if (!$this->user_info['permission']['vote_agree'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!check_user_operation_interval('vote', $this->user_id, $this->user_info['permission']['interval_vote']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_agree'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('vote')->check_user_vote_rate_limit($this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日赞同/反对已经达到上限')));
		}

		$item_info = $this->model('content')->get_item_info_by_id($_POST['type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的内容进行投票')));
		}

		if (!$this->model('vote')->check_same_user_limit($this->user_id, $item_info['uid'], 1))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能连续赞同同一个用户, 请明天再试')));
		}

		set_user_operation_last_time('vote', $this->user_id);

		$reputation_factor = $this->user_info['reputation_factor'];

		$this->model('vote')->agree($_POST['type'], $_POST['item_id'], $this->user_id, $item_info['uid'], $reputation_factor, $this->user_info['permission']['affect_currency']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function disagree_action()
	{
		if (!$this->user_info['permission']['vote_disagree'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!check_user_operation_interval('vote', $this->user_id, $this->user_info['permission']['interval_vote']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_disagree'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('vote')->check_user_vote_rate_limit($this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日赞同/反对已经达到上限')));
		}

		$item_info = $this->model('content')->get_item_info_by_id($_POST['type'], $_POST['item_id']);
		if (!$item_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容不存在')));
		}

		if ($item_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的内容进行投票')));
		}

		if (!$this->model('vote')->check_same_user_limit($this->user_id, $item_info['uid'], -1))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能连续反对同一个用户, 请明天再试')));
		}

		set_user_operation_last_time('vote', $this->user_id);

		$reputation_factor = $this->user_info['reputation_factor'];

		$this->model('vote')->disagree($_POST['type'], $_POST['item_id'], $this->user_id, $item_info['uid'], $reputation_factor, $this->user_info['permission']['affect_currency']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}