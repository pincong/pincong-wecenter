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
			'get_question_comments',
			'get_answer_comments',
			'log',
			'get_focus_users',
			'get_answer_users'
		);

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function fetch_answer_data_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_GET['id']);

		if ($answer_info['uid'] == $this->user_id OR $this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator'])
		{
			echo json_encode($answer_info);
		}
	}

	public function get_focus_users_action()
	{
		if ($focus_users_info = $this->model('question')->get_focus_users_by_question($_GET['question_id'], 18))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

			foreach($focus_users_info as $key => $val)
			{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
					$focus_users[$key] = array(
						'uid' => 0,
						'user_name' => AWS_APP::lang()->_t('匿名用户'),
						'avatar_file' => get_avatar_url(0, 'mid'),
					);
				}
				else
				{
					$focus_users[$key] = array(
						'uid' => $val['uid'],
						'user_name' => $val['user_name'],
						'avatar_file' => get_avatar_url($val['uid'], 'mid'),
						'url' => get_js_url('/people/' . $val['url_token'])
					);
				}
			}
		}

		H::ajax_json_output($focus_users);
	}

	public function save_invite_action()
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
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

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_invite_answer'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if ($this->model('answer')->has_answer_by_uid($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已经回答过该问题')));
		}

		if ($question_info['published_uid'] == $invite_user_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请问题的发起者回答问题')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已接受过邀请')));
		}

		if ($this->model('question')->has_question_invite($_POST['question_id'], $invite_user_info['uid'], $this->user_id))
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

	public function save_answer_comment_action()
	{
		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['allow_anonymous'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		$message = my_trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		if (! $_GET['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回复不存在')));
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

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administrator'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能评论锁定的问题')));
		}

		$this->model('answer')->insert_answer_comment($_GET['answer_id'], $this->user_id, $message, $_POST['anonymous']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['answer_id']),
			'type_name' => 'answer'
		), 1, null));
	}

	public function get_answer_comments_action()
	{
		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);

		TPL::assign('question', $this->model('question')->get_question_info_by_id($answer_info['question_id']));
		TPL::assign('answer_info', $answer_info);
		TPL::assign('comments', $comments);

		{
			TPL::output("question/ajax/comments");
		}
	}

	public function save_question_comment_action()
	{
		if (!$this->user_info['permission']['publish_comment'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['allow_anonymous'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		$message = my_trim($_POST['message']);
		if (!$message)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请输入评论内容')));
		}

        if (!check_repeat_submission($message))
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请不要重复提交')));
        }

		if (! $_GET['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administrator'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能评论锁定的问题')));
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

		$this->model('question')->insert_question_comment($_GET['question_id'], $this->user_id, $message, $_POST['anonymous']);

		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => intval($_GET['question_id']),
			'type_name' => 'question'
		), 1, null));
	}

	public function get_question_comments_action()
	{
		$comments = $this->model('question')->get_question_comments($_GET['question_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}

		TPL::assign('question', $this->model('question')->get_question_info_by_id($_GET['question_id']));

		TPL::assign('comments', $comments);

		TPL::output("question/ajax/comments");
	}

	public function question_vote_action()
	{
		$value = intval($_POST['value']);
		if ($value == 1)
		{
			if (!$this->user_info['permission']['vote_agree'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
			}
		}
		elseif ($value == -1)
		{
			if (!$this->user_info['permission']['vote_disagree'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投票数据错误, 无法进行投票')));
		}

		$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']);

		if (! $question_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的问题进行投票')));
		}

		if ($value === 1 AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_agree_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}
		else
		if ($value === -1 AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_disagree_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		$reputation_factor = $this->model('reputation')->get_reputation_factor_by_reputation($this->user_info['reputation']);

		$this->model('question')->change_question_vote($_POST['question_id'], $value, $this->user_id, $reputation_factor, $question_info['published_uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function answer_vote_action()
	{
		$value = intval($_POST['value']);
		if ($value == 1)
		{
			if (!$this->user_info['permission']['vote_agree'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
			}
		}
		elseif ($value == -1)
		{
			if (!$this->user_info['permission']['vote_disagree'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投票数据错误, 无法进行投票')));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if (! $answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复不存在')));
		}

		if ($answer_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('不能对自己发表的回复进行投票')));
		}

		if ($value === 1 AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_agree_answer'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}
		else
		if ($value === -1 AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_disagree_answer'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		$reputation_factor = $this->model('reputation')->get_reputation_factor_by_reputation($this->user_info['reputation']);

		$this->model('answer')->change_answer_vote($_POST['answer_id'], $value, $this->user_id, $reputation_factor, $answer_info['uid']);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
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

	public function question_thanks_action()
	{
		if (!$this->user_info['permission']['thank_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_thanks'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能感谢自己的问题')));
		}

		if ($this->model('question')->question_thanks($_POST['question_id'], $this->user_id))
		{
			/*$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_QUESTION_THANK, notify_class::CATEGORY_QUESTION, $_POST['question_id'], array(
				'question_id' => intval($_POST['question_id']),
				'from_uid' => $this->user_id
			));*/

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));
		}
	}

	public function answer_thanks_action()
	{
		if (!$this->user_info['permission']['thank_user'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		if (! $answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('回复不存在')));
		}

		if ($this->user_id == $answer_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('不能感谢自己发表的回复')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_thanks'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if ($this->model('answer')->answer_thanks($_POST['answer_id'], $this->user_id))
		{
			/*if ($answer_info['uid'] != $this->user_id)
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_ANSWER_THANK, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'question_id' => $answer_info['question_id'],
					'from_uid' => $this->user_id,
					'item_id' => $answer_info['answer_id']
				));
			}*/

			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
	}

	public function focus_action()
	{
		if (!$_POST['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if (! $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('question')->add_focus_question($_POST['question_id'], $this->user_id)
		), 1, null));
	}

	public function save_answer_action()
	{
		if (!$this->user_info['permission']['answer_question'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['allow_anonymous'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		if (human_valid('answer_valid_hour') and ! AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
		}

		$answer_content = my_trim($_POST['answer_content']);

		if (! $answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		if (!check_repeat_submission($answer_content))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请不要重复提交')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_answer_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的问题不能回复')));
		}

		// 判断是否是问题发起者
		if (get_setting('answer_self_question') == 'N' and $question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能回复自己发布的问题，你可以修改问题内容')));
		}

		// 判断是否已回复过问题
		if ((get_setting('answer_unique') == 'Y') AND $this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个问题只能回复一次，你可以编辑回复过的回复')));
		}

		$answer_length_min = intval(get_setting('answer_length_min'));
		if ($answer_length_min AND cjk_strlen($answer_content) < $answer_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字', $answer_length_min)));
		}

		$answer_length_max = intval(get_setting('answer_length_max'));
		if ($answer_length_max AND cjk_strlen($answer_content) > $answer_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得多于 %s 字', $answer_length_max)));
		}

		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		if ($_POST['later'])
		{
			//TODO: 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}
		else
		{
			$answer_id = $this->model('publish')->publish_answer($question_info['question_id'], $answer_content, $this->user_id, $_POST['anonymous'], null, $_POST['auto_focus']);

			{
				//$url = get_js_url('/question/' . $question_info['question_id'] . '?item_id=' . $answer_id . '&rf=false');
			}

			$answer_info = $this->model('answer')->get_answer_by_id($answer_id);

			$answer_info['user_info'] = $this->user_info;
			$answer_info['answer_content'] = $this->model('question')->parse_at_user($answer_info['answer_content']);

			TPL::assign('answer_info', $answer_info);

			H::ajax_json_output(AWS_APP::RSM(array(
				'ajax_html' => TPL::output('question/ajax/answer', false)
			), 1, null));
		}
	}

	public function update_answer_action()
	{
		if (! $answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('答案不存在')));
		}

		if ($_POST['do_delete'])
		{
			if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administrator'] and ! $this->user_info['permission']['is_moderator'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你没有权限进行此操作')));
			}

			// TODO: implement remove_answer_action()

			/*$this->model('answer')->remove_answer_by_id($_GET['answer_id']);

			// 通知回复的作者
			if ($this->user_id != $answer_info['uid'])
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_REMOVE_ANSWER, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'from_uid' => $this->user_id,
					'question_id' => $answer_info['question_id']
				));
			}

			$this->model('question')->save_last_answer($answer_info['question_id']);*/

			// 只清空不删除
			$this->model('answer')->update_answer($_GET['answer_id'], $answer_info['question_id'], null, $this->user_id);

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		}

		$answer_content = my_trim($_POST['answer_content']);

		if (!$answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		$answer_length_min = intval(get_setting('answer_length_min'));
		if ($answer_length_min AND cjk_strlen($answer_content) < $answer_length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得少于 %s 字', $answer_length_min)));
		}

		$answer_length_max = intval(get_setting('answer_length_max'));
		if ($answer_length_max AND cjk_strlen($answer_content) > $answer_length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复内容字数不得多于 %s 字', $answer_length_max)));
		}

		if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administrator'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个回复')));
		}

		if ($answer_info['uid'] == $this->user_id and (time() - $answer_info['add_time'] > get_setting('answer_edit_time') * 60) and get_setting('answer_edit_time') and ! $this->user_info['permission']['is_administrator'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经超过允许编辑的时限')));
		}

		$this->model('answer')->update_answer($_GET['answer_id'], $answer_info['question_id'], $answer_content, $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(array(
			'target_id' => $_GET['target_id'],
			'display_id' => $_GET['display_id']
		), 1, null));
	}

	public function log_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('指定问题不存在')));
		}

		$log_list = $this->model('question')->list_logs($_GET['id'], (intval($_GET['page']) * get_setting('contents_per_page')) . ', ' . get_setting('contents_per_page'));

		TPL::assign('question_info', $question_info);

		TPL::assign('list', $log_list);

		TPL::output('question/ajax/log');
	}

	public function redirect_action()
	{
		$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);

		if ($question_info['lock'] AND ! ($this->user_info['permission']['is_administrator'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('锁定的问题不能设置重定向')));
		}

		if (! ($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		$this->model('question')->redirect($this->user_id, $_POST['item_id'], $_POST['target_id']);

		/*if ($_POST['target_id'] AND $_POST['item_id'] AND $question_info['published_uid'] != $this->user_id)
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_REDIRECT_QUESTION, notify_class::CATEGORY_QUESTION, $_POST['item_id'], array(
				'from_uid' => $this->user_id,
				'question_id' => intval($_POST['item_id'])
			));
		}*/

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function remove_question_action()
	{
		if (!$this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
		}

		if ($question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			if ($this->user_id != $question_info['published_uid'])
			{
				$this->model('account')->send_delete_message($question_info['published_uid'], $question_info['question_content'], $question_info['question_detail']);
			}

			$this->model('question')->remove_question($question_info['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/')
		), 1, null));
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
				$this->model('question')->set_recommend($_POST['question_id']);
			break;

			case 'unset':
				$this->model('question')->unset_recommend($_POST['question_id']);
			break;
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	/*public function remove_comment_action()
	{
		if (! in_array($_GET['type'], array(
			'answer',
			'question'
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('错误的请求')));
		}

		if (! $_GET['comment_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		$comment = $this->model($_GET['type'])->get_comment_by_id($_GET['comment_id']);

		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administrator'] AND $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限删除该评论')));
		}

		$this->model($_GET['type'])->remove_comment($_GET['comment_id']);

		if ($_GET['type'] == 'answer')
		{
			$this->model('answer')->update_answer_comments_count($comment['answer_id']);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->update_question_comments_count($comment['question_id']);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}*/

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

		$comment = $this->model($_GET['type'])->get_comment_by_id($comment_id);
		if (!$comment || !$comment['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('评论不存在')));
		}

		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administrator'] AND $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限删除该评论')));
		}

		if ($_GET['type'] == 'answer')
		{
			$this->model('question')->remove_answer_comment($comment, $this->user_id);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->remove_question_comment($comment, $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function lock_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		$this->model('question')->lock_question($_POST['question_id'], !$question_info['lock'], $this->user_id);

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function set_best_answer_action()
	{
		if (! $this->user_info['permission']['is_moderator'] AND ! $this->user_info['permission']['is_administrator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('你没有权限进行此操作')));
		}

		if (!$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('回答不存在')));
		}

		if (! $question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['best_answer'])
		{
			$this->model('answer')->unset_best_answer($_POST['answer_id'], $this->user_id);
		}
		else
		{
			$this->model('answer')->set_best_answer($_POST['answer_id'], $this->user_id);
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function bump_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_move_up_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('posts')->bump_post($this->user_id, $_POST['question_id'], 'question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function sink_action()
	{
		if (!$this->user_info['permission']['bump_sink'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_move_down_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('posts')->sink_post($this->user_id, $_POST['question_id'], 'question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题不存在')));
		}

		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

}