<?php

/**
 * Composant d'interface pour Killi.
 *
 * Affiche des listes imbriquées les unes dans les autres à partir du XML suivant :
 * <nestedlisting
 *		object="objet_nom"					INHERITED: Le nom de l'objet Killi affiché par la liste
 *
 * 		src="data_src"						La clef du tableau data contenant les données
 * 											à afficher. S'il s'agit d'une liste imbriqué,
 * 											la source doit se trouver dans l'enregistrement
 * 											du parent.
 *
 * 											exemple :
 * 												<nestedlisting src="A">
 * 													<nestedlisting src="B" />
 * 												</ nestedlisting>
 *
 * 											Les données seront cherchés dans data['A'] par la
 * 											première liste, et dans Data['A'][iterator]['B'] par son
 * 											enfant.
 *
 * 		data-extra="field, field.subfield"	OPTIONNEL: Des données du tableau data à faire passer
 * 											lors de la sélection de la ligne.
 * 
 * 		expanded = "0"						OPTIONNEL: Faux par défaut, détermine si le sous-éléments
 *											doivent apparaitrent ou pas lors du rendu initiale.
 * 
 * 		header = "1"						OPTIONNEL, INHERITED: Vrai par défaut, le composant doi-il afficher les header de table.
 *
 * 		key = "adresse_id"					OPTIONNEL: Si l'attribut est renseigné, le composant utilisera ce champs du tableau
 * 											datta comme clef identifiant l'enregistrement.
 * 
 * 		render_empty = "0"					OPTIONNEL : Affiche un tableau vite si la sous-liste ne contient aucuns éléments..
 * 
 * 		/>
 *
 *
 * La node <nestedlisting> peut contenir en enfants :
 * - d'autres <nestedlisting>	: 	définissant un liste imbriquée. Chaque liste pouvant
 * 								 	contenir plusieurs autres listes.
 * - des <nestedfield>			: 	définissant un champs à afficher dans la liste.
 *
 * La récupération des données se fait par la méthode POST, la fonction static
 * getSelection() permettant une récupération et un décryptage facilité des données.
 *
 * Nécessite nestedlisting.js et nestedlisting.css
 *
 * @author boutillon
 *
 */
class NestedListingXMLNode extends NestedTableXMLNode
{	
	/**
	 * Les données supplémentaires que doit pouvoir transmettre une ligne.
	 * 
	 * @var Array
	 */
	protected $_data_extra_list = null;
	
	/**
	 * Les données qui determinent si une ligne est editable.
	 * 
	 * @var Array
	 */
	protected $_data_editable_list = null;
	
	/**
	 * La clef à utiliser pour acceder au tableau de données.
	 *
	 * @var String
	 */
	protected $_data_src = false;

	/**
	 * Un tableau pouvant potentiellement contenir la description de l'objet
	 * ainsi que de ses enfants.
	 * 
	 * Ne devrait jamais être utiliser directement. Pssez par la fonction
	 * getDescription.
	 * 
	 * @var Array
	 */
	protected $_description_list = array();

	/**
	 * L'élément doit-il afficher son table header.
	 *
	 * @var Boolean
	 */
	protected $_header = true;

	/**
	 * Le champs de donnée à utiliser comme clef pour les lignes.
	 *
	 * @var String
	 */
	protected $_key = false;
	
	/**
	 * Le message a affiché si la liste est vide et le rendu des listes vides activées
	 */
	protected $_msg_empty = false;
	
	/**
	 * La description de l'object affiché par la liste.
	 * 
	 */
	public $object_description = false;
	
	/**
	 * Détermine si le composant contient un champs checkbox.
	 * 
	 * @var Boolean
	 */
	public $selectbox = false;
	
	protected $_import_key = false;

