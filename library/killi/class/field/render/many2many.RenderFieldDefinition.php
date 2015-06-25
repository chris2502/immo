<?php

/**
 *  @class Many2manyRenderFieldDefinition
 *  @Revision $Revision: 4619 $
 *
 */

class Many2manyRenderFieldDefinition extends Many2oneRenderFieldDefinition
{
	// TODO Filter sur Many2Many
	// Sur Many2Many, le filter ne fonctionne qu'avec l'opérateur "="
	// Trouver une solution pour le faire fonctionner avec les autres opérateur
	// ou pour ne pas afficher la liste d'opérateurs...
	// (mais désactiver le filtre, c'est pas une bonne idée...)
	/*
	public function renderFilter($name, $selected_value)
	{
		return TRUE;
	}
	*/
	
	public function renderValue($value, $input_name, $field_attributes)
	{
		if($value['value'] == null)
		{
			$value['value'] = array();
		}
		
		if (empty($value['value']))
		{
			?><div></div><?php

			return TRUE;
		}
			
		$in_listing = $this->node->getNodeAttribute('inside_listing', '0') == '1';

		$hInstance	= ORM::getObjectInstance($this->node->getNodeAttribute('object', NULL, TRUE));
		$attr		= $this->node->getNodeAttribute('attribute');
		$sort		= $this->node->getNodeAttribute('sort', '1');
		$m2m_separator	= $this->node->getNodeAttribute('m2m-separator', ',&nbsp;');
		
		$tooltip_values_max	= (int)$this->node->getNodeAttribute('tooltip_value_max', 20);

		//---Extract Class Name
		$object_class = $this->field->object_relation;

		$view = isset($_GET['view']) ? $_GET['view'] : 'search';
		if (($view === 'form') && ($in_listing === FALSE))
		{
			$display_values	= $this->node->getNodeAttribute('display_value', '1') == '1';
			
			?><div<?= $this->node->style(array('white-space'=> 'initial','word-break'=> 'break-word')); ?><?= self::_attributesToClass(); ?>><?php
			
			echo '('.count($value['value']).' élément'.(count($value['value'])>1?'s':'').')';
			
			if($display_values)
			{
				$hInstance = ORM::getControllerInstance($object_class);
	
				//---Get reference
				$reference_list = array();
	
				//---Si on a des ref
				if (count($value['value'])>0)
				{
					$hInstance->getReferenceString($value['value'], $reference_list);
				}
			
				$num=0;
				
				echo '&nbsp;:&nbsp;';
				
				foreach($reference_list as $key => $reference)
				{
					Security::crypt($key,$crypt_pk2);
	
					if(isset(ORM::getObjectInstance($object_class)->view) && ORM::getObjectInstance($object_class)->view==TRUE)
					{
						?><a href="./index.php?action=<?= strtolower($object_class) ?>.edit&token=<?= $_SESSION['_TOKEN'] ?>&view=form&crypt/primary_key=<?= $crypt_pk2 ?>"><?php
					}
	
					echo $reference;
	
					if(isset(ORM::getObjectInstance($object_class)->view) && ORM::getObjectInstance($object_class)->view==TRUE)
					{
						?></a><?php
					}
	
					$num++;
	
					if($num!=count($reference_list))
					{
						echo $m2m_separator;
					}
				}
			}
			
			?></div><?php
		}
		else
		{
			$display_values	= $this->node->getNodeAttribute('display_value', '0') == '1';
			$tooltip_values	= $this->node->getNodeAttribute('tooltip_value', '1') == '1' && !$display_values;
			
			$hInstance	= ORM::getObjectInstance($this->node->getNodeAttribute('object', NULL, TRUE));
			$attr		= $this->node->getNodeAttribute('attribute');
			
			$hInstance->$attr->getFondamental($hInstance, $attr);

			/**
			 * Calcul de références.
			 */
			if(($display_values || $tooltip_values) && (!isset($value['reference']) || count($value['reference'])!=count($value['value'])))
			{
				$list = $this->node->getParent();
				$attr = $this->node->getNodeAttribute('attribute');
				$id_list = array();

				if(!property_exists($this->node, 'src'))
				{
					$this->node->src = $list->getNodeAttribute('object', '', TRUE);
				}
		
				if(isset($list->_data_list[$this->node->src]))
				{
					foreach($list->_data_list[$this->node->src] as $key=>$cur_data)
					{
						if($cur_data[$attr]['value']==null)
						{
							$list->_data_list[$this->node->src][$key][$attr]['value'] = $cur_data[$attr]['value'] = array();
						}
	
						if(!isset($cur_data[$attr]['reference']))
						{
							if($tooltip_values)
							{
								$id_list=array_merge($id_list,array_slice($cur_data[$attr]['value'], 0, $tooltip_values_max));
							}
							else
							{
								$id_list=array_merge($id_list,$cur_data[$attr]['value']);
							}
							
							$list->_data_list[$this->node->src][$key][$attr]['reference'] = array();
						}
					}
					$id_list=array_unique($id_list);
	
					if(!empty($id_list))
					{
						$reference_list=array();
						ORM::getControllerInstance($hInstance->$attr->object_relation)->getReferenceString($id_list,$reference_list);
	
						foreach($list->_data_list[$this->node->src] as $key=>$cur_data)
						{
							foreach($cur_data[$attr]['value'] as $v)
							{
								$list->_data_list[$this->node->src][$key][$attr]['reference'][$v] = isset($reference_list[$v]) ? $reference_list[$v] : NULL;
							}
						}
						
						$value['reference'] = $list->_data_list[$this->node->src][$this->node->real_key][$attr]['reference'];
					}
				}
				else
				{
					$reference_list=array();
					ORM::getControllerInstance($hInstance->$attr->object_relation)->getReferenceString($value['value'],$reference_list);
					
					foreach($value['value'] as $v)
					{
						$value['value'][$v] = $reference_list[$v];
					}
				}
			}

			if(!isset($value['reference']))
			{
				$value['reference'] = array();
			}
			$reference_list = $value['reference'];
			

			$num_elements = count($value['value']);

			?><div<?= $this->node->style() ?>><?php
			if ($display_values)
			{
				$num=0;
				foreach($reference_list as $key => $reference)
				{
					Security::crypt($key,$crypt_pk2);

					if(isset(ORM::getObjectInstance($hInstance->$attr->object_relation)->view) && ORM::getObjectInstance($hInstance->$attr->object_relation)->view==TRUE)
					{
						?><a href="./index.php?action=<?= strtolower($hInstance->$attr->object_relation) ?>.edit&token=<?= $_SESSION['_TOKEN'] ?>&view=form&crypt/primary_key=<?= $crypt_pk2 ?>"><?php
					}

					echo $reference;

					if(isset(ORM::getObjectInstance($hInstance->$attr->object_relation)->view) && ORM::getObjectInstance($hInstance->$attr->object_relation)->view==TRUE)
					{
						?></a><?php
					}

					$num++;

					if($num!=count($reference_list))
					{
						echo $m2m_separator;
					}
				}
			}
			else
			{
				$tooltip = '';

				if($tooltip_values)
				{
					$num=0;

					foreach($reference_list as $reference)
					{
						$tooltip.= $reference;

						$num++;

						if($num!=count($reference_list))
						{
							$tooltip.=$m2m_separator;
						}

						if($num==$tooltip_values_max)
						{
							break;
						}
					}

					if($num_elements>$num)
					{
						$reste = ($num_elements-$num);
						$tooltip.='<br/><br/>et '.$reste.' autre'.($reste>1?'s':'').'.';
					}
				}

				if($num_elements!=0)
				{
					?><span title="<?= $tooltip; ?>" <?= $tooltip_values?'class="tooltip_link"':'' ?>>(<?= $num_elements; ?>)</span><?php
				}
			}
			?></div><?php
		}

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		return $this->renderValue($value, $input_name, $field_attributes);
	}
}
