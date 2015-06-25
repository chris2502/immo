<?php
/**
 * Composant d'interface pour Killi.
 *
 * Affiche un liens dans une nestedlist, notament vers une application extérieur.
 * <nestedfield
 * 		object="objet_nom"					OPTIONNEL (celui de la <nestedlisting> parent par défaut ):
 * 											Le nom de l'objet Killi affiché par la liste.
 *
 * 		attribute="attr_nom"				Le nom du champs de l'objet que doit afficher le composant.
 * 
 * 		url="FTTH_PATH"						OPTIONNEL: L'url que doit suivre le liens si elle est differente de index.php.
 * 											Si le nom d'une constant PHP est donnée, celle-ci sera utilisée.
 * 
 * 		target="popup|_blank"				OPTIONNEL: Détermine si le liens doit s'ouvrir normalement, 
 * 											ou dans une nouvelle fenetre / onglet.
 * 
 * 		action="object_nom.edit"			OPTIONNEL: L'action à appeler sur le page ciblée. Le edit de l'objet courrant par défaut.
 * 
 * 		key="primary_id"					OPTIONNEL: le champs de l'objet que le composant doit utiliser comme clef
 * 											primaire pour son lien.
 * 
 * 		create="Creation d'un X"			OPTIONNEL: Permet la création d'un liens vers l'action de création si l'enregistrement est vide.
 * 											Le contenu de l'attribut servant de titre du liens.
 * 
 * 		size="800x600"						OPTIONNEL: La taille de la fenetre popup à ouvrir, au format WxH.
 * 
 * />
 *
 * @author boutillon
 *
 */
class NestedLinkXMLNode extends  NestedFieldXMLNode
{
	/**
	 * L'action que doit appeler le script.
	 * 
	 * @var String
	 */
	protected $_action;
	
	/**
	 * L'index du tableau Data à utiliser en guise de clef pour le liens.
	 * 
	 * @var String
	 */
	protected $_key;
	
	/**
	 * le liens doit-il permettre la création d'un nouvelle objet si l'attriobut est null ?
	 * 
	 * @var String / False
	 */
	protected $_create;
	
	/**
	 * La taille de la fenetre popup à ouvrir, au format WxH.
	 *
	 * @var String
	 */
	protected $_size = false;
		
	/**
	 * Ajoute le rendu du composant au tableau $stack.
	 *
	 * @param	Int			$key		La clef primaire de l'élément à afficher. 
	 * @param	Array		$data		Le tableau de données à utiliser.
	 */
	public function render2(&$key, &$data)
	{
		if (isset($data[$this->_attr]) && $data[$this->_attr]['value'])
		{
			$data_node = $data[$this->_attr];
			Security::crypt($data[$this->_key]['value'], $crypted_key);	
			$url = $this->_url.'index.php?action='.$this->_action.'&amp;view=form&amp;token='.$_SESSION['_TOKEN'].'&amp;crypt/primary_key='.$crypted_key.$this->_env($data);			
		}
		elseif ($this->_create)
		{
			$data_node['html'] = $this->_create;
			$url = $this->_url.'index.php?action='.$this->_action.'&view=create&amp;&amp;token='.$_SESSION['_TOKEN'].$this->_env($data);
		}
		else
		{
			$data_node = array('value' => null);
			$url = FALSE;
		}
		
		if (isset($data_node['html']))
		{
			$reference = $data_node['html'];
		}
		elseif (isset($data_node['reference']))
		{
			$reference = $data_node['reference'];
		}
		else
		{
			$reference = $data_node['value'];
		}

		if (is_array($reference))
		{
			$reference = implode (', ', $reference);
		}
		
		echo '<td ',$this->_css($data),' ',$this->style(),'>';
		if ($url)
		{
			switch ($this->_target)
			{
				case '_blank':
				echo '<div><a href="',$url,'" target="_blank">',$reference,'</a></div>';			
				break;
				
				case 'popup':
				$url .= '&amp;inside_popup=1';
				echo '<div><a data-url="',$url,'"';
				if ($this->_size)
				{
					echo ' data-size="', $this->_size,'"';
				}
				echo '>',$reference,'</a></div>';
				break;
				
				default:
				echo '<div><a href="',$url,'">',$reference,'</a></div>';
				break;
			}
		}
		echo '</td>';
		return true;
	}

	/**
	 * Affiche le rendu du Header du composant au tableau $stack.
	 */
	public function renderHeader()
	{
		if ($this->_sort)
		{
			echo '<th class="sortable" data-sort="',$this->_sort,'">',$this->_object_field->name,'</th>';
		}
		else
		{
			echo '<th class="sortable">',$this->_object_field->name,'</th>';
		}
		return true;
	}
	
	/**
	 * La fonction appelé à l'ouverture de la node XML.
	 *
	 * Initialise le composant.
	 *
	 * @return Boolean
	 */
	public function open()
	{
		parent::open();
		$this->_url = $this->getNodeAttribute('url', false);
		
		if ($this->_url && defined($this->_url))
		{
			$this->_url = constant($this->_url);
		}		
		
		$this->_action = $this->getNodeAttribute('action', $this->_object_name . '.edit');
		$this->_target = $this->_getInheritedAttribute('target', false);
		$this->_key = $this->getNodeAttribute('key', $this->_attr);
		$this->_create = $this->getNodeAttribute('create', false);
		$this->_size = $this->getNodeAttribute('size', false);
		return true;
	}
}

