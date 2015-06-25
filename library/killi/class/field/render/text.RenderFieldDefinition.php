<?php

/**
 *  @class TextRenderFieldDefinition
 *  @Revision $Revision: 4515 $
 *
 */

class TextRenderFieldDefinition extends RenderFieldDefinition
{
	public $html5_type = 'text';

	public function renderFilter($name, $selected_value)
	{
		?><table class='ui-filter ui-filter-text'><?php
			?><tr><?php
				?><td><?php
					?><input type='hidden' name="<?= $name.'/op' ?>" value='LIKE'/><?php
					?><input class="search_input" value="<?= htmlentities($selected_value,ENT_COMPAT,'UTF-8') ?>" name="<?= $name ?>" id="search_<?= $this->node->id ?>"/><?php
				?></td><?php

			if($selected_value != '')
			{
				?><td class='ui-filter-clear'><?php
					?><img onclick="$('#search_<?= $this->node->id ?>').val('');trigger_search($('#search_<?= $this->node->id ?>'));" src='library/killi/images/delete.png'/><?php
				?></td><?php
			}

			?></tr><?php
		?></table><?php

		$autocomplete = $this->node->getNodeAttribute('autocomplete', '');

		if ($autocomplete != '')
		{
			$object_name='';
			$field_name='';
			if(preg_match('/^(.*)\.(.*)$/', $autocomplete, $matches))
			{
				list($ereg, $object_name, $field_name)=$matches;
			}
			else
			{
				$object_name = $autocomplete;
			}

			$hInstance = ORM::getObjectInstance($object_name);
			if(!$hInstance->$field_name->search_disabled)
			{
				$attr_domain = '';
				if (!empty($hInstance->$field_name->domain))
				{
					$crypt_domain = NULL;
					Security::crypt(serialize($hInstance->$field_name->domain),$crypt_domain);
					$attr_domain = "&crypt/domain=".$crypt_domain;
				}

				?><script>
					$(function(){
						$( "#search_<?= $this->node->id ?>" ).autocomplete({
							source: function( request, response ) {
								$.ajax({
									url: "./index.php?action=<?= $object_name ?>.autocomplete&field=<?= $field_name ?>&search="+$('#search_<?= $this->node->id ?>').val()+"&token=<?= $_SESSION['_TOKEN'] ?><?= $attr_domain ?>",
									dataType: "json",
									success: function( json )
									{
										response (json.data);
									}
								});
							},
							minLength: 2,
							autoFocus:1,
							select: function( event, ui )
							{
								$('#search_<?= $this->node->id ?>').val(ui.item.label);

								trigger_search($('#search_<?= $this->node->id ?>'));
							}
						});
					});
				</script><?php
			}
			else
			{
				$autocomplete = '';
			}
		}

		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		$use_reference	= $this->node->getNodeAttribute('reference', true);
		$default_value	= (isset($value['reference']) AND $use_reference) ? $value['reference'] : $value['value'];
		$unit			= $this->node->getNodeAttribute('unit', '');
		$refresh		= $this->node->getNodeAttribute('refresh', '0');
		$format			= $this->node->getNodeAttribute('format', '%s');
		$refresh_class	= array();

		$attr = $this->node->getNodeAttribute('attribute');
		
		if((string)$default_value == '')
		{
			?><div></div><?php

			return TRUE;
		}

		if (isset($_GET[$attr]))
		{
			$crypt_value = NULL;
			Security::crypt($default_value,$crypt_value);
			?><input type="hidden" name="crypt/<?= $input_name ?>" id="crypt_<?= $this->node->id ?>"  value="<?= $crypt_value ?>" <?= join(' ', $field_attributes); ?>/><?php
		}

		if (isset($value['url']))
		{
			$target = '';
			if (isset($value['newtab']) && $value['newtab'] === TRUE)
			{
				$target = 'target="_blank"';
			}
			?><a <?=$target?> href="<?= $value['url'] ?>&amp;token=<?= $_SESSION['_TOKEN'] ?>"><?php
		}

		if(isset($value['html']) && !empty($value['html']))
		{
			echo $value['html'];
		}
		else
		{
			$printed_value = htmlentities($default_value, ENT_COMPAT, 'UTF-8');

			if($unit!='' && $printed_value!='')
			{
				$format .= ' %s';
			}
			printf($format, $printed_value, $unit);
		}

		if (isset($value['url']))
		{
			?></a><?php
		}
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$unit = $this->node->getNodeAttribute('unit', '');
		$placeholder = $this->node->getNodeAttribute('placeholder', '');
		if(empty($placeholder) && !empty($field_attributes['placeholder']))
		{
			$placeholder=$field_attributes['placeholder'];
			unset($field_attributes['placeholder']);
		}
		$this->node->_getClass($this->field, $class);

		$events = '';
		foreach($field_attributes AS $event => $foncs)
		{
			$events .= $event . '="';
			foreach($foncs AS $key => $fonc)
			{
				$events .= $fonc . '(this);';
			}
			$events .= '"';
		}

		?><div <?= $this->node->css_class() ?> <?= $this->node->style()?>><?php

			?><input maxlength="<?= property_exists($this->field, 'max_length') ? $this->field->max_length : '' ?>" type="<?= $this->html5_type ?>" name="<?= $input_name ?>" id="<?= $this->node->id ?>" <?= $class ?> value="<?= htmlentities($value['value'],ENT_COMPAT,'UTF-8') ?>" placeholder="<?= $placeholder; ?>" /><?php

			if (!empty($unit))
			{
				?><span>&nbsp;<?= $unit; ?></span><?php
			}

		?></div><?php
	}
}
