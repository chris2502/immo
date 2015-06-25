<?php

/**
 *  @class CommonTest
 *  @Revision $Revision: 3328 $
 *
 */

class CommonTest extends Killi_TestCase {
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

	/**
	 * @ticket #1803
	 */
	public function testValidForm()
	{
		$hInstance = new SimpleObjectMethod();
		$object = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => '0', 'simpleobject_date' => '2012-10-10',
				'simpleobject_time'=>'22:12:05'
				);
		$this->assertTrue($hInstance->create($object,$id));
		$this->assertGreaterThan(0, $id);
// 		$this->assertEquals($hInstance->simpleobject_value ,$_POST);

		foreach($object as $key =>  $value){
			$_POST["simpleobject/".$key]= $value;

		}
		//$_POST['simpleobject/simpleobject_date'] = '10/10/2012';
		$_POST['primary_key'] = $id;
		//$this->assertTrue($hInstance->write(array()));


	}
	
	/**
	 * Test champs particulier serialized
	 */
	public function testSerialized()
	{
		$serialized = array(5=>array('a'=>TRUE),array(array('6')),NULL,'B'=>9);
		
		$this->assertTrue(ORM::getORMInstance('simpleobject')->create(array('simpleobject_serialized'=>$serialized),$id));
		$this->assertGreaterThan(0, $id);

		$this->assertTrue(ORM::getORMInstance('simpleobject')->read($id, $object, array('simpleobject_serialized')));
	
		$this->assertEquals($object['simpleobject_serialized']['value'], $serialized);
		
		$fliped_serialized = array_reverse(array(5=>array('a'=>TRUE),array(array('6')),NULL,'B'=>9));
		
		$this->assertTrue(ORM::getORMInstance('simpleobject')->write($id, array('simpleobject_serialized'=>$fliped_serialized)));
		$this->assertTrue(ORM::getORMInstance('simpleobject')->read($id, $object,array('simpleobject_serialized')));
		
		$this->assertEquals($object['simpleobject_serialized']['value'], $fliped_serialized);
		
	}

	/**
	 * SQLWarningException attendu car la date n'est pas correcte
	 *
	 * @expectedException SQLWarningException
	 */
	public function testSQLWarning()
	{
		$hInstance = new SimpleObjectMethod();
		$object = array(
			'simpleobject_name' => 'Objet 1',
			'simpleobject_value' => 42,
			'simpleobject_check' => FALSE,
			'simpleobject_date' => 'THIS IS NOT A VALID DATE',
			'simpleobject_time'=>'12:49:05'
		);

		$hInstance->create($object,$id);
	}

	public function testValidDataLogic()
	{
		$hInstance = new SimpleObjectMethod();
		$hORM = ORM::getORMInstance('simpleobject');

		/* Création d'un objet à dupliquer. */
		$object = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => FALSE, 'simpleobject_date' => '2012-11-29',
				'simpleobject_time'=>'12:49:05'
		);
		$this->assertTrue($hInstance->create($object,$id));
		$this->assertGreaterThan(0, $id);

		/* Récupération de l'objet. */
		$objects_list = array();
		$this->assertTrue($hORM->read(array($id), $objects_list));
		$this->assertEquals(1, count($objects_list));
		$this->assertNotEmpty($objects_list[$id]);

		/* Création d'un autre objet à partir du premier. */
		$data = $objects_list[$id];
		foreach($data AS $key => $value)
		{
			if($key != 'simpleobject_id')
			{
				$new_obj[$key] = $value['value'];
			}
		}

		$this->assertTrue($hInstance->create($new_obj, $id2));
		$this->assertGreaterThan(0, $id2);

		$objects_list_2 = array();
		$this->assertTrue($hORM->read(array($id2), $objects_list_2));
		$this->assertEquals(1, count($objects_list_2));
		$this->assertNotEmpty($objects_list_2[$id2]);

		foreach($object AS $field_name => $field_value)
		{
			if(isset($objects_list[$id][$field_name]['date_str']))
			{
				$this->assertEquals($field_value, date('Y-m-d', $objects_list[$id][$field_name]['timestamp']));
				$this->assertEquals($field_value, date('Y-m-d', $objects_list_2[$id2][$field_name]['timestamp']));
				continue;
			}

			$this->assertEquals($field_value, $objects_list[$id][$field_name]['value']);
			$this->assertEquals($field_value, $objects_list_2[$id2][$field_name]['value']);
		}
	}

	public function testValidBrowse()
	{
		$hInstance = new SimpleObjectMethod();
		$hORM = ORM::getORMInstance('simpleobject', true);

		/* Insertion des éléments. */
		$object = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 43, 'simpleobject_check' => '0', 'simpleobject_date' => '2012-11-29',
				'simpleobject_time'=>'12:49:05'
		);
		$this->assertTrue($hInstance->create($object,$id));
		$this->assertGreaterThan(0, $id);

		$object = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => '0', 'simpleobject_date' => '2012-11-29',
				'simpleobject_time'=>'12:49:05'
		);
		$this->assertTrue($hInstance->create($object,$id));
		$this->assertGreaterThan(0, $id);

		/* Récupération des éléments via browse. */
		$object_list = array();
		$total = 0;
		$this->assertTrue($hORM->browse($object_list, $total, array('simpleobject_id')));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($object_list));

		/* Récupération des éléments via Search. */
		$id_list = array();
		$this->assertTrue($hORM->search($id_list, $total));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($id_list));

		/* Vérification de la correspondance des éléments retournés par browse avec ceux retournés par Search. */
		foreach($id_list AS $id)
		{
			$this->assertArrayHasKey($id, $object_list);
		}


	}
}
