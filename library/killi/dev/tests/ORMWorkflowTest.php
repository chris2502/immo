<?php

/**
 *  @class ORMWorkflowTest
 *  @Revision $Revision: 4515 $
 *
 */

class ORMWorkflowTest extends Killi_TestCase
{
	public function setUp()
	{
		$_SESSION['_USER']['profil_id']['value'] = array(ADMIN_PROFIL_ID);
		$_SESSION['_USER']['login']['value'] = 'killi_test';
		parent::setUp();

		$hORM = ORM::getORMInstance('WorkflowStatusObject');
		$hORM->createObjectInDatabase();

		ORM::setWorkflowAttributes('WorkflowStatusObject');
	}

	public function tearDown()
	{
		$hORM = ORM::getORMInstance('WorkflowStatusObject');
		$hORM->deleteObjectInDatabase();
		parent::tearDown();
	}
	
	public function testValidWorkflowFilter()
	{		
		/* Création d'un workflow de test. */
		$wfMethod = new WorkflowMethod();

		$wfData = array('nom' => 'Workflow de test', 'workflow_name' => 'test_workflow');
		$this->assertTrue($wfMethod->create($wfData, $wfId));

		/* Ajout de type de noeud. */
		$ntMethod = new NodeTypeMethod();

		$ntData = array('nom' => 'test');
		$this->assertTrue($ntMethod->create($ntData, $ntId));

		/* Ajout d'un groupe de noeud. */
		$ngMethod = new NodeGroupMethod();

		$ngData = array('name' => 'Noeud de test', 'color' => 'lightblue');
		$this->assertTrue($ngMethod->create($ngData, $ngId));

		/* Ajout des noeuds du workflow. */
		$nodeMethod = new NodeMethod();

		$nodeData = array('etat' => 'Point de départ', 'node_name' => 'start_point', 'commande' => null, 'object' => 'WorkflowStatusObject',
				          'interface' => 'dummy', 'workflow_id' => $wfId, 'type_id' => 3,
				          'killi_workflow_node_group_id' => $ngId, 'ordre' => 1);
		$this->assertTrue($nodeMethod->create($nodeData, $nodeId1));

		$nodeData = array('etat' => 'Etat intermediaire', 'node_name' => 'IntermediateState', 'commande' => null, 'object' => 'WorkflowStatusObject',
				'interface' => 'dummy', 'workflow_id' => $wfId, 'type_id' => 2,
				'killi_workflow_node_group_id' => $ngId, 'ordre' => 2);
		$this->assertTrue($nodeMethod->create($nodeData, $nodeId2));

		$nodeData = array('etat' => 'Point final', 'node_name' => 'end_point', 'commande' => null, 'object' => 'WorkflowStatusObject',
				'interface' => 'dummy', 'workflow_id' => $wfId, 'type_id' => 4,
				'killi_workflow_node_group_id' => $ngId, 'ordre' => 3);
		$this->assertTrue($nodeMethod->create($nodeData, $nodeId3));

		/* Ajout d'une qualification au noeud 1. */
		$nqMethod = new NodeQualificationMethod();

		$nqData = array('nom' => 'Qualification de test', 'node_id' => $nodeId1);
		$this->assertTrue($nqMethod->create($nqData, $nqId));

		/* Création des liens dans les noeuds. */
		$nlMethod = new NodeLinkMethod();

		$nlData = array('label' => 'Première transition', 'input_node' => $nodeId1, 'output_node' => $nodeId2, 'traitement_echec' => true,
				        'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($nlMethod->create($nlData, $nlId1));

		$nlData = array('label' => 'Dernière transition', 'input_node' => $nodeId2, 'output_node' => $nodeId3, 'traitement_echec' => true,
				'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($nlMethod->create($nlData, $nlId2));

		/* boucle */
		$nlData = array('label' => 'Retour au départ', 'input_node' => $nodeId3, 'output_node' => $nodeId1, 'traitement_echec' => true,
				'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($nlMethod->create($nlData, $nlId3));

		/* Création d'objet dans ce workflow. */
		$wsoMethod = new WorkflowStatusObjectMethod();
		$data = array();
		$this->assertTrue($wsoMethod->create($data, $id1));
		$this->assertTrue($wsoMethod->create($data, $id2));
		$this->assertTrue($wsoMethod->create($data, $id3));

		/* Affectation du noeud à l'objet. */
		$kwtMethod = new WorkflowTokenMethod();

		$kwtData = array('node_id' => $nodeId1, 'id' => $id1, 'adresse_id' => $id1);
		$this->assertTrue($kwtMethod->create($kwtData, $idKwt1));
		
		// on attend 1 seconde pour que la date du token soit différente
		sleep(1);
		
		$kwtData = array('node_id' => $nodeId2, 'id' => $id2, 'adresse_id' => $id2);
		$this->assertTrue($kwtMethod->create($kwtData, $idKwt2));

		// on attend 1 seconde pour que la date du token soit différente
		sleep(1);
		
		$kwtData = array('node_id' => $nodeId1, 'id' => $id3, 'adresse_id' => $id3);
		$this->assertTrue($kwtMethod->create($kwtData, $idKwt31));
		$kwtData = array('node_id' => $nodeId2, 'id' => $id3, 'adresse_id' => $id3);
		$this->assertTrue($kwtMethod->create($kwtData, $idKwt32));
		$kwtData = array('node_id' => $nodeId3, 'id' => $id3, 'adresse_id' => $id3);
		$this->assertTrue($kwtMethod->create($kwtData, $idKwt33));

		/* Lecture de l'objet et de son status de workflow. */
		$hORM = ORM::getORMInstance('workflowstatusobject', true);
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, NULL, array(array('status', '=', $nodeId2))));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($objects));
		$this->assertNotEmpty($objects[$id2]);

		$this->assertTrue($hORM->browse($objects, $total, NULL, array(array('status', 'in', array($nodeId1, $nodeId3)))));
		$this->assertEquals(2, $total);
		$this->assertEquals(2, count($objects));
		$this->assertNotEmpty($objects[$id3]);

		$this->assertTrue($hORM->read($id3, $object3, array('status')));
		$this->assertEquals(3, count($object3['status']['workflow_node_id']));
		$this->assertContains($nodeId1, $object3['status']['workflow_node_id']);
		$this->assertContains($nodeId2, $object3['status']['workflow_node_id']);
		$this->assertContains($nodeId3, $object3['status']['workflow_node_id']);

		/* Lecture de l'ensemble des objets sans spécification d'arguments (Suite à un bug). */
		$this->assertTrue($hORM->browse($objects, $total));

		/* Lecture de l'ensemble des objets en les triants par date de token */
		$objects=array();
		$this->assertTrue($hORM->search($objects, $total, array(), array('status DESC')));
		
		$this->assertEquals($id3, $objects[0]);
		$this->assertEquals($id2, $objects[1]);
		$this->assertEquals($id1, $objects[2]);
		
		/* Lecture de l'ensemble des objets en les triants par date de token */
		$objects=array();
		$this->assertTrue($hORM->search($objects, $total, array(), array('status ASC')));
		
		$this->assertEquals($id1, $objects[0]);
		$this->assertEquals($id2, $objects[1]);
		$this->assertEquals($id3, $objects[2]);
		
		/* Déplacement natif du token 1 du noeud 1 au noeud 2 */
		Security::crypt($id1, $crypted_id1);
		Security::crypt($nqId, $crypted_nqId);

		$_GET['origin_node']					 = $nodeId1;
		$_GET['destination_node']				 = $nodeId2;
		$_POST['listing_selection']				 = array($id1);
		$_POST['crypt/listing_selection']		 = array($crypted_id1);
		$_POST['comment/'.$crypted_id1]			 = null;
		$_POST['qualification_id/'.$crypted_id1] = null;

		$this->assertTrue($wfMethod->move_token());

		// lecture du status de l'objet
		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, array(), array(array('status', '=', $nodeId2))));
		$this->assertNotEmpty($objects[$id1]);

		// lecture du token
		$hORMToken = ORM::getORMInstance('workflowtoken', true);

		$objects = array();
		$this->assertTrue($hORMToken->browse($objects, $total, array('id','commentaire','qualification_id'), array(array('id', '=', $id1), array('node_id', '=', $nodeId2))));
		$this->assertEquals(1, count($objects));
		$this->assertEquals(1, $total);
		$this->assertEquals($id1, $objects[key($objects)]['id']['value']);
		$this->assertEquals(null, $objects[key($objects)]['commentaire']['value']);
		$this->assertEquals(null, $objects[key($objects)]['qualification_id']['value']);

		/* Déplacement natif du token 1 et 2 du noeud 2 au noeud 3 */
		Security::crypt($id2, $crypted_id2);

		$_GET['origin_node']					 = $nodeId2;
		$_GET['destination_node']				 = $nodeId3;
		$_POST['listing_selection']				 = array($id1, $id2);
		$_POST['crypt/listing_selection']		 = array($crypted_id1, $crypted_id2);
		$_POST['comment/'.$crypted_id1]			 = 'Commentaire token '.$id1;
		$_POST['qualification_id/'.$crypted_id1] = $nqId;
		$_POST['comment/'.$crypted_id2]			 = 'Commentaire token '.$id2;
		$_POST['qualification_id/'.$crypted_id2] = null;

		$this->assertTrue($wfMethod->move_token());

		$objects = array();
		$this->assertTrue($hORM->browse($objects, $total, null, array(array('status', '=', $nodeId3))));
		$this->assertEquals(3, $total);
		$this->assertEquals(3, count($objects));
		$this->assertNotEmpty($objects[$id1]);
		$this->assertNotEmpty($objects[$id2]);
		$this->assertNotEmpty($objects[$id3]);

		// lecture du token
		$objects = array();
		$this->assertTrue($hORMToken->browse($objects, $total, array('id','commentaire','qualification_id'), array(array('id', 'in', array($id1, $id2)), array('node_id', '=', $nodeId3)),array('id asc')));
		$this->assertEquals(2, count($objects));
		$this->assertEquals(2, $total);

		$this->assertEquals($id1, $objects[key($objects)]['id']['value']);
		$this->assertEquals('Commentaire token '.$id1, $objects[key($objects)]['commentaire']['value']);
		$this->assertEquals($nqId, $objects[key($objects)]['qualification_id']['value']);

		next($objects);

		$this->assertEquals($id2, $objects[key($objects)]['id']['value']);
		$this->assertEquals('Commentaire token '.$id2, $objects[key($objects)]['commentaire']['value']);
		$this->assertEquals(null, $objects[key($objects)]['qualification_id']['value']);

		reset($objects);

		$_GET['workflow_id'] = $wfId;

		$this->assertTrue($wfMethod->displaySchema());
	}
	
	public function testStatusReading()
	{
		$workflow_map = $this->_buildWorkflow();		
		$hORM = ORM::getORMInstance('WorkflowStatusObject');
		
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);						
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);	
	}
	
	public function testDomainReading()
	{
		$workflow_map = $this->_buildWorkflow();		
		$hORM = ORM::getORMInstance('WorkflowStatusObject');
					
		$hObj = ORM::getObjectInstance('WorkflowStatusObject');
		$hObj->object_domain = array(array('status', '=',  $workflow_map->node_2_id));
		
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(2, count($object_list));
		$this->assertArrayNotHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$hObj->object_domain = array(array('status', '=',  $workflow_map->node_3_id));
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(1, count($object_list));
		$this->assertArrayNotHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayNotHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$hObj->object_domain = array(array('status', 'IN',  array($workflow_map->node_1_id, $workflow_map->node_3_id)));
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(2, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayNotHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
	}
	
	public function testDomainOnNameReading()
	{
		$workflow_map = $this->_buildWorkflow();		
		$hORM = ORM::getORMInstance('WorkflowStatusObject');						
		$hObj = ORM::getObjectInstance('WorkflowStatusObject');
		$hObj->object_domain = array(array('status', '=',  $workflow_map->node_2_id));
		
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(2, count($object_list));
		$this->assertArrayNotHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$hObj->object_domain = array(array('status', '=',  $workflow_map->node_3_id));
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(1, count($object_list));
		$this->assertArrayNotHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayNotHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$hObj->object_domain = array(array('status', 'IN',  array($workflow_map->node_1_id, $workflow_map->node_3_id)));
		$object_list = array();
		$hORM->browse($object_list, $num_rows, array('status'));
		$this->assertEquals(2, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayNotHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		
		$object_list = array();
		$object_id_list = array($workflow_map->object_1_id, $workflow_map->object_2_id, $workflow_map->object_3_id);
		$hORM->read($object_id_list, $object_list, array('status'));
		$this->assertEquals(3, count($object_list));
		$this->assertArrayHasKey($workflow_map->object_1_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_2_id, $object_list);
		$this->assertArrayHasKey($workflow_map->object_3_id, $object_list);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_1_id]['status']['value']);
		$this->assertEquals('Etat intermediaire ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_2_id]['status']['value']);
		$this->assertEquals('Point de départ ('.date('d/m/Y').' j+0) & Etat intermediaire ('.date('d/m/Y')
							.' j+0) & Point final ('.date('d/m/Y').' j+0)', $object_list[$workflow_map->object_3_id]['status']['value']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_1_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_2_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_1_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_2_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains($workflow_map->node_3_id, $object_list[$workflow_map->object_3_id]['status']['workflow_node_id']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_1_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_2_id]['status']['workflow_node_name']);
		$this->assertContains('start_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('IntermediateState', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
		$this->assertContains('end_point', $object_list[$workflow_map->object_3_id]['status']['workflow_node_name']);
	}
	
	protected function _buildWorkflow()
	{		
		$workflow_map = (object) array();
		
		$workflow_map->workflow_method = new WorkflowMethod();
		$workflow_map->node_type_method = new NodeTypeMethod();
		$workflow_map->node_group_method = new NodeGroupMethod();
		$workflow_map->node_method = new NodeMethod();
		$workflow_map->node_qualification_method = new NodeQualificationMethod();
		$workflow_map->node_link_method = new NodeLinkMethod();
		$workflow_map->object_method = new WorkflowStatusObjectMethod();
		$workflow_map->token_method = new WorkflowTokenMethod();		
		
		$wfData = array('nom' => 'Workflow de test', 'workflow_name' => 'test_workflow');
		$this->assertTrue($workflow_map->workflow_method->create($wfData, $workflow_map->workflow_id));
		
		$ntData = array('nom' => 'test');
		$this->assertTrue($workflow_map->node_type_method->create($ntData, $workflow_map->node_type_id));

		$ngData = array('name' => 'Noeud de test', 'color' => 'lightblue');
		$this->assertTrue($workflow_map->node_group_method->create($ngData, $workflow_map->node_group_id));

		/* Ajout des noeuds du workflow. */
		$nodeData = array('etat' => 'Point de départ', 'node_name' => 'start_point', 'commande' => null, 'object' => 'WorkflowStatusObject',
				          'interface' => 'dummy', 'workflow_id' => $workflow_map->workflow_id, 'type_id' => 3,
				          'killi_workflow_node_group_id' => $workflow_map->node_group_id, 'ordre' => 1);
		$this->assertTrue($workflow_map->node_method->create($nodeData, $workflow_map->node_1_id));

		$nodeData = array('etat' => 'Etat intermediaire', 'node_name' => 'IntermediateState', 'commande' => null, 'object' => 'WorkflowStatusObject',
				'interface' => 'dummy', 'workflow_id' => $workflow_map->workflow_id, 'type_id' => 2,
				'killi_workflow_node_group_id' => $workflow_map->node_group_id, 'ordre' => 2);
		$this->assertTrue($workflow_map->node_method->create($nodeData, $workflow_map->node_2_id));

		$nodeData = array('etat' => 'Point final', 'node_name' => 'end_point', 'commande' => null, 'object' => 'WorkflowStatusObject',
				'interface' => 'dummy', 'workflow_id' => $workflow_map->workflow_id, 'type_id' => 4,
				'killi_workflow_node_group_id' => $workflow_map->node_group_id, 'ordre' => 3);
		$this->assertTrue($workflow_map->node_method->create($nodeData, $workflow_map->node_3_id));

		/* Ajout d'une qualification au noeud 1. */
		$nqData = array('nom' => 'Qualification de test', 'node_id' => $workflow_map->node_1_id);
		$this->assertTrue($workflow_map->node_qualification_method->create($nqData, $workflow_map->node_qualification_1_id));
		
		/* Création des liens dans les noeuds. */
		$nlData = array('label' => 'Première transition', 'input_node' => $workflow_map->node_1_id, 'output_node' => $workflow_map->node_2_id, 'traitement_echec' => true,
				        'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($workflow_map->node_link_method->create($nlData, $workflow_map->node_link_1_id));

		$nlData = array('label' => 'Dernière transition', 'input_node' => $workflow_map->node_2_id, 'output_node' => $workflow_map->node_3_id, 'traitement_echec' => true,
				'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($workflow_map->node_link_method->create($nlData, $workflow_map->node_link_2_id));

		/* boucle */
		$nlData = array('label' => 'Retour au départ', 'input_node' => $workflow_map->node_3_id, 'output_node' => $workflow_map->node_1_id, 'traitement_echec' => true,
				'display_average' => true, 'deplacement_manuel' => true);
		$this->assertTrue($workflow_map->node_link_method->create($nlData, $workflow_map->node_link_3_id));

		/* Création d'objet dans ce workflow. */		
		$data = array();
		$this->assertTrue($workflow_map->object_method->create($data, $workflow_map->object_1_id));
		$this->assertTrue($workflow_map->object_method->create($data, $workflow_map->object_2_id));
		$this->assertTrue($workflow_map->object_method->create($data, $workflow_map->object_3_id));


		$workflow_map->token_id_list = array();
		$workflow_map->token_id_list[1] = array();
		$workflow_map->token_id_list[2] = array();
		$workflow_map->token_id_list[3] = array();
		
		// L'objet 1 est à la node 1.		
		$kwtData = array('node_id' => $workflow_map->node_1_id, 'id' => $workflow_map->object_1_id, 'adresse_id' => $workflow_map->object_1_id);
		$this->assertTrue($workflow_map->token_method->create($kwtData, $id));
		$workflow_map->token_id_list[1][] = $id;
		// L'objet 2 est à la node 2.
		$kwtData = array('node_id' => $workflow_map->node_2_id, 'id' => $workflow_map->object_2_id, 'adresse_id' => $workflow_map->object_2_id);
		$this->assertTrue($workflow_map->token_method->create($kwtData, $id));
		$workflow_map->token_id_list[2][] = $id;

		// L'objet 3 a déjà parcouru les 3 nodes.
		$kwtData = array('node_id' => $workflow_map->node_1_id, 'id' => $workflow_map->object_3_id, 'adresse_id' => $workflow_map->object_3_id);
		$this->assertTrue($workflow_map->token_method->create($kwtData, $id));
		$workflow_map->token_id_list[3][] = $id;
		$kwtData = array('node_id' => $workflow_map->node_2_id, 'id' => $workflow_map->object_3_id, 'adresse_id' => $workflow_map->object_3_id);
		$this->assertTrue($workflow_map->token_method->create($kwtData, $id));
		$workflow_map->token_id_list[3][] = $id;
		$kwtData = array('node_id' => $workflow_map->node_3_id, 'id' => $workflow_map->object_3_id, 'adresse_id' => $workflow_map->object_3_id);
		$this->assertTrue($workflow_map->token_method->create($kwtData, $id));
		$workflow_map->token_id_list[3][] = $id;
		return $workflow_map;
	}
}
