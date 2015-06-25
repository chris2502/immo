<?php

/**
 *  @class ORMComputedFieldTest
 *  @Revision $Revision: 2736 $
 *
 */

class ORMComputedFieldTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('ComputedFieldObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		$hORM = ORM::getORMInstance('ComputedFieldObject');
		$hORM->deleteObjectInDatabase();
		parent::tearDown();
	}

	public function testValidComputedField()
	{
		$hORM = ORM::getORMInstance('ComputedFieldObject');

		/* Création de l'objet calculé 1 */
		$object1 = array('computedobject_value1' => 10, 'computedobject_value2' => 42);
		$this->assertTrue($hORM->create($object1, $id1));
		$this->assertGreaterThan(0, $id1);

		/* Création de l'objet calculé 2 */
		$object2 = array('computedobject_value1' => 20, 'computedobject_value2' => 18);
		$this->assertTrue($hORM->create($object2, $id2));
		$this->assertGreaterThan(0, $id2);

		/* Lecture de l'objet */
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, array('computedobject_value1', 'computedobject_value2', 'computedobject_sum')));
		$this->assertEquals(2, count($objects));
		$this->assertEquals($id1, $objects[$id1]['computedobject_id']['value']);
		$this->assertEquals($object1['computedobject_value1'], $objects[$id1]['computedobject_value1']['value']);
		$this->assertEquals($object1['computedobject_value2'], $objects[$id1]['computedobject_value2']['value']);
		$this->assertEquals($object1['computedobject_value1']+$object1['computedobject_value2'], $objects[$id1]['computedobject_sum']['value']);
		$this->assertEquals($object2['computedobject_value1'], $objects[$id2]['computedobject_value1']['value']);
		$this->assertEquals($object2['computedobject_value2'], $objects[$id2]['computedobject_value2']['value']);
		$this->assertEquals($object2['computedobject_value1']+$object2['computedobject_value2'], $objects[$id2]['computedobject_sum']['value']);
	}
}
