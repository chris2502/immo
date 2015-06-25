<?php

/**
 * Une série d'outils visant à simplifier les tests automatiques de workflows.
 * 
 * L'implémentation ne devrait demander d'une surcharge de WorkflowTest, 
 * avec éventuellement la définitions de nouveaux types d'InputParameter. 
 */
if (!defined('FAST_TRACK'))
{
	DEFINE('FAST_TRACK', false);
}
if (!defined('DEBUG'))
{
	DEFINE('DEBUG', false);
}

abstract class InputParameter
{	
	public function clean() {}
	
	/**
	 * Fonction principale.
	 * 
	 * Doit mettre en place l'environnement correspondant au Parametre.
	 * 
	 * Doit retourner 	TRUE si le parametre est valide.
	 * 					FAUX si le parametre est invalide.
	 * 					NULL si la boucle prends fin.
	 */
	abstract public function set($index);
	
	abstract public function get();
}

class scenario {
	protected $_content = array();
	protected $_edition = array();
	protected $_messages = array();
	
	public function set($name, $value)
	{
		$this->_edition[$name] = $value;
	}
	
	public function save()
	{
		if (!empty($this->_edition))
		{
			$this->_content[] = $this->_edition;
		}
		$this->_edition = array();
	}
	
	public function validate($capsules, $result)
	{
		$m = $this->_match($capsules);
		$compare = ($m === false) ? false : true;
		if ($result != $compare)
		{
			$msg = "\033[1;31mInputParameters:\n";
			$msg .= implode("\n", $capsules);
			$msg .= $this->_printScenarios('1;31', $m);
			$msg .= "\nRESULT: ".(($result) ? 'true' : 'false')."\033[0m\n";			
			$this->_messages[] = $msg;			
			return FALSE;
		}
		elseif (DEBUG)
		{
			$msg = implode("\n", $capsules);
			$msg .= $this->_printScenarios('0', $m);
			$msg .= "\nRESULT: ".(($result) ? 'true' : 'false')."\n";
			$this->_messages[] = $msg;	
		}
		return TRUE;
	}
	
	protected function _match($capsules)
	{
		foreach ($this->_content as $index => $scene)
		{
			if($this->_compare($capsules, $scene))
			{
				return $index;
			}			
		}
		return FALSE;
	}
	
	protected function _compare($capsules, $scene)
	{
		foreach ($scene as $key => $value)
		{
			if ($value === false)		// On ignore les élements nons testés
			{
				continue;
			}
			
			$val = $capsules[$key]->isValid();
			if (!$val)
			{
				return FALSE;
			}
			if ($value !== true AND $value != $capsules[$key]->get())
			{
				return FALSE;
			}
		}
		return TRUE;		
	}
	
	protected function _printScenarios($color, $match)
	{
		$msg = "\nScenarios:";
		foreach ($this->_content as $index => $data)
		{
			if ($index === $match)
			{
				$msg .= "\033[1;32m";
				$msg .= $this->_printScene($data);
				$msg .= "\033[".$color."m";
			}
			else
			{
				$msg .= $this->_printScene($data);
			}
		}
		return $msg;
	}
	
	protected function _printScene($scene)
	{
		$msg = "\n{";
		foreach ($scene as $k => $d)
		{
			$msg .= $k.': '.$d."\t";
		}
		$msg .= "}";
		return $msg;
	}
	
	public function __toString()
	{
		return "\n".implode("\n", $this->_messages)."\n";
	}
}

class capsule
{
	protected $_iterator = -1;
	protected $_skip = false;
	protected $_valid = false;
	protected $_name;
	protected $_ip;	
	
	public function __construct(InputParameter $ip, $name)
	{
		$this->_ip = $ip;
		$this->_name = $name;
	}
	
	/**
	 * @return                false             Fin d'iteration.
	 *                        true  			On continue la boucle.              
	 */
	public function iterate($key = null)
	{
		$this->_ip->clean();
		if($this->_skip)
		{
			return FALSE;
		}
		elseif ($this->_iterator == -1)			// L'enregistrement vide.
		{
			$this->_iterator++;
			return TRUE;
		}
		
		$set = $this->_ip->set($this->_iterator);
		if ($set === null)
		{
			$this->_iterator = -1;
			return FALSE;
		}
		$this->_valid = $set;
		$this->_iterator++;
		return TRUE;
	}
	
