<?php

/**
 *  @class ConditionModule
 *  @Revision $Revision: 3647 $
 *
 */

/*
{
	"output1": {
		"operateur": "and",
		"operande1": [
			{
				"operande1": {
					"attribute": "process1.contact.firstname"
				},
				"operateur": "!=",
				"operande2": ""
			},
			{
				"operande1": {
					"attribute": "process1.contact.lastname"
				},
				"operateur": "!=",
				"operande2": ""
			},
			{
				"operande1": {
					"attribute": "process1.contact.birthdate"
				},
				"operateur": "!=",
				"operande2": ""
			}
		]
	}
}
*/

class ConditionModule extends Module
{
	public $_transit = FALSE;

	protected function getOperande($operande)
	{
		if(is_string($operande))
		{
			return $operande;
		}

		if(!isset($operande['attribute']))
		{
			return $operande;
		}

		return $this->getValue($operande['attribute']);
	}

	protected function conditionProcessor($condition)
	{
		switch(strtolower($condition['operateur']))
		{
			case 'and':
				$result = true;
				foreach($condition['operande1'] AS $op)
				{
					$result = $result && $this->conditionProcessor($op);
					if(!$result) return FALSE;
				}
				return $result;
				break;
			case 'or':
				$result = false;
				foreach($condition['operande1'] AS $op)
				{
					$result = $result || $this->conditionProcessor($op);
					if($result) return TRUE;
				}
				return $result;
				break;
			case '=':
				if($this->getOperande($condition['operande1']) == $this->getOperande($condition['operande2']))
				{
					return TRUE;
				}
				break;
			case '!=':
				if($this->getOperande($condition['operande1']) != $this->getOperande($condition['operande2']))
				{
					return TRUE;
				}
				break;
			default:
				throw new Exception('Opérateur "'.$condition['operateur'].'" non reconnu !');
		}

		return FALSE;
	}

	public function execute()
	{
		$cases = $this->getModuleData();

		$link_name = NULL;
		foreach($cases AS $case => $condition)
		{
			if(!empty($condition) && $this->conditionProcessor($condition))
			{
				$link_name = $case;
				break;
			}
		}

		return $this->_transit = $this->goNext($link_name);
	}

	public function checkNext()
	{
		return $this->_transit;
	}
}
