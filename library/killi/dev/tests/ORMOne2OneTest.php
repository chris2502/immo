<?php

/**
 *  @class ORMOne2OneTest
 *  @Revision $Revision: 4372 $
 *
 */

class LinkO2Object
{
	public $description  = 'Objet link';
	public $table		 = 'test_link_o2o_object';
	public $primary_key  = 'link_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->link_id = new PrimaryFieldDefinition ();
		$this->link_name = new TextFieldDefinition ();
		$this->toto_id = new Many2oneFieldDefinition ('One2OneObject');
	}
}

ORM::declareObject('LinkO2Object');

class LinkO2Object2
{
	public $description  = 'Objet link 2';
	public $table		 = 'test_link_o2o_object2';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->adresse_id = new PrimaryFieldDefinition ();
		$this->name = new TextFieldDefinition ();
	}
}

ORM::declareObject('LinkO2Object2');

class One2OneObject
{
	public $description  = 'Objet one2one';
	public $table		 = 'test_one2one_object';
	public $primary_key  = 'one2one_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'object_name';

	function __construct()
	{
		$this->one2one_id = new PrimaryFieldDefinition ();
		$this->object_name = new TextFieldDefinition ();

		/* Cas simple, pas de détection de l'attribut de liaison. */
		$this->attr_id = new One2oneFieldDefinition('LinkO2Object', 'toto_id');
		$this->name = new RelatedFieldDefinition('attr_id', 'link_name');

		/* Cas complexe, détection de l'attribut de liaison. */
		$this->geo_id = new One2oneFieldDefinition('LinkO2Object2'); // one2one_id == adresse_id
		$this->geo_name = new RelatedFieldDefinition('geo_id', 'name');
	}
}

ORM::declareObject('One2OneObject');

class ORMOne2OneTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('LinkO2Object');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('LinkO2Object2');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('One2OneObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('One2OneObject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('LinkO2Object2');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('LinkO2Object');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidOperation()
	{
		/* Création des données de tests. */
		$hORMo2o = ORM::getORMInstance('One2OneObject');
		$data = array('object_name' => 'Objet de test 1');
		$this->assertTrue($hORMo2o->create($data, $id5));
		$this->assertGreaterThan(0, $id5);
		$data = array('object_name' => 'Objet de test 1');
		$this->assertTrue($hORMo2o->create($data, $id1));
		$this->assertGreaterThan(0, $id1);
		$data = array('object_name' => 'Objet de test 1');
		$this->assertTrue($hORMo2o->create($data, $id6));
		$this->assertGreaterThan(0, $id6);

		$hORMlo = ORM::getORMInstance('LinkO2Object');
		$data = array('link_name' => 'Test 1', 'toto_id' => NULL);
		$this->assertTrue($hORMlo->create($data, $id2));
		$this->assertGreaterThan(0, $id2);
		$data = array('link_name' => 'Test 2', 'toto_id' => $id1);
		$this->assertTrue($hORMlo->create($data, $id3));
		$this->assertGreaterThan(0, $id3);

		$hORMlo2 = ORM::getORMInstance('LinkO2Object2');
		$data = array('adresse_id' => $id1, 'name' => 'Test GEO 1');
		$this->assertTrue($hORMlo2->create($data, $id4));
		$this->assertGreaterThan(0, $id4);
		$this->assertEquals($id1, $id4);

		/* Lecture des données de l'objet effectuant les one2one. */
		$obj = array();
		$hORMo2o->read(array($id1), $obj, array('geo_name', 'name', 'geo_id', 'attr_id'));
		$this->assertEquals(1, count($obj));
		$this->assertEquals('Test GEO 1', $obj[$id1]['geo_name']['value']);
		$this->assertEquals('Test 2', $obj[$id1]['name']['value']);
		$this->assertEquals($id1, $obj[$id1]['geo_id']['value']);
		$this->assertEquals($id3, $obj[$id1]['attr_id']['value']);

		$obj = array();
		$hORMo2o->read(array($id5), $obj, array('geo_name', 'name', 'geo_id', 'attr_id'));
		$this->assertEquals(1, count($obj));
		$this->assertNull($obj[$id5]['geo_name']['value']);
		$this->assertNull($obj[$id5]['name']['value']);
		$this->assertNull($obj[$id5]['geo_id']['value']);
		$this->assertNull($obj[$id5]['attr_id']['value']);

		/* Test du search */
		$object_id_list = array();
		$hORMo2o->search($object_id_list, $total_record);
		$this->assertEquals(3, count($object_id_list));

		/* Test du search avec critère */
		/* Cas simple. */
		$object_id_list = array();
		$hORMo2o->search($object_id_list, $total_record, array(array('name','=','Test 2')));
		$this->assertEquals(1, count($object_id_list));
		$this->assertContains($id1, $object_id_list);

		/* Cas complexe. */
		$object_id_list = array();
		$hORMo2o->search($object_id_list, $total_record, array(array('geo_name','=','Test GEO 1')));
		$this->assertEquals(1, count($object_id_list));
		$this->assertContains($id1, $object_id_list);

		/* Test du browse */
		$object_list = array();
		$hORMo2o->browse($object_list,$total,array('geo_id'));
		$this->assertArrayHasKey($id1, $object_list);
		$this->assertEquals($id1, $object_list[$id1]['geo_id']['value']);
	}
}
