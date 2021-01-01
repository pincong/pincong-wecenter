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

	private function get_anonymous_uid($type)
	{
		if (!$anonymous_uid = $this->model('anonymous')->get_anonymous_uid($this->user_info))
		{
			H::ajax_error((_t('本站未开启匿名功能')));
		}

		if (!$this->model('anonymous')->check_rate_limit($type, $anonymous_uid))
		{
			H::ajax_error((_t('今日匿名额度已经用完')));
		}

		if (!$this->model('anonymous')->check_spam($anonymous_uid))
		{
			H::ajax_error((_t('检测到滥用行为, 匿名功能暂时关闭')));
		}

		return $anonymous_uid;
	}



	public function save_answer_discussion_action()
	{
		if (H::POST_I('anonymous') AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_error((_t('你的声望还不够, 不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$message = H::POST_S('message');
		if (!$message)
		{
			H::ajax_error((_t('请输入讨论内容')));
		}

        if (!check_repeat_submission($this->user_id, $message))
        {
            H::ajax_error((_t('请不要重复提交')));
        }

		$discussion_length_min = S::get_int('discussion_length_min');
		if ($discussion_length_min AND iconv_strlen($message) < $discussion_length_min)
		{
			H::ajax_error((_t('讨论内容字数不得少于 %s 字', $discussion_length_min)));
		}

		$discussion_length_max = S::get_int('discussion_length_max');
		if ($discussion_length_max AND iconv_strlen($message) > $discussion_length_max)
		{
			H::ajax_error((_t('讨论内容字数不得超过 %s 字', $discussion_length_max)));
		}

		$answer_info = $this->model('post')->get_reply_info_by_id('question_reply', H::GET('answer_id'));
		if (!$answer_info)
		{
			H::ajax_error((_t('回复不存在')));
		}

		if (!$this->model('ratelimit')->check_answer_discussion($this->user_id, $this->user_info['permission']['discussion_limit_per_day']))
		{
			H::ajax_error((_t('今日讨论回复已经达到上限')));
		}

		$question_info = $this->model('post')->get_thread_info_by_id('question', $answer_info['parent_id']);
		if (!$question_info)
		{
			H::ajax_error((_t('问题不存在')));
		}

		$org_question_uid = $question_info['uid'];

		if ($question_info['redirect_id'])
		{
			$question_info = $this->model('post')->get_thread_info_by_id('question', $question_info['redirect_id']);
			if (!$question_info)
			{
				H::ajax_error((_t('合并问题不存在')));
			}
		}

		if (!$this->model('publish')->check_user_permission('question_discussion', $this->user_info) AND $org_question_uid != $this->user_id AND $question_info['uid'] != $this->user_id AND $answer_info['uid'] != $this->user_id)
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if ($question_info['lock'])
		{
			H::ajax_error((_t('不能讨论锁定的问题')));
		}

		if (!$question_info['title'])
		{
			H::ajax_error((_t('不能讨论已删除的问题')));
		}

		$days = intval($this->user_info['permission']['unallowed_necropost_days']);
		if ($days > 0)
		{
			$seconds = $days * 24 * 3600;
			$time_before = real_time() - $seconds;

			if (intval($question_info['update_time']) < $time_before)
			{
				H::ajax_error((_t('你的声望还不够, 不能回应已失去时效性的主题')));
			}
		}

		if (!$this->model('category')->check_user_permission_reply($question_info['category_id'], $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够, 不能在这个分类发言')));
		}

		if (H::POST_I('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid('question_discussion');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

        set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		$item_id = $this->model('publish')->publish_answer_discussion(array(
			'parent_id' => $answer_info['id'],
			'message' => $message,
			'uid' => $publish_uid,
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, false);

		$item_info = $this->model('question')->get_answer_discussion_by_id($item_id);
		TPL::assign('discussion_info', $item_info);
		H::ajax_response(array(
			'ajax_html' => TPL::process('question/ajax_discussion')
		));
	}

	public function save_question_discussion_action()
	{
		if (H::POST_I('anonymous') AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_error((_t('你的声望还不够, 不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_error((_t('操作过于频繁, 请稍后再试')));
		}

		$message = H::POST_S('message');
		if (!$message)
		{
			H::ajax_error((_t('请输入讨论内容')));
		}

        if (!check_repeat_submission($this->user_id, $message))
        {
            H::ajax_error((_t('请不要重复提交')));
        }

		$discussion_length_min = S::get_int('discussion_length_min');
		if ($discussion_length_min AND iconv_strlen($message) < $discussion_length_min)
		{
			H::ajax_error((_t('讨论内容字数不得少于 %s 字', $discussion_length_min)));
		}

		$discussion_length_max = S::get_int('discussion_length_max');
		if ($discussion_length_max AND iconv_strlen($message) > $discussion_length_max)
		{
			H::ajax_error((_t('讨论内容字数不得超过 %s 字', $discussion_length_max)));
		}

		$question_info = $this->model('post')->get_thread_info_by_id('question', H::GET('question_id'));
		if (!$question_info)
		{
			H::ajax_error((_t('问题不存在')));
		}

		if (!$this->model('publish')->check_user_permission('question_comment', $this->user_info) AND $question_info['uid'] != $this->user_id)
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if ($question_info['lock'])
		{
			H::ajax_error((_t('不能讨论锁定的问题')));
		}

		if (!$question_info['title'])
		{
			H::ajax_error((_t('不能讨论已删除的问题')));
		}

		$days = intval($this->user_info['permission']['unallowed_necropost_days']);
		if ($days > 0)
		{
			$seconds = $days * 24 * 3600;
			$time_before = real_time() - $seconds;

			if (intval($question_info['update_time']) < $time_before)
			{
				H::ajax_error((_t('你的声望还不够, 不能回应已失去时效性的主题')));
			}
		}

		if (!$this->model('category')->check_user_permission_reply($question_info['category_id'], $this->user_info))
		{
			H::ajax_error((_t('你的声望还不够, 不能在这个分类发言')));
		}

		if (!$this->model('ratelimit')->check_question_discussion($this->user_id, $this->user_info['permission']['discussion_limit_per_day']))
		{
			H::ajax_error((_t('今日讨论问题已经达到上限')));
		}

		if (H::POST_I('anonymous'))
		{
			$publish_uid = $this->get_anonymous_uid('question_comment');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

        set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		$item_id = $this->model('publish')->publish_question_discussion(array(
			'parent_id' => $question_info['id'],
			'message' => $message,
			'uid' => $publish_uid,
			'permission_inactive_user' => $this->user_info['permission']['inactive_user'],
		), $this->user_id, false);

		$item_info = $this->model('question')->get_question_discussion_by_id($item_id);
		TPL::assign('discussion_info', $item_info);
		H::ajax_response(array(
			'ajax_html' => TPL::process('question/ajax_comment')
		));
	}


	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_moderator'])
		{
			H::ajax_error((_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('post')->get_thread_info_by_id('question', H::POST('question_id')))
		{
			$this->model('question')->clear_question($question_info['id'], null);
		}

		H::ajax_location(url_rewrite('/'));
	}

	public function save_invite_action()
	{
		if (!$this->user_info['permission']['invite_answer'])
		{
			H::ajax_error((_t('你的声望还不够')));
		}

		if (!$question_info = $this->model('post')->get_thread_info_by_id('question', H::POST('question_id')))
		{
			H::ajax_error((_t('问题不存在或已被删除')));
		}

		if (!$invite_user_info = $this->model('account')->get_user_info_by_uid(H::POST('uid')))
		{
			H::ajax_error((_t('用户不存在')));
		}

		if ($invite_user_info['uid'] == $this->user_id)
		{
			H::ajax_error((_t('不能邀请自己回复问题')));
		}

		if ($this->model('invite')->has_question_invite(H::POST('question_id'), $invite_user_info['uid']))
		{
			H::ajax_error((_t('已邀请过该用户')));
		}

		$this->model('invite')->add_invite(H::POST('question_id'), $this->user_id, $invite_user_info['uid']);

		$this->model('account')->update_question_invite_count($invite_user_info['uid']);

		H::ajax_success();
	}

	public function cancel_question_invite_action()
	{
		$this->model('invite')->cancel_question_invite(H::GET('question_id'), $this->user_id, H::GET('recipients_uid'));

		$this->model('account')->update_question_invite_count(H::GET('recipients_uid'));

		H::ajax_success();
	}

	public function question_invite_delete_action()
	{
		$question_invite_id = H::POST_I('question_invite_id');

		$this->model('invite')->delete_question_invite($question_invite_id, $this->user_id);

		$this->model('account')->update_question_invite_count($this->user_id);

		H::ajax_success();
	}


	// 只清空不删除
	public function remove_comment_action()
	{
		if (! in_array(H::GET('type'), array(
			'question_reply',
			'question'
		)))
		{
			H::ajax_error((_t('错误的请求')));
		}

		$comment_id = H::GET_I('comment_id');
		if (!$comment_id)
		{
			H::ajax_error((_t('讨论不存在')));
		}

		if (H::GET('type') == 'question_reply')
		{
			$comment = $this->model('question')->get_answer_discussion_by_id($comment_id);
		}
		else if (H::GET('type') == 'question')
		{
			$comment = $this->model('question')->get_question_discussion_by_id($comment_id);
		}
		if (!$comment || !$comment['message'])
		{
			H::ajax_error((_t('讨论不存在')));
		}

		if (!can_edit_post($comment['uid'], $this->user_info))
		{
			H::ajax_error((_t('你没有权限删除该讨论')));
		}

		if (H::GET('type') == 'question_reply')
		{
			$this->model('question')->clear_answer_discussion(
				$comment,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}
		else if (H::GET('type') == 'question')
		{
			$this->model('question')->clear_question_discussion(
				$comment,
				(!$this->user_info['permission']['is_moderator'] ? $this->user_id : null)
			);
		}

		H::ajax_success();
	}


}