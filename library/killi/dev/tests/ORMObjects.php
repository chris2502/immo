<?php

/**
 *  Ensemble d'objet réutilisable pour les tests d'ORM.
 *
 *  @Revision $Revision: 4445 $
 *
 */

/* Objet simple en base de données. */
class SimpleObject
{
	public $description = 'Objet simple';
	public $table = 'test_simple_object';
	public $primary_key = 'simpleobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->simpleobject_id = new PrimaryFieldDefinition ();
		$this->simpleobject_name = new TextFieldDefinition ();
		$this->simpleobject_value = new IntFieldDefinition ();
		$this->simpleobject_check = new BoolFieldDefinition ();
		$this->simpleobject_textarea = new TextareaFieldDefinition ();
		$this->simpleobject_time = new TimeFieldDefinition ();
		$this->simpleobject_date = new DateFieldDefinition ();
		$this->simpleobject_serialized = new SerializedFieldDefinition ();
	}
}

class SimpleObjectMethod extends Common
{

	public function getReferenceString(array $id_list, array &$references)
	{
		$references = array ();
		foreach ( $id_list as $id )
		{
			$references [$id] = 'Reference : ' . $id;
		}
	}
}

ORM::declareObject ( 'SimpleObject' );

/* Object inexistant en base de données. */
class UnexistingObject
{
	public $description = 'Objet inexistant en base de données';
	public $table = 'test_unexisting_object';
	public $primary_key = 'unexistingobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->unexistingobject_id = new PrimaryFieldDefinition ();
		$this->unexistingobject_name = new TextFieldDefinition ();
	}
}

ORM::declareObject ( 'UnexistingObject' );

/* Objet many2one en base de données. */
class Many2OneObject
{
	public $description = 'Objet many2one';
	public $table = 'test_many2one_object';
	public $primary_key = 'many2oneobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->many2oneobject_id = new PrimaryFieldDefinition ();
		$this->many2oneobject_name = new TextFieldDefinition ();
		$this->simpleobject_id = new Many2oneFieldDefinition ( 'SimpleObject' );
	}
}

ORM::declareObject ( 'Many2OneObject' );

/* Objet many2many en base de données. */
class Many2ManyObject
{
	public $description = 'Objet many2many';
	public $table = 'test_many2many_object';
	public $primary_key = 'many2manyobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->many2manyobject_id = new PrimaryFieldDefinition ();
		$this->many2manyobject_name = new TextFieldDefinition ();
		$this->simpleobject_id = new Many2manyFieldDefinition ( 'SimpleObject' );
	}
}

ORM::declareObject ( 'Many2ManyObject' );

/* Table de liaison entre le many2many et le simpleobject */
class Many2ManyObjectSimpleObject
{
	public $description = 'Objet Many2Many-Simple link';
	public $table = 'test_m2mso_object';
	public $primary_key = 'm2msoobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->m2msoobject_id = new PrimaryFieldDefinition ();
		$this->many2manyobject_id = new Many2oneFieldDefinition ( 'Many2ManyObject' );
		$this->simpleobject_id = new Many2oneFieldDefinition ( 'SimpleObject' );
	}
}
ORM::declareObject ( 'Many2ManyObjectSimpleObject' );

/* Objet extended en base de données. */
class ExtendedObject
{
	public $description = 'Objet extended';
	public $table = 'test_extended_object';
	public $primary_key = 'simpleobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->simpleobject_id = new ExtendsFieldDefinition ( 'SimpleObject' );
		$this->extendedobject_value = new IntFieldDefinition ();
		$this->many2one_brother = new Many2oneFieldDefinition ( 'SimpleObject' );
	}
}

ORM::declareObject ( 'ExtendedObject' );

/* Objet utilisant des related. */
class RelatedObject
{
	public $description = 'Objet related';
	public $table = 'test_related_object';
	public $primary_key = 'relatedobject_id';
	public $database = TESTS_DATABASE;
	public $order = array (
		'name ASC'
	);

