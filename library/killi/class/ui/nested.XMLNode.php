<?php

/**
 * La classe de base de la famille des nesteds.
 * 
 * Implemente des fonctions d'interactions fils <-> parents.
 * 
 * @author boutillon 
 */
abstract class NestedXMLNode extends XMLNode
{
	/**
	 * La profondeur inhérente à la node.
	 * 
	 * @var int
	 */
	protected $_base_depth = 0;
	
	/**
	 * La profondeur d'arborescence à laquel se trouve la node XML.
	 * 
	 * @var int
	 */
	protected $_depth;
	
	/**
	 * Les classes CSSs à alouer au composant.
	 * 
	 * @var Array
	 */
	protected $_css_list = array();
	
	/**
	 * Une classe CSS dépendant d'un attribut.
	 * 
	 * @var String.
	 */
	protected $_css_class_with_value = false;
	
	/**
	 * L'attribut environnement du composant.
	 * 
	 * @var Array
	 */
	protected $_env_definition_list;
	
	/**
	 * Détermine si cette liste est la racine.
	 * 
	 * @var boolean
	 */
	protected $_is_root = false;
	
	/**
	 * Les fichiers dont dépend le composant ont-ils été chargés ?
	 * ( Principalement des fichiers CSS ou JS )
	 * 
	 * @var Boolean  
	 */
	protected static $_loaded_dependency = array();
	
	/**
	 * La profondeur d'arborescence maximale à partir de la node.
	 * 
	 * @var int
	 */
	protected $_max_depth;
	
	/**
	 * Le nom de l'objet sur lequel travail le composant
	 * 
	 * @var String
	 */
	protected $_object_name;
	
	/**
	 * Le parent du composant, s'il s'agit d'une NestedXMLNode.
	 * 
	 * @var NestedXMLNode
	 */
	protected $_parent = false;
	
	/**
	 * Génère la liste des classes CSS à utiliser par la classe.
	 * 
	 * @param	Array		$data		 Les données de l'élément à afficher. 
	 */
	protected function _css(&$data)
	{
		$css_list = $this->_css_list;
		if ($this->_css_class_with_value)
		{
			if (isset($data[$this->_css_class_with_value]))
			{
				$value = $data[$this->_css_class_with_value]['value'];
				$css_list[] = $this->_css_class_with_value . '_'.strtolower(str_replace(' ', '_', $value));
			}
		}
		return $this->css_class($css_list);
	}
	
	/**
	 * Génère la string décrivant l'environement à inclure.
	 * 
	 * @param	Array		$data		Le tableau de données à utiliser.
	 */
	protected function _env(&$data)
	{
		$env_string = '';
		if ($this->_env_definition_list)
		{
			foreach($this->_env_definition_list as $env_definition)
			{
				$env = explode('=', $env_definition);
				$env_string .= "&crypt/" . $env[0] . "=";
				list($envobj, $envattr) = explode('.', $env[1]);
				if(preg_match("/^[a-z\_]+$/", $envobj) && isset($data[$envattr]))
				{
					Security::crypt($data[$envattr]['value'], $crypt_value);
				}
				else
				{
					throw new Exception("Invalid env attribute");
				}
				$env_string .= $crypt_value;
			}
		}
		return $env_string;
	}
	
	/**
	 * Récupère un attribut sur le fichier XML.
	 * 
	 * Si cet attribut n'existe pas sur la node XML du block, la fonction
	 * va chercher à le récuperer sur son parent s'il s'agit également
	 * d'un NestedXMLNode.
	 * 
	 * @param	String			Le nom de l'attribut à récuperer.
	 * @param	Mixed			La valeur par défaut de l'attribut, s'il n'est trouvé
	 * 							ni sur le block, ni sur les parents de celui-ci.
	 */
	protected function _getInheritedAttribute($name, $default_value)
	{
		$value = $this->getNodeAttribute($name, null);
		if ($value === null)
		{			
			if ($this->_parent)
			{
				$value = $this->_parent->_getInheritedAttribute($name, $default_value);
			}
			else
			{
				$value = $default_value;
			}
			return $value;
		}
		return $value;
	}
	
	/**
	 * Charge les fichiers nécessaire au composant.
	 * 
	 * ( Généralement des fichiers JS ou CSS ).
	 * 
	 * @param	String			Le nom du fichier à charger.
	 */
	protected function _load($file_name)
	{
		if (!in_array($file_name, self::$_loaded_dependency))
		{			
			$exploded_file_name = explode('.', $file_name);
			$extension = end($exploded_file_name);
			switch ($extension)
			{
				case 'css':
					$path = './css/'.$file_name;
					if (!is_file($path))
					{
						$path = KILLI_DIR. '/css/'.$file_name;
					}
					echo '<link type="text/css" rel="stylesheet" href="', $path, '">';
					break;
				case 'js':
					$path = './js/'.$file_name;
					if (!is_file($path))
					{
						$path = KILLI_DIR. '/js/'.$file_name;
					}
					echo '<script src="', $path, '"></script>';
					break;				
			}
			self::$_loaded_dependency[] = $file_name;
		}
		return TRUE;
	}
	
	/**
	 * La fonction appelé à la fermeture de la node XML. ( et donc après
	 * chargement des enfants. )
	 * 
	 * @return Boolean
	 */
	public function close()
	{
		if ($this->_parent && ($this->_parent->_max_depth < $this->_max_depth))
		{
			$this->_parent->_max_depth = $this->_max_depth;
		}
		return true;
	}
	
	/**
	 * La fonction appelé à l'ouverture de la node XML.
	 * 
	 * Initialise le composantet détermine si le composant est la 
	 * racine de son arborescence de nested. 
	 * 
	 * @return Boolean
	 */
	public function open()
	{
		$this->_parent = $this->getParent();
		
		if ($this->_parent !== null && is_a($this->_parent, 'NestedXMLNode'))
		{
			$this->_parent->add($this);
			$this->_depth = $this->_parent->_depth + $this->_base_depth;
		}
		else
		{
			$this->_parent = false;
			$this->_is_root = true;
			$this->_depth = $this->_base_depth;
		}
		$this->_max_depth = $this->_depth;
		$this->_object_name = $this->_getInheritedAttribute('object', false);
		$this->_env_definition_list = $this->getNodeAttribute('env', false);
		$this->_css_class_with_value = $this->getNodeAttribute('css_class_with_value', false);
		if($this->_env_definition_list)
		{
			$this->_env_definition_list = explode(',', $this->_env_definition_list);
		}
		return TRUE;
	}
}
