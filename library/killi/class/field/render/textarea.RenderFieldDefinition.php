<?php

/**
 *  @class TextareaRenderFieldDefinition
 *  @Revision $Revision: 4122 $
 *
 */

class TextareaRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		$default_value	= $value['value'];

		$attr = $this->node->getNodeAttribute('attribute');
		$target = $this->node->getNodeAttribute('target','_blank');

		if (isset($_GET[$attr]))
		{
			$crypt_value = NULL;
			Security::crypt($default_value,$crypt_value);
			?><input type="hidden" name="crypt/<?= $input_name ?>" id="crypt_<?= $this->node->id ?>"  value="<?= $crypt_value ?>" <?= join(' ', $field_attributes); ?>/><?php
		}

		if (isset($value['url']))
		{
			?><a <?= $target ?> href="<?= $value['url'] ?>"><?php
		}

		if($default_value !== null && $default_value !== false)
		{
			?><pre><?php
			
			echo htmlentities($default_value,ENT_COMPAT,'UTF-8');
			
			?></pre><?php
		}

		if (isset($value['url']))
		{
			?></a><?php
		}
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$placeholder = $this->node->getNodeAttribute('placeholder', '');
		$cols = $this->node->getNodeAttribute('cols', 40);
		$rows = $this->node->getNodeAttribute('rows', 6);

		$class = '';
		$this->node->_getClass($this->field, $class);

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
		
		?>
		<div <?= self::_attributesToClass() ?>>
			<textarea placeholder="<?= $placeholder ?>" <?= $this->node->style(array('width'=>'100%')) ?> name="<?= $input_name ?>" id="<?= $this->node->id ?>" cols="<?= $cols; ?>" rows="<?= $rows; ?>" <?= $class ?> <?= $parameters ?>><?= htmlentities($value['value'],ENT_COMPAT,'UTF-8') ?></textarea>
		</div>
		<?php
	}
}
