<?php

/**
 *  @class UserObjectTest
 *  @Revision $Revision: 4517 $
 *
 */

class UserObjectTest extends Killi_TestCase
{
	private static $user;

	private static $user_data = array(
				'nom' => 'Nom',
				'prenom' => 'Prenom',
				'mail' => 'Mail@free.fr',
				'login' => 'pnom',
				'password' => 'password',
				'actif' => true,
				'last_connection' => '2015-12-31 15:16:17',
				'certificat_duree' => 365);

	public static function main()
	{
		return new UserObjectTest('main');
	}

	public function setUp()
	{
		/* Définition du profil de l'utilisateur. */
		$_SESSION['_USER']['profil_id']['value'] = array(ADMIN_PROFIL_ID);
		parent::setUp();
	}

	public function testValidCreateUser()
	{
		$userMethod = new UserMethod();
		$this->assertTrue($userMethod->create(self::$user_data, $id));
		$this->assertGreaterThan(0, $id);
		self::$user = $id;
	}

	/**
	 * @depends testValidCreateUser
	 * @expectedException Exception
	 */
	public function testWrongDuplicateUser()
	{
		$userMethod = new UserMethod();
		$userMethod->create(self::$user_data, $id);

		throw new Exception('Les contraintes d\'unicité ne peuvent pas être testés sur la table user pour des raisons spécifiques.');
	}

	/**
	 * @depends testValidCreateUser
	 */
	public function testValidGetUserInformations()
	{
		$hORM = ORM::getORMInstance('user');
		$users_list = array();
		$this->assertTrue($hORM->browse($users_list, $total, NULL, array(array('killi_user_id', '=', self::$user))));
		$this->assertGreaterThan(0, count($users_list));

		$user = current($users_list);
		
		$this->assertEquals($user['killi_user_id']['value'], self::$user);
		$this->assertEquals($user['nom']['value'], self::$user_data['nom']);
		$this->assertEquals($user['prenom']['value'], self::$user_data['prenom']);
		$this->assertEquals($user['mail']['value'], self::$user_data['mail']);
		$this->assertEquals($user['login']['value'], self::$user_data['login']);
		$this->assertNotEquals($user['password']['value'], self::$user_data['password']);
		$this->assertEquals($user['actif']['value'], self::$user_data['actif']);
		$this->assertEquals($user['last_connection']['value'], self::$user_data['last_connection']);
		$this->assertEquals($user['certificat_duree']['value'], self::$user_data['certificat_duree']);
		//$this->assertEquals($user['operateur_id']['value'], self::$user_data['operateur_id']);
	}

	/**
	 * @depends testValidGetUserInformations
	 */
	public function testValidUpdateUser()
	{
		//$this->markTestIncomplete('Ce test n\'a pas encore été implémenté.');
	}

	/**
	 * @depends testValidUpdateUser
	 */
	public function testValidDeleteUser()
	{
		$userMethod = new UserMethod();
		$this->assertTrue($userMethod->unlink(self::$user));
	}

	/**
	 * @expectedException Exception
	 */
	public function testWrongDeleteUser()
	{
		$userMethod = new UserMethod();
		$userMethod->unlink(-1);
	}

	/**
	 * @dataProvider populateWrongUser
	 * @expectedException Exception
	 */
    public function testWrongCreateUserList($nom, $prenom, $mail, $login, $password, $actif, $last_connection, $certificat_duree, $operateur_id)
    {
    	$data = array('nom' => $nom,
    			'prenom' => $prenom,
    			'mail' => $mail,
    			'login' => $login,
    			'password' => $password,
    			'actif' => $actif,
    			'last_connection' => $last_connection,
    			'certificat_duree' => $certificat_duree,
    			//'operateur_id' => $operateur_id
    			);

    	$userMethod = new UserMethod();
    	$userMethod->create($data, $id);
    }

