<?php

/**
 *  @class SelectorRenderFieldDefinition
 *  @Revision $Revision: 4607 $
 *
 */
class SelectorRenderFieldDefinition extends RenderFieldDefinition
{
	public function renderFilter($name, $selected_value)
	{
		?><input type='hidden' name="<?= $name.'/op' ?>" value='='/><?php
		?><select id="search_<?= $this->node->id ?>" class="search_input" onchange="return trigger_search($('#search_<?= $this->node->id ?>'));" name="crypt/<?= $name ?>"><?php
			?><option></option><?php

			foreach ( $this->field->type as $key => $array_value )
			{
				if(empty($key))
				{
					continue;
				}
				Security::crypt ( $key, $crypt_key );

				?><option <?= ($selected_value == $key ? 'selected' : '') ?> value="<?= $crypt_key ?>"><?= htmlentities($array_value, ENT_QUOTES, 'UTF-8') ?></option><?php
			}

		?></select><?php

		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		$this->renderInput($value, $input_name, $field_attributes);
		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$id			= $this->node->id;
		$empty		= $this->node->getNodeAttribute('empty', '1') == '1';
		$multiple	= $this->node->getNodeAttribute('multiple', '0') == '1';
		$grouping	= $this->node->getNodeAttribute('grouping', '0') == '1';
		$name		= $this->node->getNodeAttribute('attribute');
		$dataindex	= $this->node->getNodeAttribute('data', '');
		$object		= $this->node->getNodeAttribute('object', '');

		$onchange = '';
		if(isset($field_attributes['onchange']))
		{
			foreach($field_attributes['onchange'] AS $key => $v)
			{
				if(!empty($v))
				{
					$onchange .= $v . '($(this));';
				}
			}
		}

		if(empty($dataindex) && !empty($object))
		{
			$hORM = ORM::getORMInstance($object);
			$object_id_list = array();
			$hORM->search($object_id_list, $num_object, array());

			$hInstance = ORM::getControllerInstance($object);
			$reference_list = array();
			$hInstance->getReferenceString($object_id_list, $reference_list);
			$dataindex = $object . '_selectorReferences';
			foreach ($reference_list as $key=>$reference)
			{
				$selected = FALSE;
				if((isset($_POST[$name]) && $key == $_POST[$name]) ||
				   (isset($_GET[$name]) && $key == $_GET[$name]))
				{
					$selected = TRUE;
				}
				$this->node->_data_list[$dataindex][] = array('id' => $key, 'value' => $reference, 'selected' => $selected);
			}
		}

		$combo = '<select ' . $this->node->style() . ($multiple=='1'?' multiple ':'') . ' id="' . $id . '" name="crypt/' . $input_name . ($multiple=='1'?'[]':'') . '" onchange="' . $onchange . '">' . PHP_EOL;

		if( $empty )
		{
			$combo .= '<option value=""></option>' . PHP_EOL;
		}

		if( isset( $this->node->_data_list[ $dataindex ] ) )
		{
			if ($grouping == '1')
			{
				foreach ($this->node->_data_list[$dataindex] as $group => $arData)
				{
					$combo .= '<optgroup label="'.$group.'">';
					foreach ($arData as $opt)
					{
						Security::crypt( $opt['id'], $crypt_id);
	 					$combo .= '<option value="'.$crypt_id.'"';
						if(isset($opt['selected']) && $opt['selected'])
						{
							$combo .= ' selected="selected"';
						}
						$combo .= '>'.$opt['value'].'</option>';
					}
					$combo .= '</optgroup>';
				}
			}
			else
			{
				$count = count( $this->node->_data_list[ $dataindex ] );
				for( $i = 0 ; $i <  $count; $i++ )
				{
					Security::crypt( $this->node->_data_list[ $dataindex ][ $i ][ 'id' ], $crypt_id);
					$combo .= '<option value="' .$crypt_id. '"' ;

					if(isset($this->node->_data_list[$dataindex][$i]['selected']) && $this->node->_data_list[$dataindex][$i]['selected'])
					{
						$combo .= ' selected="selected"';
					}

					$combo .= '>' . $this->node->_data_list[ $dataindex ][ $i ][ 'value' ] . '</option>' . "\n";
				}
			}
		}

		$combo .= '</select>' . "\n" ;

		echo $combo;

		if($this->node->getNodeAttribute('jquery', '1') == '1')
		{
			?>
			<script>
			$('#<?= $id ?>').multiselect({
				selectedList: 1, // 0-based index
				multiple: <?= $multiple==true?'true':'false' ?>, // multiple or not
				open: function(event, ui) {
				},
				close: function(event, ui) {
					<?= $onchange ?>
				},
				<?php
					$w = $this->node->getNodeAttribute('width', '');
					if(!empty($w))
					{
						echo 'width: \'', $w, '\',';
					}
				?>
			});
			$('#<?= $id ?>').multiselect("uncheckAll"); // clear pour afficher select options
			</script>
			<?php
		}

		return TRUE;
	}
}
