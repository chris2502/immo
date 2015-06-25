<?php

/**
 *  @class ORMFailureTest
 *  @Revision $Revision: 3345 $
 *
 */

class ORMFailureTest extends Killi_TestCase
{
	/**
	 * @expectedException Exception
	 */
	public function testExceptionOnInsert()
	{
		$hORM = ORM::getORMInstance('unexistingobject');

		$object = array('unexistingobject_name' => 'Objet impossible');
		$this->assertTrue($hORM->create($object, $id));
		$this->assertGreaterThan(0, $id);
	}

	/**
	 * @expectedException Exception
	 */
	public function testExceptionOnGetValue()
	{
		$hORM = ORM::getORMInstance('unexistingobject');
		$objects = array();
		$hORM->browse($objects, $total);
	}

	/**
	 * @expectedException Exception
	 */
	public function testExceptionOnUpdate()
	{
		$hORM = ORM::getORMInstance('unexistingobject');
		$id = 1;
		$data = array('unexistingobject_name' => 'Objet impossible');
		$hORM->write($id, $data);
	}

	/**
	 * @expectedException Exception
	 */
	public function testExceptionOnDelete()
	{
		$hORM = ORM::getORMInstance('unexistingobject');
		$id = 1;
		$hORM->unlink($id);
	}
}
