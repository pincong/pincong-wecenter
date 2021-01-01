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

class search_class extends AWS_MODEL
{
	public function search($q, $search_type, $page = 1, $limit = 20)
	{
		if (!$q)
		{
			return false;
		}
		$q = escape_like_clause(htmlspecialchars($q));

		if ($search_type == 'users')
		{
			$result_list = $this->fetch_all('users', [['forbidden', 'eq', 0], ['user_name', 'like', $q . '%', 's']], 'uid ASC', $page, $limit);
		}
		else if ($search_type == 'topics')
		{
			$result_list = $this->fetch_all('topic', ['topic_title', 'like', $q . '%', 's'], 'topic_id ASC', $page, $limit);
		}
		else
		{
			return false;
		}

		if ($result_list)
		{
			foreach ($result_list as $result_info)
			{
				$result = $this->prase_result_info($search_type, $result_info);

				if (is_array($result))
				{
					$data[] = $result;
				}
			}
		}

		return $data;
	}


	private function prase_result_info($result_type, $result_info)
	{
		if ($result_type == 'users')
		{
			$search_id = $result_info['uid'];
			$name = $result_info['user_name'];
			$url = url_rewrite('/people/' . safe_url_encode($name));

			$detail = array(
				'avatar_file' => UF::avatar($result_info, 'mid'),	// 头像
				'signature' => $result_info['signature'],	// 签名
				'reputation' =>  intval($result_info['reputation']),	// 声望
				'agree_count' =>  $result_info['agree_count'],	// 赞同
			);
		}
		else if ($result_type == 'topics')
		{
			$search_id = $result_info['topic_id'];
			$name = $result_info['topic_title'];
			$url = url_rewrite('/topic/' . safe_url_encode($name));

			$detail = array(
				'topic_pic'=> get_topic_pic_url($result_info, 'mid'),
				'topic_id' => $result_info['topic_id'],	// 话题 ID
				'focus_count' => $result_info['focus_count'],
				'discuss_count' => $result_info['discuss_count'],	// 讨论数量
				'topic_description' => $result_info['topic_description']
			);
		}

		if ($result_type)
		{
			return array(
				'uid' => $result_info['uid'],
				'type' => $result_type,
				'url' => $url,
				'search_id' => $search_id,
				'name' => $name,
				'detail' => $detail
			);
		}
	}

}