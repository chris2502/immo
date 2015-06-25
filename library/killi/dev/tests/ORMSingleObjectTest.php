<?php

/**
 *  @class ORMSingleObjectTest
 *  @Revision $Revision: 3189 $
 *
 */

class ORMSingleObjectTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');

		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidSimpleOperationInsert()
	{
		$hORM = ORM::getORMInstance('simpleobject', true);

		/* Création */
		$object = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => FALSE);
		$this->assertTrue($hORM->create($object, $id));
		$this->assertGreaterThan(0, $id);

		/* Lecture via BROWSE */
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, array('simpleobject_name', 'simpleobject_value', 'simpleobject_check'), array(array('simpleobject_id', '=', $id))));
		$this->assertNotNull(key($objects));
		$this->assertEquals(1, count($objects));
		$this->assertEquals(1, $total);
		$this->assertEquals($id, $objects[$id]['simpleobject_id']['value']);
		$this->assertEquals($object['simpleobject_name'], $objects[$id]['simpleobject_name']['value']);
		$this->assertEquals($object['simpleobject_value'], $objects[$id]['simpleobject_value']['value']);
		$this->assertEquals($object['simpleobject_check'], $objects[$id]['simpleobject_check']['value']);
		//$object = $objects[$id];

		/* Test du comptage */
		$this->assertTrue($hORM->count($total_record));
		$this->assertEquals(1, $total_record);

		/* Test de lecture via READ */
		$this->assertTrue($hORM->read(array($id), $objects, array('simpleobject_name', 'simpleobject_value', 'simpleobject_check')));
		$this->assertNotNull(key($objects));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id, $objects[$id]['simpleobject_id']['value']);
		$this->assertEquals($object['simpleobject_name'], $objects[$id]['simpleobject_name']['value']);
		$this->assertEquals($object['simpleobject_value'], $objects[$id]['simpleobject_value']['value']);
		$this->assertEquals($object['simpleobject_check'], $objects[$id]['simpleobject_check']['value']);

		/* Test de lecture via SEARCH */
		$objects_id_list = array();
		$this->assertTrue($hORM->search($objects_id_list, $total_record, array(array('simpleobject_id', '=', $id))));
		$this->assertNotNull(key($objects_id_list));
		$this->assertEquals(1, count($objects_id_list));
		$this->assertEquals(1, $total_record);
		$this->assertContains($id, $objects_id_list);

		/* Edition */
		$object['simpleobject_name'] = 'Objet 2';
		$this->assertTrue($hORM->write($id, $object));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, array('simpleobject_name'), array(array('simpleobject_id', '=', $id))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id, $objects[$id]['simpleobject_id']['value']);
		$this->assertEquals($object['simpleobject_name'], $objects[$id]['simpleobject_name']['value']);
		/* On a pas récupéré les autres champs. */
		$this->assertNotContains('simpleobject_value', $objects[$id]);
		$this->assertNotContains('simpleobject_check', $objects[$id]);

		/* Suppression */
		$this->assertTrue($hORM->unlink($id));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, array('simpleobject_name'), array(array('simpleobject_id', '=', $id))));
		$this->assertEquals(0, count($objects));

		/* Création avec primary key forcé */
		$object = array('simpleobject_id' => 42,'simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => FALSE);
		$this->assertTrue($hORM->create($object, $id));
		$this->assertGreaterThan(0, $id);

		/* Lecture */
		$this->assertTrue($hORM->read(array($id), $objects, array('simpleobject_name', 'simpleobject_value', 'simpleobject_check')));
		$this->assertNotNull(key($objects));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id, $objects[$id]['simpleobject_id']['value']);
		$this->assertEquals($object['simpleobject_name'], $objects[$id]['simpleobject_name']['value']);
		$this->assertEquals($object['simpleobject_value'], $objects[$id]['simpleobject_value']['value']);
		$this->assertEquals($object['simpleobject_check'], $objects[$id]['simpleobject_check']['value']);
	}

}
