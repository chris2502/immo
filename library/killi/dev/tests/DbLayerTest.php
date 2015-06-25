<?php

/**
 *  @class DbLayerTest
 *  @Revision $Revision: 2736 $
 *
 */

class DbLayerTest extends Killi_TestCase
{
	public static $database;

	private static $readonly_database_connection;

	public static function main()
	{
		return new DbLayerTest('main');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * Test de construction de l'objet de connexion à la base de données.
	 */
	public function testConstruct()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD
				)
		) ;

		$database = new DbLayer($dbconfig);
		self::$database = $database;
	}

	/**
	 * Test de construction de l'objet de connexion à la base de données
	 * en mode persistant.
	 */
	public function testPConstruct()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
						'ctype' => 'persistent'
				)
		) ;

		$database = new DbLayer($dbconfig);
	}

	/**
	 * @depends testConstruct
	 * @expectedException Exception
	 */
	public function testWrongDbUse()
	{
		self::$database->db_use('rw', 'wrongName');
	}

	/**
	 * @depends testConstruct
	 */
	public function testDbUse()
	{
		$this->assertTrue(self::$database->db_use('rw', DBSI_DATABASE));
	}

	/**
	 * @expectedException SQLConnectionException
	 */
	public function testWrongConnection()
	{
		/* Connection fail */
		self::$database->db_connect('rw', 'localhost', 'toto', 'truc', 'wrongdb');
	}

	public function testConnection()
	{
		/* Connection success */
		$this->assertTrue(self::$database->db_connect('rw', DBSI_MASTER_HOSTNAME, DBSI_MASTER_USERNAME, DBSI_MASTER_PASSWORD, DBSI_DATABASE));
	}

	/**
	 * Opération interdite
	 *
	 * @expectedException SQLOperationException
	 */
	public function testWrongSelect()
	{
		/* Fail */
		self::$database->db_select('insert into table (column) values (1)', $result);
	}

	/**
	 * Connexion en lecture seule
	 */
	public function testReadOnlyConnection()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,

				// mode lecture seule

				'r' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
				)
		) ;

		self::$readonly_database_connection = new DbLayer($dbconfig);
	}

	/**
	 * Test de db_escape_string en mode lecture seule
	 */
	public function testProtectFieldOnReadOnly()
	{
		$this->assertEquals(
			"# this is\'nt a valid text !! \' or 1=1\' ## ;",
			self::$readonly_database_connection->db_escape_string("# this is'nt a valid text !! ' or 1=1' ## ;")
		);
	}

	/**
	 * Opération interdite en mode lecture seule en spécifiant le flag
	 *
	 * @depends testConstruct
	 * @expectedException SQLOperationException
	 */
	public function testDangerousOperationFlaged()
	{
		/* Fail */
		self::$readonly_database_connection->db_execute('insert into table (column) values (1)', $rows, $result, 'r');
	}

	/**
	 * Opération interdite en mode lecture seule (pas de connexion flaguée rw)
	 *
	 * @depends testConstruct
	 * @expectedException SQLOperationException
	 */
	public function testDangerousOperation()
	{
		/* Fail */
		self::$readonly_database_connection->db_execute('insert into table (column) values (1)'); // flag rw par défaut
	}

	/**
	 * Commit en mode lecture seule, aucun effet
	 *
	 * @depends testConstruct
	 */
	public function testReadOnlyCommit()
	{
		$this->assertTrue(self::$readonly_database_connection->db_commit());
	}

	/**
	 * Opération interdite en mode lecture seule
	 *
	 * @depends testConstruct
	 * @expectedException SQLOperationException
	 */
	public function testForgottenRollback()
	{
		/* Fail */
		self::$readonly_database_connection->db_rollback();
	}

	public function testConnectionWithOldBadUserId()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,
				'users_id' => 0, // BAD ID !

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
				)
		) ;

		new DbLayer($dbconfig);
	}

	public function testConnectionWithUserId()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,
				'users_id' => 1,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
				)
		) ;

		new DbLayer($dbconfig);
	}

	/**
	 * @expectedException Exception
	 */
	public function testConnectionWithBadUserId()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,
				'users_id' => -1, // BAD ID !

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
				)
		) ;

		new DbLayer($dbconfig);
	}

	/**
	 * Exception attendu car pas de commit pendant les tests.
	 *
	 *
	 * @depends testConnection
	 * @expectedException Exception
	 */
	public function testCommitDuringTest()
	{
		$this->hDB->db_commit();
	}

	/**
	 * En réalité, aucun commit n'est éffectué car la connexion ne passe en mode autocommit=0
	 * qu'à la premiere opération de manipulation de structure
	 */
	public function testCommit()
	{
		$this->assertTrue(self::$database->db_commit());
	}

	/**
	 * Commit réel
	 * On execute une requête pourrie pour forcer le passage en mode autocommit=0
	 */
	public function testRealCommit()
	{
		$dbconfig = array(
				'dbname'   => DBSI_DATABASE,
				'charset'  => DBSI_CHARSET,

				'rw' => array(
						'host'  => DBSI_MASTER_HOSTNAME,
						'user'  => DBSI_MASTER_USERNAME,
						'pwd'   => DBSI_MASTER_PASSWORD,
				)
		) ;

		$commitable_connection = new DbLayer($dbconfig);

		$this->assertTrue($commitable_connection->db_start());
        $this->assertTrue(
            $commitable_connection->db_execute(
                'update '.$commitable_connection->db_escape_string(RIGHTS_DATABASE).'.killi_actionmenu set actionmenu_id=actionmenu_id where actionmenu_id= -9999'
                )
            );
		$this->assertTrue($commitable_connection->db_commit());
	}

	/**
	 * @depends testConnection
	 */
	public function testRollback()
	{
		$this->assertTrue($this->hDB->db_rollback());
	}


}
