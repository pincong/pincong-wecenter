<?php

class Services_WhereBuilder
{
	private static $condition_operators = array(
		'isNull',
		'isNotNull',
		'between',
		'notBetween',
		'like',
		'notLike',
		'in',
		'notIn',
		'eq',
		'notEq',
		'lt',
		'gt',
		'lte',
		'gte',
	);

	private static $concatenation_operators = array(
		'and',
		'or',
	);

	private static $data_types = array(
		'i',
		'd',
		's',
	);


	private static function _is_safe_string($string)
	{
		for ($i = 0, $l = strlen($string); $i < $l; $i++) {
			$c = ord($string[$i]);
			if ($c == 0x5f) // _
			{
				continue;
			}
			elseif ($c >= 0x30 AND $c <= 0x39) // 0-9
			{
				continue;
			}
			elseif ($c >= 0x41 AND $c <= 0x5a) // A-Z
			{
				continue;
			}
			elseif ($c >= 0x61 AND $c <= 0x7a) // a-z
			{
				continue;
			}
			return false;
		}
		return true;
	}

	private static function _convert_data_string($val)
	{
		if (!self::_is_safe_string($val))
		{
			$val = bin2hex($val);
			if (!$val)
			{
				return "''";
			}
			return '0x' . $val;
		}
		else
		{
			return "'" . $val . "'";
		}
	}

	private static function _convert_data_auto($val)
	{
		if (is_int($val))
		{
			return $val;
		}
		elseif (is_float($val))
		{
			if (is_infinite($val) OR is_nan($val))
			{
				return 0;
			}
			return $val;
		}
		elseif (is_string($val))
		{
			return self::_convert_data_string($val);
		}
		else
		{
			return intval($val);
		}
	}

	private static function _convert_data($val, $type)
	{
		if (!isset($type))
		{
			return self::_convert_data_auto($val);
		}

		if ($type === true)
		{
			return $val;
		}

		if (!is_string($type) OR !in_array($type, self::$data_types))
		{
			return intval($val);
		}

		if ($type == 'i')
		{
			return intval($val);
		}
		elseif ($type == 'd')
		{
			if (is_infinite($val) OR is_nan($val))
			{
				return 0;
			}
			return $val;
		}
		elseif ($type == 's')
		{
			return self::_convert_data_string(strval($val));
		}
	}

	// e.g. ['id', 'between', 1, 99]
	private static function _parse_condition_between($params, $not = false)
	{
		if (count($params) < 4)
		{
			return false;
		}
		$type = $params[4];
		$from = self::_convert_data($params[2], $type);
		$to = self::_convert_data($params[3], $type);
		$result = "BETWEEN {$from} AND {$to}";
		if ($not)
		{
			$result = 'NOT ' . $result;
		}
		return $result;
	}

	// e.g. ['name', 'like', 'admin%']
	private static function _parse_condition_like($params, $not = false)
	{
		if (count($params) < 3)
		{
			return false;
		}
		$type = $params[3];
		$val = self::_convert_data($params[2], $type);
		$result = "LIKE {$val}";
		if ($not)
		{
			$result = 'NOT ' . $result;
		}
		return $result;
	}

	// e.g. ['id', 'in', [1, 2, 3]]
	private static function _parse_condition_in($params, $not = false)
	{
		if (count($params) < 3)
		{
			return false;
		}
		$array = $params[2];
		if (!is_array($array) OR count($array) < 1)
		{
			return false;
		}
		$type = $params[3];
		foreach ($array as &$val)
		{
			$val = self::_convert_data($val, $type);
		}
		unset($val);
		$array = implode(', ', $array);
		$result = "IN ({$array})";
		if ($not)
		{
			$result = 'NOT ' . $result;
		}
		return $result;
	}

	// e.g. ['id', 'eq', 3]
	private static function _parse_condition_comparison($params, $operator)
	{
		if (count($params) < 3)
		{
			return false;
		}
		$type = $params[3];
		$val = self::_convert_data($params[2], $type);
		return $operator . ' ' . $val;
	}

	private static function _parse_condition($params)
	{
		$column = $params[0];
		if (!is_string($column) OR $column == '' OR !self::_is_safe_string($column))
		{
			return false;
		}

		switch ($params[1])
		{
			case 'isNull':
				$result = 'IS NULL';
				break;
			case 'isNotNull':
				$result = 'IS NOT NULL';
				break;
			case 'between':
				$result = self::_parse_condition_between($params);
				break;
			case 'notBetween':
				$result = self::_parse_condition_between($params, 'not');
				break;
			case 'like':
				$result = self::_parse_condition_like($params);
				break;
			case 'notLike':
				$result = self::_parse_condition_like($params, 'not');
				break;
			case 'in':
				$result = self::_parse_condition_in($params);
				break;
			case 'notIn':
				$result = self::_parse_condition_in($params, 'not');
				break;
			case 'eq':
				$result = self::_parse_condition_comparison($params, '=');
				break;
			case 'notEq':
				$result = self::_parse_condition_comparison($params, '<>');
				break;
			case 'lt':
				$result = self::_parse_condition_comparison($params, '<');
				break;
			case 'gt':
				$result = self::_parse_condition_comparison($params, '>');
				break;
			case 'lte':
				$result = self::_parse_condition_comparison($params, '<=');
				break;
			case 'gte':
				$result = self::_parse_condition_comparison($params, '>=');
				break;
			default:
				return false;
		}
		if ($result === false)
		{
			return false;
		}
		return "`{$column}` {$result}";
	}

	private static function _append(&$where, $part)
	{
		if (!$part)
		{
			return;
		}

		if ($part == 'AND' OR $part == 'OR')
		{
			if (!$where OR substr($where, -1, 1) != ')')
			{
				return;
			}
			$where .= ' ';
			$where .= $part;
			return;
		}

		if (!$where)
		{
			$where .= $part;
			return;
		}

		if (substr($where, -1, 1) == ')')
		{
			$where .= ' AND ';
		}
		else
		{
			$where .= ' ';
		}
		$where .= $part;
		return;
	}

	private static function _parse_array($array)
	{
		$result = '';
		for ($i = 0, $l = count($array); $i < $l; $i++)
		{
			$val = $array[$i];
			if (!is_array($val)) // string
			{
				if ($i == 0 AND $l > 1 AND in_array($array[$i + 1], self::$condition_operators))
				{
					$r = self::_parse_condition($array);
					if ($r === false)
					{
						return false;
					}
					self::_append($result, $r);
					break;
				}
				elseif (in_array($val, self::$concatenation_operators)) // and, or
				{
					self::_append($result, strtoupper($val));
				}
				else
				{
					return false;
				}
			}
			else
			{
				$r = self::_parse_array($val);
				if ($r === false)
				{
					return false;
				}
				if (!!$r)
				{
					self::_append($result, '('. $r . ')');
				}
			}
		}
		return $result;
	}

	public static function build($array)
	{
		if (!is_array($array))
		{
			return false;
		}
		return self::_parse_array($array);
	}

}
