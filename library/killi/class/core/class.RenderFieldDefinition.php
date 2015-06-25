<?php

/**
 *  Classe de rendu d'un attribut
 *
 *  @package killi
 *  @class RenderFieldDefinition
 *  @Revision $Revision: 4638 $
 *
 */

abstract class RenderFieldDefinition
{
	protected $field = NULL;
	protected $node = NULL;

	//--------------------------------------------------------------------
	public function __construct(NodeInterface $node, FieldDefinition $field)
	{
		$this->field = $field;
		$this->node = $node;
	}
	//.........................................................................
	protected function _attributesTo($from, $base_style)
	{
		$style = $this->node->getNodeAttribute($from, '');
		if (!empty($style))
		{
			$base_style[] = $style;
		}

		if(empty($base_style))
		{
			return null;
		}

		return ' class="'.implode(' ',$base_style) . '"';
	}

	protected function _attributesToClass($base_style = array())
	{
		return $this->_attributesTo('css_class', $base_style);
	}

	protected function _fieldToClass($base_style = array())
	{
		return $this->_attributesTo('label_class', $base_style);
	}
	//--------------------------------------------------------------------
	/**
	 * Rendu du filtre des vues list et des listing
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function renderFilter($name, $selected_value)
	{
		return TRUE;
	}
	//--------------------------------------------------------------------
	/**
	 * Rendu de la valeur du field (vue form, list, listing, etc)
	 *
	 * @return boolean
	 */
	public function renderValue($value, $input_name, $field_attributes)
	{
		?><div></div><?php

		return TRUE;
	}
	//--------------------------------------------------------------------
	/**
	 * Rendu du champ de formulaire pour l'édition de la valeur du field (vue form, list, listing, etc)
	 *
	 * @return boolean
	 */
	public function renderInput($value, $input_name, $field_attributes)
	{
		return $this->renderValue($value, $input_name, $field_attributes);
	}
	//--------------------------------------------------------------------
	/**
	 * Rendu de la valeur du field (vue form, list, listing, etc)
	 * gestion du mode édition, des droits de lecture et d'écriture.
	 *
	 * @return boolean
	 */
	public function renderField($value, $input_name, $mode_edition)
	{
		$object = $this->node->getNodeAttribute('object', NULL, TRUE);
		$attr = $this->node->getNodeAttribute('attribute');
		$readonly = $this->node->getNodeAttribute ('readonly', FALSE, TRUE);
		$attribute_is_editable = (isset($value['editable'])) ? $value['editable'] : TRUE;

		$read = true;
		$write = true;

		if (!empty($object))
		{
			$hObj = ORM::getObjectInstance($object);
			if (property_exists($hObj, $attr))
			{
				$attribute_is_editable =  $attribute_is_editable AND $hObj->$attr->editable;
				Rights::getRightsByAttribute ( $this->field->objectName, $attr, $read, $write );
			}
		}

		$write = ($mode_edition || ($this->node->_view == 'create') || ($this->node->_view == 'panel')) && $attribute_is_editable && $write && !$readonly;
		$this->_update		= $this->node->getNodeAttribute('update', '');
		$this->_onchange	= $this->node->getNodeAttribute('onchange', '');
		$field_attributes = array();

		$events_str = $this->node->getNodeAttribute('events', '');
		if(!empty($events_str))
		{
			$events = explode('|e|', $events_str);

			foreach($events as $event)
			{
				$raw = explode('::', $event);

				if(!isset($raw[1]))
				{
					continue;
				}

				$type = 'on' . $raw[0];
				$funcs = explode('|f|', $raw[1]);
				$funcstr = array();
				$this->_set_func_javascript($funcs, $funcstr);
				foreach($funcstr AS $key => $func)
				{
					$field_attributes[$type][] = str_replace('();', '', $func);
				}
			}
		}

		if(!empty($this->_onchange))
		{
		  $onchange = str_replace('();', '', $this->_onchange);
		  $field_attributes['onchange'][] = $onchange;
		}

		if(!empty($this->_update))
		{
			$field_attributes['onchange'][] = 'update_' . $this->node->id;
		}

		if($write)
		{
			$this->renderInput($value, $input_name, $field_attributes);
		}
		else
		if($read)
		{
			$refresh = $this->node->getNodeAttribute('refresh', '0');
			$refresh_class = array();
			$refreshClass = '';
			if($refresh != '0' && (isset($_GET['crypt/primary_key']) || isset($this->node->parent_id)))
			{
				$parameters = '';
				foreach($field_attributes AS $event => $foncs)
				{
					$parameters .= $event . '="';
					foreach($foncs AS $key => $fonc)
					{
						$parameters .= $fonc . '($(this));';
					}
					$parameters .= '"';
				}

				$data = array();
				foreach($this->node->attributes AS $attribute => $v)
				{
					switch($attribute)
					{
						case 'parent_object':
						case 'crypt/parent_object_id':
						case 'input_name':
							continue;
						default:
							$data[$attribute] = $v;
					}
				}

				$obj = $this->node->getNodeAttribute('object', NULL, TRUE);
				$parent = $this->node->getParent();
				$p_obj = $parent->getNodeAttribute('object', $obj);
				$p_id = NULL;
				if(isset($this->node->parent_id))
				{
					Security::crypt($this->node->parent_id, $p_id);
				}
				else
				{
					$p_id = $_GET['crypt/primary_key'];
				}

				$key = md5($p_obj . $p_id . $obj . $attr);
				$refreshClass = ' name="'. $input_name . '" key="' . $key . '" ' . $parameters;
				$data['parent_object'] = $p_obj;
				$data['crypt/parent_object_id'] = $p_id;
				$data['input_name'] = $input_name;

				UI::setRefreshField($refresh, $key, $data);

				$refresh_class = array('refresh','field_value');
			}

			$old_bg_color =  $this->node->getNodeAttribute('background-color', NULL);
			if(isset($value['bgcolor']) && !empty($value['bgcolor']))
			{
				$this->node->attributes['background-color'] = $value['bgcolor'];
			}

			?><div id="<?= $this->node->id ?>" <?= self::_attributesToClass($refresh_class) ?><?= $this->node->style() ?><?= $refreshClass; ?>><?php

			$this->renderValue($value, $input_name, $field_attributes);

			?></div><?php

			$this->node->attributes['background-color'] = $old_bg_color;
		}
		else
		{
			?><img style="opacity: 0.3;" width="14" src="./library/killi/images/stop.png"/><?php
		}

		if(!empty($this->_update))
		{
			$elements = explode(' ', $this->_update);
			?>
			<script>
				function update_<?= $this->node->id ?>(field)
				{
					var new_value = "";
					if(field[0] && field[0].options)
					{
						$('#<?= $this->node->id ?> option:selected').each(function() {
							new_value += ' ' + $(this).attr('value');
						});
					}
					else
					{
						new_value = $.trim($(field).val());
					}
					<?php foreach($elements AS $key => $val ) { ?>
					$('#<?= $val ?>').updateAttribute('<?php echo $this->node->getNodeAttribute('attribute') ?>', new_value);
					<?php } ?>
				}
			</script>
			<?php
		}

		return TRUE;
	}
	//--------------------------------------------------------------------
	protected function _set_func_javascript($funcs, &$funcstr)
	{
		foreach($funcs as $func)
		{
			$fdata = explode(':', $func);
			$fargs = explode(',', $fdata[1]);

			$args = array();
			foreach($fargs as $farg)
			{
				if($farg)
					$args[] = "'".$farg."'";
			}

			$funcstr[] = $fdata[0].'('.join(',',$args).');';
		}
	}
}
