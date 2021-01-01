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

		if ($this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'get_question_discussions',
				'get_answer_discussions'
			);
		}

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('content')->get_thread_info_by_id('question', $_POST['question_id']))
		{
			$this->model('question')->clear_question($question_info['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
	}

	public function save_answer_discussion_action()
	{
		if (!$this->user_info['permission']['publish_discussion'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if ($_POST['anonymous'])// AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$message = trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($this->user_id, $message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		$discussion_length_min = intval(get_setting('discussion_length_min'));
		if ($discussion_length_min AND cjk_strlen($message) < $discussion_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得少于 %s 字', $discussion_length_min)));
		}

		$discussion_length_max = intval(get_setting('discussion_length_max'));
		if ($discussion_length_max AND cjk_strlen($message) > $discussion_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字', $discussion_length_max)));
		}

		if (!$this->model('ratelimit')->check_answer_discussion($this->user_id, $this->user_info['permission']['discussion_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日评论回复已经达到上限')));
		}

		$answer_info = $this->model('content')->get_reply_info_by_id('answer', $_GET['answer_id']);
		if (!$answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回复不存在')));
		}
		$question_info = $this->model('content')->get_thread_info_by_id('question', $answer_info['question_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		if (!$question_info['question_content'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论已删除的问题')));
		}

        set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		$this->model('answer')->insert_answer_discussion($_GET['answer_id'], $this->user_id, $message);

		if (get_setting('discussion_bring_top') == 'Y')
		{
			$this->model('posts')->bring_to_top($this->user_id, $question_info['question_id'], 'question');
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['answer_id']),
			'type_name' => 'answer'
		), 1, null));
	}

	public function save_question_discussion_action()
	{
		if (!$this->user_info['permission']['publish_discussion'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if ($_POST['anonymous'])// AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能匿名')));
		}

		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$message = trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($this->user_id, $message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		$discussion_length_min = intval(get_setting('discussion_length_min'));
		if ($discussion_length_min AND cjk_strlen($message) < $discussion_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得少于 %s 字', $discussion_length_min)));
		}

		$discussion_length_max = intval(get_setting('discussion_length_max'));
		if ($discussion_length_max AND cjk_strlen($message) > $discussion_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论内容字数不得超过 %s 字', $discussion_length_max)));
		}

		if (!$this->model('ratelimit')->check_question_discussion($this->user_id, $this->user_info['permission']['discussion_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日评论问题已经达到上限')));
		}

		$question_info = $this->model('content')->get_thread_info_by_id('question', $_GET['question_id']);
		if (!$question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		if (!$question_info['question_content'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论已删除的问题')));
		}

        set_repeat_submission_digest($this->user_id, $message);
		set_user_operation_last_time('publish', $this->user_id);

		$this->model('question')->insert_question_discussion($_GET['question_id'], $this->user_id, $message);

		if (get_setting('discussion_bring_top') == 'Y')
		{
			$this->model('posts')->bring_to_top($this->user_id, $question_info['question_id'], 'question');
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['question_id']),
			'type_name' => 'question'
		), 1, null));
	}


	public function save_invite_action()
	{
		if (!$this->user_info['permission']['invite_answer'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (!$invite_user_info = $this->model('account')->get_user_info_by_uid($_POST['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($invite_user_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
		}

		$this->model('question')->add_invite($_POST['question_id'], $this->user_id, $invite_user_info['uid']);

		$this->model('account')->update_question_invite_count($invite_user_info['uid']);

		$notification_id = $this->model('notify')->send($this->user_id, $invite_user_info['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval($_POST['question_id']), array(
			'from_uid' => $this->user_id,
			'question_id' => intval($_POST['question_id'])
		));

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function get_answer_discussions_action()
	{
		$comments = $this->model('answer')->get_answer_discussions($_GET['answer_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = $this->model('mention')->parse_at_user($comments[$key]['message']);
			$comments[$key]['user_info'] = $user_infos[$val['uid']];
		}

		$answer_info = $this->model('content')->get_reply_info_by_id('answer', $_GET['answer_id']);

		TPL::assign('question', $this->model('content')->get_thread_info_by_id('question', $answer_info['question_id']));
		TPL::assign('answer_info', $answer_info);
		TPL::assign('comments', $comments);

		TPL::output("question/answer_discussions_template");
	}

	public function get_question_discussions_action()
	{
		$comments = $this->model('question')->get_question_discussions($_GET['question_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = $this->model('mention')->parse_at_user($comments[$key]['message']);
			$comments[$key]['user_info'] = $user_infos[$val['uid']];
		}

		TPL::assign('question', $this->model('content')->get_thread_info_by_id('question', $_GET['question_id']));

		TPL::assign('comments', $comments);

		TPL::output("question/question_discussions_template");
	}

	public function cancel_question_invite_action()
	{
		$this->model('question')->cancel_question_invite($_GET['question_id'], $this->user_id, $_GET['recipients_uid']);

		$this->model('account')->update_question_invite_count($_GET['recipients_uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function question_invite_delete_action()
	{
		$question_invite_id = intval($_POST['question_invite_id']);

		$this->model('question')->delete_question_invite($question_invite_id, $this->user_id);

		$this->model('account')->update_question_invite_count($this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function focus_action()
	{
		if (!$_POST['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if (!check_user_operation_interval('focus', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (! $this->model('content')->get_thread_info_by_id('question', $_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		set_user_operation_last_time('focus', $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('question')->add_focus_question($_POST['question_id'], $this->user_id)
		), 1, null));
	}


	// 只清空不删除
	public function remove_comment_action()
	{
		if (! in_array($_GET['type'], array(
			'answer',
			'question'
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}

		$comment_id = intval($_GET['comment_id']);
		if (!$comment_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		if ($_GET['type'] == 'answer')
		{
			$comment = $this->model('answer')->get_answer_discussion_by_id($comment_id);
		}
		else if ($_GET['type'] == 'question')
		{
			$comment = $this->model('question')->get_question_discussion_by_id($comment_id);
		}
		if (!$comment || !$comment['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		if (! $this->user_info['permission']['edit_any_post'] AND $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限删除该评论')));
		}

		if ($_GET['type'] == 'answer')
		{
			$this->model('question')->remove_answer_discussion($comment, $this->user_id);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->remove_question_discussion($comment, $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}


}