	/**
	 * Affiches les champs headers de table necessaire au parent.
	 *
	 * @param	Array		$stack		le tableau auquel ajouter le contenu de la colonne.
	 */
	protected function _buildHeaderOnParent(&$stack)
	{
		if ($this->_push_count_on_parent)
		{
			$desc = reset($this->_description_list);
			$stack[] = '<th class="sortable">'.$desc->desc.'</th>';
		}
		return TRUE;
	}

	/**
	 * Effectue l'affichage de la liste.
	 *
	 * @param		Array		$data_input			La tableau de données à afficher.
	 */
	protected function _render2(&$data_input)
	{
		$padding = 0;
		if (isset($data_input[$this->_data_src]))
		{
			$data_src_list = $data_input[$this->_data_src];
		}
		else
		{
			$data_src_list = array();
		}
		if (count($data_src_list) > 0)
		{
			$this->_renderTableOpening();
			if ($this->_header)
			{
				echo '<thead class="ui-widget-header"><tr>';
				foreach ($this->_table_data_list as $table_data)
				{
					$table_data->renderHeader();
				}
				echo '</tr></thead>';
			}
			echo '<tbody>';
			foreach ( $data_src_list as $key => $data_src)
			{
				$this->_renderRowOpening($key, $data_src);
				foreach ($this->_table_data_list as $table_data)
				{
					if ($table_data instanceof XMLNode)
					{
						$table_data->setData($data_src);
					}
					$table_data->render2($pkey, $data_src);
				}
				echo '</tr>';

				$this->_renderTables($data_src);
			}
			echo '</tbody></table>';
			if (!$this->_is_root)
			{
				echo '</td></tr>';
			}
		}
		elseif ($this->_render_empty)
		{
			$this->_renderTableOpening();
			if ($this->_header)
			{
				echo '<thead class="ui-widget-header"><tr>';
				foreach ($this->_table_data_list as $table_data)
				{
					$table_data->renderHeader();
				}
				echo '</tr></thead>';
			}
			echo '<tbody>';
			echo '<tr><td colspan="'.$this->_column_count.'" class="emptyList"> - '.$this->_msg_empty.' - </td></tr>';
			echo '</tbody></table>';
		}
		return TRUE;
	}
	
	/**
	 * Affiches l'ouverture d'une ligne de la table.
	 *
	 * @param	String		$key		La clef primaire correspondant à la ligne
	 * 									de donnée à afficher.
	 * @param	Array		$data_src	Le tableau de données à utiliser.
	 */
	protected function _renderRowOpening($key, $data_list)
	{
		$css_class_list = array('row');	
		if ($this->_css_class_with_value)
		{
			if (isset($data_list[$this->_css_class_with_value]))
			{
				$v = $data_list[$this->_css_class_with_value]['value'];
				$css_class_list[] = $this->_css_class_with_value . '_'.strtolower(str_replace(' ', '_', $v));
			}
		}
		if ($this->_start_expanded)
		{			
			$pattern = array('(\bAND\b)', '(\bOR\b)', '(\band\b)', '(\bor\b)');
			$replacement = array('&&', '||', '&&', '||');
			$condition = preg_replace($pattern, $replacement, $this->_start_expanded);

			/* On remplace les {variable} */
			$pattern = '((\{)(([a-zA-Z_]+))(\}))';
			$replacement = '\$data_list[\'$3\'][\'value\']';

			$condition = 'return ('.preg_replace($pattern, $replacement, $condition).');';
			if (eval($condition))
			{
				$css_class_list[] = 'expanded';	
			}							
		}
		echo '<tr class="', implode(' ', $css_class_list), '"';
		if ($this->_key === false)
		{
			Security::crypt($key, $crypted_key);
		}
		else
		{
			Security::crypt($data_list[$this->_key]['value'], $crypted_key);
		}
		echo ' data-key="crypt/',$this->_object_name, '/', $crypted_key ,'"';
		if ($this->_import_key && !empty($data_list[$this->_import_key]['value']))
		{
			Security::crypt($data_list[$this->_import_key]['value'], $crypted_compare);
			echo ' data-import-key="crypt/',$this->_import_object,'/', $crypted_compare ,'"';
		}
		$this->_getDataExtra($data_list);
		$this->_getDataEditable($data_list);		
		echo '>';
		return TRUE;
	}

