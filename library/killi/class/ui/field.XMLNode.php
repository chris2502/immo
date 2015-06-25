<?php

/**
 *  @class FieldXMLNode
 *  @Revision $Revision: 4658 $
 *
 */

class FieldXMLNode extends XMLNode
{
	public $no_label = FALSE;

	protected $_id;
	protected $_label;
	protected $_name;
	protected $_object;
	protected $_attribute;
	protected $_type;
	protected $_value;
	protected $_object_instance;
	protected $_field_definition;
	protected $_renderer_name;
	protected $_render;

	protected $_editable;
	protected $_default_value;
	protected $_tooltip;
	protected $_onchange;
	protected $_update;

	protected $_parent_id;

	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		parent::__construct($structure, $parent, $view);
// 		if(array_key_exists('__HTML', $data_list) && array_key_exists($this->id, $data_list['__HTML']))
// 		{
// 			$this->attributes = array_merge($this->attributes, $data_list['__HTML'][$this->id]);
// 		}
		$this->_label         = $this->getNodeAttribute('label', '');
		$this->_name          = $this->getNodeAttribute('name', '');
		$this->_type          = $this->getNodeAttribute('type', '');
		$this->_update        = $this->getNodeAttribute('update', '');
		$this->_onchange      = $this->getNodeAttribute('onchange', '');
		$this->_tooltip       = $this->getNodeAttribute('tooltip', '');
		$this->_renderer_name = $this->getNodeAttribute('render', '');
		$this->_value         = $this->getNodeAttribute('value', '');

		$default_object		= '';
		if(empty($this->_type))
		{
			$default_object = ATTRIBUTE_HAS_NO_DEFAULT_VALUE;
		}

		$this->_object		= $this->getNodeAttribute('object', $default_object, TRUE);
		$this->_attribute	= $this->getNodeAttribute('attribute');

		/* Cas d'un champ lié à un objet. */
		if(!empty($this->_object))
		{
			$this->_object_instance = ORM::getObjectInstance($this->_object, FALSE);

			if(property_exists($this->_object_instance, $this->_attribute))
			{
				$this->_constructAsDependant();
			}
			else
			{
				if ($this->_type == '' OR  $this->_label == '')
				{
					throw new NoPropertyException($this->_attribute . ' n\'existe pas dans ' . get_class($this->_object_instance));
				}
				else
				{
					$this->_constructAsIndependant();
				}
			}
		}
		/* Cas d'un champ indépendant. */
		else
		{
			$this->_constructAsIndependant();
		}
		
		/* Appel du renderFieldDefinition approprié. */
		$render_name = $this->getNodeAttribute('render', $this->_field_definition->render) . 'RenderFieldDefinition';

		if(!Autoload::loadClass($render_name))
		{
			throw new Exception('Pas de render pour de type : ' . $render_name );
			
			return FALSE;
		}

