<?php

/**
 *  @class ORMRelatedTest
 *  @Revision $Revision: 3431 $
 *
 */

class ORMRelatedTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('relatedobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('relatedobject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidSimpleOperation()
	{
		$hORMsimple = ORM::getORMInstance('simpleobject');
		$hORMrelated = ORM::getORMInstance('relatedobject');

		/* Création de l'objet simple 1 */
		$object1 = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => true);
		$this->assertTrue($hORMsimple->create($object1, $id1));
		$this->assertGreaterThan(0, $id1);

		/* Création de l'objet simple 2 */
		$object2 = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => false);
		$this->assertTrue($hORMsimple->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Création d'un objet related */
		$object3 = array('relatedobject_name' => 'Objet related', 'simpleobject_id' => $id1);
		$this->assertTrue($hORMrelated->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Création de l'objet related sur lequel on va tester */
		$object3 = array('relatedobject_name' => 'Objet related', 'simpleobject_id' => $id2);
		$this->assertTrue($hORMrelated->create($object3, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Verification de présence. */
		$this->assertTrue($hORMsimple->count($total_record));
		$this->assertEquals(2, $total_record);

		$this->assertTrue($hORMrelated->count($total_record));
		$this->assertEquals(2, $total_record);

		$this->assertTrue($hORMrelated->count($total_record, array(array('simpleobject_id', '=', $id2))));
		$this->assertEquals(1, $total_record);

		$this->assertTrue($hORMrelated->count($total_record, array(array('value', '=', 43))));
		$this->assertEquals(1, $total_record);

		/* Lecture simple de l'objet related */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name'), array(array('relatedobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['relatedobject_id']['value']);
		$this->assertEquals($object3['relatedobject_name'], $objects[$id3]['relatedobject_name']['value']);
		$this->assertEquals($id2, $objects[$id3]['simpleobject_id']['value']);
		$this->assertNotContains('simpleobject_name', $objects[$id3]);
		$this->assertNotContains('simpleobject_value', $objects[$id3]);
		$this->assertNotContains('simpleobject_check', $objects[$id3]);

		/* Lecture simple de l'objet related avec champs related */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name', 'name', 'value', 'check'), array(array('relatedobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['relatedobject_id']['value']);
		$this->assertEquals($object3['relatedobject_name'], $objects[$id3]['relatedobject_name']['value']);
		$this->assertEquals($id2, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals($object2['simpleobject_name'], $objects[$id3]['name']['value']);
		$this->assertEquals($object2['simpleobject_value'], $objects[$id3]['value']['value']);
		$this->assertEquals($object2['simpleobject_check'], $objects[$id3]['check']['value']);
		$this->assertNotContains('simpleobject_name', $objects[$id3]);
		$this->assertNotContains('simpleobject_value', $objects[$id3]);
		$this->assertNotContains('simpleobject_check', $objects[$id3]);
		//DbLayer::trace_queries();

		/* Lecture avec jointure de l'objet related */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name', 'simpleobject_id'), array(array('relatedobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['relatedobject_id']['value']);
		$this->assertEquals($object3['relatedobject_name'], $objects[$id3]['relatedobject_name']['value']);
		$this->assertEquals($id2, $objects[$id3]['simpleobject_id']['value']);
		$this->assertEquals('Reference : ' . $id2, $objects[$id3]['simpleobject_id']['reference']);

		/* Lecture avec filtrage sur attribut related (Sur 1 seul niveau) */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name'), array(array('value', '=', 43))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['relatedobject_id']['value']);
		$this->assertEquals($object3['relatedobject_name'], $objects[$id3]['relatedobject_name']['value']);
		$this->assertEquals($id2, $objects[$id3]['simpleobject_id']['value']);

		/* Edition de l'objet related pour pointer sur l'objet simple 1 */
		$object3['simpleobject_id'] = $id1;
		$this->assertTrue($hORMrelated->write($id3, $object3));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name'), array(array('relatedobject_id', '=', $id3))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals($id3, $objects[$id3]['relatedobject_id']['value']);
		$this->assertEquals($object3['relatedobject_name'], $objects[$id3]['relatedobject_name']['value']);
		$this->assertEquals($id1, $objects[$id3]['simpleobject_id']['value']);

		/* Suppression */
		$this->assertTrue($hORMrelated->unlink($id3));

		/* Lecture */
		$objects = array();
		$this->assertTrue($hORMrelated->browse($objects, $total, array('relatedobject_name'), array(array('relatedobject_id', '=', $id3))));
		$this->assertEquals(0, count($objects));

		/* Suppression des objets simples. */
		$this->assertTrue($hORMsimple->unlink($id1));
		$this->assertTrue($hORMsimple->unlink($id2));

	}

}

