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

class core_pagination
{
	public function create($params)
	{
		if ($params['total_rows'] == 0 OR $params['per_page'] == 0)
		{
			return null;
		}

		$num_pages = ceil($params['total_rows'] / $params['per_page']);
		if ($num_pages < 2)
		{
			return null;
		}

		$result = array(
			'links' => array()
		);

		$cur_page = intval($_GET['page'] ?? null);
		if ($cur_page < 1)
		{
			$cur_page = 1;
		}
		else if ($cur_page > $num_pages)
		{
			$cur_page = $num_pages;
		}
		$result['cur_page'] = $cur_page;

		$num_links = $params['num_links'] ?? null; // 当前页码前后各显示多少页
		if (!$num_links)
		{
			$num_links = 3;
		}
		else if ($num_links < 1)
		{
			$num_links = 1;
		}

		if (isset($params['base_url']))
		{
			$base_url = $params['base_url'];
			if (substr($base_url, -1, 1) == '/')
			{
				$page_base_url = $base_url . 'page-';
			}
			else
			{
				$page_base_url = $base_url . '__page-';
			}
		}
		else
		{
			$base_url = '';
			$page_base_url = 'page-';
		}

		if (isset($params['prefix']))
		{
			$prefix = $params['prefix'];
		}
		else
		{
			$prefix = '';
		}

		if (isset($params['suffix']))
		{
			$suffix = $params['suffix'];
		}
		else
		{
			$suffix = '';
		}

		if ($cur_page > ($num_links + 1))
		{
			$result['first'] = $prefix . $base_url . $suffix;
		}

		if (($cur_page + $num_links) < $num_pages)
		{
			$result['last'] = $prefix . $page_base_url . $num_pages . $suffix;
		}

		if  ($cur_page > 1)
		{
			if ($cur_page == 2)
			{
				$result['prev'] = $prefix . $base_url . $suffix;
			}
			else
			{
				$result['prev'] = $prefix . $page_base_url . ($cur_page - 1) . $suffix;
			}
		}

		if ($cur_page < $num_pages)
		{
			$result['next'] = $prefix . $page_base_url . ($cur_page + 1) . $suffix;
		}

		$start = $cur_page - $num_links;
		if ($start < 1)
		{
			$start = 1;
		}
		$end = $cur_page + $num_links;
		if ($end > $num_pages)
		{
			$end = $num_pages;
		}

		for ($n = $start; $n <= $end; $n++)
		{
			if ($n == 1)
			{
				$result['links'][$n] = $prefix . $base_url . $suffix;
			}
			else
			{
				$result['links'][$n] = $prefix . $page_base_url . $n . $suffix;
			}
		}

		return $result;
	}

}
