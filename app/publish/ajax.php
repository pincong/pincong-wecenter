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

	private function get_anonymous_uid($type)
	{
		if (!$anonymous_uid = $this->model('anonymous')->get_anonymous_uid($this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('本站未开启匿名功能')));
		}

		if (!$this->model('anonymous')->check_rate_limit($type, $anonymous_uid))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日匿名额度已经用完')));
		}

		if (!$this->model('anonymous')->check_spam($anonymous_uid))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('检测到滥用行为, 匿名功能暂时关闭')));
		}

		return $anonymous_uid;
	}

	private function validate_title_length($type, &$length)
	{
		$length_min = intval(S::get('title_length_min'));
		$length_max = intval(S::get('title_length_max'));
		$length = cjk_strlen($_POST['title']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_body_length($type)
	{
		$length_min = intval(S::get($type . '_body_length_min'));
		$length_max = intval(S::get($type . '_body_length_max'));
		$length = cjk_strlen($_POST['message']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('正文字数不得大于 %s 字', $length_max)));
		}
	}

	private function validate_reply_length($type)
	{
		$length_min = intval(S::get($type . '_reply_length_min'));
		$length_max = intval(S::get($type . '_reply_length_max'));
		$length = cjk_strlen($_POST['message']);
		if ($length_min AND $length < $length_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复字数不得小于 %s 字', $length_min)));
		}
		if ($length_max AND $length > $length_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('回复字数不得大于 %s 字', $length_max)));
		}
	}

	private function do_validate()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']['interval_post']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		$_POST['later'] = intval($_POST['later']);
		if ($_POST['later'])
		{
			if (!$this->user_info['permission']['post_later'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能延迟发布')));
			}

			if ($_POST['later'] < 10)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能小于 10 分钟')));
			}

			if ($_POST['later'] > 1440)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能大于 1440 分钟')));
			}
		}
	}

	private function validate_thread($type)
	{
		$this->do_validate();

		if ($_POST['anonymous'] AND !$this->user_info['permission']['post_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能匿名')));
		}

		$_POST['title'] = trim($_POST['title']);
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入标题')));
		}
		$this->validate_title_length($type, $title_length);

		if ($type == 'question' AND S::get('question_ends_with_question') == 'Y')
		{
			$question_mark = cjk_substr($_POST['title'], $title_length - 1, 1);
			if ($question_mark != '？' AND $question_mark != '?' AND $question_mark != '¿')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请以问号提问')));
			}
		}

		if (!check_repeat_submission($this->user_id, $_POST['title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}

		$_POST['message'] = trim($_POST['message']);
		$this->validate_body_length($type);

		$topics_limit_min = intval(S::get('topics_limit_min'));
		$topics_limit_max = intval(S::get('topics_limit_max'));

		$num_topics = 0;
		if ($_POST['topics'])
		{
			$num_topics = sizeof($_POST['topics']);
		}
		if ($topics_limit_min AND $num_topics < $topics_limit_min)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题数量最少 %s 个, 请添加话题', $topics_limit_min)));
		}
		if ($topics_limit_max AND $num_topics > $topics_limit_max)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题数量最多 %s 个, 请调整话题数量', $topics_limit_max)));
		}

		if ($num_topics)
		{
			$topic_title_limit = intval(S::get('topic_title_limit'));
			foreach ($_POST['topics'] AS $key => $topic_title)
			{
				$topic_title = trim($topic_title);

				if (!$topic_title)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写话题标题')));
					break;
				}

				$topic_title_length = strlen($topic_title);
				if ($topic_title_length > 100)
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题字数超出限制')));
					break;
				}

				$topic_exists = !!$this->model('topic')->get_topic_id_by_title($topic_title);
				if (!$topic_exists AND !$this->user_info['permission']['create_topic'])
				{
					H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能创建新话题「%s」, 请选择现有话题', $topic_title)));
					break;
				}

				if ($topic_title_limit AND $topic_title_length > $topic_title_limit)
				{
					if (!$topic_exists)
					{
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题字数超出限制')));
						break;
					}
				}

				$_POST['topics'][$key] = $topic_title;
			}
		}

		if (S::get('category_enable') == 'N')
		{
			$_POST['category_id'] = 1;
		}
		else
		{
			$_POST['category_id'] = intval($_POST['category_id']);
		}

		if (!$_POST['category_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择分类')));
		}

		if (!$this->model('category')->check_user_permission($_POST['category_id'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的声望还不能在这个分类发言')));
		}

		if (!$this->model('category')->category_exists($_POST['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}

		if (!$this->model('ratelimit')->check_thread($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('今日发帖数量已经达到上限')));
		}
	}


	private function validate_reply($parent_type)
	{
		$this->do_validate();

		if ($_POST['anonymous'] AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不能匿名')));
		}

		$_POST['message'] = trim($_POST['message']);
		if (!$_POST['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}
		$this->validate_reply_length($parent_type);

		if (!check_repeat_submission($this->user_id, $_POST['message']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}
	}



/*
+--------------------------------------------------------------------------
|   发布主题
+---------------------------------------------------------------------------
*/

	public function publish_question_action()
	{
		if (!$this->model('publish')->check_user_permission('question', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		$this->validate_thread('question');

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('question');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['title']);
		set_user_operation_last_time('publish', $this->user_id);

		$question_id = $this->model('publish')->publish_question(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
			'follow' => !$_POST['anonymous'],
			'ask_user_id' => $_POST['ask_user_id'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/question/' . $question_id)
		), 1, null));
	}


	public function publish_article_action()
	{
		if (!$this->model('publish')->check_user_permission('article', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		$this->validate_thread('article');

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('article');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['title']);
		set_user_operation_last_time('publish', $this->user_id);

		$article_id = $this->model('publish')->publish_article(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
			'follow' => !$_POST['anonymous'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/article/' . $article_id)
		), 1, null));
	}


	public function publish_video_action()
	{
		if (!$this->model('publish')->check_user_permission('video', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$web_url = trim($_POST['web_url']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入影片来源')));
		}

		$metadata = Services_VideoParser::parse_video_url($web_url);
		if (!$metadata)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('无法识别影片来源')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		$this->validate_thread('video');

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('video');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['title']);
		set_user_operation_last_time('publish', $this->user_id);

		$video_id = $this->model('publish')->publish_video(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
			'follow' => !$_POST['anonymous'],
			'source_type' => $metadata['source_type'],
			'source' => $metadata['source'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => url_rewrite('/video/' . $video_id)
		), 1, null));
	}



/*
+--------------------------------------------------------------------------
|   发布回应
+---------------------------------------------------------------------------
*/

	public function publish_answer_action()
	{
		if (!$this->model('publish')->check_user_permission('answer', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$this->model('ratelimit')->check_answer($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的回复已经达到上限')));
		}

		$this->validate_reply('question');

		if (!$question_info = $this->model('content')->get_thread_info_by_id('question', $_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的问题不能回复')));
		}

		if (!$question_info['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经删除的问题不能回复')));
		}

		if (!$this->model('category')->check_user_permission_reply($question_info['category_id'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的声望还不能在这个分类发言')));
		}

		// 判断是否是问题发起者
		if (S::get('answer_self_question') == 'N' AND $question_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能回复自己发布的问题，你可以修改问题内容')));
		}

		$pay = true;
		$replied = $this->model('content')->has_user_relpied_to_thread('question', $question_info['id'], $this->user_id, true);
		if ((S::get('reply_pay_only_once') == 'Y') AND $replied)
		{
			$pay = false;
		}

		if ($pay AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		// 判断是否已回复过问题
		if ((S::get('answer_unique') == 'Y'))
		{
			if ($replied == 2)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你已经使用延迟显示功能回复过该问题')));
			}
			else if ($replied)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个问题只能回复一次，你可以编辑回复过的回复')));
			}
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('answer');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['message']);
		set_user_operation_last_time('publish', $this->user_id);

		$answer_id = $this->model('publish')->publish_answer(array(
			'parent_id' => $question_info['id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'follow' => ($_POST['follow'] AND !$_POST['anonymous'] AND ($this->user_info['permission']['follow_thread'] OR $question_info['uid'] == $this->user_id)),
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
		), $this->user_id, $_POST['later'], $pay);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		$answer_info = $this->model('question')->get_answer_by_id($answer_id);
		//$answer_info['message'] = $this->model('mention')->parse_at_user($answer_info['message']);
		TPL::assign('answer_info', $answer_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::process('question/ajax_reply')
		), 1, null));
	}


	public function publish_article_comment_action()
	{
		if (!$this->model('publish')->check_user_permission('article_comment', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$this->model('ratelimit')->check_article_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的文章评论已经达到上限')));
		}

		$this->validate_reply('article');

		if (!$article_info = $this->model('content')->get_thread_info_by_id('article', $_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定文章不存在')));
		}

		if ($article_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能回复')));
		}

		if (!$article_info['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经删除的文章不能回复')));
		}

		if (!$this->model('category')->check_user_permission_reply($article_info['category_id'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的声望还不能在这个分类发言')));
		}

		$pay = true;
		$replied = $this->model('content')->has_user_relpied_to_thread('article', $article_info['id'], $this->user_id, true);
		if ((S::get('reply_pay_only_once') == 'Y') AND $replied)
		{
			$pay = false;
		}

		if ($pay AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('article_comment');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['message']);
		set_user_operation_last_time('publish', $this->user_id);

		$comment_id = $this->model('publish')->publish_article_comment(array(
			'parent_id' => $article_info['id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'follow' => ($_POST['follow'] AND !$_POST['anonymous'] AND ($this->user_info['permission']['follow_thread'] OR $article_info['uid'] == $this->user_id)),
			'at_uid' => $_POST['at_uid'],
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
		), $this->user_id, $_POST['later'], $pay);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		$comment_info = $this->model('article')->get_article_comment_by_id($comment_id);
		//$comment_info['message'] = $this->model('mention')->parse_at_user($comment_info['message']);
		TPL::assign('comment_info', $comment_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::process('article/ajax_reply')
		), 1, null));
	}


	public function publish_video_comment_action()
	{
		if (!$this->model('publish')->check_user_permission('video_comment', $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的声望还不够')));
		}

		if (!$this->model('ratelimit')->check_video_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的影片评论已经达到上限')));
		}

		$this->validate_reply('video');

		if (!$video_info = $this->model('content')->get_thread_info_by_id('video', $_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定影片不存在')));
		}

		if ($video_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的影片不能回复')));
		}

		if (!$video_info['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经删除的影片不能回复')));
		}

		if (!$this->model('category')->check_user_permission_reply($video_info['category_id'], $this->user_info))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('你的声望还不能在这个分类发言')));
		}

		$pay = true;
		$replied = $this->model('content')->has_user_relpied_to_thread('video', $video_info['id'], $this->user_id, true);
		if ((S::get('reply_pay_only_once') == 'Y') AND $replied)
		{
			$pay = false;
		}

		if ($pay AND !$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', S::get('currency_name'))));
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('video_comment');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		set_repeat_submission_digest($this->user_id, $_POST['message']);
		set_user_operation_last_time('publish', $this->user_id);

		$comment_id = $this->model('publish')->publish_video_comment(array(
			'parent_id' => $video_info['id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'follow' => ($_POST['follow'] AND !$_POST['anonymous'] AND ($this->user_info['permission']['follow_thread'] OR $video_info['uid'] == $this->user_id)),
			'at_uid' => $_POST['at_uid'],
			'permission_affect_currency' => $this->user_info['permission']['affect_currency'],
		), $this->user_id, $_POST['later'], $pay);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => url_rewrite('/publish/delay/')
			), 1, null));
		}

		$comment_info = $this->model('video')->get_video_comment_by_id($comment_id);
		//$comment_info['message'] = $this->model('mention')->parse_at_user($comment_info['message']);
		TPL::assign('comment_info', $comment_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::process('video/ajax_reply')
		), 1, null));
	}


}
