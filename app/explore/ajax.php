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
			$rule_action['actions'][] = 'list';
		}

		return $rule_action;
	}

	public function list_action()
	{
		$topic_ids = explode(',', $_GET['topic_id']);

		$posts_list = $this->model('posts')->get_posts_list_by_topic_ids(null, $topic_ids, $_GET['page'], get_setting('contents_per_page'));

		if ($posts_list)
		{
			foreach ($posts_list AS $key => $val)
			{
				if ($val['answer_count'])
				{
					$posts_list[$key]['answer_users'] = $this->model('question')->get_answer_users_by_question_id($val['question_id'], 2, $val['uid']);
				}
			}
		}

		TPL::assign('posts_list', $posts_list);

		TPL::output('explore/ajax/list');
	}
}