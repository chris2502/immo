<?php

/**
 *  @class FieldDefinitionTest
 *  @Revision $Revision: 4469 $
 *
 */

class FieldDefinitionTest extends Killi_TestCase
{
	private $_field;

	public static function main()
	{
		return new FieldDefinitionTest('main');
	}

	public function setUp()
	{
		parent::setUp();
		
		$this->_field = TextFieldDefinition::create()->setLabel('Test');
	}

	public function assertPreconditions()
	{
		$this->assertEquals('Test', $this->_field->name);
	}

	public function testFieldName()
	{
		$this->assertEquals('Test', $this->_field->name);
	}

	public function testFieldType()
	{
		$this->assertEquals('text', $this->_field->type);
	}
}