	/**
	 * Affiche le tag d'ouverture de la table.
	 *
	 */
	protected function _renderTableOpening($extend = '')
	{
		if (!$this->_is_root)
		{
			echo '<tr class="nest"><td colspan="', $this->_parent->_column_count, '">';			
		}
		echo '<table', $this->css_class($this->_css_list), ' data-object="',$this->_object_name,'"';
		echo $extend;
		echo ' >';
		return TRUE;
	}
	
	/**
	 * Affiche les enfants de type block de la liste.
	 *
	 * @param	Array		$data_src		La tableau de données à utiliser par les enfants.
	 */
	protected function _renderTables($data_src)
	{
		foreach ($this->_table_list as $block)
		{
			$block->_render2($data_src);
		}
		return TRUE;
	}
	
	/**
	 * Remplis le tableau décrivant les données supplémentaires à fournir. 
	 */
	protected function _setDataExtra()
	{
		$this->_data_extra_list = $this->getNodeAttribute('data-extra', false);
		if ($this->_data_extra_list)
		{
			$tmp_list = explode(',', $this->_data_extra_list);
			$this->_data_extra_list = array();
			foreach ($tmp_list as &$tmp)
			{
				$tmp = trim($tmp);
				$this->_data_extra_list[$tmp] = explode('.', $tmp);				
			}
		}
		return TRUE;
	}

	/**
	 * Remplis le tableau décrivant les données supplémentaires à fournir pour savoir si l'item est editable. 
	 */
	protected function _setDataEditable()
	{
		$this->_data_editable_list = $this->getNodeAttribute('data-editable', false);
		if ($this->_data_editable_list)
		{
			$tmp_list = explode(',', $this->_data_editable_list);
			$this->_data_editable_list = array();
			foreach ($tmp_list as &$tmp)
			{
				$tmp = trim($tmp);
				$this->_data_editable_list[$tmp] = explode('.', $tmp);				
			}
		}
		return TRUE;
	}
	
	/**
	 * Fonction récursive parcourant le tableau afin de décrypter les
	 * clefs dans celui-ci.
	 * 
	 * @param	Array		$array		Le tableau de données en entré.
	 * 
	 * @return 	Array
	 */
	protected static function _uncryptArray($array)
	{
		if (!is_array($array))
		{
			return $array;
		}
		$data = array();
		foreach ($array as $key => $content)
		{
			$k = explode('/', $key);
			if ($k[0] == 'crypt')
			{
				if (isset($k[2]))
				{
					Security::decrypt($k[2], $uncrypted_key);
					$uncrypted_key = $k[1].'/'.$uncrypted_key;
				}
				else
				{
					Security::decrypt($k[1], $uncrypted_key);	
				}				
			}
			else
			{
				$uncrypted_key = $key;
			}
			
			
			if (is_array($content))
			{
				$uncrypted_content = self::_uncryptArray($content);
			}
			else
			{
				$c = explode('/', $content);
				if ($c[0] == 'crypt')
				{
					if (isset($c[2]))
					{
						Security::decrypt($c[2], $uncrypted_content);
						$uncrypted_content = $c[1].'/'.$uncrypted_content;
					}
					else
					{
						Security::decrypt($c[1], $uncrypted_content);
					}
				}
				else
				{
					$uncrypted_content = $content;
				}	
			}
			$data[$uncrypted_key] = $uncrypted_content;
		}
		return $data;
	}
	
