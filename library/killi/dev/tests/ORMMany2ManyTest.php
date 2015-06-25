<?php

/**
 *  @class ORMMany2ManyTest
 *  @Revision $Revision: 4445 $
 *
 */

class ORMMany2ManyTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobjectsimpleobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('many2manyobjectsimpleobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('many2manyobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidSimpleOperation()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMmany2many = ORM::getORMInstance('many2manyobject');
		$hORMm2mso = ORM::getORMInstance('many2manyobjectsimpleobject');

		/* Création d'objet pour faire varier les id autoincrementés. */
		$object = array('simpleobject_name' => 'Objet auto', 'simpleobject_value' => 22, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object, $id));
		$this->assertGreaterThan(0, $id);
		$object = array('simpleobject_name' => 'Objet auto', 'simpleobject_value' => 22, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object, $id));
		$this->assertGreaterThan(0, $id);
		$object = array('simpleobject_name' => 'Objet auto', 'simpleobject_value' => 22, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object, $id));
		$this->assertGreaterThan(0, $id);
		$object = array('simpleobject_name' => 'Objet auto', 'simpleobject_value' => 22, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object, $id));
		$this->assertGreaterThan(0, $id);
		$object = array('simpleobject_name' => 'Objet auto', 'simpleobject_value' => 22, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object, $id));
		$this->assertGreaterThan(0, $id);

		/* Test de comptage */
		$this->assertTrue($hORMsimple->count($total));
		$this->assertEquals(5, $total);

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id));
		$this->assertGreaterThan(0, $id);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création de l'objet many2many. */
		$object3 = array('many2manyobject_name' => 'Objet 3');
		$this->assertTrue($hORMmany2many->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Affectation de simpleobject à un many2many. */
		$link1 = array('simpleobject_id' => $id, 'many2manyobject_id' => $id3);
		$this->assertTrue($hORMm2mso->create($link1, $id4));
		$this->assertGreaterThan(0, $id4);

		/* Affectation de simpleobject à un many2many. */
		$link1 = array('simpleobject_id' => $id2, 'many2manyobject_id' => $id3);
		$this->assertTrue($hORMm2mso->create($link1, $id5));
		$this->assertGreaterThan(0, $id5);

		/* Test de comptage */
		$this->assertTrue($hORMsimple->count($total, array(array('simpleobject_value', '=', '22'))));
		$this->assertEquals(5, $total);

		/* Test de comptage */
		$this->assertTrue($hORMm2mso->count($total));
		$this->assertEquals(2, $total);

		/* Test de comptage */
		$this->assertTrue($hORMm2mso->count($total, array(array('simpleobject_id', '=', $id))));
		$this->assertEquals(1, $total);

		/* Lecture des infos en many2many SANS récupération des many2many. */
		$this->assertTrue($hORMmany2many->browse($objects, $total, array('many2manyobject_id', 'many2manyobject_name'), array(array('many2manyobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($objects[$id3]['many2manyobject_id']['value'], $id3);
		$this->assertEquals($objects[$id3]['many2manyobject_name']['value'], $object3['many2manyobject_name']);
		//$this->assertEmpty($objects[$id3]['simpleobject_id']['value']);

		/* Lecture des infos en many2many AVEC récupération des many2many. */
		$this->assertTrue($hORMmany2many->browse($objects, $total, array('many2manyobject_id', 'many2manyobject_name', 'simpleobject_id'), array(array('many2manyobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['many2manyobject_id']['value']);
		$this->assertEquals($object3['many2manyobject_name'], $objects[$id3]['many2manyobject_name']['value']);
		$this->assertNotEmpty($objects[$id3]['simpleobject_id']['value']);
		$this->assertContains($id, $objects[$id3]['simpleobject_id']['value']);
		$this->assertContains($id2, $objects[$id3]['simpleobject_id']['value']);

		/* Création de 2 objets many2many. */
		$object4 = array('many2manyobject_name' => 'Objet 4');
		$this->assertTrue($hORMmany2many->create($object4, $id6));
		$this->assertGreaterThan(0, $id6);

		$object5 = array('many2manyobject_name' => 'Objet 5');
		$this->assertTrue($hORMmany2many->create($object5, $id7));
		$this->assertGreaterThan(0, $id7);

		/* Affectation de simpleobject 1 aux 2 objets many2many. */
		$link1 = array('simpleobject_id' => $id, 'many2manyobject_id' => $id6);
		$this->assertTrue($hORMm2mso->create($link1, $id8));
		$this->assertGreaterThan(0, $id8);

		$link1 = array('simpleobject_id' => $id, 'many2manyobject_id' => $id7);
		$this->assertTrue($hORMm2mso->create($link1, $id9));
		$this->assertGreaterThan(0, $id9);

		/* Test de comptage */
		$this->assertTrue($hORMmany2many->count($total, array(array('simpleobject_id', '=', $id))));
		$this->assertEquals(3, $total);

		/* Lecture des infos en many2many AVEC récupération des many2many et filtrage sur l'attribut many2many. */
		$this->assertTrue($hORMmany2many->browse($objects, $total, array('many2manyobject_id', 'many2manyobject_name', 'simpleobject_id'), array(array('simpleobject_id', '=', $id))));
		$this->assertEquals(3, count($objects));
	}
}

