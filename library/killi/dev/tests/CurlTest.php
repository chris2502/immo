<?php

/**
 *  @class CurlTest
 *  @Revision $Revision: 3409 $
 *
 */

if (defined('TESTS_WEB_PATH'))
{
	class SimpleJSONObject
	{
		public $description  = 'Objet JSON simple';
		public $primary_key  = 'simplejson_id';
		public $json 		 =  array(
			'path'       => TESTS_WEB_PATH,
			'login'      => 'Killi',
			'password'   => 'Nage',
			'object'	 => 'SimpleObject',
			'cert'		 => '',
			'cert_pwd'	 => ''
		);

		function __construct()
		{
			$this->simpleobject_id = new PrimaryFieldDefinition();
			$this->simpleobject_name = new TextFieldDefinition();
			$this->simpleobject_value = new IntFieldDefinition();
		}
	}
	ORM::declareObject('SimpleJSONObject');


	/*********************************************************
	* Si TESTS_WEB_PATH est définis, alors la page est
	* appelée par l'invocateur PHPUnit.
	*********************************************************/
	class CurlTest extends Killi_TestCase {

		public function testErrorNoURL1() {
			$curl = new KilliCurl();
			$this->setExpectedException('CurlException', 'No Url');
			$curl->request();
		}

		public function testErrorNoURL2() {
			$curl = new KilliCurl(null, false);
			$this->assertFalse($curl->request());
			$errors = $curl->getErrors();
			$this->assertEquals($errors[0], 'No Url');
		}

		public function testErrorDoubleData() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setPost('data', 'Oulà');
			$curl->fichier = 'class.KilliCurl.php';
			$this->setExpectedException('CurlException', 'Définition de données à la clef data du $_POST en plus de données JSON.');
			$curl->request();
		}

		public function testAuthError1() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setUser('', 'password');
			$this->setExpectedException('CurlException', 'Password utilisateur sans login.');
			$curl->request();
		}

		public function testCertificatError1() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setSSL('/tmp/cert', '');
			$this->setExpectedException('CurlException', 'Certificat sans password.');
			$curl->request();
		}

		public function testCertificatError2() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setSSL(null, 'password');
			$this->setExpectedException('CurlException', 'Password de certificat sans certificat.');
			$curl->request();
		}

		public function testCurlError() {
			$curl = new KilliCurl('http://reallydontexist');
			$this->setExpectedException('CurlException', 'Couldn\'t resolve host \'reallydontexist\'');
			$curl->request();
		}

		public function testCurlError2() {
			$curl = new KilliCurl('http://reallydontexist', false);
			$this->assertFalse($curl->request());
			$this->assertEquals(array('Couldn\'t resolve host \'reallydontexist\''), $curl->getErrors());
		}

		public function testManyErrors1() {
			$curl = new KilliCurl();
			$curl->setSSL(null, 'password');
			$curl->setUser(null, 'password');
			$this->setExpectedException('CurlException', 'No Url'.PHP_EOL.'Password de certificat sans certificat.'.PHP_EOL.'Password utilisateur sans login.');
			$curl->request();
		}

		public function testAuthError2() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setUser('Admin', 'Rogue');
			try {
				$curl->request();
			}
			catch(CurlException $e)
			{
				$this->assertEquals($e->curl->__toString(), json_encode(array('authentification'=>'Invalid Login and/or Password')));
			}
		}

		public function testDeathError() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=ErrorGen.suddenDeath', false);
			$curl->setUser('Killi', 'Nage');
			$this->assertFalse($curl->request());
			$this->assertEquals(array('Empty Curl result, maybe the script died'), $curl->getErrors());
		}

		public function testNoData() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=ErrorGen.noData', false);
			$curl->setUser('Killi', 'Nage');
			$this->assertFalse($curl->request());
			$this->assertEquals(array('Bad Curl result, return must have the property "data"'), $curl->getErrors());
		}

		public function testNoData2() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=ErrorGen.noData', false);
			$curl->setUser('Killi', 'Nage');
			$this->assertEquals(array('bruit' => 'Clap Clap Clap'), $curl->requestNoData());
		}

		public function testDeathError2() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=ErrorGen.suddenDeath');
			$curl->setUser('Killi', 'Nage');
			$this->setExpectedException('CurlException', 'Empty Curl result, maybe the script died');
			$curl->request();
		}

		public function testNoActionError() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php');
			$curl->setUser('Killi', 'Nage');
			try {
				$curl->request();
			}
			catch(CurlException $e)
			{
				$this->assertEquals($e->curl->__toString(), json_encode(array('error' => 'No Action')));
			}
		}

		public function testNoActionError2() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php', false);
			$curl->setUser('Killi', 'Nage');
			$this->assertFalse($curl->request());
			$this->assertEquals(json_encode(array('error' => 'No Action')), $curl->__toString());
		}

		public function testRequest() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=thepublic.applaudir');
			$curl->setUser('Killi', 'Nage');
			$this->assertEquals(array('bruit' => 'Clap Clap Clap'), $curl->request());
		}

		public function testJSONFunction() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=json.Power');
			$curl->setUser('Killi', 'Nage');
			$curl->X = 2;
			$curl->p = 8;
			$this->assertEquals($curl->request(), array('power' => '256'));
		}

		public function testJSONBrowse() {
			$list = array();
			ORM::getORMInstance('SimpleJSONObject')->browse($list, $num, null);
			$this->assertEquals(array(
				'1' => array(
					'simpleobject_id' => array('value' => 1, 'editable' => true),
					'simpleobject_name' => array('value' => 'FTTH', 'editable' => true),
					'simpleobject_value' => array('value' => 3, 'editable' => true)
				),
				'2' => array(
					'simpleobject_id' => array('value' => 2, 'editable' => true),
					'simpleobject_name' => array('value' => 'MEDEF', 'editable' => true),
					'simpleobject_value' => array('value' => 4, 'editable' => true)
				),
				'3' => array(
					'simpleobject_id' => array('value' => 3, 'editable' => true),
					'simpleobject_name' => array('value' => 'Gdcf', 'editable' => true),
					'simpleobject_value' => array('value' => 15, 'editable' => true)
				)
			), $list);
		}

		public function testJSONRead() {
			$list = array();
			ORM::getORMInstance('SimpleJSONObject')->read(2, $result, array('simpleobject_value'));
			$this->assertEquals(array(
				'simpleobject_id' => array('value' => 2, 'editable' => true),
				'simpleobject_value' => array('value' => 4, 'editable' => true)
			), $result);
		}

		public function testJSONSearch() {
			$list = array();
			ORM::getORMInstance('SimpleJSONObject')->search($list, $num, array(
				array('simpleobject_value', '>' , 3)
			));
			$this->assertEquals(array(2, 3), $list);
		}

		public function testPost() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=thepublic.goingPostal');
			$curl->setUser('Killi', 'Nage')
					->setPost('Moist', 'Lord')
					->setPost('von', 'Havelock')
					->setPost('Lipwig', 'Vetinari');
			$this->assertEquals(array(
				'Lord' => 'Moist',
				'Havelock' => 'von',
				'Vetinari' => 'Lipwig'
			), $curl->request());
		}

		public function testToString()
		{
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=json.Power');
			$curl->setUser('Killi', 'Nage');
			$curl->X = 2;
			$curl->p = 8;
			$this->assertEquals('', $curl->__toString());
			$curl->request();
			$this->assertEquals(json_encode(array('data' => array('power' => 256))), $curl->__toString());
		}

		public function test()
		{
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=json.Power', false);
			$curl->setUser('Admin', 'Rogue');
			$this->assertFalse($curl->request());
			$this->assertEquals(json_encode(array('authentification'=>'Invalid Login and/or Password')), $curl->__toString());
		}

		public function testRequestJsonFalse() {
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=thepublic.applaudir');
			$curl->setUser('Killi', 'Nage')->setJson(FALSE);
			$this->assertEquals(json_encode(array('data' => array('bruit' => 'Clap Clap Clap'))), $curl->request());
		}

		public function testGet()
		{
			$curl = new KilliCurl(TESTS_WEB_PATH.'index.php?action=json.Power', false);
			$curl->setUser('Admin', 'Rogue');
			$curl->X = 2;
			$curl->p = 8;
			$this->assertEquals(null, $curl->bruit);
			$this->assertEquals(8, $curl->p);
		}
	}
}
else
{
	class CurlTest extends Killi_TestCase
	{
		public function testDummy()
		{

		}
	}
}
