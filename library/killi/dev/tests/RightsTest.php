<?php

/**
 *  @class UserObjectTest
 *  @Revision $Revision: 2736 $
 *
 */

class RightsTest extends Killi_TestCase
{
	private static $other_profil_id = null;
	private static $other_profil_id_2 = null;

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

	public function testCreateProfil()
	{
		ORM::getORMInstance('profil')->create(array('nom'=>"OtherProfil"),self::$other_profil_id);

		ORM::getORMInstance('profil')->create(array('nom'=>"OtherProfil_2"),self::$other_profil_id_2);
	}

	public function testDefaultRightsObjectExists()
	{
		self::setAdmin();

		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);

		Rights::getCreateDeleteViewStatus('USER', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);

		self::setReadOnly();

		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);

		self::setNoProfil();

		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);
	}

	public function testDefaultRightsObjectDoesNotExists()
	{
		self::setAdmin();

		Rights::getCreateDeleteViewStatus('objectdoesnotexists', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);

		self::setReadOnly();

		Rights::getCreateDeleteViewStatus('objectdoesnotexists', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);

		self::setNoProfil();

		Rights::getCreateDeleteViewStatus('objectdoesnotexists', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);
	}

	public function testRightsInsert()
	{
		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/create/user'=>true,
			'object/delete/user'=>false,
			'object/view/user'=>1
		);

		ORM::getControllerInstance('profil')->write($_POST);

		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/create/user'=>'1',
			'object/delete/user'=>'0',
			'object/view/user'=>''
		);

		ORM::getControllerInstance('profil')->write($_POST);
	}

	public function testAdmin()
	{
		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/view/user'=>false
		);

		ORM::getControllerInstance('profil')->write($_POST);

		self::setAdmin();

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($view);
	}

	public function testReadOnly()
	{
		$_POST = array(
			'killi_profil_id'=>READONLY_PROFIL_ID,
			'object/create/user'=>true,
			'object/delete/user'=>true
		);

		ORM::getControllerInstance('profil')->write($_POST);

		self::setReadOnly();

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
	}

	public function testAdminLock()
	{
		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/create/user'=>true,
			'object/delete/user'=>true,
			'object/view/user'=>true,
		);

		ORM::getControllerInstance('profil')->write($_POST);

		self::setAdmin();

		Rights::lockObjectRights('user', 'create', false);
		Rights::lockObjectRights('user', 'delete', false);
		Rights::lockObjectRights('user', 'view', false);

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);

		//

		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/create/user'=>false,
			'object/delete/user'=>false,
			'object/view/user'=>false,
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::lockObjectRights('user', 'create', true);
		Rights::lockObjectRights('user', 'delete', true);
		Rights::lockObjectRights('user', 'view', true);

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertTrue($delete);
		$this->assertTrue($view);
	}

	public function testReadOnlyLock()
	{
		$_POST = array(
			'killi_profil_id'=>READONLY_PROFIL_ID,
			'object/create/user'=>true,
			'object/delete/user'=>true,
			'object/view/user'=>true,
		);

		ORM::getControllerInstance('profil')->write($_POST);

		self::setReadOnly();

		Rights::lockObjectRights('user', 'create', false);
		Rights::lockObjectRights('user', 'delete', false);
		Rights::lockObjectRights('user', 'view', false);

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);

		//

		$_POST = array(
			'killi_profil_id'=>READONLY_PROFIL_ID,
			'object/create/user'=>false,
			'object/delete/user'=>false,
			'object/view/user'=>false,
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::lockObjectRights('user', 'create', true);
		Rights::lockObjectRights('user', 'delete', true);
		Rights::lockObjectRights('user', 'view', true);

		Rights::clearCache();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertTrue($delete);
		$this->assertTrue($view);
	}

	public function testAdminReadOnly()
	{
		// Bah quoi ? c'est possible
		self::setAdminReadOnly();

		$_POST = array(
			'killi_profil_id'=>READONLY_PROFIL_ID,
			'object/view/user'=>false,
		);

		ORM::getControllerInstance('profil')->write($_POST);

		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/create/user'=>true,
			'object/delete/user'=>false
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);
	}

	public function testOtherProfil()
	{
		self::setOther();

		// default
		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);

		$_POST = array(
			'killi_profil_id'=>self::$other_profil_id,
			'object/create/user'=>true,
			'object/delete/user'=>true,
			'object/view/user'=>true
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertTrue($delete);
		$this->assertTrue($view);

		$_POST = array(
			'killi_profil_id'=>self::$other_profil_id,
			'object/create/user'=>false,
			'object/delete/user'=>false,
			'object/view/user'=>false
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertFalse($create);
		$this->assertFalse($delete);
		$this->assertFalse($view);
	}

	public function testOtherProfilAndReadOnly()
	{
		self::setOtherAndReadonly();

		$_POST = array(
			'killi_profil_id'=>self::$other_profil_id,
			'object/create/user'=>true,
			'object/delete/user'=>false,
			'object/view/user'=>TRUE
		);

		ORM::getControllerInstance('profil')->write($_POST);

		$_POST = array(
			'killi_profil_id'=>READONLY_PROFIL_ID,
			'object/view/user'=>false
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertFalse($delete);
		$this->assertTrue($view);
	}

	public function testOtherProfilAndAdmin()
	{
		self::setOtherAndAdmin();

		$_POST = array(
			'killi_profil_id'=>self::$other_profil_id,
			'object/create/user'=>true,
			'object/delete/user'=>false,
			'object/view/user'=>false
		);

		ORM::getControllerInstance('profil')->write($_POST);

		$_POST = array(
			'killi_profil_id'=>ADMIN_PROFIL_ID,
			'object/delete/user'=>true
		);

		ORM::getControllerInstance('profil')->write($_POST);

		Rights::clearCache();
		Rights::clearLock();
		Rights::getCreateDeleteViewStatus('user', $create, $delete, $view);

		$this->assertTrue($create);
		$this->assertTrue($delete);
		$this->assertTrue($view);
	}
}