	public function clean()
	{
		return $this->_ip->clean();
	}
	
	public function get()
	{
		return $this->_ip->get();
	}
	
	public function isValid()
	{
		return $this->_valid;
	}
	
	/**
	 * Affiche le contenu de la capsule.
	 */
	public function __toString()
	{
		if (method_exists($this->_ip, '__toString'))
		{
			$ip = ($this->_iterator == 0) ? 'Iteration VIDE' : $this->_ip;
		}
		else
		{
			$ip = '__toString non définis';
		}
		return '{'.$this->_name."\t".$ip."\t".'Get:'.$this->_ip->get().' Valid:'.(($this->_valid) ? 'true' : 'false').'}';
	}
}


abstract class WFTest extends Killi_TestCase
{
	protected $_capsules = array();
	protected $_env = array();
	protected $_scenario;

	public function __set($name, $value)
	{
		if (is_a($value, 'InputParameter'))
		{
			$this->_capsules[$name] = new capsule($value, $name);
			if ($this->_scenario == null)
			{
				$this->_scenario = new scenario();
			}
		}
		elseif(isset($this->_capsules[$name]))
		{
			$this->_scenario->set($name, $value);
		}
		else
		{
			$this->_env[$name] = $value;
		}
	}
	
	public function save()
	{
		$this->_scenario->save();
		return TRUE;
	}
	
	public function go($callback = null)
	{
		$this->_scenario->save();
		return $this->_loop($this->_capsules, $callback);
	}

	public function tearDown()
	{
		foreach ($this->_capsules as $capsule)
		{
			$capsule->clean();
		}
		return TRUE;
	}

	protected function _loop($objs, $callback = null)
	{
		$current = array_shift($objs);        
		if ($current == null)
		{
			$res = call_user_func($callback);
			return $this->_scenario->validate($this->_capsules, $res);
		}
		else
		{
			$res = true;
			while ($r = $current->iterate())
			{
				if (!$this->_loop($objs, $callback))
				{
					$res = false;
				}
			}
			return $res;
		}
	}
}

/**
 * Classe automatisant les testes à base de moveTokenTo.
 * 
 * Le fonction go appelant celle-ci par défaut si aucun 
 * callback n'est précisé.
 * 
 * Auquel cas, les nodes d'entrées et de sorties seront 
 * trouvées ou dans l'environnement, ou dans les 
 * InputParameters. 
 */
abstract class WorkflowTest extends WFTest
{
	public $workflow;
	
	protected $_hORM = null;
	
	protected $_env = array(
		'input' => 'input_node_name',
		'output' => 'output_node_name'
	);
	
	protected function _moveTokenTo()
	{
		if ($this->_hORM == null)
		{
			$this->_hORM = ORM::getORMInstance('WorkflowToken');
		}
		
		$res = true;
		$wf = new WorkflowAction();
		
		$in = (isset($this->_capsules['input'])) ? $this->_capsules['input']->get() : $this->_env['input'];
		$out = (isset($this->_capsules['output'])) ? $this->_capsules['output']->get() : $this->_env['output'];
		$id = (isset($this->_env['object'])) ? $this->_env['object'] : 1;
		$id_list = array($id => array('id' => $id));		
		$tokens_before = array();
		$this->_hORM->search($tokens_before, $num, array(
			array('id', '=', $id),
			array('node_name', '=', $out),
			array('workflow_name', '=', $this->workflow)
		));				
		try {
			$wf->MoveTokenTo($id_list, $this->workflow, $in, $this->workflow, $out);
		}
		catch (Exception $e) {
			$res = false;
		}
		$tokens_after = array();
		$this->_hORM->search($tokens_after, $num, array(
			array('id', '=', $id),
			array('node_name', '=', $out),
			array('workflow_name', '=', $this->workflow)
		));		
		$diff = array_diff($tokens_after, $tokens_before);
		if (count($diff) != 1)
		{
			$res = false;
		}		
		return $res;
	} 
	
	public function go($callback = null)
	{
		if ($callback === null)
		{
			$callback = array($this, '_moveTokenTo');
		}
		$res = parent::go($callback);
		echo $this->_scenario;
		$this->assertTrue($res);
		return TRUE;              
	}	
	
}

