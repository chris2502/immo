<?php

/**
 *  @class ORMMany2OneTest
 *  @Revision $Revision: 2736 $
 *
 */

class ORMMany2OneTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('many2oneobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('many2oneobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidSimpleOperation()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMmany2one = ORM::getORMInstance('many2oneobject');

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id1));
		$this->assertGreaterThan(0, $id1);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création d'un objet many2one */
		$objectMany = array('many2oneobject_name' => 'Objet many2one', 'simpleobject_id' => $id1);
		$this->assertTrue($hORMmany2one->create($objectMany, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Création de l'objet many2one utilisé pour les tests */
		$objectMany = array('many2oneobject_name' => 'Objet many2one', 'simpleobject_id' => $id1);
		$this->assertTrue($hORMmany2one->create($objectMany, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Lecture simple de l'objet many2one */
		$objects = array();
		$this->assertTrue($hORMmany2one->browse($objects, $total, array('many2oneobject_name'), array(array('many2oneobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['many2oneobject_id']['value']);
		$this->assertEquals($objectMany['many2oneobject_name'], $objects[$id3]['many2oneobject_name']['value']);
		$this->assertEquals($id1, $objects[$id3]['simpleobject_id']['value']);
		$this->assertNotContains('simpleobject_name', $objects[$id3]);
		$this->assertNotContains('simpleobject_value', $objects[$id3]);
		$this->assertNotContains('simpleobject_check', $objects[$id3]);

		/* Lecture avec jointure de l'objet many2one */
		$objects = array();
		$this->assertTrue($hORMmany2one->browse($objects, $total, array('many2oneobject_name', 'simpleobject_id'), array(array('many2oneobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['many2oneobject_id']['value']);
		$this->assertEquals($objectMany['many2oneobject_name'], $objects[$id3]['many2oneobject_name']['value']);
		$this->assertEquals($id1, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals('Reference : ' . $id1, $objects[$id3]['simpleobject_id']['reference']);
		/*
		var_dump($objects);
		$this->assertEquals($objects[$id]['simpleobject_name']['value'], $object1['simpleobject_name']);
		$this->assertEquals($objects[$id]['simpleobject_value']['value'], $object1['simpleobject_value']);
		$this->assertEquals($objects[$id]['simpleobject_check']['value'], $object1['simpleobject_check']);
		*/

		/* Edition de l'objet many2one pour pointer sur l'objet simple 2 */
		$objectMany['simpleobject_id'] = $id2;
		$this->assertTrue($hORMmany2one->write($id3, $objectMany));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORMmany2one->browse($objects, $total, array('many2oneobject_name'), array(array('many2oneobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['many2oneobject_id']['value']);
		$this->assertEquals($objectMany['many2oneobject_name'], $objects[$id3]['many2oneobject_name']['value']);
		$this->assertEquals($id2, $objects[$id3]['simpleobject_id']['value']);

		/* Suppression */
		$this->assertTrue($hORMmany2one->unlink($id3));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORMmany2one->browse($objects, $total, array('many2oneobject_name'), array(array('many2oneobject_id', '=', $id3))));
		$this->assertEquals(0, count($objects));

		/* Suppression des objets simples. */
		$this->assertTrue($hORMsimple->unlink($id1));
		$this->assertTrue($hORMsimple->unlink($id2));

	}

}


