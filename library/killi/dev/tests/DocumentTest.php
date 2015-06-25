<?php

/**
 *  @class DocumentTest
 *  @Revision $Revision: 4445 $
 *
 */

class DocumentTest extends Killi_TestCase
{

	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('FilesObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('FilesObject');
		$hORM->deleteObjectInDatabase();
	}

	public function testDocumentCreation()
	{
		$docMethod = new DocumentMethod();

		$docTypeMethod = new DocumentTypeMethod();

		$docStateMethod = new EtatDocumentMethod();

		$data = array('name' => 'Test Type', 'rulename' => 'Test', 'object' => 'simpleobject', 'obsolete' => false);
		$id_t = 0;
		$this->assertTrue($docTypeMethod->create($data, $id_t));
		$this->assertGreaterThan(0, $id_t);

		// Prevent OUT OF RANGE
		$query = "ALTER TABLE `etat_document` ENGINE = InnoDB auto_increment = 4;";

		$data = array('nom' => 'Etat test');
		$id_s = 0;
		$this->assertTrue($docStateMethod->create($data, $id_s));
		$this->assertGreaterThan(0, $id_s);

		$data = array('document_type_id' => $id_t, 'etat_document_id' => $id_s, 'object' => 'simpleobject', 'object_id' => 1, 'mime_type' => 'text/plain', 'size' => '128', 'file_name' => 'toto.txt');
		$id = 0;
		$this->assertTrue($docMethod->create($data, $id));
		$this->assertGreaterThan(0, $id);

		$hORM = ORM::getORMInstance('filesobject');
		$id = 0;
		$this->assertTrue($hORM->create($data, $id));
		$this->assertGreaterThan(0, $id);

	}

}
