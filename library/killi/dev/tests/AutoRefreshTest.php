<?php

/**
 *  @class AutoRefreshTest
 *  @Revision $Revision: 3838 $
 *
 */

class AutoRefreshTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		$hORM = ORM::getORMInstance('simpleobject');
		$hORM->deleteObjectInDatabase();
		parent::tearDown();
	}

	public function testValidRefresh()
	{
		$hInstance = new SimpleObjectMethod();

		/* Création */
		$object = array('simpleobject_name' => 'Objet 1', 'simpleobject_value' => 42, 'simpleobject_check' => '0', 'simpleobject_textarea' => 'Taratata Tata');
		$this->assertTrue($hInstance->create($object, $id1));
		$this->assertGreaterThan(0, $id1);

		$object = array('simpleobject_name' => 'Objet 2', 'simpleobject_value' => 43, 'simpleobject_check' => true);
		$this->assertTrue($hInstance->create($object, $id2));
		$this->assertGreaterThan(0, $id2);

		$object = array('simpleobject_name' => 'Objet 3', 'simpleobject_value' => 44, 'simpleobject_check' => true);
		$this->assertTrue($hInstance->create($object, $id3));
		$this->assertGreaterThan(0, $id3);

		/* Appel de refresh pour la récupération d'une donnée spécifique. */
		$fields = array();

		$fields[] = array('parent_object' => 'simpleobject', 'parent_object_id' => $id1, 'attribute' => 'simpleobject_value', 'key' => 'unique_key_for_field1', 'unit' => 'euros');
		$fields[] = array('object' => 'simpleobject', 'parent_object_id' => $id2, 'attribute' => 'simpleobject_check', 'key' => 'unique_key_for_field2');
		$fields[] = array('object' => 'simpleobject', 'parent_object_id' => $id3, 'attribute' => 'simpleobject_name', 'key' => 'unique_key_for_field3', 'format' => '<toto>%s</toto>');
		$fields[] = array('object' => 'simpleobject', 'parent_object_id' => $id1, 'attribute' => 'simpleobject_textarea', 'key' => 'unique_key_for_field4');
		// Wrong field.
		$fields[] = array('object' => 'simpleobject', 'input_name' => 'toto', 'parent_object_id' => '42', 'attribute' => 'simpleobject_value', 'key' => 'unique_key_for_field5');

		$data = array();
		$this->assertTrue($hInstance->refresh($data, $fields));

		/* Vérification des données. */
		$this->assertEquals(5, count($data));
		$this->assertEquals('42 euros',                 $data['unique_key_for_field1']);
		$this->assertEquals('<div   class="check-true" ><span style="display:none;">1</span><img src="./library/killi/images/true.png"></div>', $data['unique_key_for_field2']);
		$this->assertEquals('<toto>Objet 3</toto> euros',     $data['unique_key_for_field3']);
		$this->assertEquals('<pre>Taratata Tata</pre>', $data['unique_key_for_field4']);
		$this->assertEquals('Error: Unable to retrieve data.', $data['unique_key_for_field5']);
	}
}

