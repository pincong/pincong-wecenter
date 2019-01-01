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

class api extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		if ($this->user_info['permission']['visit_question'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'get_video_metadata';
			//$rule_action['actions'][] = 'index';
		}

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function get_video_metadata_action()
	{
		if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投稿不存在')));
		}

		$metadata = Services_VideoParser::fetch_video_metadata($video_info['source_type'], $video_info['source']);
		if (!$metadata)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('无法解析视频')));
		}

		H::ajax_json_output(AWS_APP::RSM($metadata, 1, null));
	}


}