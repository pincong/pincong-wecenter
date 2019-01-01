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

    public function fetch_question_category_action()
    {
        if (get_setting('category_enable') != 'Y')
        {
            exit(json_encode(array()));
        }

        exit($this->model('system')->build_category_json('question', 0));
    }

    public function modify_question_action()
    {
        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
        }

        if ($question_info['lock'] AND !($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题已锁定, 不能编辑')));
        }

        if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_question'])
        {
            if ($question_info['published_uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个问题')));
            }
        }

        if (!$_POST['category_id'] AND get_setting('category_enable') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请选择分类')));
        }

        $question_content = my_trim($_POST['question_content']);

        if (cjk_strlen($question_content) < 5)
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得少于 5 个字')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($question_content) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得大于') . ' ' . get_setting('question_title_limit') . ' ' . AWS_APP::lang()->_t('字节')));
        }

        $question_detail = my_trim($_POST['question_detail']);

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        if ($_POST['do_delete'] AND !$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除问题的权限')));
        }

        if ($_POST['do_delete'])
        {
            if ($this->user_id != $question_info['published_uid'])
            {
                $this->model('account')->send_delete_message($question_info['published_uid'], $question_info['question_content'], $question_info['question_detail']);
            }

            $this->model('question')->remove_question($question_info['question_id']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/home/explore/')
            ), 1, null));
        }

        $IS_MODIFY_VERIFIED = TRUE;

        if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'] AND $question_info['published_uid'] != $this->user_id)
        {
            $IS_MODIFY_VERIFIED = FALSE;
        }

        $this->model('question')->update_question($question_info['question_id'], $question_content, $question_detail, $this->user_id, $IS_MODIFY_VERIFIED, $_POST['modify_reason'], $question_info['anonymous'], $_POST['category_id']);

        if ($this->user_id != $question_info['published_uid'])
        {
            $this->model('question')->add_focus_question($question_info['question_id'], $this->user_id);

            $this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_MOD_QUESTION, notify_class::CATEGORY_QUESTION, $question_info['question_id'], array(
                'from_uid' => $this->user_id,
                'question_id' => $question_info['question_id']
            ));
        }

        if ($_POST['category_id'] AND $_POST['category_id'] != $question_info['category_id'])
        {
            $category_info = $this->model('system')->get_category_info($_POST['category_id']);

            ACTION_LOG::save_action($this->user_id, $question_info['question_id'], ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::MOD_QUESTION_CATEGORY, $category_info['title'], $category_info['id']);
        }

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/question/' . $question_info['question_id'] . '?column=log&rf=false')
        ), 1, null));

    }

    public function publish_question_action()
    {
        if (!$this->user_info['permission']['publish_question'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        $question_content = my_trim($_POST['question_content']);
        if (!$question_content)
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入问题标题')));
        }

        if (!check_repeat_submission($question_content))
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
        }

        if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_question'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择问题分类')));
        }

        if (cjk_strlen($question_content) < 5)
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('问题标题字数不得少于 5 个字')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($question_content) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题标题字数不得大于 %s 字节', get_setting('question_title_limit'))));
        }

        $question_detail = my_trim($_POST['question_detail']);

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

        if (!$_POST['topics'] AND get_setting('new_question_force_add_topic') == 'Y')
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为问题添加话题')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        {
            $question_id = $this->model('publish')->publish_question(
                $question_content,
                $question_detail,
                $_POST['category_id'],
                $this->user_id,
                $_POST['topics'],
                $_POST['anonymous'],
                null,
                $_POST['ask_user_id'],
                $this->user_info['permission']['create_topic'],
                null,
                $_POST['later']
            );

            if (intval($_POST['later']))
            {
                $url = get_js_url('/publish/delay_display/');
            }
            else
            {
                $url = get_js_url('/question/' . $question_id);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $url
            ), 1, null));
        }
    }

    public function publish_article_action()
    {
        if (!$this->user_info['permission']['publish_article'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的等级还不够')));
        }

        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        $article_title = my_trim($_POST['title']);
        if (!$article_title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入文章标题')));
        }

        if (!check_repeat_submission($article_title))
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请不要重复提交')));
        }

        if (!$this->model('currency')->check_balance_for_operation($this->user_info['currency'], 'currency_system_config_new_article'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余%s已经不足以进行此操作', get_setting('currency_name'))));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章分类')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($article_title) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章标题字数不得大于 %s 字节', get_setting('question_title_limit'))));
        }

        $article_content = my_trim($_POST['message']);

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
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('单个文章话题数量最多为 %s 个, 请调整话题数量', get_setting('question_topics_limit'))));
            }
        }
        if (get_setting('new_question_force_add_topic') == 'Y' AND !$_POST['topics'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请为文章添加话题')));
        }

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        {
            $article_id = $this->model('publish')->publish_article(
                $article_title,
                $article_content,
                $this->user_id,
                $_POST['topics'],
                $_POST['category_id'],
                null,
                $this->user_info['permission']['create_topic'],
                $_POST['anonymous'],
                $_POST['later']
            );

            if (intval($_POST['later']))
            {
                $url = get_js_url('/publish/delay_display/');
            }
            else
            {
                $url = get_js_url('/article/' . $article_id);
            }

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => $url
            ), 1, null));
        }
    }

    public function modify_article_action()
    {
        if (human_valid('question_valid_hour') AND !AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('请填写正确的验证码')));
        }

        if (!$article_info = $this->model('article')->get_article_info_by_id($_POST['article_id']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章不存在')));
        }

        if ($article_info['lock'] AND !($this->user_info['permission']['is_administrator'] OR $this->user_info['permission']['is_moderator']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章已锁定, 不能编辑')));
        }

        if (!$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'] AND !$this->user_info['permission']['edit_article'])
        {
            if ($article_info['uid'] != $this->user_id)
            {
                H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你没有权限编辑这个文章')));
            }
        }

        $article_title = my_trim($_POST['title']);

        if (!$article_title)
        {
            H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('请输入文章标题')));
        }

        if (get_setting('category_enable') == 'N')
        {
            $_POST['category_id'] = 1;
        }

        if (!$_POST['category_id'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('请选择文章分类')));
        }

        if (get_setting('question_title_limit') > 0 AND cjk_strlen($article_title) > get_setting('question_title_limit'))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('文章标题字数不得大于') . ' ' . get_setting('question_title_limit') . ' ' . AWS_APP::lang()->_t('字节')));
        }

        $article_content = my_trim($_POST['message']);

        // !注: 来路检测后面不能再放报错提示
        if (!valid_post_hash($_POST['post_hash']))
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('页面停留时间过长,或内容已提交,请刷新页面')));
        }

        if ($_POST['do_delete'] AND !$this->user_info['permission']['is_administrator'] AND !$this->user_info['permission']['is_moderator'])
        {
            H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对不起, 你没有删除文章的权限')));
        }

        if ($_POST['do_delete'])
        {
            if ($this->user_id != $article_info['uid'])
            {
                $this->model('account')->send_delete_message($article_info['uid'], $article_info['title'], $article_info['message']);
            }

            $this->model('article')->remove_article($article_info['id']);

            H::ajax_json_output(AWS_APP::RSM(array(
                'url' => get_js_url('/home/explore/')
            ), 1, null));
        }

        $this->model('article')->update_article($article_info['id'], $this->user_id, $article_title, $article_content, $_POST['topics'], $_POST['category_id'], $this->user_info['permission']['create_topic']);

        H::ajax_json_output(AWS_APP::RSM(array(
            'url' => get_js_url('/article/' . $article_info['id'])
        ), 1, null));
    }

}
