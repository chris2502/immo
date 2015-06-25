<?php
/**
 * Composant d'interface pour Killi.
 *
 * Affiche des champs de donnée des tables imbriquées. ( Voir <nestedtable> )
 * <nestedfield
 * 		object="objet_nom"					OPTIONNEL (celui de la <nestedlisting> parent par défaut ):
 * 											Le nom de l'objet Killi affiché par la liste
 *
 * 		attribute="attr_nom"				Le nom du champs de l'objet que doit afficher le composant.
 *
 * 		sort="desc"							OPTIONNEL: Si définis, le champs sera utiliser comme clef de tri par
 * 											défaut. Prends pour valeur "asc" ou "desc".
 *
 * 		css_class="colored_field"			OPTIONNEL: Des classe CSSs à ajouter au composant.
 *
 * 		css_class_with_value="attr_nom"		OPTIONNEL: Permet d'ajouter au composant une classe CSS
 * 											lié à un champ de l'objet. La classe associé aura le nom suivant :
 * 											attr_nom_valeur_du_champs.
 *
 * 											Exemple :
 * 												css_class_with_value="objet_id"
 * 													donnera
 * 												<td class="objet_id_14">...
 *
 * 		quantity="0"						OPTIONNEL: Indique que la colonne correspond aux quantités selectionnées dans le cadre d'un
 * 											nestedimport.
 * />
 *
 * Ou des mecanismes propres aux nestedlistes.
 *
 * <nestedfield
 * 		type="empty | child_count | selected_count | open_marker | checkbox"
 *
 * 											Détermine le mécanisme à afficher.
 * 												empty : 		Une colonne vide de la même largeur
 * 																qu'une colonne de checkbox.
 * 												child_count :	Affiche le nombre d'enregistrement dans un enfants.
 * 																Le premier si l'enfant n'est pas précisé.
 * 																Affiche actuellement le nombre d'enfant actuellement sélectionnés
 * 																Si une sélection est possible.
 * 												open_marker	:	Affiche une icone signifiant si les enfants ont été dépliés.
 * 												checkbox :		Affiche une checkbox afin de permettre la selection d'enfants.
 *
 * 		expand="0"							OPTIONNEL, checkbox:	Détermine si les enfants doivent être dépliés lors de la sélection
 * 																	de la checkbox.
 * 		selected="is_selected"				OPTIONNEL, checkbox:	Le nom du champs dans la tableau Data dont le résultat d'interprétation
 * 																	par un isset détermine si la checkbox est pré-sélectionnée.
 * 		global_select="1"					OPTIONNEL, checkbox:	Doit-on afficher une checkbox dans le header permettant la sélection de
 *
 * @author boutillon
 *
 */
class NestedFieldXMLNode extends NestedXMLNode
{
	/**
	 * L'attribut de l'objet utilisé par le composant
	 *
	 * @var String
	 */
	protected $_attr;

	/**
	 * La description du parent du champs.
	 *
	 * @var Object
	 */
	protected $_description;

	/**
	 * Détermine si les sous-listes doivent s'étendre lors de la selection d'une checkbox.
	 *
	 * @var Object
	 */
	protected $_expand = false;

	/**
	 * Le composant d'oit-il offrir une possibilité de selection globale.
	 *
	 * @var Boolean
	 */
	protected $_global_select;

	/**
	 * Le champs d'un objet Killi auquel est lié le composant.
	 *
	 * @var FieldDefinition
	 */
	protected $_object_field;

	/**
	 * La colonne correspond-t'elle à la quantité d'un nested import.
	 *
	 * @var Boolean
	 */
	protected $_quantity;

	/**
	 * La checkbox doit-elle être pré_selectionnée. Prends la forme
	 * d'une string définissant le champs de Data ou trouver la réponse.
	 *
	 * @var String
	 */
	protected $_selected;

	/**
	 * L'ordre de tri par défaut du composant.
	 *
	 * @var String
	 */
	protected $_sort;

	/**
	 * La clef du tableau data contenant les données concernant
	 * les tables dont on désire le compte des lignes.
	 *
	 * @var String
	 */
	protected $_src;

	/**
	 * Comment ouvrir le lien si le composant est supposé en afficher un.
	 *
	 * @var String
	 */
	protected $_target;

	/**
	 * Le type de composant à afficher.
	 *
	 * @var String
	 */
	protected $_type;

	/**
	 * Détermine si la valeur du champs doit être incluse dans un attribut "data-value".
	 * Notamment utilisé par les fonctions tri.
	 *
	 * @var Boolean
	 */
	protected $_data_value;

	/**
	 * Détermine si la checkbox doit être inversée.
	 */
	protected $_inverted;

	/**
	 * Affiche le composant sous la forme d'une checkbox.
	 *
	 * @return true;
	 */
	protected function _renderCheckbox($value)
	{
		if (is_array($value))
		{
			throw new Exception('Le composant NestField n\'accepte pas de tableau en valeur pour les types checkbox');
		}
		if ($this->_inverted)
		{
			$value = !$value;
		}

		if ($value == '1')
		{
			return '<div style="text-align: left;"><span class="field_value" style="display:none;">1</span><img src="'.KILLI_DIR.'/images/true.png"></div>';
		}
		else
		{
			return '<div style="text-align: left;"><span class="field_value" style="display:none;">0</span><img src="'.KILLI_DIR.'/images/false.png"></div>';
		}
	}

