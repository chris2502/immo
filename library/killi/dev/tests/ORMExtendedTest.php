<?php

/**
 *  @class ORMExtendedTest
 *  @Revision $Revision: 2827 $
 *
 */

class ORMExtendedTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('extendedobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('extendedobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidCreation()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMextended = ORM::getORMInstance('extendedobject');

		/* Création de l'objet parent */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id));
		$this->assertGreaterThan(0, $id);

		/* Création du fils */
		$object3 = array('simpleobject_name' => 'Objet 3', 'many2one_brother' => NULL);
		$this->assertTrue($hORMextended->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_name'), array(array('simpleobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_name'], $objects[$id3]['simpleobject_name']['value']);

		/* Création du fils en forçant l'id du parent ! */
		$object2 = array('simpleobject_id' => $id, 'many2one_brother' => NULL);
		$this->assertTrue($hORMextended->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_name'), array(array('simpleobject_id', '=', $id2))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id2, $objects[$id2]['simpleobject_id']['value']);
		$this->assertEquals($object1['simpleobject_name'], $objects[$id2]['simpleobject_name']['value']);

	}

	public function testValidSimpleOperation()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMextended = ORM::getORMInstance('extendedobject');

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id));
		$this->assertGreaterThan(0, $id);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création de l'objet étendu 3 (avec création de l'objet simple 3). */
		$object3 = array('simpleobject_name' => 'Objet 3', 'simpleobject_value' => 44, 'simpleobject_check' => true);
		$this->assertTrue($hORMextended->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Lecture simple de l'objet extended */
		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_name'), array(array('simpleobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_name'], $objects[$id3]['simpleobject_name']['value']);

		/* Test de comptage */
		$this->assertTrue($hORMextended->count($total));
		$this->assertEquals(1, $total);

		/* Modification de l'objet extended */
		$object3['simpleobject_value'] = 45;
		$this->assertTrue($hORMextended->write($id3, $object3, true));

		/* Vérification des informations */
		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_value'), array(array('simpleobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_value'], $objects[$id3]['simpleobject_value']['value']);
	}

	public function testValidFilteringOnParent()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMextended = ORM::getORMInstance('extendedobject');

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id));
		$this->assertGreaterThan(0, $id);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création de l'objet étendu 3 (avec création de l'objet simple 3). */
		$object3 = array('simpleobject_name' => 'Objet 3', 'simpleobject_value' => 44, 'simpleobject_check' => true);
		$this->assertTrue($hORMextended->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Lecture simple de l'objet extended */
		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_name'), array(array('simpleobject_value', '=', 44))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_name'], $objects[$id3]['simpleobject_name']['value']);
	}

	public function testValidM2OInExtended()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMextended = ORM::getORMInstance('extendedobject');

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id));
		$this->assertGreaterThan(0, $id);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création de l'objet étendu 3 (avec création de l'objet simple 3). */
		$object3 = array('simpleobject_name' => 'Objet 3', 'simpleobject_value' => 44, 'simpleobject_check' => true, 'many2one_brother' => $id);
		$this->assertTrue($hORMextended->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Lecture simple de l'objet extended */
		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_name', 'many2one_brother'), array(array('simpleobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_name'], $objects[$id3]['simpleobject_name']['value']);
		$this->assertEquals($object3['many2one_brother'], $objects[$id3]['many2one_brother']['value']);

		/* Test de comptage */
		$this->assertTrue($hORMextended->count($total));
		$this->assertEquals(1, $total);

		/* Modification de l'objet extended */
		$object3['simpleobject_value'] = 45;
		$this->assertTrue($hORMextended->write($id3, $object3, true));

		/* Vérification des informations */
		$objects = array();
		$this->assertTrue($hORMextended->browse($objects, $total, array('simpleobject_id', 'simpleobject_value', 'many2one_brother'), array(array('simpleobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object3['simpleobject_value'], $objects[$id3]['simpleobject_value']['value']);
		$this->assertEquals($object3['many2one_brother'], $objects[$id3]['many2one_brother']['value']);

	}
}
