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

		$rule_action['actions'] = array(
			'get_video_metadata'
		);

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
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('影片不存在')));
		}

		$metadata = Services_VideoParser::fetch_metadata($video_info['source_type'], $video_info['source']);
		if (!$metadata)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('影片接口故障')));
		}

		if ($metadata['error'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, $metadata['error']));
		}

		H::ajax_json_output(AWS_APP::RSM($metadata, 1, null));
	}

	public function save_danmaku_action()
	{
		if (!$this->user_info['permission']['publish_danmaku'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('影片不存在')));
		}

		$stime = intval($_POST['stime']); // 毫秒
		$dur = $video_info['duration'] * 1000; // 秒 * 1000

		if ($stime < 0 OR $stime >= $dur)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('数据错误')));
		}

		$text = trim($_POST['text']);

		if (!$text)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('数据错误')));
		}

		$length_limit = intval(get_setting('danmaku_length_limit'));
		if (cjk_strlen($text) > $length_limit)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('字数不得多于 %s 字', $length_limit)));
		}

		if (!check_repeat_submission($this->user_id, $text))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}

		if (!$this->model('ratelimit')->check_video_danmaku($this->user_id, $this->user_info['permission']['danmaku_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日发布的弹幕已经达到上限')));
		}

		// 1:滚动字幕 4:底端渐隐 5:顶端渐隐
		$mode = intval($_POST['mode']);
		if ($mode !== 4 AND $mode !== 5)
		{
			$mode = 1;
		}

		// 18:小字号 25:中字号
		$size = intval($_POST['size']);
		if ($size !== 18)
		{
			$size = 25;
		}

		$color = intval($_POST['color']);
		if ($color < 0 OR $color > 0xffffff)
		{
			$color = 0xffffff;
		}

		set_repeat_submission_digest($this->user_id, $text);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$this->model('danmaku')->save_danmaku(
			$video_info['id'],
			$this->user_id,
			$stime,
			$text,
			$mode,
			$size,
			$color
		);

		if (get_setting('danmaku_bring_top') == 'Y')
		{
			$this->model('posts')->bring_to_top($this->user_id, $video_info['id'], 'video');
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_danmaku_action()
	{
	}


}