<?php

/**
 *  @class SelectorXMLNode
 *  @Revision $Revision: 4409 $
 *
 */

class SelectorXMLNode extends XMLNode
{
	public $current_id = NULL;
	private static $cache = array();

	public function open()
	{
		global $hUI;

		//---Si object est defini, on construit le tableau data
		$attr_name = $this->getNodeAttribute('name');
		$multiple  = (isset($attr_name) && $attr_name == '1');
		if ( isset( $this->attributes['object'] ) && !isset( $this->attributes['attribute'] ))
		{
			if(!isset($this->_data_list[$attr_name]))
			{
				$arSort = array();
				if (isset($this->attributes['sort']) && !empty($this->attributes['sort']))
				{
					$arSort[] = $this->attributes['sort'];
				}
				
				$key = md5($this->attributes['object']);
				if(!isset(self::$cache[$key]))
				{
					$object_id_list = array();
					ORM::getORMInstance($this->attributes['object'])->search($object_id_list, $num_object, NULL, $arSort);
	
					//---Get reference
					$hInstance = ORM::getControllerInstance($this->attributes['object']);
					$reference_list = array();
					$hInstance->getReferenceString($object_id_list,$reference_list);
					
					self::$cache[$key] = $reference_list;
				}
				
				$this->_data_list[$attr_name] = array();

				$i=0;
				foreach (self::$cache[$key] as $id=>$reference)
				{
					Security::crypt($id,$crypt_id);
					$this->_data_list[$attr_name][$i]['id']	= $crypt_id;
					$this->_data_list[$attr_name][$i]['value'] = $reference;
					$i++;
				}
			}
			$object = $this->getParent()->getNodeAttribute('object', NULL, TRUE);
			$primary_key = ORM::getObjectInstance($object)->primary_key;

			Security::crypt($this->_current_data[$primary_key]['value'],$crypt_id);

			$this->attributes['data'] = $attr_name;
			$attr_name = $attr_name.'/'.$crypt_id;
		}
		elseif( isset( $this->attributes['object'] ) && isset( $this->attributes['attribute'] ) )
		{
			$object = $this->getNodeAttribute('object', NULL, TRUE);
			$primary_key = ORM::getObjectInstance($object)->primary_key;

			Security::crypt($this->_current_data[$primary_key]['value'],$crypt_id);
			$this->attributes[ 'data' ] =  $this->attributes['attribute'] ;
			$this->attributes[ 'name' ] =  $this->attributes['attribute'] .'/'.$crypt_id;
			$this->_data_list[ $this->attributes[ 'data' ] ] = $this->_current_data[ $this->attributes['attribute'] ] ;
		}

		$genericstyle = (isset($this->attributes[ 'genericstyle' ]) && $this->attributes[ 'genericstyle' ] == "1");
		$empty = (isset($this->attributes[ 'empty' ]) && $this->attributes[ 'empty' ] == "1");
		$onchange = (isset($this->attributes[ 'onchange' ]) ? ' onchange="'.$this->attributes[ 'onchange' ].'"' : '');

		if( isset( $this->attributes[ 'label' ] ) && trim( $this->attributes[ 'label' ] ) != '' )
		{
			if(!$genericstyle)
			{
				echo $this->attributes[ 'label' ] . ': ' ;
			}
			else
			{
				?><table class="field" cellspacing="2" cellpadding="1">
					<tr>
					<td class="field_label"><?= $this->attributes[ 'label' ]; ?> : </td>
					<td class="field_td"><?php
			}
		}

		if (!isset($this->attributes['encryption']) || (isset($this->attributes['encryption']) && $this->attributes['encryption'] == "1"))
		{
			$name  = 'crypt/'.$attr_name;
		}
		else
		{
			$name  = $attr_name;
		}
		$dataindex = $this->attributes[ 'data' ] ;
		$real_name = $this->getNodeAttribute('name');
		$combo = '<select '.(($multiple)? 'multiple ' : '').'class=" search_input ' .$attr_name . '_class search_input ' .$real_name . '_class" ' . $this->style() . $onchange . ' id="' . str_replace('/', '_', $name) . '" name="' . $name . '">' . "\n";
		if( $empty )
			$combo .= '<option value=""></option>' . "\n";
		if( isset( $this->_data_list[ $dataindex ] ) )
		{
			if (isset($this->attributes['grouping']) && $this->attributes['grouping'] == '1')
			{
				foreach ($this->_data_list[$dataindex] as $group => $arData)
				{
					$combo .= '<optgroup label="'.$group.'">';
					foreach ($arData as $opt)
					{
						$combo .= '<option value="'.$opt['id'].'"';
						if(isset($opt['selected']) && $opt['selected'])
							$combo .= ' selected="selected"';
						$combo .= '>'.$opt['value'].'</option>';
					}
					$combo .= '</optgroup>';
				}
			}
			else
			{
				for( $i = 0 ; $i < count( $this->_data_list[ $dataindex ] ) ; $i++ )
				{
					$combo .= '<option value="' . $this->_data_list[ $dataindex ][ $i ][ 'id' ] . '"' ;
					if (isset($this->_data_list[$dataindex][$i]['style']) && !empty($this->_data_list[$dataindex][$i]['style']))
					{
						$combo .= ' style="'.$this->_data_list[$dataindex][$i]['style'].'"';
					}
					if (isset($this->_data_list[$dataindex][$i]['selected']) && $this->_data_list[$dataindex][$i]['selected'])
					{
						$combo .= ' selected="selected"';
					}
					$combo .= '>' . $this->_data_list[ $dataindex ][ $i ][ 'value' ] . '</option>' . "\n";
				}
			}
		}

		$combo .= '</select>' . "\n" ;
		if($genericstyle)
		{
			?>
				<table cellspacing="0" cellpadding="0">
				<tr><td>
				<?= $combo; ?>
				</td>
				<td style="white-space: no-wrap; text-align: left;">&nbsp;</td>
			</tr>
			<tr>
			<td colspan="2" id="selector_sub_content"></td>
			</tr>
			<tr>
			<td colspan="2" id="selector_oth_content"></td>
			</tr>
			</table>
			</td>
			</tr>
			</table>
			<?php
		} else {
			echo $combo ;
		}

		return TRUE;
	}
}
