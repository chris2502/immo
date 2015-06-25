<?php

/**
 *  @class ORMRelatedOnComputedTest
 *  @Revision $Revision: 4445 $
 *
 */

/**********************************/
/*                                */
/* Objet Regroupement d'exemple   */
/*                                */
/**********************************/
class RegroupementExample
{
	public $description  = 'Regroupement';
	public $table		 = 'test_regroupement_example_object';
	public $primary_key  = 'regroupement_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'nom';

	public function __construct()
	{
		$this->regroupement_id = new PrimaryFieldDefinition ();
		$this->nom = new TextFieldDefinition ();
		$this->gestionnaire_id = new IntFieldDefinition ();
	}
}

ORM::declareObject('RegroupementExample');

class RegroupementExampleMethod extends Common {}


/*****************************/
/*                           */
/* Objet Adresse d'exemple   */
/*                           */
/*****************************/
class AdresseExample
{
	public $description  = 'Adresse';
	public $table		 = 'test_adresse_example_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'nom';

	public function __construct()
	{
		$this->adresse_id = new PrimaryFieldDefinition ();
		$this->nom = new TextFieldDefinition ();

		$this->regroupement_id = Many2oneFieldDefinition::create()
				->setObjectRelation('RegroupementExample')
				->setFunction('AdresseExample', 'setRegroupement');

		$this->gestionnaire_id = RelatedFieldDefinition::create('regroupement_id','gestionnaire_id');
	}
}

ORM::declareObject('AdresseExample');

class AdresseExampleMethod extends Common
{
	public static function setRegroupement(&$adresse_list)
	{
		foreach($adresse_list AS $id => &$adr)
		{
			$adr['regroupement_id']['value'] = $id;
		}
		if(isset($adresse_list[3]))
		{
			$adresse_list[3]['regroupement_id']['value'] = NULL;
		}
	}
}

/************************************/
/*                                  */
/* Objet Adresse hérité d'exemple   */
/*                                  */
/************************************/
class AdresseExampleExtended
{
	public $description  = 'Adresse';
	public $table		 = 'test_adresse_example_ext_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference	 = 'nom';

	public function __construct()
	{
		$this->adresse_id = new ExtendsFieldDefinition('AdresseExample');
		$this->gestionnaire_id = new RelatedFieldDefinition('regroupement_id','gestionnaire_id');
	}
}

ORM::declareObject('AdresseExampleExtended');

class AdresseExampleExtendedMethod extends Common {}


class ORMRelatedOnComputedTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('RegroupementExample');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('AdresseExample');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('AdresseExampleExtended');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('AdresseExampleExtended');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('AdresseExample');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('RegroupementExample');
		$hORM->deleteObjectInDatabase();
	}

	/**
	 * Test unitaire permettant de tester la récupération de related via un champ calculé.
	 */
	public function testAccessToRelated()
	{
		$hORMregroupement	= ORM::getORMInstance('RegroupementExample');
		$hORMadresse		= ORM::getORMInstance('AdresseExample');

		/* Création des regroupements d'exemples. */
		$data = array('nom' => 'Regroupement 1', 'gestionnaire_id' => 41);
		$hORMregroupement->create($data, $id_r_1);
		$this->assertGreaterThan(0, $id_r_1);

		$data = array('nom' => 'Regroupement 2', 'gestionnaire_id' => 42);
		$hORMregroupement->create($data, $id_r_2);
		$this->assertGreaterThan(0, $id_r_1);

		/* Création des adresses d'exemples. */
		$data = array('nom' => 'Adresse 1');
		$this->assertTrue($hORMadresse->create($data, $id_a_1));
		$this->assertGreaterThan(0, $id_a_1);

		$data = array('nom' => 'Adresse 2');
		$this->assertTrue($hORMadresse->create($data, $id_a_2));
		$this->assertGreaterThan(0, $id_a_2);

		$data = array('nom' => 'Adresse 3');
		$this->assertTrue($hORMadresse->create($data, $id_a_3));
		$this->assertGreaterThan(0, $id_a_3);

		/* Accès au gestionnaires des adresses. */
		/* Adresse sans regroupement. */
		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_3, $adresse));
		$this->assertEquals($id_a_3, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 3', $adresse['nom']['value']);
		$this->assertEquals(NULL, $adresse['regroupement_id']['value']);
		$this->assertEquals(NULL, $adresse['gestionnaire_id']['value']);

		/* Adresses avec regroupement. */
		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_1, $adresse));
		$this->assertEquals($id_a_1, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 1', $adresse['nom']['value']);
		$this->assertEquals($id_r_1, $adresse['regroupement_id']['value']);
		$this->assertEquals(41, $adresse['gestionnaire_id']['value']);

		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_2, $adresse));
		$this->assertEquals($id_a_2, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 2', $adresse['nom']['value']);
		$this->assertEquals($id_r_2, $adresse['regroupement_id']['value']);
		$this->assertEquals(42, $adresse['gestionnaire_id']['value']);

		$adresses_list = array();
		$this->assertTrue($hORMadresse->browse($adresses_list, $total, array('adresse_id', 'nom', 'gestionnaire_id'), array(array('gestionnaire_id', '=', 42))));
		$this->assertEquals(3, count($adresses_list)); // Le filtrage ne doit pas fonctionner car il passe par un champ calculé.
	}

	/**
	 * Test unitaire permettant de tester la récupération de related via un champ calculé sur le parent.
	 */
	public function testAccessToRelatedExtended()
	{
		$hORMregroupement	= ORM::getORMInstance('RegroupementExample');
		$hORMadresse		= ORM::getORMInstance('AdresseExampleExtended');

		/* Création des regroupements d'exemples. */
		$data = array('nom' => 'Regroupement 1', 'gestionnaire_id' => 41);
		$hORMregroupement->create($data, $id_r_1);
		$this->assertGreaterThan(0, $id_r_1);

		$data = array('nom' => 'Regroupement 2', 'gestionnaire_id' => 42);
		$hORMregroupement->create($data, $id_r_2);
		$this->assertGreaterThan(0, $id_r_1);

		/* Création des adresses d'exemples. */
		$data = array('nom' => 'Adresse 1');
		$this->assertTrue($hORMadresse->create($data, $id_a_1));
		$this->assertGreaterThan(0, $id_a_1);

		$data = array('nom' => 'Adresse 2');
		$this->assertTrue($hORMadresse->create($data, $id_a_2));
		$this->assertGreaterThan(0, $id_a_2);

		$data = array('nom' => 'Adresse 3');
		$this->assertTrue($hORMadresse->create($data, $id_a_3));
		$this->assertGreaterThan(0, $id_a_3);

		/* Accès au gestionnaires des adresses. */
		/* Adresse sans regroupement. */
		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_3, $adresse));
		$this->assertEquals($id_a_3, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 3', $adresse['nom']['value']);
		$this->assertEquals(NULL, $adresse['regroupement_id']['value']);
		$this->assertEquals(NULL, $adresse['gestionnaire_id']['value']);

		/* Adresses avec regroupement. */
		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_1, $adresse));
		$this->assertEquals($id_a_1, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 1', $adresse['nom']['value']);
		$this->assertEquals($id_r_1, $adresse['regroupement_id']['value']);
		$this->assertEquals(41, $adresse['gestionnaire_id']['value']);

		$adresse = array();
		$this->assertTrue($hORMadresse->read($id_a_2, $adresse));
		$this->assertEquals($id_a_2, $adresse['adresse_id']['value']);
		$this->assertEquals('Adresse 2', $adresse['nom']['value']);
		$this->assertEquals($id_r_2, $adresse['regroupement_id']['value']);
		$this->assertEquals(42, $adresse['gestionnaire_id']['value']);

		$adresses_list = array();
		$this->assertTrue($hORMadresse->browse($adresses_list, $total, array('adresse_id', 'nom', 'gestionnaire_id'), array(array('gestionnaire_id', '=', '42'))));
		$this->assertEquals(3, count($adresses_list)); // Le filtrage ne doit pas fonctionner car il passe par un champ calculé.
	}
}

