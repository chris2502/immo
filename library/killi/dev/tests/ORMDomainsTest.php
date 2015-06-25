<?php

/**
 *  @class ORMDomainsTest
 *  @Revision $Revision: 4469 $
 *
 */

class AdresseObject
{
	public $description  = 'Fake Objet adresse';
	public $table		 = 'test_adresse_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'adresse_id';

	public function __construct()
	{
		$this->adresse_id = new PrimaryFieldDefinition ();
		$this->adresse = new TextFieldDefinition ();
		$this->nro_id = new IntFieldDefinition ();
	}

}
ORM::declareObject('AdresseObject');

class AdresseObjectMethod extends Common {};

class WorkflowTokenObject
{
	public $description  = 'Fake Objet workflow token';
	public $table		 = 'test_kwt_object';
	public $primary_key  = 'workflow_token_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'workflow_token_id';

	public function __construct()
	{
		$this->workflow_token_id = new PrimaryFieldDefinition ();
		$this->node_id = new IntFieldDefinition ();
		$this->object_id = new IntFieldDefinition ();
	}

}
ORM::declareObject('WorkflowTokenObject');

class AdresseWithDomainObject
{
	public $description  = 'Fake Objet adresse with domain';
	public $table		 = 'test_adresse_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'adresse_id';

	public function setDomain()
	{
		$this->domain_with_join = array(
				'table' => array(TESTS_DATABASE . '.test_kwt_object AS kwt'),
				'join' => array('kwt.object_id=test_adresse_object.adresse_id'),
				'filter' => array(array('kwt.node_id', '=', 210)));
	}

	public function __construct()
	{
		$this->adresse_id = new PrimaryFieldDefinition ();
		$this->nro_id = new IntFieldDefinition ();
	}

}
ORM::declareObject('AdresseWithDomainObject');

class WrongAdresseWithDomainObject
{
	public $description  = 'Fake Objet adresse with domain';
	public $table		 = 'test_adresse_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'adresse_id';

	public function setDomain()
	{
		$this->domain_with_join = array(
				'table' => array(TESTS_DATABASE . '.test_kwt_object AS kwt'),
				'join' => array('kwt.object_id=test_adresse_object.adresse_id'),
				'filter' => array(array('kwt.node_id', '=', 210)));
	}

	public function __construct()
	{
		$this->adresse_id = new Many2oneFieldDefinition('AdresseObject');
		$this->nro_id = new RelatedFieldDefinition('adresse_id','nro_id');
	}

}
ORM::declareObject('WrongAdresseWithDomainObject');


class ORMDomainsTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('WorkflowTokenObject');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('WorkflowTokenObject');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORM->deleteObjectInDatabase();
	}

	public function testValidDomainUsage()
	{
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORMkwt = ORM::getORMInstance('WorkflowTokenObject');
		$hORMdom = ORM::getORMInstance('AdresseWithDomainObject', true);

		$adresses = array();

		/* Création des données d'entrées. */
		$adresse = array('adresse' => '1 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$adresse = array('adresse' => '2 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '3 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '4 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '5 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '6 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		/* Test de la bonne utilisation sans domaines. */
		$adresses_object = array();
		$hORMdom_withoutd = ORM::getORMInstance('AdresseWithDomainObject', TRUE, FALSE);
		$this->assertTrue($hORMdom_withoutd->browse($adresses_object, $total, array('adresse_id', 'nro_id')));
		$this->assertEquals(6, $total);
		$this->assertEquals(6, count($adresses_object));

		/* Test de la bonne utilisation sans domaines en POO */
		$adresses_object = array();
		$hORMdom_withoutd = ORM::getORMInstance('AdresseWithDomainObject', TRUE, FALSE);
		$this->assertTrue($hORMdom_withoutd->browse($adresses_object, $total, array('adresse_id', 'nro_id')));
		$this->assertEquals(6, $total);
		$this->assertEquals(6, count($adresses_object));

		/* Test de la bonne utilisation des domaines. */
		$adresses_object = array();
		$this->assertTrue($hORMdom->browse($adresses_object, $total, array('adresse_id', 'nro_id')));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($adresses_object));

		$this->assertArrayHasKey(2, $adresses_object);
		$this->assertArrayHasKey('adresse_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['adresse_id']);
		$this->assertEquals(2, $adresses_object[2]['adresse_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['adresse_id']);
		$this->assertEquals(FALSE, $adresses_object[2]['adresse_id']['editable']);
		$this->assertArrayHasKey('nro_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['nro_id']);
		$this->assertEquals(1, $adresses_object[2]['nro_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['nro_id']);
		$this->assertEquals(FALSE, $adresses_object[2]['nro_id']['editable']);

		$this->assertArrayHasKey(5, $adresses_object);
		$this->assertArrayHasKey('adresse_id', $adresses_object[5]);
		$this->assertArrayHasKey('value', $adresses_object[5]['adresse_id']);
		$this->assertEquals(5, $adresses_object[5]['adresse_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[5]['adresse_id']);
		$this->assertEquals(FALSE, $adresses_object[5]['adresse_id']['editable']);
		$this->assertArrayHasKey('nro_id', $adresses_object[5]);
		$this->assertArrayHasKey('value', $adresses_object[5]['nro_id']);
		$this->assertEquals(2, $adresses_object[5]['nro_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[5]['nro_id']);
		$this->assertEquals(false, $adresses_object[5]['nro_id']['editable']);
	}

	public function testValidDomainUsageWithFilter()
	{
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORMkwt = ORM::getORMInstance('WorkflowTokenObject');
		$hORMdom = ORM::getORMInstance('AdresseWithDomainObject', true);

		$adresses = array();

		/* Création des données d'entrées. */
		$adresse = array('adresse' => '1 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$adresse = array('adresse' => '2 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '3 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '4 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '5 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '6 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		/* Test de la bonne utilisation des domaines. */
		$adresses_object = array();
		$this->assertTrue($hORMdom->browse($adresses_object, $total, array('adresse_id', 'nro_id'), array(array('nro_id', '=', 1))));
		$this->assertEquals(1, $total);
		$this->assertEquals(1, count($adresses_object));

		$this->assertArrayHasKey(2, $adresses_object);
		$this->assertArrayHasKey('adresse_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['adresse_id']);
		$this->assertEquals(2, $adresses_object[2]['adresse_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['adresse_id']);
		$this->assertEquals(FALSE, $adresses_object[2]['adresse_id']['editable']);
		$this->assertArrayHasKey('nro_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['nro_id']);
		$this->assertEquals(1, $adresses_object[2]['nro_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['nro_id']);
		$this->assertEquals(FALSE, $adresses_object[2]['nro_id']['editable']);
	}

	public function testValidDomainUsage2()
	{
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORMkwt = ORM::getORMInstance('WorkflowTokenObject');
		$hORMdom = ORM::getORMInstance('WrongAdresseWithDomainObject', true);

		$adresses = array();

		/* Création des données d'entrées. */
		$adresse = array('adresse' => '1 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$adresse = array('adresse' => '2 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '3 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '4 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '5 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '6 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		/* Test de la bonne utilisation des domaines. */
		$adresses_object = array();
		$this->assertTrue($hORMdom->browse($adresses_object, $total, array('adresse_id', 'nro_id')));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($adresses_object));

		$this->assertArrayHasKey(2, $adresses_object);
		$this->assertArrayHasKey('adresse_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['adresse_id']);
		$this->assertEquals(2, $adresses_object[2]['adresse_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['adresse_id']);
		$this->assertEquals(false, $adresses_object[2]['adresse_id']['editable']);
		$this->assertArrayHasKey('nro_id', $adresses_object[2]);
		$this->assertArrayHasKey('value', $adresses_object[2]['nro_id']);
		$this->assertEquals(1, $adresses_object[2]['nro_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[2]['nro_id']);
		$this->assertEquals(false, $adresses_object[2]['nro_id']['editable']);

		$this->assertArrayHasKey(5, $adresses_object);
		$this->assertArrayHasKey('adresse_id', $adresses_object[5]);
		$this->assertArrayHasKey('value', $adresses_object[5]['adresse_id']);
		$this->assertEquals(5, $adresses_object[5]['adresse_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[5]['adresse_id']);
		$this->assertEquals(false, $adresses_object[5]['adresse_id']['editable']);
		$this->assertArrayHasKey('nro_id', $adresses_object[5]);
		$this->assertArrayHasKey('value', $adresses_object[5]['nro_id']);
		$this->assertEquals(2, $adresses_object[5]['nro_id']['value']);
		$this->assertArrayHasKey('editable', $adresses_object[5]['nro_id']);
		$this->assertEquals(false, $adresses_object[5]['nro_id']['editable']);
	}

	/**
	 * Test de filtrage avec related.
	 */
	public function testValidDomainUsageWithFilter2()
	{
		$hORM = ORM::getORMInstance('AdresseObject');
		$hORMkwt = ORM::getORMInstance('WorkflowTokenObject');
		$hORMdom = ORM::getORMInstance('WrongAdresseWithDomainObject', true);

		$adresses = array();

		/* Création des données d'entrées. */
		$adresse = array('adresse' => '1 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$adresse = array('adresse' => '2 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '3 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '4 rue machin', 'nro_id' => 1);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '5 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;

		$this->assertTrue($hORMkwt->create(array('node_id' => 210, 'object_id' => $id), $kwt_id));

		$adresse = array('adresse' => '6 rue machin', 'nro_id' => 2);
		$this->assertTrue($hORM->create($adresse, $id));
		$adresses[$id] = $adresse;
		$this->assertTrue($hORMkwt->create(array('node_id' => 250, 'object_id' => $id), $kwt_id));

		/* Test de la bonne utilisation des domaines. */
		$adresses_object = array();
		$this->assertTrue($hORMdom->browse($adresses_object, $total, array('adresse_id', 'nro_id'), array(array('nro_id', '=', 1))));

	}

}
