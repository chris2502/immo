<?php

/**
 *  @class XMLNode
 *  @Revision $Revision: 4687 $
 *
 */

abstract class XMLNode implements NodeInterface
{
	public $id; 		// id unique pour javascript
	public $attributes; // attributs du noeud
	public $name; 		// nom du noeud
	public $index;		// index dans le tableau de noeud

	protected $_current_data	= array();	// données du noeud
	public $_data_list		= array();	// tableau de données
	protected $_edition			= FALSE;	// (boolean) Sommes-nous en mode "édition" ?
	public $child_struct		= array();

	protected $_parent			= NULL;
	protected $_childs			= array();

	private $_parent_index; // index du parent dans le tableau de noeud

	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		$this->name			= $structure['markup'];
		$this->attributes	= $structure['attributes'];
		$this->child_struct	= $structure['value'];

		$this->id 			= $this->getNodeAttribute('id', preg_replace('/\./', '_', uniqid(get_class($this).'_', true)));
		$this->_parent		= $parent;
		$this->_view		= $view;

		$this->_edition = (isset($_GET['mode']) && $_GET['mode'] == 'edition') ? TRUE : FALSE;

		if(!is_array($structure) || !isset($structure['value']) || !is_array($structure['value']))
		{
			throw new Exception('Erreur de construction de l\'arbre des XMLNode sur la classe ' . get_class($this));
		}

