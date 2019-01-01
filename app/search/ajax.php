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

	public function search_result_action()
	{
		if (!in_array($_GET['search_type'], array('questions', 'topics', 'users', 'articles')))
		{
			$_GET['search_type'] = null;
		}

		$search_result = $this->model('search')->search(cjk_substr($_GET['q'], 0, 64), $_GET['search_type'], $_GET['page'], get_setting('contents_per_page'), null, $_GET['recommend']);

		if ($this->user_id AND $search_result)
		{
			foreach ($search_result AS $key => $val)
			{
				switch ($val['type'])
				{
					case 'questions':
						$search_result[$key]['focus'] = $this->model('question')->has_focus_question($val['search_id'], $this->user_id);

						break;

					case 'topics':
						$search_result[$key]['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $val['search_id']);

						break;

					case 'users':
						$search_result[$key]['focus'] = $this->model('follow')->user_follow_check($this->user_id, $val['search_id']);

						break;
				}
			}
		}

		TPL::assign('search_result', $search_result);

		TPL::output('search/ajax/search_result');
	}

	public function search_action()
	{
		$limit = intval($_GET['limit']);
		if ($limit > 50)
		{
			$limit = 50;
		}
		$result = $this->model('search')->search(cjk_substr($_GET['q'], 0, 64), $_GET['type'], 1, $limit, $_GET['topic_ids'], $_GET['recommend']);

		if (!$result)
		{
			$result = array();
		}

		if ($_GET['is_question_id'] AND is_digits($_GET['q']))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['q']);

			if ($question_info)
			{
				$result[] = $this->model('search')->prase_result_info($question_info);
			}
		}

		H::ajax_json_output($result);
	}
}