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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();

		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	// TODO: 删除
	public function fetch_question_category_action()
	{
		if (get_setting('category_enable') != 'Y')
		{
			exit(json_encode(array()));
		}

		exit($this->model('system')->build_category_json());
	}

	private function get_anonymous_uid($type)
	{
		if (!$anonymous_uid = $this->model('anonymous')->get_anonymous_uid())
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

	private function do_validate($act = 'new')
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['post_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		$_POST['later'] = intval($_POST['later']);
		if ($_POST['later'])
		{
			if (!$this->user_info['permission']['post_later'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能延迟发布')));
			}

			if ($_POST['later'] < 10)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能小于 10 分钟')));
			}

			if ($_POST['later'] > 1440 AND !$this->user_info['permission']['is_administrator'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能大于 1440 分钟')));
			}
		}

		$_POST['title'] = my_trim($_POST['title']);
		if (!$_POST['title'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入标题')));
		}

		// TODO: 在管理后台添加字数选项
		if (cjk_strlen($_POST['title']) > 150)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('标题字数不得大于 150 字节', get_setting('question_title_limit'))));
		}

		if ($act == 'new' AND !check_repeat_submission($_POST['title']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}

		$_POST['message'] = my_trim($_POST['message']);
		// TODO: 在管理后台添加字数选项
		if (cjk_strlen($_POST['message']) > 20000)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容字数不得多于 20000 字')));
		}

		if ($_POST['topics'])
		{
			$topic_title_limit = intval(get_setting('topic_title_limit'));
			foreach ($_POST['topics'] AS $key => $topic_title)
			{
				$topic_title = my_trim($topic_title);

				if (!$topic_title)
				{
					unset($_POST['topics'][$key]);
				}
				else
				{
					if ($topic_title_limit AND cjk_strlen($topic_title) > $topic_title_limit AND !$this->model('topic')->get_topic_id_by_title($topic_title))
					{
						H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('话题标题字数不得超过 %s 字', $topic_title_limit)));
						break;
					}
					$_POST['topics'][$key] = $topic_title;
				}
			}

			if (get_setting('question_topics_limit') AND sizeof($_POST['topics']) > get_setting('question_topics_limit'))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('单个问题话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
			}
		}

		if (get_setting('category_enable') == 'N')
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

		if (!$this->model('category')->category_exists($_POST['category_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('分类不存在')));
		}
	}


	private function do_validate_reply()
	{
		if (!check_user_operation_interval('publish', $this->user_id, $this->user_info['permission']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if ($_POST['anonymous'] AND !$this->user_info['permission']['reply_anonymously'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能匿名')));
		}

		$_POST['later'] = intval($_POST['later']);
		if ($_POST['later'])
		{
			if (!$this->user_info['permission']['reply_later'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不能延迟发布')));
			}

			if ($_POST['later'] < 10)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能小于 10 分钟')));
			}

			if ($_POST['later'] > 1440 AND !$this->user_info['permission']['is_administrator'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('延迟时间不能大于 1440 分钟')));
			}
		}

		$_POST['message'] = my_trim($_POST['message']);
		if (!$_POST['message'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请输入回复内容')));
		}

		// TODO: 在管理后台添加字数选项
		if (cjk_strlen($_POST['message']) > 20000)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('内容字数不得多于 20000 字')));
		}

		if (!check_repeat_submission($_POST['message']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
		}
	}


	private function validate_video_metadata(&$metadata)
	{
		if (!$metadata)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('视频接口故障')));
		}

		if ($metadata['error'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, $metadata['error']));
		}

		$metadata['source_type'] = trim($metadata['source_type']);
		$metadata['source'] = trim($metadata['source']);
		$metadata['duration'] = intval($metadata['duration']);

		if (!$metadata['source_type'] OR !$metadata['source'] OR !$metadata['duration'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('无法解析视频')));
		}
	}


