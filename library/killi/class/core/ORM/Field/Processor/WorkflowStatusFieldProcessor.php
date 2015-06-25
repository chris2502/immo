<?php

namespace Killi\Core\ORM\Field\Processor;

/**
 *  Classe de calcul des champs Workflow Status.
 *
 *  @package killi
 *  @class WorkflowStatusFieldProcessor
 *  @Revision $Revision: 4558 $
 */
use \FieldDefinition;

class WorkflowStatusFieldProcessor extends AbstractFieldProcessor
{
	protected $_workflow_status_list = array();

	public function select(FieldDefinition $field)
	{
		/* Les champs virtuels ne sont pas traités. */
		if($field->isVirtual())
		{
			return TRUE;
		}

		/* Champs récupéré sur l'objet parent. */
		if($field->objectName != $this->_orm->getObjectName())
		{
			return TRUE;
		}

		/* Alias SQL */
		if($field->isSQLAlias())
		{
			return TRUE;
		}

		if($field->type == 'workflow_status')
		{
			$this->_workflow_status_list[$field->attribute_name] = $field;
		}

		return TRUE;
	}

	public function read(&$object_list)
	{
		global $hDB;

		//---Process workflow status
		foreach($this->_workflow_status_list AS $workflow_status => $field)
		{
			$workflow_name	= $field->object_relation;
			$object_id		=  $field->related_relation;

			$object_id_list = array();

			//---Id list
			foreach($object_list as $id=>$object)
			{
				if(is_array($this->_orm->_object_key_name))
				{
					if(!isset($object[$object_id]))
					{
						throw new Exception("Process workflow status failed");
					}
					$object_id_list[$object[$object_id]['value']][] = $id;
				}
				else
				{
					$object_id_list[$id][] = $id;
				}
				$object_list[$id][$workflow_status]['value']	= NULL;
				$object_list[$id][$workflow_status]['url']		= NULL;
				$object_list[$id][$workflow_status]['workflow_node_id'] = array();
				$object_list[$id][$workflow_status]['workflow_node_name'] = array();
			}

			//---Nom des objects enfants
			$object_name_list = array('"'.strtolower($this->_orm->getObjectName()).'"');

			foreach(ORM::$_objects as $obj)
			{
				$instance = $obj['class'];
				if(isset($instance->primary_key) && !is_array($instance->primary_key))
				{
					$pk = $instance->primary_key;
					if (isset($instance->$pk) && (strtolower($instance->$pk->type)==strtolower('extends:'.$this->_orm->getObjectName())))
					{
						$object_name_list[] = '"'.strtolower(get_class($instance)).'"';
					}
					unset($pk);
				}
				unset($instance);
			}

			$cp = ($object_id=='id')?" and (killi_workflow_node.object in (".join(',',$object_name_list).")) ":'';
			$query = "select killi_workflow_token.$object_id,killi_workflow_node.etat,killi_workflow_node.workflow_node_id,killi_workflow_node.node_name,killi_workflow_node.`object`, UNIX_TIMESTAMP(killi_workflow_token.date) as token_date, DATEDIFF( NOW(), killi_workflow_token.date ) AS dday
			from killi_workflow_token,killi_workflow_node,killi_workflow
			where killi_workflow_token.$object_id in (".join(',',array_keys($object_id_list)).")
			and (killi_workflow_token.node_id=killi_workflow_node.workflow_node_id)
			and (killi_workflow_node.workflow_id=killi_workflow.workflow_id)
			$cp
			and (killi_workflow.workflow_name=\"".$workflow_name."\")
					order by killi_workflow_token.date ASC";

			unset($cp);

			$rresult = NULL;
			$rnumrows = NULL;

			$hDB->db_select($query,$rresult,$rnumrows);

			unset($query);
			unset($rnumrows);

			while(($row = $rresult->fetch_assoc())!= NULL)
			{
				$o_id_list = $object_id_list[$row[$object_id]];
				foreach($o_id_list AS $o_id)
				{
					$object_list[$o_id][$workflow_status]['url']	   = NULL;
					if ((isset($object_list[$o_id][$workflow_status]['value'])))
					{
						$object_list[$o_id][$workflow_status]['value']	.= ' & '.$row['etat'].' ('.date('d/m/Y',$row['token_date']).' j+' . $row['dday'] . ')';
						$object_list[$o_id][$workflow_status]['workflow_node_id'][]	= $row['workflow_node_id'];
						$object_list[$o_id][$workflow_status]['workflow_node_name'][]	= $row['node_name'];
					}
					else
					{
						Security::crypt($row['workflow_node_id'], $crypt_node_id);
						$object_list[$o_id][$workflow_status]['value']	= $row['etat'].' ('.date('d/m/Y',$row['token_date']).' j+' . $row['dday'] . ')';
						$object_list[$o_id][$workflow_status]['workflow_node_id'][]	= $row['workflow_node_id'];
						$object_list[$o_id][$workflow_status]['workflow_node_name'][]	= $row['node_name'];

						if(!empty($row['object']))
						{
							$hInstance = ORM::getObjectInstance($row['object']);
							if ($hInstance->view)
							{
								$object_list[$o_id][$workflow_status]['url']	  = './index.php?action='.$row['object'].'.edit&crypt/workflow_node_id='.$crypt_node_id;
							}
						}

					}
				}
				unset($row);
			}
			$rresult->free();
		}
		$this->outcast($this->_workflow_status_list, $object_list);
		return TRUE;
	}
}