		foreach($structure['value'] AS $element)
		{
			$node_name = $element['markup'];
			$classNode = ucfirst($node_name) . 'XMLNode';

			if(!class_exists($classNode))
			{
				//throw new Exception('Does not support ' . $node_name . ' Method');
				$node = new KilliUIXMLNode($element, $this, $this->_view);
				$this->_childs[] = $node;
				continue;
			}
			$node = new $classNode($element, $this, $this->_view);

			if ($node->_check_profile_node())
			{
				$this->_childs[] = $node;
			}
		}
	}

	public function isEdition()
	{
		return $this->_edition == TRUE;
	}

	public function render($data_list, $view)
	{
		/* Parameters. */
		$this->_data_list = &$data_list;
		$this->_view = $view;

		/* Récupération du mode édition à partir du parent. */
		if($this->_parent !== NULL)
		{
			$this->_edition = $this->_parent->isEdition();
		}

		/* On force le mode édition si demandé. */
		$force_edition	= $this->getNodeAttribute('force_edition', '') == '1';
		if($force_edition)
		{
			$this->_edition = $force_edition;
		}

		if(!$this->check_render_condition())
		{
			return FALSE;
		}

		/* Recursive Rendering. */
		if(headers_sent() === TRUE)
		{
			echo '<!-- Open ', $this->name, ' -->', PHP_EOL;
		}

		$this->open();
		foreach($this->_childs AS $child)
		{
			if($view == 'form')
			{
				$object = $this->getNodeAttribute('object', '', TRUE);
				if(!empty($object))
				{
					if(isset($_GET['primary_key']) && isset($data_list[$object][$_GET['primary_key']]))
					{
						$child->setData($data_list[$object][$_GET['primary_key']]);
					}
				}
			}

			try
			{
				$child->render($data_list, $view);
			}
			catch (Exception $e)
			{
				?><div class='norights'>Une erreur est survenue pendant l'exécution de cette partie de la page.<br/>Un technicien a été averti.</div><?php

				?><div style='display:none'><?php
					new NonBlockingException($e);
				?></div><?php
			}

		}
		$this->close();
		echo '<!-- Close ', $this->name, ' -->', PHP_EOL;
	}

	//.....................................................................
	// ouverture du noeud
	public function open()
	{
		return TRUE;
	}
	//.....................................................................
	// fermeture du noeud
	public function close()
	{
		return TRUE;
	}
	final public function setAttribute($attr_name, $value)
	{
		$this->attributes[$attr_name] = $value;
		return $this;
	}
	//.....................................................................
	// Valeur par défaut d'un field
	final protected function _getDefaultValue(&$default_value)
	{
		if (isset($_SESSION['_POST'][$this->attributes['object'].'/'.$this->attributes['attribute']]))
		{
			$default_value = $_SESSION['_POST'][$this->attributes['object'].'/'.$this->attributes['attribute']];
		}
		else if (isset($this->_current_data[$this->attributes['attribute']]) && (!empty($this->_current_data[$this->attributes['attribute']]['value']) || $this->_current_data[$this->attributes['attribute']]['value'] == 0))
		{
			$default_value = $this->_current_data[$this->attributes['attribute']]['value'];
		}
		else if (isset($this->attributes['value']))
		{
			$default_value = (int)$_GET[$this->attributes['value']];
		}
		else
		{
			$default_value = null;
		}
		return TRUE;
	}
	//.....................................................................
	// liste des enfants
	final public function getChildren()
	{
		return $this->_childs;
	}
	//.....................................................................
	final public function countChildren()
	{
		return count($this->_childs);
	}
	//.....................................................................
	// retourne l'instance XMLNode du noeud parent (si existante, sinon null)
	// Si $node_name est spécifié, déroule l'arborescence jusqu'à trouver le
	// noeud parent avec le nom node_name. Si non trouvé, retourne null.
	final public function getParent($node_name = NULL)
	{
		$parent = $this->_parent;
		if($node_name === NULL || $parent == NULL || $parent->name == $node_name)
		{
			return $parent;
		}

		return $parent->getParent($node_name);
	}

	final public function search($node_name)
	{
		if($this->name == $node_name)
		{
			return $this;
		}

		foreach($this->_childs AS $child)
		{
			$node = $child->search($node_name);
			if($node != NULL)
			{
				return $node;
			}
		}

		return NULL;
	}

	final public function getNodesByName($node_name)
	{
		$node_list = array();

		if($this->name == $node_name)
		{
			$node_list[] = $this;
		}

		foreach($this->_childs AS $child)
		{
			$node = $child->getNodesByName($node_name);
			$node_list = array_merge($node_list, $node);
		}

		return $node_list;
	}

	final public function getNodeById($node_id)
	{
		if($this->id == $node_id)
		{
			return $this;
		}

		foreach($this->_childs AS $child)
		{
			$node = $child->getNodeById($node_id);
			if($node !== NULL)
			{
				return $node;
			}
		}

		return NULL;
	}

	//.....................................................................
	final public function setData($current_data)
	{
		$this->_current_data = $current_data;
	}
	//.....................................................................
	public function getData(&$object_data)
	{
		return $this->_current_data;
	}
	//.....................................................................
	final public function getDataList()
	{
		return $this->_data_list;
	}
	//.....................................................................
	// génération de l'attribut de class
	final public function css_class($base_style = array())
	{
		if (isset($this->attributes['css_class']))
		{
			$base_style[]=$this->attributes['css_class'];
		}

		if(empty($base_style))
		{
			return null;
		}

		return ' class="'.implode(' ',$base_style) . '" ';
	}
	//.........................................................................
	private function _check_profile_node()
	{
		$profiles = $this->getNodeAttribute('profiles', '');
		$users = $this->getNodeAttribute('users', '');
		if (!empty($profiles))
		{
			$authorizedProfiles = explode(',', $profiles);
			$unauthorizedProfiles = array();
			foreach ($authorizedProfiles as $userProfile)
			{
				if (substr($userProfile, 0, 1) === "!")
				{
					$userProfile = constant(substr($userProfile, 1));
					if (in_array($userProfile, $_SESSION['_USER']['profil_id']['value']))
					{
						return false;
					}
					return true;
				}
				$userProfile = constant($userProfile);
				if (in_array($userProfile, $_SESSION['_USER']['profil_id']['value']))
				{
					return true;
				}
			}
		}
		else if(!empty($users))
		{
			$authorizedUsers = explode(',', $users);
			foreach ($authorizedUsers as $login)
			{
				if ($_SESSION['_USER']['login']['value'] == $login)
				{
					return true;
				}
			}
		}
		else
		{
			return true;
		}
		return false;
	}
	//.....................................................................
	public function check_render_condition()
	{
		if(!$this->_check_profile_node())
		{
			return FALSE;
		}

		$condition = $this->getNodeAttribute('render_condition', FALSE);
		if ($condition === FALSE)
		{
			return TRUE;
		}

		if(!empty($condition))
		{
			/* Construction de la condition à générer en PHP. */
			// And && Or
			$pattern = array('(\bAND\b)', '(\bOR\b)', '(\band\b)', '(\bor\b)');
			$replacement = array('&&', '||', '&&', '||');
			$condition = preg_replace($pattern, $replacement, $condition);

			// Variables
			/* On remplace le cas de {object/attribute} (vue form) */
			if(isset($_GET['primary_key']))
			{
				$pk = $_GET['primary_key'];

				$pattern4 = '`\{([a-zA-Z_0-9]+)/([a-zA-Z_0-9]+)/([a-zA-Z_0-9]+)/([a-zA-Z_0-9]+)\}`';
				$pattern3 = '`\{([a-zA-Z_0-9]+)/([a-zA-Z_0-9]+)/([a-zA-Z_0-9]+)\}`';
				$condition = preg_replace($pattern4, '\$this->_data_list[\'$1\'][\$pk][\'$2\'][\'$3\'][\'$4\']', $condition);
				$condition = preg_replace($pattern3, '\$this->_data_list[\'$1\'][\$pk][\'$2\'][\'$3\']', $condition);

				$pattern  = '((\{)(([a-zA-Z_0-9]+)(/)([a-zA-Z_0-9]+)*)(\}))';
				$replacement = '\$this->_data_list[\'$3\'][\$pk][\'$5\'][\'value\']';
				$condition = preg_replace($pattern, $replacement, $condition);
			}

			/* On remplace le cas de {object/attribute} dans un listing */
			$parent = $this->getParent();
			if($parent != NULL && isset($parent->data_key))
			{
				$pattern = '((\{)(([a-zA-Z_0-9]+))(\}))';
				$replacement = '\$this->_current_data[\'$3\'][\'value\']';
				$condition = preg_replace($pattern, $replacement, $condition);
			}

			/* On remplace le cas de {variable} */
			$pattern = '((\{)(([a-zA-Z0-9_]+))(\}))';
			$replacement = '\$this->_data_list[\'$3\']';

			$condition = 'return ('.preg_replace($pattern, $replacement, $condition).');';
			Debug::info($condition);

			if (eval($condition))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return TRUE;
		}
	}
	//.....................................................................
	// génération de l'attribut de style
	final public function style($base_style = array())
	{
		$style = '';

		//----------------------------------------------------------------
		// ajouter attribut attrs_type dans fichier xml
		if (isset($this->attributes['attrs_type']))
		{
			// Ajouts des class css pour document
			if( $this->attributes['attrs_type'] == 'document')
			{
				if(isset($this->_current_data[$this->attributes['attribute']]['value']))
				{
						$value_attr = $this->_current_data[$this->attributes['attribute']]['value'];
						$style .= ' class="document_etat document_' . strtolower($value_attr) . '" ';
				}
			}
		}
		//----------------------------------------------------------------

		foreach($base_style AS $rule => $value)
		{
			$style .= $rule . ':' . $value . ';';
		}

		if (isset($this->attributes['attrs']))
		{
			//preg_match_all('/[^,]+(\(?[^\)]+\))?[^,]+(\(?[^\)]+\))?/',  $this->attributes['attrs'], $matches);
			//$raw = $matches[0];

			$raw = explode(',', $this->attributes['attrs']);
			// TODO Trouver un moyen d'utiliser la , comme délimiter MAIS de pouvoir l'utiliser dans une string (par exemple, pour la valeur à comparer)...

			foreach($raw as $attrs)
			{
				$attrs = trim($attrs);
				$matches = array() ;
				if(preg_match( '/^([^:]+)\:([^:]+):([^;]+)\;(.*)$/', $attrs, $matches) != 0 )
				{
					$hObject = ORM::getObjectInstance($matches[1]);
					if(isset( $hObject->primary_key ) === true)
					{
						// gestion des multi-style
						$styles = explode(';', $matches[4]); // color:red;border:3px;height:100px
						foreach($styles as $sub_style)
						{
							$sub_style=explode(':', $sub_style,2); // color:red

							$valueToCompare = '';
							$compare_location = 'value';

							if(property_exists($hObject, $matches[2]) && $hObject->$matches[2]->type=="workflow_status")
							{
								$compare_location='workflow_node_id';
							}

							if(property_exists($hObject, $matches[2]) && ($hObject->$matches[2]->type=="time" || $hObject->$matches[2]->type=="date" || $hObject->$matches[2]->type=="datetime"))
							{
								$compare_location='timestamp';
							}

							if( isset( $this->_current_data[$hObject->primary_key][ 'value' ]  ) === false )
							{
								if( isset( $this->_data_list[ $matches[ 1 ] ] ) === true )
								{
									$data = array_slice( $this->_data_list[ $matches[ 1 ] ], 0 ) ;
									$valueToCompare = (isset($data[$matches[2]][$compare_location]))? $data[$matches[2]][$compare_location] : '';
								}
							}
							elseif( isset( $this->_current_data[ $matches[ 2 ] ][ $compare_location ] ) === true )
							{
								$valueToCompare = $this->_current_data[$matches[2]][$compare_location];
							}

							if(!is_array($valueToCompare))
							{
								$valueToCompare = array($valueToCompare);
							}
							$global_isOk = null;
							foreach($valueToCompare as $current_valueToCompare)
							{
								$isOk = false;

								if(preg_match('/^\{(.+)\}$/i',$matches[3], $null)!=0)
								{
									eval('$matches[3]='.substr($matches[3],1,-1).';');
								}

								//if (isset($matches[3]))
								{
									if (preg_match('/(\(lte?\)|\(gte?\)|\(\!\)){1}(.*)/i', $matches[3], $arMatches) != 0)
									{
										switch ($arMatches[1])
										{
											case '(!)':
												//$isOk = !(strcmp($arMatches[2],$current_valueToCompare)===0);
												$isOk = $arMatches[2] != $current_valueToCompare;
												break;
											case '(lt)':
												//$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare < $arMatches[2]);
												$isOk = ($current_valueToCompare != NULL && $current_valueToCompare < floatval($arMatches[2]));
												break;
											case '(lte)':
												//$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare <= $arMatches[2]);
												$isOk = ($current_valueToCompare != NULL && $current_valueToCompare <= floatval($arMatches[2]));
												break;
											case '(gt)':
												//$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare > $arMatches[2]);
												$isOk = ($current_valueToCompare != NULL && $current_valueToCompare > floatval($arMatches[2]));
												break;
											case '(gte)':
												//$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare >= $arMatches[2]);
												$isOk = ($current_valueToCompare != NULL && $current_valueToCompare >= floatval($arMatches[2]));
												break;
											default:
												throw new Exception('Unrecognized operator.');
												break;
										}
									}
									else
									{
										//$isOk = (strcmp($current_valueToCompare,$matches[3])===0);
										$isOk = $current_valueToCompare == $matches[3]; // double-égal suffisant...
									}
								}

								$global_isOk = (($global_isOk===null || $global_isOk===TRUE) && $isOk===TRUE);
							}
							if ($global_isOk===true)
							{
								$style .= $sub_style[0] . ':' . $sub_style[1] . ';';
							}
						}
					}
				}
			}
		}

		//@todo optimsation des css pris en charge ?

		if (isset($this->attributes['color']))
			$style .= 'color: '. $this->attributes['color'] . ' !important;';

		if (isset($this->attributes['display']))
			$style .= 'display: ' . $this->attributes['display'] . ' !important;';

		if (isset($this->attributes['background-color']))
			$style .= 'background-color: ' . $this->attributes['background-color'] . ' !important;';

		if (isset($this->attributes['width']))
			$style .= 'width: ' . $this->attributes['width'] . ' !important;';

		if (isset($this->attributes['height']))
			$style .= 'height: ' . $this->attributes['height'] . ' !important;';

		if (isset($this->attributes['text-align']))
			$style .= 'text-align: ' . $this->attributes['text-align'] . ' !important;';

		if (isset($this->attributes['padding']))
			$style .= 'padding: ' . $this->attributes['padding'] . ' !important;';

		if (isset($this->attributes['border']) && !isset($this->attributes['border_color']))
			$style .= 'border: ' . $this->attributes['border'] . ' !important;';

		if (isset($this->attributes['border']) && isset($this->attributes['border_color']))
			$style .= 'border: ' . $this->attributes['border'] . 'px solid ' . $this->attributes['border_color'] . ' !important;';

		if (isset($this->attributes['border_color']) && !isset($this->attributes['border']))
			$style .= 'border : 1px solid ' . $this->attributes['border_color'] . ' !important;';

		if($style=='')
		{
			return null;
		}

		return ' style="'.$style . '" ';
	}

	//.........................................................................
	// gestion des [%object.attribute] dans les chaines de caractères
	final public static function parseString($string, $data_list, $current_data)
	{
		$from_string = $string;

		$substitute_to_do = FALSE;

		//---On recherche les [%
		$position = mb_strstr($from_string,"[%");

		if ($position!="")
		{
			$substitute_to_do = TRUE;
		}

		while ($substitute_to_do)
		{
			//---Search "]"
			$end = strstr($position,"]");

			$to_replace = mb_substr($position,0,mb_strlen($position)-mb_strlen($end)+1);

			$raw =  explode(".",str_replace("]","",str_replace("[%","",$to_replace)));

			$table_name = $raw[0];
			$table_key  = $raw[1];
			//---Get data
			if (isset($data_list[$table_name][$table_key]))
			{
				$txt = $data_list[$table_name][$table_key];
				if(is_array($txt) && array_key_exists('value', $txt))
				{
					$data = $txt['value'];
				}
				else
				{
					$data = $txt;
				}
			}
			elseif (isset($current_data[$table_key]['value']))
			{
				$data = $current_data[$table_key]['value'];
			}
			elseif (isset($data_list[$table_name]))
			{
				$raw = array_slice($data_list[$table_name],0);

				if (isset($raw[0][$table_key]['reference']))
				{
					$data = $raw[0][$table_key]['reference'];
				}
				elseif (isset($raw[0][$table_key]['value']))
				{
					$data = $raw[0][$table_key]['value'];
				}
				else
				{
					$data = '';
				}
			}
			else
			{
				//---No data
				return TRUE;
			}

			//---Substitution
			$string = str_replace($to_replace,$data,$string);

			$from_string = $string;

			$position = mb_strstr($from_string,"[%");

			if (empty($position))
			{
				$substitute_to_do = FALSE;
			}
		}

		return $string;
	}

	//.........................................................................
	// gestion des [%object.attribute] dans le node courant
	final protected function _getStringFromAttributes(&$string)
	{
		$string = $this->getNodeAttribute('string','');
		$string = self::parseString($string, $this->_data_list, $this->_current_data);
		return TRUE;
	}
	//.....................................................................
	// initialise un attribut de noeud
	final public function getNodeAttribute($attribute, $default_value = ATTRIBUTE_HAS_NO_DEFAULT_VALUE, $from_parent = FALSE)
	{
		if(empty($attribute))
		{
			throw new Exception('getNodeAttribute need not empty attribute.');
		}

		if($default_value === ATTRIBUTE_HAS_NO_DEFAULT_VALUE)
		{
			if(!array_key_exists($attribute, $this->attributes))
			{
				if($from_parent)
				{
					$parent = $this;
					do
					{
						if(array_key_exists($attribute, $parent->attributes))
						{
							return $parent->attributes[$attribute];
						}
						$parent = $parent->_parent;
					} while($parent != NULL);
				}
				throw new XmlTemplateErrorException('\''.$this->name. '\' node need \'' . $attribute.'\' attribute !');
			}
		}

		if(isset($this->attributes[$attribute]))
		{
			return $this->attributes[$attribute];
		}

		if($from_parent)
		{
			$parent = $this;
			do
			{
				if(array_key_exists($attribute, $parent->attributes))
				{
					return $parent->attributes[$attribute];
				}
				$parent = $parent->_parent;
			} while($parent != NULL);
		}

		return $default_value;
	}
	//.....................................................................
	final protected function _getInputName(&$name)
	{
		if (isset($this->attributes['key']))
		{
			$key = $this->_current_data[$this->attributes['key']]['value'];
		}
		else
		{
			$key = '';
		}

		$prefix = '';
		if (!empty($this->attributes['object']))
		{
			$prefix = $this->attributes['object'].'/';
		}
		$name = $prefix.$this->attributes['attribute'].($key ? "/".$key:'');

		return TRUE;
	}
}