	/**
	 * Ajoute le rendu du composant au tableau $stack.
	 *
	 * @param	Int			$key		La clef primaire de l'élément à afficher.
	 * @param	Array		$data		Le tableau de données à utiliser.
	 */
	public function render2(&$key, &$data)
	{
		if ($this->_type)
		{
			$this->_renderTyped($key, $data);
		}
		else
		{
			if (!isset($data[$this->_attr]))
			{
				$data_node = array('value' => null);
			}
			else
			{
				$data_node = $data[$this->_attr];
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

			echo '<td ',$this->_css($data),' ',$this->style();
			if ($this->_data_value == 1)
			{
				echo ' data-value="'.$data_node['value'].'"';
			}
			if ($this->_data_value && isset($data_node[$this->_data_value]))
			{
				echo ' data-value="'.$data_node[$this->_data_value].'"';
			}
			echo '>';
			switch($this->_object_field->type)
			{
				case 'date':
					echo date('d/m/Y',is_numeric($data_node['value']) ? $data_node['value'] : strtotime($data_node['value']));
					break;
				case 'checkbox':
					echo $this->_renderCheckbox($data_node['value']);
					break;
				case 'extends':
				case 'primary key':
					$this->_renderLink($data_node['value'], $this->_object_name, $reference);
					break;
				case 'many2one':
				case 'one2many':
					$obj_rel = strtolower($this->_object_field->object_relation);
					$this->_renderLink($data_node['value'], $obj_rel, $reference);
					break;
				default:
					if (is_array($reference))
					{
						echo implode (', ', $reference);
					}
					else
					{
						echo $reference;
					}
			}
			echo '</td>';
		}
		return true;
	}

	/**
	 * Effectue le rendu des liens.
	 *
	 * Si la valeur de l'élément est un tableau, alors la fonction va générer une suite de
	 * liens séparés par des virgules.
	 *
	 * @object	String / Array	$value			Les clefs primaires à associés aux liens.
	 * @object	String			$object			L'objet sur lequel redirige le liens.
	 * @param	String / Array	$reference		La reference à afficher pour le liens. Si c'est un tableau pour un tableau de valeurs,
	 * 											alors la fonction va chercher la reference associée à la valeur.
	 */
	protected function _renderLink($value, $object, $reference)
	{
		if (!ORM::getObjectInstance($object)->view)
		{
			echo $reference;
			return TRUE;
		}

		echo '<div>';
		if (is_array($value) && is_array($reference))
		{
			$link_list = array();
			if (is_array($reference))
			{
				foreach ($value as $key => $v)
				{
					$link_list[] = $this->_generateLink($v, $object, $reference[$key]);
				}
			}
			else
			{
				foreach ($value as $key => $v)
				{
					$link_list[] = $this->_generateLink($v, $object, $reference);
				}
			}
			echo implode(', ', $link_list);
		}
		else
		{
			echo $this->_generateLink($value, $object, $reference);
		}
		echo '</div>';
		return TRUE;
	}

	/**
	 * Génère le liens à proprement parler.
	 *
	 * @object	String / Array	$value			Les clefs primaires à associés aux liens.
	 * @object	String			$object			L'objet sur lequel redirige le liens.
	 * @param	String / Array	$reference		La reference à afficher pour le liens.
	 */
	protected function _generateLink($key, $object, $reference)
	{
		Security::crypt($key, $crypted_key);
		switch ($this->_target)
		{
			case '_blank':
			return '<a href="index.php?action='.$object.'.edit&amp;view=form&amp;crypt/primary_key='.$crypted_key.'&amp;token='.$_SESSION['_TOKEN'].'" target="_blank">'.$reference.'</a>';
			break;

			case 'popup':
			$url = 'index.php?action='.$object.'.edit&amp;inside_popup=1&amp;view=form&amp;crypt/primary_key='.$crypted_key.'&amp;token='.$_SESSION['_TOKEN'];
			return '<a data-url="'.$url.'" data-id="'.$crypted_key.'">'.$reference.'</a>';
			break;

			default:
			return '<a href="index.php?action='.$object.'.edit&amp;view=form&amp;crypt/primary_key='.$crypted_key.'&amp;token='.$_SESSION['_TOKEN'].'">'.$reference.'</a>';
			break;
		}
		return TRUE;
	}

	/**
	 * Affiche le rendu du Header du composant au tableau $stack.
	 */
	public function renderHeader()
	{
		$label = $this->getNodeAttribute('label', '');
		if ($this->_type)
		{
			$this->_renderTypedHeader();
		}
		else
		{
			$css_class_list = array('sortable');
			if ($this->_quantity)
			{
				$css_class_list[] = 'quantity';
			}

			echo '<th class="', implode(' ', $css_class_list), '" ';
			if ($this->_sort)
			{
				echo 'data-sort="',$this->_sort,'" ';
			}
			if (!empty($label))
			{
				echo '>',$label,'</th>';
			}
			else
			{
				echo '>',$this->_object_field->name,'</th>';
			}
		}
		return true;
	}

	/**
	 * Ajoute le rendu du composant au tableau $stack.
	 *
	 * @param	Int			$key		La clef primaire de l'élément à afficher.
	 * @param	Array		$data		Le tableau de données à utiliser.
	 */
	protected function _renderTyped(&$key, &$data)
	{
		switch($this->_type)
		{
			case 'empty':
				echo '<td class="checkbox"></td>';
				break;
			case 'child_count':
				if (isset($data[$this->_description->src]) && ($count = count($data[$this->_description->src]) > 0))
				{
					echo '<td class="count">'.count($data[$this->_description->src]).'</td>';
				}
				else
				{
					echo '<td class="count"></td>';
				}
				break;
			case 'message':
				if (isset($data[$this->_src]))
				{
					if (is_array($data[$this->_src]))
					{
						$msg = implode('<br />', $data[$this->_src]);
					}
					else
					{
						$msg = $data[$this->_src];
					}
				}
				else
				{
					$msg = '';
				}
				echo '<td>'.$msg.'</td>';
				break;
			case 'open_marker':
				echo '<td class="open_marker"><div class="ui-icon"></div></td>';
				break;
			case 'checkbox':
				$this->_css_list = array('checkbox');
				echo '<td ',$this->_css($data),' ',$this->style(), '>';
				echo '<input';
				if ($this->_expand)
				{
					echo ' class="expand" ';
				}
				echo ' type="checkbox" autocomplete="off" />';
				echo '</td>';
			break;
			case 'radio':
				echo '<td class="radio">';
				echo '<input';
				if ($this->_expand)
				{
					echo ' class="expand" ';
				}
				echo ' name="'.$this->_object_name.'/" ';
				echo ' type="radio" autocomplete="off" />';
				echo '</td>';
				break;
			case 'total':
				$ex = explode('.',$this->_src);
				if (!isset($ex[2]))
				{
					$ex[2] = 'value';
				}
				$row = 0;
				$total = 0;
				foreach ($data[$ex[0]] as $tmp)
				{
					$row++;
					$total += $tmp[$ex[1]][$ex[2]];
				}
				if ($row == 0)
				{
					echo '<td '.$this->css_class(array('nestedtotal')).'>';
				}
				else
				{
					echo '<td '.$this->css_class(array('nestedtotal')).' data-value="'.$total.'">';
					echo number_format($total,2,',',' '),' €';
				}
				echo '</td>';
			break;
		}

		return TRUE;
	}
	/**
	 * Affiche le rendu du Header du composant au tableau $stack.
	 *
	 */
	protected function _renderTypedHeader()
	{
		switch($this->_type)
		{
			case 'message':
				echo '<th>'.(($this->_title) ? $this->_title : '').'</th>';
				break;
			case 'empty':
				echo '<th class="checkbox"></th>';
				break;
			case 'child_count':
				$description_list = $this->_parent->getDescription();
				$this->_description = $description_list->childs[0];
				if ($this->_src)
				{
					foreach($description_list->childs as $description)
					{

						if ($description->src == $this->_src)
						{
							$this->_description = $description;
						}
					}
				}
				echo '<th class="sortable">'.$this->_description->desc.'</th>';
				break;
			case 'open_marker':
				echo '<th></th>';
				break;
			case 'checkbox':
				if ($this->_global_select)
				{
					echo '<th class="checkbox"><input type="checkbox" autocomplete="off" /></th>';
				}
				else
				{
					echo '<th class="checkbox"></th>';
				}
				break;
			case 'radio':
					echo '<th class="radio"></th>';
				break;
			case 'total':
				echo '<th class="sortable">Totaux</th>';
			break;
		}
		return TRUE;
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
		$this->_type = $this->getNodeAttribute('type', null);
		if ($this->_type)
		{
			$this->_src = $this->getNodeAttribute('src', false);
			$this->_expand = $this->getNodeAttribute('expand', false);
			$this->_selected = $this->getNodeAttribute('selected', false);
			$this->_global_select = $this->getNodeAttribute('global_select', 1);
			$this->_title = $this->getNodeAttribute('title', false);

			if ($this->_type == 'checkbox')
			{
				$this->_parent->selectbox = true;
			}
		}
		else
		{
			$attr = $this->_attr = $this->getNodeAttribute('attribute');
			$hObject = ORM::getObjectInstance($this->_object_name);
			$this->_object_field = $hObject->$attr;
			$this->_src = $this->getNodeAttribute('src', false);
			$this->_sort = $this->getNodeAttribute('sort', null);
			$this->_target = $this->_getInheritedAttribute('target', false);
			$this->_quantity = $this->getNodeAttribute('quantity', false);
			$this->_data_value = $this->getNodeAttribute('data-value', false);
			$this->_inverted = $this->getNodeAttribute('inverted', false);
		}
		return true;
	}


}

