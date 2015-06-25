<?php

/**
 *
 *  @class DBParam
 *  @Revision $Revision: 4139 $
 *
 */

class DBParam
{
	public static function get()
	{
		$args = func_get_args();
		if (count($args) == 0)
		{
			return FALSE;
		}
		else
		{
			$hORMParams = ORM::getORMInstance('parametric');
			$param_list = array();
			$hORMParams->browse($param_list, $num_param, array('parametric_datatype', 'parametric_name', 'parametric_value'), array(array('parametric_name', 'in', $args)));
			$return_list = array();
			foreach ($param_list as $param_id => $param)
			{
				$return_list[$param['parametric_name']['value']] = ($param['parametric_datatype']['value'] == 'integer')? intval($param['parametric_value']['value']) : $param['parametric_value']['value'];
			}
			unset($hORMParams, $param_list);
			if (count($return_list) == 1)
			{
				return reset($return_list);
			}
			else
			{
				return $return_list;
			}
		}
	}

	public function set($param_name, $param_value)
	{
		$hORMParam = ORM::getORMInstance('parametric');
		$param_list = array();
		$hORMParam->browse($param_list, $num_param, array('parametric_name', 'parametric_value', 'parametric_id'), array(array('parametric_name', '=', $param_name)));
		if ($num_param > 1)
		{
			return FALSE;
		}
		list($param_id, $param) = each($param_list);
		if ($param['parametric_value']['value'] != $param_value)
		{
			$hORMParam->write($param_id, array('parametric_value' => $param_value));
		}
		return TRUE;
	}
}
