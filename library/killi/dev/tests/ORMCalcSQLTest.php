<?php

/**
 *  @class ORMCalcSQLTest
 *  @Revision $Revision: 4445 $
 *
 */

class SQLComputedObject
{
	public $description  = 'Objet sql computed field';
	public $table		 = 'test_sql_computed_object';
	public $primary_key  = 'object_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'name';

	function __construct()
	{
		$this->object_id = new PrimaryFieldDefinition ();
		$this->data_1 = new TextFieldDefinition ();
		$this->data_2 = new TextFieldDefinition ();

		$this->name = new TextFieldDefinition ();
		$this->name->setSQLAlias('CONCAT(%data_1%, %data_2%)');

		$this->related_field = new Many2oneFieldDefinition('SQLComputedObjectRelated');
		$this->related_reference_field = new RelatedFieldDefinition('related_field','related_field');

		$this->related_field_compute = new TextFieldDefinition ();
		$this->related_field_compute->setSQLAlias('CONCAT(%data_1%, %related_field%)');

		$this->related_field_compute_related = new TextFieldDefinition ();
		$this->related_field_compute_related->setSQLAlias('CONCAT(%data_1%, %related_reference_field%)');
	}
}

ORM::declareObject('SQLComputedObject');

class SQLComputedObjectRelated
{
	public $description  = 'Objet sql computed related field';
	public $table		 = 'test_sql_computed_related_object';
	public $primary_key  = 'object_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'related_field';

	function __construct()
	{
		$this->object_id = new PrimaryFieldDefinition ();
		$this->related_field = new TextFieldDefinition ();
	}
}

ORM::declareObject('SQLComputedObjectRelated');

class SQLComputedMany2OneObject
{
	public $description  = 'Objet avec un Many2One calculé';
	public $table		 = 'test_sql_computed_many2one_object';
	public $primary_key  = 'object_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'related_id';

	function __construct()
	{
		$this->object_id = new PrimaryFieldDefinition ();
		$this->data = new TextFieldDefinition ();

		$this->related_id = new Many2oneFieldDefinition('SQLComputedObjectRelated');
		$this->related_id->setSQLAlias('%object_id%');

		$this->related_field = new RelatedFieldDefinition('related_id','related_field');
	}
}

ORM::declareObject('SQLComputedMany2OneObject');

class M2OSQLObject
{
	public $description  = 'Objet m2o sql computed field';
	public $table		 = 'test_m2o_computed_object';
	public $primary_key  = 'object_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'object_id';

	public function __construct()
	{
		$this->object_id = new PrimaryFieldDefinition ();
		$this->sql_computed = new Many2oneFieldDefinition('SQLComputedObject');
		$this->related_computed = new RelatedFieldDefinition('sql_computed','name');;
	}
}

ORM::declareObject('M2OSQLObject');

class ORMCalcSQLTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('SQLComputedObject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('M2OSQLObject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('SQLComputedObjectRelated');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('M2OSQLObject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('SQLComputedObject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('SQLComputedObjectRelated');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORM->deleteObjectInDatabase();
	}

	public function testSQLCalcBrowse()
	{
		$hORMsql = ORM::getORMInstance('SQLComputedObject');
		$hORMSQLComputedObjectRelated = ORM::getORMInstance('SQLComputedObjectRelated');

		/* Création de l'objet */
		$object_1 = array('data_1' => 'Hello ',
						  'data_2' => 'World !!!');

		$this->assertTrue($hORMsql->create($object_1, $id_1));
		$this->assertGreaterThan(0, $id_1);

		$related_object = array('related_field' => 'you ;)');

		$this->assertTrue($hORMSQLComputedObjectRelated->create($related_object, $id_related));
		$this->assertGreaterThan(0, $id_related);

		$object_2 = array('data_1' => '4',
						  'data_2' => '2',
						  'related_field' => $id_related);

		$this->assertTrue($hORMsql->create($object_2, $id_2));
		$this->assertGreaterThan(0, $id_2);

		/* Lecture de l'objet. */
		$this->assertTrue($hORMsql->read($id_1, $object, array('name')));
		$this->assertEquals('HelloWorld !!!', $object['name']['value']);

		$this->assertTrue($hORMsql->read($id_2, $object, array('name')));
		$this->assertEquals('42', $object['name']['value']);

		/* Récupération via un many2one de la référence calculé */
		$hORMm2o = ORM::getORMInstance('M2OSQLObject');

		$m2o_object_1 = array('sql_computed' => $id_2);
		$this->assertTrue($hORMm2o->create($m2o_object_1, $m2o_id_1));

		$m2o_object_2 = array('sql_computed' => $id_1);
		$this->assertTrue($hORMm2o->create($m2o_object_2, $m2o_id_2));

		$m2o_object_3 = array('sql_computed' => $id_2);
		$this->assertTrue($hORMm2o->create($m2o_object_3, $m2o_id_3));

		$m2o_object_4 = array('sql_computed' => $id_2);
		$this->assertTrue($hORMm2o->create($m2o_object_4, $m2o_id_4));

		$m2o_object_5 = array('sql_computed' => $id_1);
		$this->assertTrue($hORMm2o->create($m2o_object_5, $m2o_id_5));

		$this->assertTrue($hORMm2o->read($m2o_id_1, $m2o_object, array('sql_computed')));
		$this->assertEquals($id_2, $m2o_object['sql_computed']['value']);
		$this->assertEquals('42', $m2o_object['sql_computed']['reference']);

		$this->assertTrue($hORMm2o->read($m2o_id_2, $m2o_object, array('sql_computed')));
		$this->assertEquals($id_1, $m2o_object['sql_computed']['value']);
		$this->assertEquals('HelloWorld !!!', $m2o_object['sql_computed']['reference']);

		/* Filtrage sur champs calculé */
		$object_id_list = array();
		$this->assertTrue($hORMsql->search($object_id_list, $num, array(array('name', '=', '42'))));
		$this->assertContains($id_2, $object_id_list);
		$this->assertNotContains($id_1, $object_id_list, 'Le filtrage sur les champs calculés SQL ne fonctionne pas...');
		$this->assertEquals(1, count($object_id_list));

		$object_id_list = array();
		$this->assertTrue($hORMm2o->search($object_id_list, $num, array(array('related_computed', '=', 'HelloWorld !!!'))));
		$this->assertContains($m2o_id_2, $object_id_list);
		$this->assertContains($m2o_id_5, $object_id_list);
		$this->assertNotContains($m2o_id_1, $object_id_list);
		$this->assertNotContains($m2o_id_3, $object_id_list);
		$this->assertNotContains($m2o_id_4, $object_id_list);
		$this->assertEquals(2, count($object_id_list));

		/* Test sur un champs SQL non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('name')));

		/* Test sur un champs non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('data_1')));

		/* Test sur un champs m2o non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('related_field')));

		/* Test sur un champs related non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('related_reference_field')));

		/* Test sur un champs SQL avec m2o non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('related_field_compute')));

		/* Test sur un champs SQL avec related non précisé */
		$objects_id_list = array();
		$this->assertTrue($hORMsql->search($objects_id_list, $total_record, array(), array('related_field_compute_related')));

		/*
		Bug #2414: Le filtrage sur un related calculé par fonction SQL ne fonctionne pas.

		SELECT DISTINCT(test_m2o_computed_object.object_id)
		FROM killi_testsuite.test_m2o_computed_object
		LEFT JOIN killi_testsuite.test_sql_computed_object ON (killi_testsuite.test_sql_computed_object.object_id=killi_testsuite.test_m2o_computed_object.sql_computed)
		WHERE (killi_testsuite.test_sql_computed_object.name = 'Hello World !!!')
		ORDER BY test_m2o_computed_object.object_id
		*/
	}

	public function testRelatedInSQLFunction()
	{
		$hORMsql = ORM::getORMInstance('SQLComputedObject');
		$hORMSQLComputedObjectRelated = ORM::getORMInstance('SQLComputedObjectRelated');

		/* Création de l'objet */
		$object_1 = array('data_1' => 'Hello ',
						  'data_2' => 'World !!!');

		$this->assertTrue($hORMsql->create($object_1, $id_1));
		$this->assertGreaterThan(0, $id_1);

		$related_object = array('related_field' => 'you ;)');

		$this->assertTrue($hORMSQLComputedObjectRelated->create($related_object, $id_related));
		$this->assertGreaterThan(0, $id_related);

		$object_2 = array('data_1' => '4',
						  'data_2' => '2',
						  'related_field' => $id_related);

		$this->assertTrue($hORMsql->create($object_2, $id_2));
		$this->assertGreaterThan(0, $id_2);

		$hORMsql->write($id_1, array('related_field' => $id_related));

		$object = array();
		$this->assertTrue($hORMsql->read($id_1, $object, array('related_field_compute')));
		$this->assertEquals($object['related_field_compute']['value'], 'Hello' . $id_related);

		$object = array();
		$this->assertTrue($hORMsql->read($id_1, $object, array('related_field_compute_related')));
		$this->assertEquals($object['related_field_compute_related']['value'], 'Hello you ;)');

	}

	public function testSQLCalcMany2OneReadNull()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->read($id_many, $many, null));
		$this->assertArrayHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);

		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneReadData()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many = array();
		$this->assertTrue($hORMmany->read($id_many, $many, array('data')));
		$this->assertArrayHasKey('data', $many);
		$this->assertArrayNotHasKey('related_field', $many);

		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
	}


	public function testSQLCalcMany2OneReadRelated()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->read($id_many, $many, array('related_field')));
		$this->assertArrayNotHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);

		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneReadBoth()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->read($id_many, $many, array('data', 'related_field')));
		$this->assertArrayHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);

		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneBrowseNull()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->browse($many_list, $num_rows, null));
		$many = reset($many_list);

		$this->assertArrayHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);
		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneBrowseData()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->browse($many_list, $num_rows, array('data')));
		$many = reset($many_list);

		$this->assertArrayHasKey('data', $many);
		$this->assertArrayNotHasKey('related_field', $many);
		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
	}

	public function testSQLCalcMany2OneBrowseRelated()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->browse($many_list, $num_rows, array('related_field')));
		$many = reset($many_list);

		$this->assertArrayNotHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);
		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneBrowseBoth()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id_related));

		$many_list = array();
		$this->assertTrue($hORMmany->browse($many_list, $num_rows, array('data', 'related_field')));
		$many = reset($many_list);

		$this->assertArrayHasKey('data', $many);
		$this->assertArrayHasKey('related_field', $many);
		$this->assertEquals($many['data']['value'], 'Goodbye World !!!');
		$this->assertEquals($many['related_field']['value'], 'Related Data !!');
	}

	public function testSQLCalcMany2OneSearchBoth()
	{
		$hORMmany = ORM::getORMInstance('SQLComputedMany2OneObject');
		$hORMrelated = ORM::getORMInstance('SQLComputedObjectRelated');

		$this->assertTrue($hORMmany->create(array('data' => 'Goodbye World !!!'), $id1_many));
		$this->assertTrue($hORMmany->create(array('data' => 'So long World !!!'), $id2_many));
		$this->assertTrue($hORMmany->create(array('data' => 'Hello World !!!'), $id3_many));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Related Data !!'), $id1_related));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Not so related Data !!'), $id2_related));
		$this->assertTrue($hORMrelated->create(array('related_field' => 'Unrelated Data !!'), $id3_related));

		$many_id_list = array();
		$this->assertTrue($hORMmany->search($many_id_list, $num_rows, array(
			array('related_field', '=','Not so related Data !!')
		)));
		$this->assertEquals($many_id_list, array($id2_related));

		$many_id_list = array();
		$this->assertTrue($hORMmany->search($many_id_list, $num_rows, array(
			array('related_field', 'IN', array('Not so related Data !!', 'Unrelated Data !!'))
		)));
		$this->assertEquals($many_id_list, array($id2_related, $id3_related));
	}
}