	/**
	 * Récupere les données à fournir dans les attributs data-extra.
	 * 
	 * @param 	Array	$data			Le tableau de données.
	 * 
	 * @return 	Json					Les données de l'attribut
	 */
	protected function _getDataExtra($data)
	{
		if (is_array($this->_data_extra_list))
		{
			$output = array();
			foreach ($this->_data_extra_list as $key => $data_extra)
			{
				$i = $data;
				foreach ($data_extra as $extra) 
				{
					if (array_key_exists($extra, $i)) 
					{
						$i = $i[$extra];
					}
					else
					{
						$i = null;
						break;
					}
				}
				if ($i != null)
				{
					Security::crypt($i, $i);
					if (is_array($i))
					{
						foreach($i as $inside_key => $value)
						{
							$i['crypt/'.$inside_key] = 'crypt/'.$value;
							unset($i[$inside_key]);
						}
						$output[$key] = $i;
					}
					else
					{
						$output[$key] = 'crypt/'.$i;
					}
				}
			}
			echo " data-extra='".json_encode($output)."'";
		}
		return true;
	}

	/**
	 * Récupere les données à fournir dans les attributs data-editable.
	 * 
	 * @param 	Array	$data			Le tableau de données.
	 * 
	 * @return 	Json					Les données de l'attribut
	 */
	protected function _getDataEditable($data)
	{
		if (is_array($this->_data_editable_list))
		{
			foreach ($this->_data_editable_list as $key => $data_editable)
			{
				$i = $data;
				foreach ($data_editable as $editable) 
				{
					if (array_key_exists($editable, $i)) 
					{
						$i = $i[$editable];
					}
					else
					{
						$i = null;
						break;
					}
				}
				if (is_array($i))
				{
					if (isset($i['value']))
					{
						$output = $i['value'];
					}
				}
			}
			echo " data-editable='".$output."'";
		}
		return true;
	}
	
	/**
	 * Affiche le composant maintenant que tous ses fils sont chargés.
	 *
	 * @return boolean
	 */
	public function close()
	{		
		$this->_setDataExtra();
		$this->_setDataEditable();
		parent::close();		
		return TRUE;
	}
	
	/**
	 * Récupere les données retournées par un composant NestedList.
	 * 
	 * @param	String		$post_key		La clef de retour du formulaire dans le tableau $_POST
	 * @param	Array		$data			Les données trouvées.
	 */
	public static function getFormData($post_key, &$data = array())
	{
		$data = json_decode($_POST[$post_key], true);
		$data = self::_uncryptArray($data);
		return TRUE;
	}
	
	/**
	 * Fonction d'entrée du composant, configure celui-ci
	 *
	 * @return boolean
	 */
	public function open() {
		parent::open();
		$this->_data_src = 	$this->getNodeAttribute('src');
		$this->_start_expanded = $this->getNodeAttribute('expanded', false);
		$this->_msg_empty = $this->getNodeAttribute('msg_empty', 'PAS DE DONNEES');
		$this->_render_empty = $this->getNodeAttribute('render_empty', false);
		$this->_key = $this->getNodeAttribute('key', false);	
		
		$this->_header = $this->_getInheritedAttribute('header', true);
		$this->_import_key = $this->_getInheritedAttribute('import_key', false);
		if ($this->_import_key)
		{
			$field = $this->_import_key;
			$this->_import_object = strtolower(ORM::getObjectInstance($this->_object_name)->$field->object_relation);
		}
		$this->_css_list[] = 'nestedlisting';
		$this->_css_list[] = 'table_list';
		$this->object_description = ORM::getObjectInstance($this->_object_name)->description;
		

		return TRUE;
	}
	
	/**
	 * Retourne un tableau décrivant l'objet
	 */
	public function getDescription()
	{
		if (empty($this->_description_list))
		{
			$hObject = ORM::getObjectInstance($this->_object_name);
			$this->_description_list = (object) array('desc' => $hObject->description, 'obj' => $this->_object_name, 'src' => $this->_data_src, 'childs' => array());
			foreach ($this->_table_list as $child)
			{
				$this->_description_list->childs[] = $child->getDescription();
			}	
		}
		return $this->_description_list;
	}
}



