<?php

namespace Killi\Core\ORM\Handler;

/**
 *  Traitant Native JSON Browse de l'ORM Killi
 *
 *  @package killi
 *  @class MySQLHandler
 *  @Revision $Revision: 4568 $
 */

use \FieldDefinition;

class NJBHandler  extends AbstractHandler
{
	public function boot()
	{
		if (!isset($this->_object->json['path']) || empty($this->_object->json['path']))
		{
			throw new Exception("Unset or empty json['path'] parameter in ".$this->_object_name.' object !');
		}

		$this->_object->json['ssl'] = (substr($this->_object->json['path'], 0, 5)=='https'); // bool

		if ($this->_object->json['ssl'])
		{
			if (!isset($this->_object->json['cert']) || empty($this->_object->json['cert']))
			{
				throw new Exception("Unset or empty json['cert'] parameter in ".$this->_object_name.' object !');
			}

			if (!isset($this->_object->json['cert_pwd']) || empty($this->_object->json['cert_pwd']))
			{
				throw new Exception("Unset or empty json['cert_pwd'] parameter in ".$this->_object_name.' object !');
			}
		}
	}
	//---------------------------------------------------------------------
	private function buildFieldList($fields)
	{
		if($fields === NULL)
		{
			$fields = array();
			foreach($this->_object as $key => $value)
			{
				if ($value instanceof FieldDefinition)
				{
					$fields[] = $key;
				}
			}
		}

		 // on retire les champs calculés locaux
		foreach($fields AS $position => $field)
		{
			if($this->_object->$field->isFunction())
			{
				unset($fields[$position]);
			}
		}

		return $fields;
	}
	//---------------------------------------------------------------------
	private function request($action, array $query, &$result = NULL)
	{
		$curl = new KilliCurl($this->_object->json['path'].'index.php?action=json.'.$action);

		if (isset($this->_object->json['login']))
		{
			$curl->setUser($this->_object->json['login'], $this->_object->json['password']);
		}
		else
		{
			if(isset($_SESSION['_USER']))
			{
				$curl->setUser($_SESSION['_USER']['login']['value'], $_SESSION['_USER']['password']['value']);
			}
		}

		if (isset($this->_object->json['ssl']) && !empty($this->_object->json['ssl']))
		{
			$curl->setSSL($this->_object->json['cert'], $this->_object->json['cert_pwd']);
		}

		if (isset($this->_object->json['object']))
		{
			$query['object'] = $this->_object->json['object'];
		}
		else
		{
			$query['object'] = strtolower($this->_object_name);
		}

		// local domain
		if(!empty($this->_object->object_domain))
		{
			if(!isset($query['filter']) || !is_array($query['filter']))
			{
				$query['filter'] = array();
			}

			foreach($this->_object->object_domain AS $domain_rule)
			{
				$query['filter'][] = $domain_rule;
			}
		}

		// local order
		if(isset($this->_object->order) && (!isset($query['order']) || $query['order'] == NULL))
		{
			$query['order'] = $this->_object->order;
		}

		$curl->setPost('data', json_encode($query));

		try
		{
			$result = $curl->request();
		}
		catch (Exception $e)
		{
			$ex = new NativeJSONBrowsingException($e->getMessage());

			if(property_exists($e, 'curl'))
			{
				$ex->curl = $e->curl;
			}

			throw $ex;
		}
	}
	//-------------------------------------------------------------------------
	function read($u_original_id_list, &$object_list, array $original_fields=NULL)
	{
		$query=array
		(
			'keys'=>$u_original_id_list,
			'fields'=>$this->buildFieldList($original_fields)
		);

		$this->request('read', $query, $object_list);

		reset($object_list);

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE )
	{
		$query=array
		(
			'data'=>$object_data
		);

		$this->request('create', $query, $object_id);

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL)
	{
		$query=array
		(
			'key'=>$object_id,
			'data'=>$object
		);

		$this->request('write', $query, $result);

		$affected = $result['affected'];

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function browse(array &$object_list=NULL, &$total_record=0, array $fields=NULL, array $args=NULL, array $tri=NULL, $offset=0, $limit=NULL)
	{
		$query=array
		(
			'fields'=>$this->buildFieldList($fields),
			'filter'=>$args,
			'order'=>$tri,
			'offset'=>$offset,
			'limit'=>$limit
		);

		$this->request('browse', $query, $object_list);

		reset($object_list);

		if($this->_count_total == TRUE)
		{
			$this->count($total_record, $args);
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function search(array &$object_id_list=NULL,&$total_record=0,array $args=NULL,array $order=NULL,$offset=0,$limit=NULL,array $extended_result=array())
	{
		$query=array
		(
			'filter'=>$args,
			'order'=>$order,
			'offset'=>$offset,
			'limit'=>$limit
		);

		$this->request('search', $query, $object_id_list);

		reset($object_id_list);

		if($this->_count_total == TRUE)
		{
			$this->count($total_record, $args);
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function count(&$total_record, $args=NULL)
	{
		$query=array
		(
			'filter'=>$args
		);

		$this->request('count', $query, $total_record);

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function unlink($object_id)
	{
		$query=array
		(
			'key'=>$object_id
		);

		$this->request('unlink', $query);

		return TRUE;
	}
}
