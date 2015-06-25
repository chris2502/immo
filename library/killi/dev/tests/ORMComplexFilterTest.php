<?php

/**
 *  @class ORMComplexFilterTest
 *  @Revision $Revision: 3225 $
 *
 */

/*****************************/
/*                           */
/* Objet simple Distribution */
/*                           */
/*****************************/
class ObjectDistribution
{
	public $description  = 'Objet Distribution';
	public $table		 = 'test_distrib_object';
	public $primary_key  = 'distrib_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'code';

	function __construct()
	{
		$this->distrib_id = new PrimaryFieldDefinition ();
		$this->code = new IntFieldDefinition ();
		$this->status = new WorkflowStatusFieldDefinition('test_workflow','distrib_id');
	}
}
class ObjectDistributionMethod extends Common {}
ORM::declareObject('objectdistribution');

/*********************/
/*                   */
/* Objet simple Voie */
/*                   */
/*********************/
class ObjectVoie
{
	public $description  = 'Objet Voie';
	public $table		 = 'test_voie_object';
	public $primary_key  = 'voie_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'nom';

	function __construct()
	{
		$this->voie_id = new PrimaryFieldDefinition ();
		$this->nom = new TextFieldDefinition ();
		$this->distrib_id = new Many2oneFieldDefinition('ObjectDistribution');
		$this->code_postal = new RelatedFieldDefinition('distrib_id','code');
		$this->status = new RelatedFieldDefinition('distrib_id','status');
	}
}

class ObjectVoieMethod extends Common {}
ORM::declareObject('objectvoie');

/*******************************/
/*                             */
/* Object representant adresse */
/*                             */
/*******************************/
class ObjectAdresse
{
	public $description  = 'Objet Adresse';
	public $table		 = 'test_adresse_object2';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'nom';

	function __construct()
	{
		$this->adresse_id = new PrimaryFieldDefinition ();
		$this->nom = new TextFieldDefinition ();
		$this->voie_id = new Many2oneFieldDefinition('ObjectVoie');
		$this->distrib_id = new RelatedFieldDefinition('voie_id','distrib_id');
		$this->code_prefix = new RelatedFieldDefinition('voie_id','code_postal');
		$this->status = new RelatedFieldDefinition('voie_id','status');
	}
}

class ObjectAdresseMethod extends Common {}
ORM::declareObject('objectadresse');

/*****************************/
/*                           */
/* Object liant avec adresse */
/*                           */
/*****************************/
class ObjectAdresseInfo
{
	public $description  = 'Objet AdresseInfo';
	public $table		 = 'test_adresseinfo_object';
	public $primary_key  = 'adresse_info_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'adresse_info';

	function __construct()
	{
		$this->adresse_info_id = new PrimaryFieldDefinition ();
		$this->adresse_info = new TextFieldDefinition ();
		$this->adresse_id = new Many2oneFieldDefinition('ObjectAdresse');
		$this->code_dept = new RelatedFieldDefinition('adresse_id','code_prefix');
	}
}

class ObjectAdresseInfoMethod extends Common {}
ORM::declareObject('objectadresseinfo');


/******************************/
/*                            */
/* Object extension d'adresse */
/*                            */
/******************************/
class ObjectExtendsAdresse
{
	public $description  = 'Objet Extends Adresse';
	public $table		 = 'test_extadresse_object';
	public $primary_key  = 'adresse_id';
	public $database	 = TESTS_DATABASE;
	public $reference    = 'nom';

	function __construct()
	{
		$this->adresse_id = new ExtendsFieldDefinition('ObjectAdresse');
		$this->value = new IntFieldDefinition ();
	}
}

class ObjectExtendsAdresseMethod extends Common {}
ORM::declareObject('objectextendsadresse');


class ORMComplexFilterTest extends Killi_TestCase
{
	public function setUp()
	{
		parent::setUp();
		$hORM = ORM::getORMInstance('objectdistribution');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('objectvoie');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('objectadresse');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('objectadresseinfo');
		$hORM->createObjectInDatabase();
		$hORM = ORM::getORMInstance('objectextendsadresse');
		$hORM->createObjectInDatabase();
	}

