<?php

namespace Killi\Core\ORM\Handler;

/**
 *  Traitant MongoDB de l'ORM Killi
 *
 *  @package killi
 *  @class MongoHandler
 *  @Revision $Revision: 4460 $
 */

use \FieldDefinition;
use \ExtendedFieldDefinition;

use Killi\Core\ORM\Debug\Performance;
use Killi\Core\ORM\Debug\Trace;

use Killi\Core\ORM\ObjectManager;

use Killi\Core\Database\Driver\Mongo\MongoDbLayer;
use Killi\Core\Database\Driver\Mongo\MongoDatabase;

class MongoHandler extends AbstractHandler
{
	public function boot()
	{
		$collection = $this->_object->collection;
		$database = $this->_object->database;

		$this->_object_name = $this->_object_name;
		$this->_database_name = $database;
		$this->_collection_name = $collection;
		$this->_db = MongoDatabase::getInstance($database);
		$this->_collection = $this->_db->getCollection($collection);
	}

	protected function formatFilter($filters)
	{
		$new_filters = array();
		foreach($filters AS $filter)
		{
			if($this->_object->primary_key == $filter[0])
			{
				$filter[2] = new \MongoId($filter[2]);
			}
			$new_filters[$filter[0]] = $filter[2];
		}
		return $new_filters;
	}

	public function browse(array &$object_list=NULL, &$total = 0, array $fields = NULL, array $filters = NULL, array $order = NULL, $offset = NULL, $limit = 1000)
	{
		$filters = $this->formatFilter($filters);
		$cursor	= $this->_collection->find($filters);

		if($offset != NULL)
		{
			$cursor->skip($offset*$limit);
		}

		if($limit != NULL)
		{
			$cursor->limit($limit);
		}

		$cursor->timeout(60000);

		$total = $cursor->count();

		if($fields === NULL)
		{
			$fields = array();
			foreach($this->_object AS $field_name => $field)
			{
				if($field instanceof FieldDefinition)
				{
					$fields[] = $field_name;
				}
			}
		}

		$object_list = array();
		foreach($cursor AS $c)
		{
			$id = $c['_id']->{'$id'};

			$object = array();
			$object['_id']['value'] = $id;
			$object['_id']['editable'] = FALSE;

			foreach($fields AS $field)
			{
				if($field == '_id')
				{
					continue;
				}

				if(!isset($c[$field]))
				{
					$c[$field] = '';
				}

				if(is_array($c[$field]))
				{
					$c[$field] = json_encode($c[$field]);
				}

				$object[$field]['value'] = $c[$field];
				$object[$field]['editable'] = FALSE;
			}

			$object_list[$id] = $object;
		}
		return TRUE;
	}

	public function search(array &$object_id_list = NULL, &$total = 0, array $filters = array(), array $order = NULL, $offset = NULL, $limit = NULL, array $extended_result=array())
	{
		$filters = $this->formatFilter($filters);
		$cursor	= $this->_collection->find($filters);

		if($offset != NULL)
		{
			$cursor->skip($offset*$limit);
		}

		if($limit == NULL)
		{
			$limit = 1000;
		}

		$cursor->limit($limit);

		$total = $cursor->count();

		$object_list = array();
		foreach($cursor AS $c)
		{
			$id = $c['_id']->{'$id'};
			$object_id_list[$id] = $id;
		}

		return TRUE;
	}

	public function read($object_id_list, &$object_list, array $fields=NULL)
	{
		$unitaire = false;

		if(!is_array($object_id_list))
		{
			$unitaire = true;
			$object_id_list = array($object_id_list);
		}

		$id_list = array();
		foreach($object_id_list AS $id)
		{
			$id_list[] = new \MongoId($id);
		}

		$cursor = $this->_collection->find(array('_id' => array('$in' => $id_list)));

		if($fields === NULL)
		{
			$fields = array();
			foreach($this->_object AS $field_name => $field)
			{
				if($field instanceof FieldDefinition)
				{
					$fields[] = $field_name;
				}
			}
		}

		$object_list = array();
		foreach($cursor AS $c)
		{
			$id = $c['_id']->{'$id'};

			$object = array();
			$object['_id']['value'] = $id;
			$object['_id']['editable'] = FALSE;

			foreach($fields AS $field)
			{
				if($field == '_id')
				{
					continue;
				}

				if(!isset($c[$field]))
				{
					$c[$field] = '';
				}

				$object[$field]['value'] = $c[$field];
				$object[$field]['editable'] = FALSE;
			}

			$object_list[$id] = $object;
		}

		foreach($object_id_list AS $id)
		{
			if(!isset($object_list[$id]))
			{
				throw new Exception('Object id "' . $id . '" not found !');
			}
		}

		if($unitaire)
		{
			$object_list = reset($object_id_list);
		}

		return TRUE;
	}

	//-------------------------------------------------------------------------
	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE )
	{
		return TRUE;
	}

	//-------------------------------------------------------------------------
	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL)
	{
		return TRUE;
	}

	//-------------------------------------------------------------------------
	function count(&$total_record, $args=NULL)
	{
		return TRUE;
	}

	//-------------------------------------------------------------------------
	function unlink($object_id)
	{
		return TRUE;
	}
}