    public function populateWrongUser()
    {
    	$users = array(
    			// Classe de test 1 : Attribut à null
    			array(NULL, 'Prenom', 'Mail@free.fr', 'login_1', '', true, NULL, 365, NULL),
    			array('Nom', NULL, 'Mail@free.fr', 'login_2', '', true, 0, 365, 1),
    			array('Nom', 'Prenom', NULL, 'login_3', '', true, 0, 365, 1),
    			array('Nom', 'Prenom', 'Mail@free.fr', NULL, '', true, 0, 365, 1),
    			//array('Nom', 'Prenom', 'Mail@free.fr', 'login_5', '', NULL, 0, 365, 1),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login_6', '', true, 0, NULL, 1),

    			// Classe de test 2 : Attribut vide
    			array('', 'Prenom', 'Mail@free.fr', 'login_7', '', true, NULL, 365, NULL),
    			array('Nom', '', 'Mail@free.fr', 'login_8', '', true, 0, 365, 1),
    			array('Nom', 'Prenom', '', 'login_9', '', true, 0, 365, 1),
    			array('Nom', 'Prenom', 'Mail@free.fr', '', '', true, 0, 365, 1),
    			//array('Nom', 'Prenom', 'Mail@free.fr', 'login_11', '', NULL, 0, 365, 1),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login_12', '', true, 0, '', 1),

    			);
    	return $users;
    }

    /**
     * @dataProvider populateValidUser
     */
    public function testValidCreateUserList($nom, $prenom, $mail, $login, $password, $actif, $last_connection, $certificat_duree, $operateur_id)
    {
    	$data = array('nom' => $nom,
    			'prenom' => $prenom,
    			'mail' => $mail,
    			'login' => $login,
    			'password' => $password,
    			'actif' => $actif,
    			'certificat_duree' => $certificat_duree,
    			//'operateur_id' => $operateur_id
    			);

    	$userMethod = new UserMethod();
    	$this->assertTrue($userMethod->create($data, $id));
    	$this->assertGreaterThan(0, $id);

    	$hORM = ORM::getORMInstance('user');
    	$users_list = array();
    	$this->assertTrue($hORM->browse($users_list, $total, NULL, array(array('killi_user_id', '=', $id))));
    	$this->assertGreaterThan(0, count($users_list));

    	$user = current($users_list);

    	$this->assertEquals($user['killi_user_id']['value'], $id);
    	$this->assertEquals($user['nom']['value'], $nom);
    	$this->assertEquals($user['prenom']['value'], $prenom);
    	$this->assertEquals($user['mail']['value'], $mail);
    	$this->assertEquals($user['login']['value'], $login);
    	$this->assertNotEquals($user['password']['value'], $password);
    	$this->assertEquals($user['actif']['value'], $actif == false ? 0 : 1);
    	$this->assertEquals($user['last_connection']['value'], $last_connection);
    	$this->assertEquals($user['certificat_duree']['value'], $certificat_duree);
    	//$this->assertEquals($user['operateur_id']['value'], $operateur_id);

    	/* Update de l'utilisateur */
    	$this->assertTrue($hORM->write($id, $data));

    	/* Vérification que le mot de passe à bien été changé */
    	$this->assertTrue($hORM->read(array($id), $user));
    	$user = current($user);
    	$this->assertEquals($data['password'], $user['password']['value']);

    	/* Test de connexion */
    	$_POST['user/login'] = $login;
    	$_POST['user/password'] = $password;
    	$_GET['action'] = 'user.submitAuthentification';
    	$_SERVER['HTTP_REFERER'] = 'Toto';

    	unset($_SESSION['_USER']);

    	if($actif == TRUE)
    	{
    		$this->assertTrue($userMethod->submitAuthentification());

    		$this->assertNotEmpty($_SESSION['_USER']);
    	}
    	else
    	{
    		$this->assertFalse($userMethod->submitAuthentification());
    	}
    }

    public function populateValidUser()
    {
    	$users = array(
    			// Classe de test 1 :
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login1', '', true, NULL, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login2', '', true, NULL, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login3', '', true, 0, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login4', '', true, NULL, 365, 1),

    			// Classe de test 2 :
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login5', '', false, NULL, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login6', '', false, NULL, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login7', '', false, 0, 365, NULL),
    			array('Nom', 'Prenom', 'Mail@free.fr', 'login8', '', false, NULL, 365, 1),

    			// Classe de test 3 :
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login9', 'azertyuiop', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login10', 'AZERTYUIOP', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login11', '0123456789', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login12', 'Aze123tyuIOP', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login13', '"', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login14', '\'', true, NULL, 365, 1),
    			array('Nom', 'Prénom', 'Mail@free.fr', 'login15', '\\', true, NULL, 365, 1),
    	);
    	return $users;
    }
}