/*
+--------------------------------------------------------------------------
|   发布主题
+---------------------------------------------------------------------------
*/

	public function publish_question_action()
	{
		if (!$this->user_info['permission']['publish_question'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_question($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天发布的问题已经达到上限')));
		}

		$this->do_validate();

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('question');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($_POST['title']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$question_id = $this->model('publish')->publish_question(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
			'ask_user_id' => $_POST['ask_user_id'],
			'auto_focus' => !$_POST['anonymous'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/question/' . $question_id)
		), 1, null));
	}


	public function publish_article_action()
	{
		if (!$this->user_info['permission']['publish_article'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_article($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天发布的文章已经达到上限')));
		}

		$this->do_validate();

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('article');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($_POST['title']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$article_id = $this->model('publish')->publish_article(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/article/' . $article_id)
		), 1, null));
	}


	public function publish_video_action()
	{
		if (!$this->user_info['permission']['publish_video'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		$web_url = my_trim($_POST['web_url']);
		if (!Services_VideoParser::check_url($web_url))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('无法识别视频来源')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_video($this->user_id, $this->user_info['permission']['thread_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天发布的投稿已经达到上限')));
		}

		$this->do_validate();

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('video');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		// TODO: why?
		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		// 开销大的操作放在最后
		// 从视频网站取得元数据, 如时长
		// 以 'https://www.youtube.com/watch?v=abcdefghijk' 为例
		// $metadata['source_type'] 指视频网站, 如 'youtube'
		// $metadata['source'] 指该视频在所在网站上的 id, 如 'abcdefghijk'
		$metadata = Services_VideoParser::fetch_metadata_by_url($web_url);
		$this->validate_video_metadata($metadata);

		set_repeat_submission_digest($_POST['title']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$video_id = $this->model('publish')->publish_video(array(
			'title' => $_POST['title'],
			'message' => $_POST['message'],
			'category_id' => $_POST['category_id'],
			'uid' => $publish_uid,
			'topics' => $_POST['topics'],
			'permission_create_topic' => $this->user_info['permission']['create_topic'],
			'source_type' => $metadata['source_type'],
			'source' => $metadata['source'],
			'duration' => $metadata['duration'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/v/' . $video_id)
		), 1, null));
	}


	public function modify_question_action()
	{
		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题已锁定, 不能编辑')));
		}

		if (!$this->user_info['permission']['edit_question'])
		{
			if ($question_info['published_uid'] != $this->user_id)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个问题')));
			}
		}

		if (!$_POST['do_delete'])
		{
			$this->do_validate('modify');
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('question')->clear_question(
				$question_info['question_id'],
				$this->user_id
			);
		}
		else
		{
			$this->model('question')->modify_question(
				$question_info['question_id'],
				$this->user_id,
				$_POST['title'],
				$_POST['message'],
				$_POST['category_id']
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/question/' . $question_info['question_id'])
		), 1, null));

	}


	public function modify_article_action()
	{
		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
		}

		if ($article_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章已锁定, 不能编辑')));
		}

		if (!$this->user_info['permission']['edit_article'])
		{
			if ($article_info['uid'] != $this->user_id)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个文章')));
			}
		}

		if (!$_POST['do_delete'])
		{
			$this->do_validate('modify');
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('article')->clear_article(
				$article_info['id'],
				$this->user_id
			);
		}
		else
		{
			$this->model('article')->modify_article(
				$article_info['id'],
				$this->user_id,
				$_POST['title'],
				$_POST['message'],
				$_POST['category_id']
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/article/' . $article_info['id'])
		), 1, null));
	}


	public function modify_video_action()
	{
		if (!check_user_operation_interval_by_uid('modify', $this->user_id, get_setting('modify_content_interval')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('操作过于频繁, 请稍后再试')));
		}

		if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('投稿不存在')));
		}

		if ($video_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('投稿已锁定, 不能编辑')));
		}

		if (!$this->user_info['permission']['edit_video'])
		{
			if ($video_info['uid'] != $this->user_id)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个投稿')));
			}
		}

		if (!$_POST['do_delete'])
		{
			$web_url = my_trim($_POST['web_url']);
			$modify_source = !!$web_url;
			if ($modify_source AND !Services_VideoParser::check_url($web_url))
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('无法识别视频来源')));
			}

			$this->do_validate('modify');
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_user_operation_last_time_by_uid('modify', $this->user_id);

		if ($_POST['do_delete'])
		{
			$this->model('video')->clear_video(
				$video_info['id'],
				$this->user_id
			);
		}
		else
		{
			if ($modify_source)
			{
				$metadata = Services_VideoParser::fetch_metadata_by_url($web_url);
				$this->validate_video_metadata($metadata);

				$this->model('video')->modify_video_source(
					$video_info['id'],
					$metadata['source_type'],
					$metadata['source'],
					$metadata['duration']
				);
			}

			$this->model('video')->modify_video(
				$video_info['id'],
				$this->user_id,
				$_POST['title'],
				$_POST['message'],
				$_POST['category_id']
			);
		}

		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/v/' . $video_info['id'])
		), 1, null));

	}



