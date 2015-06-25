<?php
/**
 * Une collection d'InputParameters pouvant être utiliser dans une
 * implémentation de WorkflowTest
 */

require_once 'WorkflowTest.php';

abstract class Post extends InputParameter
{
	protected $_values;
	protected $_key;
	protected $_flat;

	public function __construct()
	{
		foreach ($this->_values as $v => $r)
		{
			$this->_flat[] = array($v, $r);
		}
	}

	public function clean()
	{
		unset($_POST[$this->_key]);
	}

	public function set($index)
	{
		$this->_index = $index;
		if (isset($this->_flat[$index]))
		{
			$_POST[$this->_key] = $this->_flat[$index][0];
			return $this->_flat[$index][1];
		}
		return null;
	}
}

class WFNode extends InputParameter
{	
	protected $_id_list = array();
	protected $_name_list = array();
	protected $_cursor;
	
	public function __construct($workflow_name)
	{		
		ORM::getORMInstance('Node')->browse($node_list, $num, array('node_name'), array(
			array('workflow_name', '=', $workflow_name)
		));
		
		foreach ($node_list as $node_id => $node)
		{
			$this->_id_list[] = $node_id;
			$this->_name_list[] = $node['node_name']['value'];
		}
	}
	
	public function set($index)
	{
		if (isset($this->_name_list[$index]))
		{
			$this->_cursor = $this->_name_list[$index];
			return TRUE;
		}
		return null;
	}
	
	public function clean()
	{
		$this->_cursor = false;
	}
	
	public function get()
	{
		return $this->_cursor;
	}
	
	public function __toString()
	{
		return $this->_cursor;
	}
}

class TokenToNode extends WFNode
{
	protected $_token = null;
	protected $_hT;
	
	public function __construct($workflow_name)
	{
		parent::__construct($workflow_name);
		
		$this->_hT = ORM::getORMInstance('WorkflowToken');
	}
	
	public function clean()
	{
		if ($this->_token)
		{
			$this->_hT->unlink($this->_token);
		}
		$this->_token = null;
	}
	
	public function set($index)
	{		
		if (isset($this->_id_list[$index]))
		{
			if ($this->_token)
			{
				$this->_hT->write($this->_token, array('node_id' => $this->_id_list[$index]));
			}
			else
			{
				$this->_hT->create(array(
					'node_id' => $this->_id_list[$index],
					'id' => (isset($this->env->object)) ? $this->env->object : 1,
				), $this->_token);
			}
		}
		return parent::set($index);
	}	
}