	public function tearDown()
	{
		parent::tearDown();
		$hORM = ORM::getORMInstance('objectextendsadresse');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('objectadresseinfo');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('objectadresse');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('objectvoie');
		$hORM->deleteObjectInDatabase();
		$hORM = ORM::getORMInstance('objectdistribution');
		$hORM->deleteObjectInDatabase();
	}

	/**
	 *  Test unitaire permettant de tester le filtrage sur un attribut related via un related.
	 */
	public function testValidRelatedViaRelatedFilter()
	{
		$hORMdistribution = ORM::getORMInstance('objectdistribution');
		$hORMvoie         = ORM::getORMInstance('objectvoie');
		$hORMadresse      = ORM::getORMInstance('objectadresse');
		$hORMadresseinfo  = ORM::getORMInstance('objectadresseinfo');

		$hORMdistribution->create(array('code' => 75), $distrib_id_1);
		$this->assertGreaterThan(0, $distrib_id_1);
		$hORMdistribution->create(array('code' => 69), $distrib_id_2);
		$this->assertGreaterThan(0, $distrib_id_2);
		$hORMdistribution->create(array('code' => 39), $distrib_id_3);
		$this->assertGreaterThan(0, $distrib_id_3);

		$hORMvoie->create(array('nom' => 'Voie Lons', 'distrib_id' => $distrib_id_3), $voie_id_1);
		$this->assertGreaterThan(0, $voie_id_1);
		$hORMvoie->create(array('nom' => 'Voie Lyon', 'distrib_id' => $distrib_id_2), $voie_id_2);
		$this->assertGreaterThan(0, $voie_id_2);
		$hORMvoie->create(array('nom' => 'Voie Paris', 'distrib_id' => $distrib_id_1), $voie_id_3);
		$this->assertGreaterThan(0, $voie_id_3);

		$hORMadresse->create(array('nom' => 'Adresse sur Lyon', 'voie_id' => $voie_id_2), $adresse_id_1);
		$this->assertGreaterThan(0, $adresse_id_1);
		$hORMadresse->create(array('nom' => 'Adresse sur Paris', 'voie_id' => $voie_id_3), $adresse_id_2);
		$this->assertGreaterThan(0, $adresse_id_2);
		$hORMadresse->create(array('nom' => 'Adresse sur Lons', 'voie_id' => $voie_id_1), $adresse_id_3);
		$this->assertGreaterThan(0, $adresse_id_3);

		$hORMadresseinfo->create(array('adresse_info' => 'Info 1', 'adresse_id' => $adresse_id_1), $adresse_info_id_1);
		$this->assertGreaterThan(0, $adresse_info_id_1);
		$hORMadresseinfo->create(array('adresse_info' => 'Info 2', 'adresse_id' => $adresse_id_2), $adresse_info_id_2);
		$this->assertGreaterThan(0, $adresse_info_id_2);
		$hORMadresseinfo->create(array('adresse_info' => 'Info 3', 'adresse_id' => $adresse_id_3), $adresse_info_id_3);
		$this->assertGreaterThan(0, $adresse_info_id_3);

		/* Lecture sans filtrage. */
		$adresse_list = array();
		$this->assertTrue($hORMadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'voie_id', 'distrib_id', 'code_prefix')));
		$this->assertEquals(3, count($adresse_list));

		/* Lecture avec filtrage sur related */
		$adresse_list = array();
		$this->assertTrue($hORMadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'voie_id', 'distrib_id', 'code_prefix'), array(array('distrib_id', '=', $distrib_id_1))));
		$this->assertEquals(1, count($adresse_list));
		$this->assertEquals($adresse_id_2, $adresse_list[$adresse_id_2]['adresse_id']['value']);
		$this->assertEquals('Adresse sur Paris', $adresse_list[$adresse_id_2]['nom']['value']);
		$this->assertEquals($voie_id_3, $adresse_list[$adresse_id_2]['voie_id']['value']);
		$this->assertEquals($distrib_id_1, $adresse_list[$adresse_id_2]['distrib_id']['value']);
		$this->assertEquals(75, $adresse_list[$adresse_id_2]['code_prefix']['value']);

		/* Lecture avec filtrage sur related de related */
		$adresse_list = array();
		$this->assertTrue($hORMadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'voie_id', 'distrib_id', 'code_prefix'), array(array('code_prefix', '=', 69))));
		$this->assertEquals(1, count($adresse_list));
		$this->assertEquals($adresse_id_1, $adresse_list[$adresse_id_1]['adresse_id']['value']);
		$this->assertEquals('Adresse sur Lyon', $adresse_list[$adresse_id_1]['nom']['value']);
		$this->assertEquals($voie_id_2, $adresse_list[$adresse_id_1]['voie_id']['value']);
		$this->assertEquals($distrib_id_2, $adresse_list[$adresse_id_1]['distrib_id']['value']);
		$this->assertEquals(69, $adresse_list[$adresse_id_1]['code_prefix']['value']);

		/* Lecture avec filtrage sur related de related de related */
		$adresse_info_list = array();
		$this->assertTrue($hORMadresseinfo->browse($adresse_info_list, $num, array('adresse_id', 'adresse_info', 'code_dept'), array(array('code_dept', '=', 75))));
		$this->assertEquals(1, count($adresse_info_list));
		$this->assertEquals($adresse_info_id_2, $adresse_info_list[$adresse_info_id_2]['adresse_info_id']['value']);
		$this->assertEquals($adresse_id_2, $adresse_info_list[$adresse_info_id_2]['adresse_id']['value']);
		$this->assertEquals(75, $adresse_info_list[$adresse_info_id_2]['code_dept']['value']);
		
		// initialisation d'un workflow de test
		{
			/* Création d'un workflow de test. */
			$wfMethod = new WorkflowMethod();
			
			$wfData = array('nom' => 'Workflow de test', 'workflow_name' => 'test_workflow');
			$this->assertTrue($wfMethod->create($wfData, $wfId));

			/* Ajout des noeuds du workflow. */
			$nodeMethod = new NodeMethod();
			
			$nodeData = array('etat' => 'Point de départ', 'node_name' => 'start_point', 'commande' => null, 'object' => 'distribution',
				'interface' => 'dummy', 'workflow_id' => $wfId, 'type_id' => 1, 'ordre' => 1);
			$this->assertTrue($nodeMethod->create($nodeData, $nodeId1));

			/* Affectation du noeud à l'objet. */
			$kwtMethod = new WorkflowTokenMethod();
			
			$kwtData = array('node_id' => $nodeId1, 'id' => $distrib_id_3, 'adresse_id' => $adresse_id_3);
			$this->assertTrue($kwtMethod->create($kwtData, $idKwt1));
		}
		
		/* Lecture du statut depuis distribution (lecture direct) */
		$distribution_list = array();
		$this->assertTrue($hORMdistribution->browse($distribution_list, $num, array('distrib_id'), array(array('status', '=', $nodeId1))));
		$this->assertEquals(1, count($distribution_list));
		
		/* Lecture du statut depuis voie (lecture via related) */
		$voie_list = array();
		$this->assertTrue($hORMvoie->browse($voie_list, $num, array('voie_id'), array(array('status', '=', $nodeId1))));
		$this->assertEquals(1, count($voie_list));
		
		/* Lecture du statut depuis adresse (lecture via related de related) */
		$adresse_list = array();
		$this->assertTrue($hORMadresse->browse($adresse_list, $num, array('adresse_id'), array(array('status', '=', $nodeId1))));
		$this->assertEquals(1, count($adresse_list));
	}

	/**
	 *  Test unitaire permettant de tester le filtrage sur un attribut related via un extends.
	 */
	public function testValidRelatedViaExtendsFilter()
	{
		$hORMdistribution = ORM::getORMInstance('objectdistribution');
		$hORMvoie         = ORM::getORMInstance('objectvoie');
		$hORMadresse      = ORM::getORMInstance('objectadresse');
		$hORMextadresse   = ORM::getORMInstance('objectextendsadresse');

		$hORMdistribution->create(array('code' => 75), $distrib_id_1);
		$this->assertGreaterThan(0, $distrib_id_1);
		$hORMdistribution->create(array('code' => 69), $distrib_id_2);
		$this->assertGreaterThan(0, $distrib_id_2);
		$hORMdistribution->create(array('code' => 39), $distrib_id_3);
		$this->assertGreaterThan(0, $distrib_id_3);

		$hORMvoie->create(array('nom' => 'Voie Lons', 'distrib_id' => $distrib_id_3), $voie_id_1);
		$this->assertGreaterThan(0, $voie_id_1);
		$hORMvoie->create(array('nom' => 'Voie Lyon', 'distrib_id' => $distrib_id_2), $voie_id_2);
		$this->assertGreaterThan(0, $voie_id_2);
		$hORMvoie->create(array('nom' => 'Voie Paris', 'distrib_id' => $distrib_id_1), $voie_id_3);
		$this->assertGreaterThan(0, $voie_id_3);

		$hORMextadresse->create(array('nom' => 'Adresse sur Lyon', 'voie_id' => $voie_id_2, 'value' => 11), $adresse_id_1);
		$this->assertGreaterThan(0, $adresse_id_1);
		$hORMextadresse->create(array('nom' => 'Adresse sur Paris', 'voie_id' => $voie_id_3, 'value' => 22), $adresse_id_2);
		$this->assertGreaterThan(0, $adresse_id_2);
		$hORMextadresse->create(array('nom' => 'Adresse sur Lons', 'voie_id' => $voie_id_1, 'value' => 33), $adresse_id_3);
		$this->assertGreaterThan(0, $adresse_id_3);

		/* Lecture sans filtrage. */
		$adresse_list = array();
		$this->assertTrue($hORMextadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'value', 'voie_id', 'distrib_id', 'code_prefix')));
		$this->assertEquals(3, count($adresse_list));

		/* Lecture avec filtrage sur related */
		$adresse_list = array();
		$this->assertTrue($hORMextadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'value', 'voie_id', 'distrib_id', 'code_prefix'), array(array('distrib_id', '=', $distrib_id_1))));
		$this->assertEquals(1, count($adresse_list));
		$this->assertEquals($adresse_id_2, $adresse_list[$adresse_id_2]['adresse_id']['value']);
		$this->assertEquals('Adresse sur Paris', $adresse_list[$adresse_id_2]['nom']['value']);
		$this->assertEquals(22, $adresse_list[$adresse_id_2]['value']['value']);
		$this->assertEquals($voie_id_3, $adresse_list[$adresse_id_2]['voie_id']['value']);
		$this->assertEquals($distrib_id_1, $adresse_list[$adresse_id_2]['distrib_id']['value']);
		$this->assertEquals(75, $adresse_list[$adresse_id_2]['code_prefix']['value']);

		/* Lecture avec filtrage sur related de related */
		$adresse_list = array();
		$this->assertTrue($hORMextadresse->browse($adresse_list, $num, array('adresse_id', 'nom', 'value', 'voie_id', 'distrib_id', 'code_prefix'), array(array('code_prefix', '=', 69))));
		$this->assertEquals(1, count($adresse_list));
		$this->assertEquals($adresse_id_1, $adresse_list[$adresse_id_1]['adresse_id']['value']);
		$this->assertEquals('Adresse sur Lyon', $adresse_list[$adresse_id_1]['nom']['value']);
		$this->assertEquals(11, $adresse_list[$adresse_id_1]['value']['value']);
		$this->assertEquals($voie_id_2, $adresse_list[$adresse_id_1]['voie_id']['value']);
		$this->assertEquals($distrib_id_2, $adresse_list[$adresse_id_1]['distrib_id']['value']);
		$this->assertEquals(69, $adresse_list[$adresse_id_1]['code_prefix']['value']);

		$adresse_list = array();
		$this->assertTrue($hORMextadresse->browse($adresse_list, $num, array('code_prefix'), array(array('code_prefix', '=', 69))));
		$this->assertEquals(1, count($adresse_list));
		$this->assertEquals(69, $adresse_list[$adresse_id_1]['code_prefix']['value']);
	}
}