		$this->_render = new $render_name($this, $this->_field_definition);
		$this->_id = $this->id;
	}

	/**
	 * Initialize le champs comme étant indépendant,
	 * c'est à dire non rattaché à un objet.
	 */
	protected function _constructAsIndependant()
	{
		$this->_editable = TRUE;
		$this->_default_value = $this->getNodeAttribute('default_value', '');
		$this->_required = $this->getNodeAttribute('required', FALSE);

		if ($this->_type == 'array' || $this->_type == 'enum')
		{
			$this->_field_definition = EnumFieldDefinition::create()
					->setLabel($this->getLabel())
					->setDefaultValue($this->_default_value)
					->setValues($this->_value)
					->setRequired($this->_required);
		}
		else
		{
			$FieldDefinition_name =  $this->_type.'FieldDefinition';
			
			$this->_field_definition = $FieldDefinition_name::create()
					->setLabel($this->getLabel())
					->setDefaultValue($this->_default_value)
					->setRequired($this->_required);
		}
		
		$this->_edition = TRUE;
		$this->_parent_id = NULL;
		$this->_type	= $this->_field_definition->type;

		return TRUE;
	}

	/**
	 * Initialize le champs comme étant dépendant d'un objet.
	 */
	protected function _constructAsDependant()
	{
		$this->_field_definition = $this->_object_instance->{$this->_attribute};

		$this->_type = $this->getNodeAttribute('type', $this->_field_definition->type);
		$this->_tooltip = $this->getNodeAttribute('tooltip', $this->_field_definition->description);
		
		$this->attributes['tooltip'] = $this->_tooltip;

		$this->_parent_id = isset($_GET['crypt/primary_key']) ? array('object' => $this->_object, 'id' => $_GET['crypt/primary_key']) : NULL;
		
		return TRUE;
	}

	public function getLabel()
	{
		if(empty($this->_label))
		{
			$hInstance	= ORM::getObjectInstance($this->_object);
			return $hInstance->{$this->_attribute}->name;
		}
		return $this->_label;
	}

	//.....................................................................
	final public function _getClass($attribute, &$class = '')
	{
		if(isset($_SESSION['_ERROR_LIST'][$this->_object.'/'.$this->_attribute]))
		{
			$class = ' class="error_field" ';
		}
		else if ($attribute->required == 1)
		{
			$class = ' class="required_field" ';
		}

		return TRUE;
	}

	public function renderFilter()
	{
		$attr	= $this->_attribute;
		$name	= 'search/' . $this->_object . '/' . $this->_attribute;

		$rclass  = '';
		$loop	= true;
		$value = '';

		if(!empty($_REQUEST[$name]) || (isset($_REQUEST[$name]) && $_REQUEST[$name] == '0'))
		{
			$value = $_REQUEST[$name];
		}

		if(!empty($_REQUEST['crypt/' . $name]) && ($_REQUEST['crypt/' . $name] == 'NULL' || $_REQUEST['crypt/' . $name] == 'NOT NULL'))
		{
			$value = $_REQUEST['crypt/' . $name];
		}

		if(isset($this->_render) && !$this->_field_definition->search_disabled)
		{
			$this->_render->renderFilter($name, $value);
		}

		return TRUE;
	}

	public function renderInput()
	{
		$attr	= $this->_attribute;
		$name	= 'input/' . $this->_object . '/' . $this->_attribute . '[]';

		$rclass  = '';
		$loop	= true;
		$value = '';

		if(isset($_POST[$name]))
		{
			$value = $_POST[$name];
		}

		if(isset($this->_render))
		{
			$this->_render->renderInput(array('value' => $value, 'editable' => 1), $name, array());
		}

		return TRUE;
	}

	//.........................................................................
	private function _attributesTo($from, $base_style)
	{
		if (isset($this->_attributes[$from]))
		{
			$base_style[] = $this->_attributes[$from];
		}

		if(empty($base_style))
		{
			return null;
		}

		return ' class="'.implode(' ',$base_style) . '"';
	}

	private function _fieldToClass($base_style = array())
	{
		return $this->_attributesTo('label_class', $base_style);
	}

	public function open()
	{
		// Gestion de la valeur par défaut dans un contexte de création.
		if (!is_null($this->_field_definition->default_value) && isset($_GET['view']) && $_GET['view'] == 'create')
		{
			$_GET[$this->_attribute] = $this->_field_definition->default_value;
		}

		$show_label = $this->getNodeAttribute('nolabel', '0') == '0';
		$inside_listing = $this->getNodeAttribute('inside_listing', '0') == '1';

		$tooltip_value = isset($this->_current_data[$this->_attribute]['tooltip']) ? $this->_current_data[$this->_attribute]['tooltip'] : '';
		if($show_label && !$inside_listing)
		{
			?><table class="field" cellspacing="2" cellpadding="1" id="table_<?php echo $this->id; ?>"><tr><?php
				if (empty($this->_tooltip))
				{
					?><td <?php echo self::_fieldToClass(array('field_label'));?>><?php echo $this->getLabel() ?> : </td><?php
				}
				else
				{
					?><td <?php echo self::_fieldToClass(array('field_label'));?>><span title="<?= $this->_tooltip ?>" class="tooltip_link"><?= $this->getLabel() ?> : </span></td><?php
				}

			?><td><?php

			if(!empty($tooltip_value))
			{
				?><div title="<?= $tooltip_value ?>"><?php
			}
		}

		if(isset($this->_render))
		{
			$this->_name = (!empty($this->_object) ? $this->_object . '/' : '') . $this->_attribute;

			// @TODO: Code à revoir sur la récupération des données.
			if($this->_view != 'create')
			{

				if($inside_listing)
				{
					$parent_key = $this->getParent()->getNodeAttribute('key', $this->getParent()->data_key);
					$this->_name = $this->_object . '/' . $this->_attribute . '/' . $this->_current_data[$parent_key]['value'];
					$this->id = $this->_id . '_' . $this->_current_data[$parent_key]['value'];
					$this->parent_id = $this->_current_data[$parent_key]['value'];
				}
				else
				// Vue formulaire
				if(isset($this->_data_list[$this->_object][$this->_attribute]))
				{
					$this->_current_data = $this->_data_list[$this->_object];
				}
				else
				if(isset($this->_current_data))
				{
					// Ne rien fait si current_data est déjà défini.
				}
				else
				if(count($this->_data_list[$this->_object]) == 1)
				{
					$this->_current_data = reset($this->_data_list[$this->_object]);
				}
				// Vue liste (par défaut)
				else
				{
					$parent_key = $this->getParent()->getNodeAttribute('key', $this->_object_instance->primary_key);
					$this->_name = $this->_object . '/' . $this->_attribute . '/' . $this->_current_data[$parent_key]['value'];
					$this->id = $this->_id . '_' . $this->_current_data[$parent_key]['value'];
				}
			}

			//---Default value
			//$this->_getDefaultValue($this->_default_value);

			//---Process ENV
			if(isset($_GET[$this->_attribute]) && !$inside_listing)
			{
				$this->_current_data[$this->_attribute]['value'] = $_GET[$this->_attribute];
			}

			if(isset($_POST[$this->_object.'/'.$this->_attribute]) && !$inside_listing)
			{
				$this->_current_data[$this->_attribute]['value'] = $_POST[$this->_object.'/'.$this->_attribute];
			}

			if(isset($_SESSION['_POST'][$this->_object.'/'.$this->_attribute]) && !$inside_listing)
			{
				$this->_current_data[$this->_attribute]['value'] = $_SESSION['_POST'][$this->_object.'/'.$this->_attribute];
			}

			if(!isset($this->_current_data[$this->_attribute]))
			{
				$this->_current_data[$this->_attribute] = array('value' => '', 'editable' => TRUE);
			}

			if($this->_view != 'create' && !isset($this->_current_data[$this->_attribute]) && !TOLERATE_MISMATCH)
			{
				throw new Exception('Impossible de definir $pk, verifier l\'existence de '.$this->_attribute.' dans la base.');
			}

			//---Render field !
			$this->_render->renderField($this->_current_data[$this->_attribute], $this->_name, $this->_edition);
		}
		else
		{
			throw new Exception('Pas de render pour le field \'' . $this->_attribute . '\' de type ' . $this->_type);
		}

		$focus = $this->getNodeAttribute('focus', '0') == '1';
		if($focus)
		{
			?>
			<script>
				document.getElementById( '<?php echo $this->id ?>' ).focus() ;
			</script>
			<?php
		}

		if($show_label && !$inside_listing)
		{
			if(!empty($tooltip_value))
			{
				?></div><?php
			}

			?></td></tr></table><?php
			$this->_render_error_field($this->_field_definition, ((!is_array($this->_type)) && (mb_substr($this->_type,0,8)==='many2one')));
		}
	}

	private function _render_error_field($attribute, $ref = False)
	{
		?><table class="field" cellspacing="2" cellpadding="1"><?php
			?><tr><?php
			?><td width="40%" id="<?php echo $this->_object.'_'.$this->_attribute.($ref===true ? "reference":""). "_error_img"; ?>"><?php
			?></td><?php
			?><td><?php
				?><div id="<?php echo $this->_object."_".$this->_attribute.($ref===true ? "reference":"")."_error"; ?>" class="error_str"></div><?php
			?></td><?php
			?></tr><?php
		?></table><?php
	}
}
