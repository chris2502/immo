<?php

/**
 *  @class UserObjectTest
 *  @Revision $Revision: 2736 $
 *
 */

class AttributeRightsTest extends Killi_TestCase
{
	private static function setAdmin()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(ADMIN_PROFIL_ID);
	}
	
	private static function setReadOnly()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(READONLY_PROFIL_ID);
	}
	
	private static function setAdminReadOnly()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(READONLY_PROFIL_ID,ADMIN_PROFIL_ID);
	}
	
	private static function setOtherAndReadonly()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(READONLY_PROFIL_ID,self::$other_profil_id);
	}
	
	private static function setOtherAndAdmin()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(ADMIN_PROFIL_ID,self::$other_profil_id);
	}
	
	private static function setOther()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(self::$other_profil_id);
	}
	
	private static function setNoProfil()
	{
		$_SESSION['_USER']['profil_id']['value'] = array();
	}
	
	public function setUp()
	{
		parent::setUp();

		/* Clean des droits */
		$this->hDB->db_execute('truncate killi_objects_rights');
	}

	public function testAdmin()
	{
		self::setAdmin();
		
		Rights::clearCache();

		// WorkflowToken->node_name est un related
		Rights::getRightsByAttribute('WorkflowToken', 'node_name', $read, $write);
		
		$this->assertFalse($write);
		$this->assertFalse(ORM::getObjectInstance('WorkflowToken')->node_name->editable);
	}
	
	/**
	 * @expectedException UndeclaredObjectException
	 */
	public function testNonObject()
	{
		self::setAdmin();
	
		Rights::clearCache();
	
		Rights::getRightsByAttribute('toto', 'node_name', $read, $write);
	}

}
