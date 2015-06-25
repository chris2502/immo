<?php

DEFINE('DEBUG', false);

require_once 'library/WorkflowTest.php';
require_once 'library/InputParameters.php';

class workflowTestMethod extends WorkflowAction
{
	public function check_insert_test4(&$id_list, $win, $nin, $object = null)
	{
		return FALSE;
	}
	
	public function check_insert_test3(&$id_list, $win, $nin, $object = null)
	{
		$id_list = array();
		return TRUE;
	}
	
	public function check_insert_test5(&$id_list, $win, $nin, $object = null)
	{
		if ($nin != 'test6')
		{
			$id_list = array();
		}
		return TRUE;
	}
}

class MoveTokenToTest extends WorkflowTest
{
	protected $_wf_id;
	protected $nodes = array();
	protected $links = array();
	
	
	public function __construct()
	{
		parent::__construct();
		$this->nodes[] = (object) array(
			'node_name' => 'test1',
			'etat' => 'Etat Test 1',
			'id' => null
		);
		
		$this->nodes[] = (object) array(
			'node_name' => 'test2',
			'etat' => 'Etat Test 2',
			'id' => null
		);
		
		$this->nodes[] = (object) array(
			'node_name' => 'test3',
			'etat' => 'Etat Test 3',
			'id' => null
		);
		
		$this->nodes[] = (object) array(
			'node_name' => 'test4',
			'etat' => 'Etat Test 4',
			'id' => null
		);
		
		$this->nodes[] = (object) array(
			'node_name' => 'test5',
			'etat' => 'Etat Test 5',
			'id' => null
		);
		
		$this->nodes[] = (object) array(
			'node_name' => 'test6',
			'etat' => 'Etat Test 6',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[0],
			'output_node' => $this->nodes[1],
			'label' => '1 -> 2',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[1],
			'output_node' => $this->nodes[5],
			'label' => '2 -> 6',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[5],
			'output_node' => $this->nodes[4],
			'label' => '6 -> 5',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[1],
			'output_node' => $this->nodes[3],
			'label' => '2 -> 3',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[1],
			'output_node' => $this->nodes[3],
			'label' => '2 -> 4',
			'id' => null
		);
		
		$this->links[] = (object) array(
			'input_node' => $this->nodes[1],
			'output_node' => $this->nodes[4],
			'label' => '2 -> 5',
			'id' => null
		);
	}
	
	public function setUp()
	{		
		parent::setUp();
		$this->workflow = 'workflowTest';
		
		ORM::getORMInstance('Workflow')->create(array('nom' => 'Test', 'workflow_name' => $this->workflow), $this->_wf_id);
		$hNode = ORM::getORMInstance('Node');
		foreach ($this->nodes as $node)
		{
			$hNode->create(array(
				'workflow_id' => $this->_wf_id, 
				'node_name' => $node->node_name,
				'etat' => $node->etat,
				'type_id' => 1,
				'object' => 'test_object'
			), $node->id);			
		}
			
		$hLink = ORM::getORMInstance('NodeLink');
		foreach ($this->links as $link)
		{
			$hLink->create(array(
				'output_node' => $link->output_node->id,
				'input_node' => $link->input_node->id,
				'label' => $link->label			
			), $link->id);
			
		}
		
		$this->input = new TokenToNode($this->workflow);
		$this->output = new WFNode($this->workflow);
	}
	
	public function tearDown()
	{
		$hLink = ORM::getORMInstance('NodeLink');
		foreach ($this->links as $link)
		{
			$hLink->unlink($link->id);
		}
		
		$hNode = ORM::getORMInstance('Node');
		foreach ($this->nodes as $node)
		{
			$hNode->unlink($node->id);
		}
		
		ORM::getORMInstance('Workflow')->unlink($this->_wf_id);
		parent::tearDown();
	}
	
	public function testMoveTo2()
	{
		$this->input = 'test1';
		$this->output = 'test2';
		$this->save();
		
		$this->input = 'test2';
		$this->output = 'test6';
		$this->save();
		
		$this->input = 'test6';
		$this->output = 'test5';
		$this->save();
		
		$this->go();
		return TRUE;
	}	
}



?>
