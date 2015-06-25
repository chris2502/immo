<?php

/**
 *  @class Module
 *  @Revision $Revision: 3647 $
 *
 */

abstract class Module
{
	protected $_token_id	= '';
	protected $_module_id	= '';
	protected $_module_data	= '';
	protected $_token_data	= array();
	protected $_structure	= array();
	protected $_title		= '';
	protected $_outputs		= array();

	public function __construct($token_id, $module_data)
	{
		$this->_token_id	= $token_id;
		$this->_process_internal_name = $module_data['process_internal_name']['value'];
		$this->_title		= $module_data['process_name']['value'];
		$this->_module_id	= $module_data['module_id']['value'];
		$this->_module_data	= $module_data['module_data']['value'];

		if(!empty($this->_module_data) && is_string($this->_module_data))
		{
			$this->_structure = (array)json_decode($this->_module_data, TRUE);
		}

		if(!empty($module_data['data']['value']) && is_string($module_data['data']['value']))
		{
			$this->_token_data	= (array)json_decode($module_data['data']['value'], TRUE);
		}
	}
	
	/**
	 * Cette méthode retourne le token_id du module
	 */
	public function getTokenId()
	{
		return $this->_token_id;
	}

	/**
	 * Cette méthode retourne le titre du module affiché à l'utilisateur.
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Récupère les informations d'un module.
	 */
	public function getModuleData()
	{
		return $this->_structure;
	}

	/**
	 * Récupère les données associés au token en cours.
	 */
	public function getData()
	{
		return $this->_token_data;
	}

	/**
	 * Récupère une valeur dans l'ensemble de données.
	 */
	public function getValue($attribute)
	{
		$path = explode('.', $attribute);

		if($path[0] == 'session')
		{
			switch($path[1])
			{
				case 'user_id':
					return $_SESSION['killi_user_id']['value'];
				case 'date':
					return date('Y-m-d');
				default:
					return FALSE;
			}
		}

		$data = $this->getData();
		foreach($path AS $p)
		{
			if(!isset($data[$p]))
			{
				return FALSE;
			}
			$data = $data[$p];
		}
		return $data;
	}

	protected function _recInsert(&$set, $data)
	{
		if(!is_array($data))
		{
			$set = $data;
			return TRUE;
		}

		foreach($data AS $d => $v)
		{
			if(empty($set[$d]))
			{
				$set[$d] = $v;
				continue;
			}
			$this->_recInsert($set[$d], $v);
		}
		return TRUE;
	}

	/**
	 * Sauvegarde des données dans le token en cours.
	 */
	public function saveData($data)
	{
		$data = array($this->_process_internal_name => $data);
		$this->_recInsert($this->_token_data, $data);
		$hORM = ORM::getORMInstance('processtokendata');
		$hORM->write($this->_token_id, array('data' => json_encode($this->_token_data)));
		return TRUE;
	}

	/**
	 * Action effectué lors d'un clic sur le bouton précédent.
	 */
	public function onPrev()
	{
		return TRUE;
	}

	/**
	 * Action effectué lors d'un clic sur le bouton suivant.
	 */
	public function onNext()
	{
		return TRUE;
	}

	/**
	 * Retourne TRUE si le module à une transition possible à un état précédent.
	 */
	public function hasPrev()
	{
		/**
		 * TODO: Vérifier le passif (table de log) afin de savoir si l'utilisateur peut aller au module précédent et remonter une condition.
		 */
		$hORM = ORM::getORMInstance('processtransition');
		$transition_id_list = array();
		$hORM->search($transition_id_list, $total, array(array('module_arrivee_id', '=', $this->_module_id)));

		if(count($transition_id_list) > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Retourne TRUE si le module à une transition possible à un état suivant.
	 */
	public function hasNext()
	{
		$hORM = ORM::getORMInstance('processtransition');
		$transition_id_list = array();
		$hORM->search($transition_id_list, $total, array(array('module_depart_id', '=', $this->_module_id)));

		if(count($transition_id_list) > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Vérifie si le module doit directement passer à l'étape suivante.
	 */
	public function checkNext()
	{
		return FALSE;
	}

	/**
	 * Fonction qui effectue la transition en arrière.
	 */
	protected function goPrev()
	{
		$filters = array();
		$filters[] = array('module_arrivee_id', '=', $this->_module_id);

		$hORM = ORM::getORMInstance('processtransition');
		$transition_list = array();
		$hORM->browse($transition_list, $total, array('module_depart_id'), $filters);

		if(count($transition_list) == 0)
		{
			throw new Exception('Impossible d\'aller plus loin dans le process !');
		}
		else
		if(count($transition_list) > 1)
		{
			throw new Exception('Plusieurs transitions "next" trouvés pour un module de type FORM !');
		}

		$transition = reset($transition_list);
		$module_id = $transition['module_depart_id']['value'];

		$hORM = ORM::getORMInstance('processtoken');
		$hORM->write($this->_token_id, array('module_id' => $module_id));

		return TRUE;
	}

	/**
	 * Fonction qui effectue la transition en avant.
	 */
	protected function goNext($answer_key = NULL)
	{
		$filters = array();
		$filters[] = array('module_depart_id', '=', $this->_module_id);
		if(!empty($answer_key))
		{
			$filters[] = array('answer_key', '=', $answer_key);
		}
		else
		{
			$filters[] = array('answer_key', 'IS NULL');
		}

		$hORM = ORM::getORMInstance('processtransition');
		$transition_list = array();
		$hORM->browse($transition_list, $total, array('module_arrivee_id'), $filters);

		if(count($transition_list) == 0)
		{
			throw new Exception('Impossible d\'aller plus loin dans le process !');
		}
		else
		if(count($transition_list) > 1)
		{
			throw new Exception('Plusieurs transitions "next" trouvés pour un module de type FORM !');
		}

		$transition = reset($transition_list);
		$module_id = $transition['module_arrivee_id']['value'];

		$hORM = ORM::getORMInstance('processtoken');
		$hORM->write($this->_token_id, array('module_id' => $module_id));

		return TRUE;
	}

	/**
	 * Éxécute le module.
	 */
	public function execute()
	{
		return TRUE;
	}

	/**
	 * Effectue le rendu du module à l'utilisateur.
	 */
	public function render()
	{
		return TRUE;
	}
}
