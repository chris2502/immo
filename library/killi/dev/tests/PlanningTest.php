<?php

/**
 *  @class PlanningTest
 *  @Revision $Revision: 3641 $
 *
 */

/**
 * Objet de planification simple.
 *
 */
class PlanningObject
{
	public $description  = 'Objet de planification';
	public $table		 = 'test_planning_object';
	public $primary_key  = 'planning_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
			$this->planning_id = new PrimaryFieldDefinition ();
			$this->date = new DateFieldDefinition();
			$this->debut = new TimeFieldDefinition();
			$this->fin = new TimeFieldDefinition();
	}
}

ORM::declareObject('PlanningObject');

class PlanningObjectMethod extends Common
{
	public function getReferenceString(array $id_list, array &$reference)
	{
		foreach($id_list AS $key)
		{
			$reference[$key] = 'Id:' . $key;
		}
	}

}

/**
 * Objet de planification qui foire
 *
 */

class PlanningObject2
{
	public $description  = 'Objet de planification';
	public $table		 = 'test_planning_object';
	public $primary_key  = 'planning_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->planning_id = new PrimaryFieldDefinition ();
		$this->date = new DateFieldDefinition();
		$this->debut = new TimeFieldDefinition();
		$this->fin = new TimeFieldDefinition();
	}
}

ORM::declareObject('PlanningObject2');

class PlanningObject2Method extends Common
{

}

/**
 * Objet de planification simple.
 *
 */
class ComplexPlanningObject
{
	public $description  = 'Objet de planification';
	public $table		 = 'test_complexplanning_object';
	public $primary_key  = 'planning_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->planning_id = new PrimaryFieldDefinition ();
		$this->date = new DateFieldDefinition();
		$this->debut = new TimeFieldDefinition();
		$this->fin = new TimeFieldDefinition();
		$this->simpleobject_id = new Many2oneFieldDefinition('SimpleObject');
		$this->related_value = new RelatedFieldDefinition('simpleobject_id','simpleobject_value');
	}
}

ORM::declareObject('ComplexPlanningObject');

class ComplexPlanningObjectMethod extends Common
{
	public function getReferenceString(array $id_list, array &$reference)
	{
		foreach($id_list AS $key)
		{
			$reference[$key] = 'Id:' . $key;
		}
	}

}

/**
 * Classe de test !
 *
 */

class PlanningTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->_cleanCreateObject('planningobject');
		$this->_cleanCreateObject('simpleobject');
		$this->_cleanCreateObject('complexplanningobject');
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('complexplanningobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('planningobject');
		$hORM->deleteObjectInDatabase();
	}

	/**
	 * @expectedException Exception
	 */
	public function testWrongPlanification()
	{
		$hMethod = new PlanningObject2Method();

		/* Création d'évenement dans la grille de planification. */
		$planif1 = array('date' => '2012-11-05', 'debut' => '08:00:00', 'fin' => '12:00:00');
		$this->assertTrue($hMethod->create($planif1, $planif_id1));

		$object = 'planningobject2';
		$data = array();
		$hMethod->planning($data, '2012-11-06', '2012-11-10', $object, $primary_key);
	}

	public function testValidSimplePlanification()
	{
		$hMethod = new PlanningObjectMethod();

		/* Création d'évenement dans la grille de planification. */
		$planif1 = array('date' => '2012-11-05', 'debut' => '08:00:00', 'fin' => '12:00:00');
		$this->assertTrue($hMethod->create($planif1, $planif_id1));
		$this->assertGreaterThan(0, $planif_id1);

		$planif2 = array('date' => '2012-11-07', 'debut' => '09:30:00', 'fin' => '13:30:00');
		$this->assertTrue($hMethod->create($planif2, $planif_id2));
		$this->assertGreaterThan(0, $planif_id2);

		$planif3 = array('date' => '2012-11-09', 'debut' => '09:00:00', 'fin' => '13:00:00');
		$this->assertTrue($hMethod->create($planif3, $planif_id3));
		$this->assertGreaterThan(0, $planif_id3);

		/* Récupération des évenements de la grille de planification. */
		$object = 'planningobject';
		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-07', '2012-11-09', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(2, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals($planif2['date'],  $data[$planif_id2]['date']['value']);
		$this->assertEquals($planif2['debut'], $data[$planif_id2]['debut']['value']);
		$this->assertEquals($planif2['fin'],   $data[$planif_id2]['fin']['value']);

		$this->assertEquals($planif_id3,       $data[$planif_id3]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id3, $data[$planif_id3]['planning_id']['reference']);
		$this->assertEquals($planif3['date'],  $data[$planif_id3]['date']['value']);
		$this->assertEquals($planif3['debut'], $data[$planif_id3]['debut']['value']);
		$this->assertEquals($planif3['fin'],   $data[$planif_id3]['fin']['value']);

		/* Test le déplacement d'évenement. */
		$data = array();
		$this->assertTrue($hMethod->moveEvent($data, $planif_id2, false, 1, 30));
		$this->assertTrue($data['success']);

		/* Vérification du déplacement. */
		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-08', '2012-11-08', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(1, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals('2012-11-08',  $data[$planif_id2]['date']['value']);
		$this->assertEquals('10:00:00', $data[$planif_id2]['debut']['value']);
		$this->assertEquals('14:00:00',   $data[$planif_id2]['fin']['value']);

		/* Test le redimensionnement d'évenement. */
		$data = array();
		$this->assertTrue($hMethod->resizeEvent($data, $planif_id2, 0, -30));
		$this->assertTrue($data['success']);

		/* Vérification du redimensionnement. */
		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-08', '2012-11-08', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(1, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals('2012-11-08',  $data[$planif_id2]['date']['value']);
		$this->assertEquals('10:00:00', $data[$planif_id2]['debut']['value']);
		$this->assertEquals($planif2['fin'],   $data[$planif_id2]['fin']['value']);

		/* Test du redimensionnement en cas limite. */

	}

	public function testValidComplexPlanification()
	{
		$hMethod = new ComplexPlanningObjectMethod();

		$hORM = ORM::getORMInstance('simpleobject');

		/* Création d'objets simples */
		$object = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORM->create($object, $id1));
		$this->assertGreaterThan(0, $id1);

		$object = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => true);
		$this->assertTrue($hORM->create($object, $id2));
		$this->assertGreaterThan(0, $id2);

		$object = array('simpleobject_name' => 'Objet 3', 'simpleobject_value' => 44, 'simpleobject_check' => '0');
		$this->assertTrue($hORM->create($object, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Création d'évenement dans la grille de planification. */
		$planif1 = array('date' => '2012-11-05', 'debut' => '08:00:00', 'fin' => '12:00:00', 'simpleobject_id' => $id1);
		$this->assertTrue($hMethod->create($planif1, $planif_id1));
		$this->assertGreaterThan(0, $planif_id1);

		$planif2 = array('date' => '2012-11-07', 'debut' => '09:30:00', 'fin' => '13:30:00', 'simpleobject_id' => $id2);
		$this->assertTrue($hMethod->create($planif2, $planif_id2));
		$this->assertGreaterThan(0, $planif_id2);

		$planif3 = array('date' => '2012-11-09', 'debut' => '09:00:00', 'fin' => '13:00:00', 'simpleobject_id' => $id3);
		$this->assertTrue($hMethod->create($planif3, $planif_id3));
		$this->assertGreaterThan(0, $planif_id3);

		/* Récupération des évenements de la grille de planification. */
		$object = 'complexplanningobject';
		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-07', '2012-11-09', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(2, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals($planif2['date'],  $data[$planif_id2]['date']['value']);
		$this->assertEquals($planif2['debut'], $data[$planif_id2]['debut']['value']);
		$this->assertEquals($planif2['fin'],   $data[$planif_id2]['fin']['value']);

		$this->assertEquals($planif_id3,       $data[$planif_id3]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id3, $data[$planif_id3]['planning_id']['reference']);
		$this->assertEquals($planif3['date'],  $data[$planif_id3]['date']['value']);
		$this->assertEquals($planif3['debut'], $data[$planif_id3]['debut']['value']);
		$this->assertEquals($planif3['fin'],   $data[$planif_id3]['fin']['value']);

		/* Test le déplacement d'évenement. */
		$data = array();
		$this->assertTrue($hMethod->moveEvent($data, $planif_id2, false, 1, 30));
		$this->assertTrue($data['success']);

		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-08', '2012-11-08', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(1, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals('2012-11-08',  $data[$planif_id2]['date']['value']);
		$this->assertEquals('10:00:00', $data[$planif_id2]['debut']['value']);
		$this->assertEquals('14:00:00',   $data[$planif_id2]['fin']['value']);

		/* Test le redimensionnement d'évenement. */
		$data = array();
		$this->assertTrue($hMethod->resizeEvent($data, $planif_id2, 0, -30));
		$this->assertTrue($data['success']);

		$data = array();
		$this->assertTrue($hMethod->planning($data, '2012-11-08', '2012-11-08', $object, $primary_key));
		$this->assertEquals('planning_id', $primary_key);
		$this->assertEquals(1, count($data));
		$this->assertEquals($planif_id2,       $data[$planif_id2]['planning_id']['value']);
		$this->assertEquals('Id:'.$planif_id2, $data[$planif_id2]['planning_id']['reference']);
		$this->assertEquals('2012-11-08',  $data[$planif_id2]['date']['value']);
		$this->assertEquals('10:00:00', $data[$planif_id2]['debut']['value']);
		$this->assertEquals($planif2['fin'],   $data[$planif_id2]['fin']['value']);
	}

	public function testPlanifierToolsSetArea()
	{
		$zones = array();

		/* Définition d'une zone interdite. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '00:00:00', '23:59:59', 'rouge'));
		$this->assertEquals(1, count($zones));

		$zone = current($zones);

		$this->assertEquals(1, $zone['start']['day']);
		$this->assertEquals(1, $zone['end']['day']);
		$this->assertEquals(0, $zone['start']['hour']);
		$this->assertEquals(23, $zone['end']['hour']);
		$this->assertEquals(0, $zone['start']['minute']);
		$this->assertEquals(59, $zone['end']['minute']);
		$this->assertEquals('rouge', $zone['className']);

		/* Définition d'une zone autorisé. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '08:00:00', '18:00:00', 'vert'));
		$this->assertEquals(3, count($zones));

		$tests = array();
		$tests[] = array(1, 0, 0, 8, 0, 'rouge');
		$tests[] = array(1, 8, 0, 18, 0, 'vert');
		$tests[] = array(1, 18, 0, 23, 59, 'rouge');

		foreach($zones AS $zone)
		{
			$test = current($tests);
			$this->assertEquals($test[0], $zone['start']['day']);
			$this->assertEquals($test[0], $zone['end']['day']);
			$this->assertEquals($test[1], $zone['start']['hour']);
			$this->assertEquals($test[2], $zone['start']['minute']);
			$this->assertEquals($test[3], $zone['end']['hour']);
			$this->assertEquals($test[4], $zone['end']['minute']);
			$this->assertEquals($test[5], $zone['className']);
			next($tests);
		}

		/* Définition d'une zone interdite. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '12:00:00', '13:30:00', 'rouge'));
		$this->assertEquals(5, count($zones));

		$tests = array();
		$tests[] = array(1, 0, 0, 8, 0, 'rouge');
		$tests[] = array(1, 8, 0, 12, 0, 'vert');
		$tests[] = array(1, 12, 0, 13, 30, 'rouge');
		$tests[] = array(1, 13, 30, 18, 0, 'vert');
		$tests[] = array(1, 18, 0, 23, 59, 'rouge');

		//print_r($zones);

		foreach($zones AS $zone)
		{
			$test = current($tests);
			$this->assertEquals($test[0], $zone['start']['day']);
			$this->assertEquals($test[0], $zone['end']['day']);
			$this->assertEquals($test[1], $zone['start']['hour']);
			$this->assertEquals($test[2], $zone['start']['minute']);
			$this->assertEquals($test[3], $zone['end']['hour']);
			$this->assertEquals($test[4], $zone['end']['minute']);
			$this->assertEquals($test[5], $zone['className']);
			next($tests);
		}

		$zones = array();

		/* Test des limites de zones  contigus. */



		/* Définition d'une zone interdite. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '00:00:00', '23:59:59', 'rouge'));

		/* Définition d'une zone contigue debut et fin. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '00:00:00', '23:59:59', 'orange'));

		/* Définition d'une zone contigue debut. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '00:00:00', '10:00:00', 'vert'));

		/* Définition d'une zone contigue fin. */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '22:00:00', '23:59:59', 'vert'));

		$this->assertEquals(3, count($zones));

		$tests = array();
		$tests[] = array(1, 0, 0, 10, 00, 'vert');
		$tests[] = array(1, 10, 0, 22, 0, 'orange');
		$tests[] = array(1, 22, 0, 23, 59, 'vert');

		foreach($zones AS $zone)
		{
			$test = current($tests);
			$this->assertEquals($test[0], $zone['start']['day']);
			$this->assertEquals($test[0], $zone['end']['day']);
			$this->assertEquals($test[1], $zone['start']['hour']);
			$this->assertEquals($test[2], $zone['start']['minute']);
			$this->assertEquals($test[3], $zone['end']['hour']);
			$this->assertEquals($test[4], $zone['end']['minute']);
			$this->assertEquals($test[5], $zone['className']);
			next($tests);
		}


		$zones = array();
		/* initialisation */
		$this->assertTrue(PlanifierTools::setArea($zones, 1, '00:00:00', '23:59:59', 'rouge'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '10:01:00', '11:59:59', 'vert'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '10:02:00', '10:03:00', 'vert'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '09:59:00', '10:01:00', 'orange'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '10:02:00', '10:05:00', 'rouge'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '10:03:00', '11:59:00', 'orange'));

		$this->assertTrue(PlanifierTools::setArea($zones, 1, '10:02:00', '11:59:00', 'jaune'));

		$this->assertEquals(5, count($zones));

		$tests = array();
		$tests[] = array(1, 0, 0, 9, 59, 'rouge');
		$tests[] = array(1, 9, 59, 10, 1, 'orange');
		$tests[] = array(1, 10, 1, 10, 2, 'vert');
		$tests[] = array(1, 10, 2, 11, 59, 'jaune');
		$tests[] = array(1, 11, 59, 23, 59, 'rouge');

		foreach($zones AS $zone)
		{
			$test = current($tests);
			$this->assertEquals($test[0], $zone['start']['day']);
			$this->assertEquals($test[0], $zone['end']['day']);
			$this->assertEquals($test[1], $zone['start']['hour']);
			$this->assertEquals($test[2], $zone['start']['minute']);
			$this->assertEquals($test[3], $zone['end']['hour']);
			$this->assertEquals($test[4], $zone['end']['minute']);
			$this->assertEquals($test[5], $zone['className']);
			next($tests);
		}

	}

}


