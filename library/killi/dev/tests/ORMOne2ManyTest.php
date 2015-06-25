<?php

/**
 *  @class ORMOne2ManyTest
 *  @Revision $Revision: 4469 $
 *
 */

class LinkObject
{
	public $description  = 'Objet link';
	public $table		 = 'test_link_object';
	public $primary_key  = 'link_id';
	public $database	 = TESTS_DATABASE;

	function __construct()
	{
		$this->link_id = new PrimaryFieldDefinition ();
		$this->link_name = new TextFieldDefinition ();
		$this->one2many_id = new Many2oneFieldDefinition ( 'One2ManyObject' );
	}
}

ORM::declareObject('LinkObject');

class One2ManyObject
{
	public $description  = 'Objet one2many';
	public $table		 = 'test_one2many_object';
	public $primary_key  = 'one2many_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'object_name';

	function __construct()
	{
		$this->one2many_id = new PrimaryFieldDefinition ();
		$this->object_name = new TextFieldDefinition ();
		$this->link_id = new One2manyFieldDefinition( 'LinkObject' , 'one2many_id'); 
	}

}

class One2ManyObjectMethod extends Common
{

}

ORM::declareObject('One2ManyObject');

class ORMOne2ManyTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('LinkObject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('One2ManyObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('One2ManyObject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('LinkObject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidOperation()
	{
		$hORMlink = ORM::getORMInstance('LinkObject', true);
		$hORMo2m = ORM::getORMInstance('One2ManyObject', true);

		$o2m1 = array('object_name' => 'Object 1');
		$this->assertTrue($hORMo2m->create($o2m1, $o2m_id_1));
		$this->assertGreaterThan(0, $o2m_id_1);

		$o2m2 = array('object_name' => 'Object 2');
		$this->assertTrue($hORMo2m->create($o2m2, $o2m_id_2));
		$this->assertGreaterThan(0, $o2m_id_2);

		$link1 = array('link_name' => 'Link 1', 'one2many_id' => $o2m_id_1);
		$this->assertTrue($hORMlink->create($link1, $link_id_1));
		$this->assertGreaterThan(0, $link_id_1);

		$link2 = array('link_name' => 'Link 2', 'one2many_id' => $o2m_id_2);
		$this->assertTrue($hORMlink->create($link2, $link_id_2));
		$this->assertGreaterThan(0, $link_id_2);

		$link3 = array('link_name' => 'Link 3', 'one2many_id' => $o2m_id_1);
		$this->assertTrue($hORMlink->create($link3, $link_id_3));
		$this->assertGreaterThan(0, $link_id_3);

		$objects = array();
		$this->assertTrue($hORMlink->browse($objects, $total, array('link_id', 'link_name', 'one2many_id')));
		$this->assertEquals(3, $total);
		$this->assertEquals(3, count($objects));

		$this->assertEquals($link_id_1, $objects[$link_id_1]['link_id']['value']);
		$this->assertEquals($link1['link_name'], $objects[$link_id_1]['link_name']['value']);
		$this->assertEquals($o2m_id_1, $objects[$link_id_1]['one2many_id']['value']);
		$this->assertEquals($o2m1['object_name'], $objects[$link_id_1]['one2many_id']['reference']);

		$this->assertEquals($link_id_2, $objects[$link_id_2]['link_id']['value']);
		$this->assertEquals($link2['link_name'], $objects[$link_id_2]['link_name']['value']);
		$this->assertEquals($o2m_id_2, $objects[$link_id_2]['one2many_id']['value']);
		$this->assertEquals($o2m2['object_name'], $objects[$link_id_2]['one2many_id']['reference']);

		$this->assertEquals($link_id_3, $objects[$link_id_3]['link_id']['value']);
		$this->assertEquals($link3['link_name'], $objects[$link_id_3]['link_name']['value']);
		$this->assertEquals($o2m_id_1, $objects[$link_id_3]['one2many_id']['value']);
		$this->assertEquals($o2m1['object_name'], $objects[$link_id_3]['one2many_id']['reference']);

		$objects = array();
		$this->assertTrue($hORMo2m->browse($objects, $total, array('one2many_id', 'object_name', 'link_id')));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($objects));

		$this->assertEquals($o2m_id_1, $objects[$o2m_id_1]['one2many_id']['value']);
		$this->assertEquals($o2m1['object_name'], $objects[$o2m_id_1]['object_name']['value']);
		$this->assertEquals(FALSE, $objects[$o2m_id_1]['object_name']['editable']);
		$this->assertEquals(array($link_id_1, $link_id_3), $objects[$o2m_id_1]['link_id']['value']);
		$this->assertEquals(false, $objects[$o2m_id_1]['link_id']['editable']);

		$this->assertEquals($o2m_id_2, $objects[$o2m_id_2]['one2many_id']['value']);
		$this->assertEquals($o2m2['object_name'], $objects[$o2m_id_2]['object_name']['value']);
		$this->assertEquals(FALSE, $objects[$o2m_id_2]['object_name']['editable']);
		$this->assertEquals(array($link_id_2), $objects[$o2m_id_2]['link_id']['value']);
		$this->assertEquals(false, $objects[$o2m_id_2]['link_id']['editable']);
	}
}