	function __construct()
	{
		$this->relatedobject_id = new PrimaryFieldDefinition ();
		$this->relatedobject_name = new TextFieldDefinition ();
		$this->simpleobject_id = new Many2oneFieldDefinition ( 'SimpleObject' );
		$this->name = new RelatedFieldDefinition ( 'simpleobject_id', 'simpleobject_name' );
		$this->value = new RelatedFieldDefinition ( 'simpleobject_id', 'simpleobject_value' );
		$this->check = new RelatedFieldDefinition ( 'simpleobject_id', 'simpleobject_check' );
	}
}

ORM::declareObject ( 'RelatedObject' );

/* Objet utilisant des attributs calculés. */
class ComputedFieldObject
{
	public $description = 'Objet computed field';
	public $table = 'test_computed_object';
	public $primary_key = 'computedobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->computedobject_id = new PrimaryFieldDefinition ();
		$this->computedobject_value1 = new IntFieldDefinition ();
		$this->computedobject_value2 = new IntFieldDefinition ();
		$this->computedobject_sum = IntFieldDefinition::create ()->setFunction ( 'ComputedFieldObject', 'sumField' );
	}
}

ORM::declareObject ( 'ComputedFieldObject' );

class ComputedFieldObjectMethod extends Common
{

	public static function sumField(&$object_list)
	{
		foreach ( $object_list as $key => $value )
		{
			if (isset ( $value ['computedobject_value1'] ['value'] ) && isset ( $value ['computedobject_value2'] ['value'] ))
			{
				$object_list [$key] ['computedobject_sum'] ['value'] = $value ['computedobject_value1'] ['value'] + $value ['computedobject_value2'] ['value'];
			}
			else
			{
				$object_list [$key] ['computedobject_sum'] ['value'] = - 1;
			}
		}
	}

	public function getReferenceString(array $id_list, array &$references)
	{
		$references = array ();
		foreach ( $id_list as $id )
		{
			$references [$id] = 'Reference : ' . $id;
		}
	}
}

/* Objet avec status de workflow */
class WorkflowStatusObject
{
	public $description = 'Objet workflow status';
	public $table = 'test_wfstatus_object';
	public $primary_key = 'workflowstatus_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->workflowstatus_id = new PrimaryFieldDefinition ();
		$this->status = new WorkflowStatusFieldDefinition ( 'test_workflow' );
	}
}

ORM::declareObject ( 'WorkflowStatusObject' );

class WorkflowStatusObjectMethod extends Common
{
}

class test_WorkflowMethod extends WorkflowAction
{
}

/* Objet extremenent complexe */
class ExtremelyComplexObject
{
	public $description = 'Objet extremely complex';
	public $table = 'test_eo_object';
	public $primary_key = 'many2manyobject_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->many2manyobject_id = new ExtendsFieldDefinition ( 'Many2ManyObject' );
		$this->computedobject_id = new Many2oneFieldDefinition ( 'ComputedFieldObject' );
		$this->cfo_sum = new RelatedFieldDefinition ( 'computedobject_id', 'computedobject_sum' );
		$this->relatedobject_id = new Many2oneFieldDefinition ( 'RelatedObject' );
		$this->ro_so_name = new RelatedFieldDefinition ( 'relatedobject_id', 'name' );
	}
}

ORM::declareObject ( 'ExtremelyComplexObject' );

class ExtremelyComplexObjectMethod extends Common
{

	public function getReferenceString(array $id_list, array &$references)
	{
		$references = array ();
		foreach ( $id_list as $id )
		{
			$references [$id] = 'Reference : ' . $id;
		}
	}
}

class FilesObject
{
	public $description = 'Objet extends document';
	public $table = 'test_file_object';
	public $primary_key = 'document_id';
	public $database = TESTS_DATABASE;

	function __construct()
	{
		$this->document_id = new ExtendsFieldDefinition ( 'Document' );
		$this->file_value = new TextFieldDefinition ();
	}
}
ORM::declareObject ( 'FilesObject' );

