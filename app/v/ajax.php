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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';

		$rule_action['actions'] = array(
			'list',
			'log'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function save_comment_action()
	{
		if (!$this->user_info['permission']['comment_video'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		$later = intval($_POST['later']);
		if ($later)
		{
			if (!$this->user_info['permission']['reply_later'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能延迟回复')));
			}

			if ($later < 10 OR $later > 1440)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间只能在 10 ~ 1400 分钟之间')));
			}
		}

		$message = my_trim($_POST['message']);

		if (! $message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		if (!check_repeat_submission($message))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('publish')->check_video_comment_limit_rate($this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的投稿评论已经达到上限')));
		}

		if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定投稿不存在')));
		}

		if ($video_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的投稿不能回复')));
		}

		$comment_length_min = intval(get_setting('comment_length_min'));
		if ($comment_length_min AND cjk_strlen($message) < $comment_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得少于 %s 字', $comment_length_min)));
		}

		$comment_length_max = intval(get_setting('comment_length_max'));
		if ($comment_length_max AND cjk_strlen($message) > $comment_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字', $comment_length_max)));
		}

		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($message);

		if ($later)
		{
			// 延迟显示
			$this->model('publish')->schedule(
				'video_comment',
				real_time() + $later * 60 + rand(-30, 30),
				null,
				$message,
				$this->user_id,
				$_POST['anonymous'],
				$video_info['id'],
				array(
					'at_uid' => $_POST['at_uid'],
					'permission_bring_thread_to_top' => $this->user_info['permission']['bring_thread_to_top']
				)
			);

			$url = get_js_url('/publish/delay_display/');
		}
		else
		{
			$comment_id = $this->model('publish')->publish_video_comment(
				$video_info['id'],
				$message,
				$this->user_id,
				$_POST['at_uid'],
				$_POST['anonymous'],
				$this->user_info['permission']['bring_thread_to_top']
			);
		}

		if ($url)
		{
			H::ajax_json_output(AWS_APP::RSM(array('url' => $url), 1, null));
		}
		else
		{
			$comment_info = $this->model('video')->get_comment_by_id($comment_id);
			$comment_info['message'] = $this->model('question')->parse_at_user($comment_info['message']);
			TPL::assign('comment_info', $comment_info);
			H::ajax_json_output(AWS_APP::RSM(array(
				'ajax_html' => TPL::output('video/ajax/comment', false)
			), 1, null));
		}
	}

	public function lock_action()
	{
		if (!$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投稿不存在')));
		}

		$this->model('video')->lock_video($_POST['video_id'], !$video_info['lock'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	// 彻底删除不留痕迹
	/*public function remove_video_action()
	{
		if (!$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除投稿的权限')));
		}

		if ($video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			if ($this->user_id != $video_info['uid'])
			{
				//$this->model('account')->send_delete_message($video_info['uid'], $video_info['title'], $video_info['message']);
			}

			$this->model('video')->remove_video($video_info['id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}*/

	public function remove_comment_action()
	{
		$comment_info = $this->model('video')->get_comment_by_id($_POST['comment_id']);
		if (!$comment_info || !$comment_info['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('评论不存在')));
		}
		
		if ($this->user_id != $comment_info['uid'] AND!$this->user_info['permission']['edit_video'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除评论的权限')));
		}

		// 只清空不删除
		// TODO: implement update_comment_action()
		$this->model('video')->remove_video_comment($comment_info, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/v/' . $comment_info['video_id'])
		), 1, null));
	}

	public function log_action()
	{
		if (! $video_info = $this->model('video')->get_video_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定投稿不存在')));
		}

		$log_list = $this->model('video')->list_logs($_GET['id'], (intval($_GET['page']) * get_setting('contents_per_page')) . ', ' . get_setting('contents_per_page'));

		TPL::assign('video_info', $video_info);

		TPL::assign('list', $log_list);

		TPL::output('video/ajax/log');
	}

	public function set_recommend_action()
	{
		if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有设置推荐的权限')));
		}

		switch ($_POST['action'])
		{
			case 'set':
				$this->model('video')->set_recommend($_POST['video_id']);
			break;

			case 'unset':
				$this->model('video')->unset_recommend($_POST['video_id']);
			break;
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function bump_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('posts')->bump_post($this->user_id, $_POST['video_id'], 'video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投稿不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function sink_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('posts')->sink_post($this->user_id, $_POST['video_id'], 'video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投稿不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}