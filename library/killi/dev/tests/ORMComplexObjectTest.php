<?php

/**
 *  @class ORMComplexObjectTest
 *  @Revision $Revision: 3278 $
 *
 */

class ObjectTestWFS
{
	public $description  = 'Objet worflow_status';
	public $table		 = 'test_wfs_related_chained';
	public $primary_key  = 'obj_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->obj_id = new PrimaryFieldDefinition ();
		$this->status_a = new WorkflowStatusFieldDefinition('test_workflow','obj_id');
	}
}
ORM::declareObject('ObjectTestWFS');

class ObjectTestWFSLink
{
	public $description  = 'Objet worflow_status';
	public $table		 = 'test_wfs_related_chained_link';
	public $primary_key  = 'obj_link_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->obj_link_id = new PrimaryFieldDefinition ();
		$this->obj_id = new Many2oneFieldDefinition('ObjectTestWFS');
		$this->status_b = new RelatedFieldDefinition('obj_id','status_a');
	}
}
ORM::declareObject('ObjectTestWFSLink');

class ORMComplexObjectTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('relatedobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobjectsimpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('computedfieldobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('extremelycomplexobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('extremelycomplexobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('computedfieldobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobjectsimpleobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('relatedobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidOperations()
	{
		$hORMso = ORM::getORMInstance('simpleobject');
		$hORMro = ORM::getORMInstance('relatedobject');
		$hORMcfo = ORM::getORMInstance('computedfieldobject');
		$hORMeco = ORM::getORMInstance('extremelycomplexobject');
		$hORMwfs = ORM::getORMInstance('ObjectTestWFSLink');

		/* Création */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true, 'simpleobject_textarea' => 'description');
		$this->assertTrue($hORMso->create($object1, $id1));
		$this->assertGreaterThan(0, $id1);

		$object2 = array('relatedobject_name' => 'Objet related', 'simpleobject_id' => $id1);
		$this->assertTrue($hORMro->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		$object3 = array('computedobject_value1' => 10, 'computedobject_value2' => 42);
		$this->assertTrue($hORMcfo->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		$object4 = array('many2manyobject_name' => 'Object complex', 'computedobject_id' => $id3, 'relatedobject_id' => $id2);
		$this->assertTrue($hORMeco->create($object4, $id4));
		$this->assertGreaterThan(0, $id4);

		/* Ne devrait pas foirer ! */
		$object5 = array('computedobject_id' => $id3, 'relatedobject_id' => $id2);
		$this->assertTrue($hORMeco->create($object5, $id5));
		$this->assertGreaterThan(0, $id5);

		//$this->showTables(array('test_simple_object', 'test_related_object', 'test_many2many_object', 'test_eo_object'));

		/* Lecture de l'objet complexe */
		$objects = array();
		$this->assertTrue($hORMeco->browse($objects, $total, array('computedobject_id', 'cfo_sum', 'ro_so_name'), array(array('many2manyobject_id', '=', $id4))));
		$this->assertEquals(1, count($objects));
		$this->assertNotEmpty($objects[$id4]);
		$this->assertEquals($id4, $objects[$id4]['many2manyobject_id']['value']);

		/* Test rapide, l'attribut status_a de l'objet related est-il bien récupéré (au lieu de status_b) ? */
		$this->assertTrue($hORMwfs->browse($objects, $total, NULL, array(array('status_b', 'not in', array()))));
	}
}