/*
+--------------------------------------------------------------------------
|   发布回应
+---------------------------------------------------------------------------
*/

	public function publish_answer_action()
	{
		if (!$this->user_info['permission']['answer_question'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_question'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_answer($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的回复已经达到上限')));
		}

		$this->do_validate_reply();

		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($question_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的问题不能回复')));
		}

		// 判断是否是问题发起者
		if (get_setting('answer_self_question') == 'N' AND $question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能回复自己发布的问题，你可以修改问题内容')));
		}

		// 判断是否已回复过问题
		if ((get_setting('answer_unique') == 'Y'))
		{
			if ($this->model('answer')->has_answer_by_uid($question_info['question_id'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('一个问题只能回复一次，你可以编辑回复过的回复')));
			}
			$schedule = $this->model('answer')->fetch_one('scheduled_posts', 'id', "type = 'answer' AND parent_id = " . intval($question_info['question_id']) . " AND uid = " . intval($this->user_id));
			if ($schedule)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你已经使用延迟显示功能回复过该问题')));
			}
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('answer');
			$auto_focus = false;
		}
		else
		{
			$publish_uid = $this->user_id;
			$auto_focus = $_POST['auto_focus'];
		}

		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($_POST['message']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$answer_id = $this->model('publish')->publish_answer(array(
			'parent_id' => $question_info['question_id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'auto_focus' => $auto_focus,
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		$answer_info = $this->model('answer')->get_answer_by_id($answer_id);
		$answer_info['user_info'] = $this->model('account')->get_user_info_by_uid($publish_uid);
		$answer_info['answer_content'] = $this->model('question')->parse_at_user($answer_info['answer_content']);
		TPL::assign('answer_info', $answer_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::output('question/ajax/answer', false)
		), 1, null));
	}


	public function publish_article_comment_action()
	{
		if (!$this->user_info['permission']['comment_article'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_article'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_article_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的文章评论已经达到上限')));
		}

		$this->do_validate_reply();

		if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定文章不存在')));
		}

		if ($article_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的文章不能回复')));
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('question');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($_POST['message']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$comment_id = $this->model('publish')->publish_article_comment(array(
			'parent_id' => $article_info['id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'at_uid' => $_POST['at_uid'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		$comment_info = $this->model('article')->get_comment_by_id($comment_id);
		$comment_info['message'] = $this->model('question')->parse_at_user($comment_info['message']);
		TPL::assign('comment_info', $comment_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::output('article/ajax/comment', false)
		), 1, null));
	}


	public function publish_video_comment_action()
	{
		if (!$this->user_info['permission']['comment_video'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
		}

		if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_reply_video'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
		}

		if (!$this->model('ratelimit')->check_video_comment($this->user_id, $this->user_info['permission']['reply_limit_per_day']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你今天的投稿评论已经达到上限')));
		}

		$this->do_validate_reply();

		if (!$video_info = $this->model('video')->get_video_info_by_id($_POST['video_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('指定投稿不存在')));
		}

		if ($video_info['lock'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经锁定的投稿不能回复')));
		}

		if ($_POST['anonymous'])
		{
			$publish_uid = $this->get_anonymous_uid('video_comment');
		}
		else
		{
			$publish_uid = $this->user_id;
		}

		// !注: 来路检测后面不能再放报错提示
		if (!valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
		}

		set_repeat_submission_digest($_POST['message']);
		set_user_operation_last_time('publish', $this->user_id, $this->user_info['permission']);

		$comment_id = $this->model('publish')->publish_video_comment(array(
			'parent_id' => $video_info['id'],
			'message' => $_POST['message'],
			'uid' => $publish_uid,
			'at_uid' => $_POST['at_uid'],
		), $this->user_id, $_POST['later']);

		if ($_POST['later'])
		{
			// 延迟显示
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/delay_display/')
			), 1, null));
		}

		$comment_info = $this->model('video')->get_comment_by_id($comment_id);
		$comment_info['message'] = $this->model('question')->parse_at_user($comment_info['message']);
		TPL::assign('comment_info', $comment_info);
		H::ajax_json_output(AWS_APP::RSM(array(
			'ajax_html' => TPL::output('video/ajax/comment', false)
		), 1, null));
	}

}
