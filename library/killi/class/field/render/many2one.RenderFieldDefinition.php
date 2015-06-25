<?php

/**
 *  @class Many2oneRenderFieldDefinition
 *  @Revision $Revision: 4658 $
 *
 */

class Many2oneRenderFieldDefinition extends RenderFieldDefinition
{
	protected $_m2o_object = NULL;
	protected static $operators = array('='=>'=', '!='=>'!=', 'NULL'=>'∅', 'NOT NULL'=>'!∅');

	public function __construct(XMLNode $node, FieldDefinition $field)
	{
		parent::__construct($node, $field);
		
		$connector = $node->getNodeAttribute('connector', '');
		if(!empty($connector))
		{
			$this->_m2o_object = $connector['object'];
			return TRUE;
		}

		if($field->type == 'primary key')
		{
			$this->_m2o_object = $field->objectName;
			return TRUE;
		}

		if(!isset($field->object_relation))
		{
			throw new Exception('many2one need a parameter !');
		}
		$this->_m2o_object = $field->object_relation;

	}

	protected function computeReference(&$value)
	{
		if(isset($value['reference']))
		{
			return TRUE;
		}

		$value['reference'] = '';

		if(empty($value['value']))
		{
			return TRUE;
		}

		$list = $this->node->getParent();
		$attribute = $this->node->getNodeAttribute('attribute');
		$id_list = array();

		if(!property_exists($this->node, 'src'))
		{
			$this->node->src = $this->node->getNodeAttribute('object', '', TRUE);
		}

		if(isset($list->_data_list[$this->node->src]))
		{
			foreach($list->_data_list[$this->node->src] as $real_key=>$data)
			{
				if(!empty($data[$attribute]['value']) && !isset($data[$attribute]['reference']))
				{
					$id_list[] = $data[$attribute]['value'];
				}

				if(!isset($data[$attribute]['reference']))
				{
					$list->_data_list[$this->node->src][$real_key][$attribute]['reference'] = '';
				}
			}

			if(!empty($id_list))
			{
				$reference_list = array();
				ORM::getControllerInstance($this->_m2o_object)->getReferenceString($id_list, $reference_list);

				foreach($list->_data_list[$this->node->src] as $real_key=>$data)
				{
					if(!empty($data[$attribute]['value']) && isset($reference_list[$data[$attribute]['value']]))
					{
						$list->_data_list[$this->node->src][$real_key][$attribute]['reference'] = $reference_list[$data[$attribute]['value']];

						if($data[$attribute]['value'] == $value['value'])
						{
							$value['reference'] = $reference_list[$data[$attribute]['value']];
						}
					}
				}
			}
		}
		else
		{
			$reference_list = array();
			ORM::getControllerInstance($this->_m2o_object)->getReferenceString(array($value['value']), $reference_list);

			$value['reference'] = $reference_list[$value['value']];
		}

		return TRUE;
	}

	/********************************************************
	 * Retourne l'environnement associé à un field
	 * invoqué dans un formulaire sous la forme d'une string.
	 *
	 * Voir : _renderReference
	 *
	 * S'il n'y en a pas, une string vide.
	 */
	protected function _getFieldInFormEnvString($data)
	{
		$env_string = '';
		$env_attribut = $this->node->getNodeAttribute('env', false);
		$crypt_value = NULL;
		if ($env_attribut)
		{
			$obj = $this->node->getNodeAttribute('object', NULL, TRUE);
			
			$env_string .= ', env: \'';
			$env_definition_list  = explode(',', $env_attribut);
			
			$data  = $data[$obj][key($data[$obj])];
			
			foreach($env_definition_list as $env_definition)
			{
				$env_string .= "&crypt/search/".$this->field->object_relation."/". $env_definition . "=";
				
				if(isset($_GET[$env_definition]))
				{
					Security::crypt($_GET[$env_definition], $crypt_value);
				}
				else if(isset($data[$env_definition]))
				{
					Security::crypt($data[$env_definition]['value'], $crypt_value);
				}
				else
				{
					throw new Exception('Invalid env attribute : '.$env_definition.' not found in GET or $data');
				}

				$env_string .= $crypt_value;
			}
			$env_string .= '\'';
		}
		return $env_string;
	}

	public function renderFilter($name, $selected_value)
	{
		$object			= $this->node->getNodeAttribute('object', '', TRUE);
		$attribute		= $this->node->getNodeAttribute('attribute');
		$autocomplete	= $this->node->getNodeAttribute('autocomplete', '');
		$selector		= $this->node->getNodeAttribute('selector', '0') == '1';
		$order 			= strtolower($this->node->getNodeAttribute('order', ''));
		$rclass			= $this->node->getNodeAttribute('m2o_object', '');
		$sort_flag		= $this->node->getNodeAttribute('sort_flag', SORT_REGULAR);

		$hInstance	= ORM::getObjectInstance($object);

		if(empty($rclass))
		{
			$fondamental_attribute = $hInstance->$attribute->getFondamental($hInstance, $attribute);

			if($fondamental_attribute->type == 'primary key')
			{
				$rclass = $this->_m2o_object;
			}
			else
			{
				$rclass = $fondamental_attribute->object_relation;
			}
		}

		$showvalue = '';
		if ($selected_value)
		{
			$rlist = array();
			orm::getControllerInstance($rclass)->getReferenceString(array($selected_value), $rlist);
			$showvalue = $rlist[$selected_value];
		}

		$rclass = strtolower($rclass);

		$crypt_value = FALSE;
		Security::crypt($selected_value,$crypt_value);

		$domain_attr =array();
		if (isset($hInstance->attribute_domain) && (isset($hInstance->attribute_domain[$attribute])))
		{
			$domain_attr = $hInstance->attribute_domain[$attribute];
			unset($hInstance->attribute_domain[$attribute]);
		}

		if(isset($hInstance->$attribute->domain) && !empty($hInstance->$attribute->domain))
		{
			$domain_attr = array_merge($domain_attr, $hInstance->$attribute->domain);
		}
		$hInstance->$attribute->domain = $domain_attr;

		$selected_operator = (isset($_POST[$name.'/op']) ? htmlentities($_POST[$name.'/op']) : NULL);

		?><table class='ui-filter ui-filter-m2o'><?php
			?><tr><?php
				?><td><?php

				if($selected_operator == 'NULL')
				{
					$showvalue = '(Vide)';
					$crypt_value=NULL;
					$selected_value = TRUE;
				}
				else if($selected_operator == 'NOT NULL')
				{
					$showvalue = '(Non vide)';
					$crypt_value=NULL;
					$selected_value = TRUE;
				}

				if($selector)
				{
					//---On recup le population
					$object_id_list = array();
					ORM::getORMInstance($rclass, TRUE)->search($object_id_list,$total_record,$hInstance->$attribute->domain,NULL,0,200);

					if($total_record>200)
					{
						$selector = FALSE;
					}
				}

				if(!$selector)
				{
					?><input name="crypt/<?= $name ?>" type="hidden" id="r_<?= $this->node->id; ?>" class="<?= $rclass; ?>" value="<?= $crypt_value; ?>"><?php

					if (empty($autocomplete))
					{
						$hObject = ORM::getObjectInstance($rclass);
						$reference = NULL;
						if(isset($hObject::$reference) && !($hObject::$reference instanceof FieldDefinition))
						{
							$reference = $hObject::$reference;
						}
						else
						if(isset($hObject->reference) && !($hObject->reference instanceof FieldDefinition))
						{
							$reference = $hObject->reference;
						}

						if($reference != NULL && isset($hObject->$reference) && $hObject->$reference instanceof FieldDefinition && $hObject->$reference->function === NULL)
						{
							$autocomplete = $rclass.'.'.$reference;
						}
					}

					?><select name="<?= $name.'/op' ?>" id="op_<?= $this->node->id ?>" onChange="trigger_search($('#r_<?= $this->node->id ?>reference'));" class="ui-filter-op"><?php
					foreach(self::$operators as $operator_key=>$operator)
					{
						?><option value="<?= $operator_key; ?>" <?= ($selected_operator == $operator_key ? ' selected' : ''); ?>><?= $operator; ?></option><?php
					}
					?></select><?php

					$attr_domain = '';
					if (!empty($hInstance->$attribute->domain))
					{
						$crypt_domain = NULL;
						Security::crypt(serialize($hInstance->$attribute->domain),$crypt_domain);
						$attr_domain = "&crypt/domain=".$crypt_domain;
					}

					$onclick="onClick=\"window.open('./index.php?action=". $rclass.".edit&view=selection&input_name=r_".$this->node->id.$attr_domain."&token=". $_SESSION['_TOKEN'] ."','popup_search_". rand(10000,99999) ."', config='height=600, width=800, toolbar=no, scrollbars=yes')\"";

					if (!empty($autocomplete))
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

						if(!ORM::getObjectInstance($object_name)->$field_name->search_disabled)
						{
							?><script>
								$(document).ready(function(){
									$( "#r_<?= $this->node->id; ?>reference" ).autocomplete({
										source: function( request, response ) {
											$.ajax({
												url: "./index.php?action=<?=$object_name?>.autocomplete&field=<?=$field_name?>&search="+$('#r_<?=  $this->node->id; ?>reference').val()+"&token=<?= $_SESSION['_TOKEN'] ?>&type_feedback=pk<?= $attr_domain ?>",
												dataType: "json",
												success: function( json )
												{
													response (json.data);
												}
											});
										},
										minLength: 2,
										autoFocus: 1,
										select: function( event, ui )
										{
											$('#r_<?= $this->node->id; ?>').val(ui.item.object_id);
											trigger_search($('#r_<?= $this->node->id ?>reference'));
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

					?><input id="r_<?= $this->node->id ?>reference" class="search_input" placeholder="<?= (empty($autocomplete) ? 'Cliquez pour sélectionner' : ORM::getObjectInstance($object_name)->{$field_name}->name) ?>" <?php if (empty($autocomplete)){ echo ' readonly="readonly"'; } ?> name="<?= $rclass; ?>reference" value="<?= $showvalue; ?>" <?php if (empty($autocomplete)){ echo $onclick; } ?>/><?php

					?></td><?php

					?><td class='ui-filter-clear'><?php
						?><img <?= $onclick; ?> style="cursor: pointer;vertical-align: middle;" src="./library/killi/images/gtk-find.png"/><?php
					?></td><?php

					if($selected_value != '')
					{
						?><td class='ui-filter-clear'><?php
							?><img onclick="$('#op_<?= $this->node->id; ?>').val('');$('#r_<?=$this->node->id; ?>').val('');trigger_search($('#r_<?= $this->node->id ?>reference'));" src='library/killi/images/delete.png' /><?php
						?></td><?php
					}
				}
				else
				{
					?><td><?php

						//---On recup la liste des reference string de l'objet
						$reference_list = array();
						ORM::getControllerInstance($rclass)->getReferenceString($object_id_list,$reference_list);

						if(!empty($order))
						{
							if($order=='az')
							{
								asort($reference_list, $sort_flag);
							}
							else if($order=='za')
							{
								arsort($reference_list, $sort_flag);
							}
						}

						?><select id="r_<?= $this->node->id ?>" class="search_input" onChange="return trigger_search($(this));" <?= $rclass ?> name="crypt/<?= $name ?>"><?php
							?><option></option><?php

							foreach ($reference_list as $object_id=>$reference)
							{
								$crypt_object_id = NULL;
								Security::crypt($object_id,$crypt_object_id);

								$selected   = (isset($_POST['crypt/'.$name]) && ($crypt_object_id == $_POST['crypt/'.$name]))? 'selected' : '';

								?><option <?php echo $selected ?> value="<?php echo $crypt_object_id ?>"><?php echo $reference ?></option><?php
							}

						?></select><?php
					?></td><?php
				}

			?></tr><?php
		?></table><?php

		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		if(empty($value['value']))
		{
			?><div></div><?php

			return TRUE;
		}

		$object_class	= $this->node->getNodeAttribute('m2o_object', $this->_m2o_object);
		$enable_link	= ($this->node->getNodeAttribute('enable_link', '1') == '1');
		$show_reference = $this->node->getNodeAttribute('reference', '1') == '1';

		if (!$show_reference)
		{
			$value['reference'] = $value['value'];
		}
		else
		{
			$this->computeReference($value);
		}

		//---Si admin ou si droit en edition de l'objet ---> a href
		$obj = ORM::getObjectInstance($object_class);
		$view_link = ($value['value'] != -1 && ((in_array(ADMIN_PROFIL_ID,$_SESSION['_USER']['profil_id']['value'])) || ($obj->view == TRUE && $enable_link)));

		if ($view_link)
		{
			$crypt_pk = NULL;
			Security::crypt($value['value'], $crypt_pk);

			if (!isset($value['url']) AND $this->field->auto_remote_link AND isset($obj->json))
			{
				$value['url'] = $obj->json['path'].'index.php?action='
					.((isset($obj->json['object'])) ? strtolower($obj->json['object']) : strtolower($object_class))
					.'.edit&view=form&crypt/primary_key='. $crypt_pk .'&token='. $_SESSION['_TOKEN'];
			}

			if(isset($value['url']))
			{
				$link = $value['url'];
			}
			else
			{
				$link = './index.php?action='. strtolower($object_class) .'.edit&view=form&crypt/primary_key='. $crypt_pk .'&token='. $_SESSION['_TOKEN'];
			}

			?><a href="<?= htmlentities($link, NULL, 'UTF-8') ?>"><?php
		}

		if($value['reference'] == '')
		{
			$value['reference'] = '[SANS_NOM]';

			?><span style='color:red'><?php
		}

		echo htmlentities($value['reference'], NULL, 'UTF-8');

		if($value['reference'] == '')
		{
			?></span><?php
		}

		if ($view_link)
		{
			?></a><?php
		}

		//--- On suppose que si on n'affiche pas le label, on est dans un listing.
		$inside_listing = ($this->node->getNodeAttribute('nolabel', '0') == '1');
		if (!$inside_listing && isset($_GET['view']) && ($_GET['view']=='form' || $_GET['view']=='create'))
		{
			?><div><input name="<?= $input_name ?>" type="hidden" id="<?= str_replace('/','_',$input_name) ?>" value="<?= $value['value'] ?>"/></div><?php
		}

		return TRUE;
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		$autocomplete	= $this->node->getNodeAttribute('autocomplete', '');
		$selector		= $this->node->getNodeAttribute('selector', '0') == '1';
		$create			= $this->node->getNodeAttribute('create', '0');
		$object_class	= $this->node->getNodeAttribute('m2o_object', $this->_m2o_object);
		$obj			= $this->node->getNodeAttribute('object', NULL, TRUE);
		$noblankvalue 	= $this->node->getNodeAttribute('noblankvalue', '0') == '1';
		$order 			= strtolower($this->node->getNodeAttribute('order', ''));
		$sort_flag 		= $this->node->getNodeAttribute('sort_flag', SORT_REGULAR);
		$env_data       = $this->node->getDataList();

		$class = '';
		$this->node->_getClass($this->field, $class);

		$mattribute		= '';
		$mclass			= '';
		if(!empty($obj))
		{
			$fname			= explode('/',$input_name);

			if(isset($fname[1]))
			{
				$mattribute		= $fname[1];
			}

			$hInstance = ORM::getObjectInstance($obj);

			/**
			 * Gestion du domaine sur l'attribut.
			 */
			$mclass			= get_class($hInstance);

			$domain_attr=array();
			if (isset($hInstance->attribute_domain) && (isset($hInstance->attribute_domain[$mattribute])))
			{
				$domain_attr = $hInstance->attribute_domain[$mattribute];
				unset($hInstance->attribute_domain[$mattribute]);
			}

			if(isset($this->field->domain) && !empty($this->field->domain))
			{
				$domain_attr = array_merge($domain_attr, $this->field->domain);
			}
			$this->field->domain = $domain_attr;
		}

		/**
		 * Récupération de la référence.
		 */
		$this->computeReference($value);

		if($selector)
		{
			//---On recup le population
			$object_id_list = array();
			ORM::getORMInstance($object_class, TRUE)->search($object_id_list, $total_record, $this->field->domain, NULL, 0, 200);

			if($total_record > 200)
			{
				$selector = FALSE;
			}
		}

		if($selector)
		{


			//---On recup la liste des reference string de l'objet
			$reference_list = array();
			ORM::getControllerInstance($this->field->object_relation)->getReferenceString($object_id_list,$reference_list);

			$onchange = '';
			$parameters = '';
			foreach($field_attributes AS $event => $foncs)
			{
				if (!is_array($foncs))
				{
					continue;
				}
				$parameters .= $event . '="';
				foreach($foncs AS $key => $fonc)
				{
					if($event == 'onchange')
					{
						$onchange .= $fonc . '|';
					}
					$parameters .= $fonc . '(this);';
				}
				$parameters .= '"';
			}

			?><select <?= $class ?> id="<?= str_replace('/','_',$input_name) ?>" <?= $parameters ?> name="crypt/<?= $input_name ?>"<?= $this->node->style();?>><?php

			if(!($noblankvalue || ($this->field->required && isset($_GET['view']) && $_GET['view']=='form')))
			{
				?><option></option><?php
			}

			if(!empty($order))
			{

				if($order=='az')
				{
					asort($reference_list, $sort_flag);
				}
				else if($order=='za')
				{
					arsort($reference_list, $sort_flag);
				}
			}

			foreach ($reference_list as $object_id=>$ref)
			{
				$crypt_object_id = NULL;
				Security::crypt($object_id,$crypt_object_id);
				$selected = '';
				$current_id = $value['value'];
				$selected   = ($object_id == $current_id)? 'selected' : '';
				?><option <?php echo $selected ?> value="<?php echo $crypt_object_id ?>"><?php echo htmlentities($ref, ENT_QUOTES, 'UTF-8') ?></option><?php
			}

			?></select><?php
		}
		else
		{
			if (empty($autocomplete))
			{
				$hObject = ORM::getObjectInstance($object_class);
				$reference = NULL;
				if(isset($hObject::$reference) && !($hObject::$reference instanceof FieldDefinition))
				{
					$reference = $hObject::$reference;
				}
				else
				if(isset($hObject->reference) && !($hObject->reference instanceof FieldDefinition))
				{
					$reference = $hObject->reference;
				}

				if($reference != NULL && isset($hObject->$reference) && $hObject->$reference instanceof FieldDefinition && $hObject->$reference->function === NULL)
				{
					$autocomplete = $object_class.'.'.$reference;
				}
			}

			$crypted_value = NULL;
			Security::crypt($value['value'], $crypted_value);
			$parameters = '';

			if(!$this->field->required)
			{
				$field_attributes['onchange'][]='show_reset';
			}

			foreach($field_attributes AS $event => $foncs)
			{
				$parameters .= $event . '="';
				foreach($foncs AS $key => $fonc)
				{
					$parameters .= $fonc . '($(this));';
				}
				$parameters .= '"';
			}

			$domain	= '';
			if(!empty($this->field->domain))
			{
				$crypt_domain = NULL;
				Security::crypt(serialize($domain_attr),$crypt_domain);
				$domain = "&crypt/domain=".$crypt_domain;
			}

			$width  = 800;
			$height = 600;

			$uniqid = strtolower($object_class) . '_' . $mattribute . '_' . $crypted_value;
			$onclick="onClick=\"popupReference('". str_replace(array('/', '.', ':'), '_',$input_name) ."', {field: '". $this->node->getNodeAttribute('attribute') ."', keywordID: '".str_replace('/','_',$input_name)."reference', create: '". $create ."', input_name: '". $input_name ."', uniqid: '". $uniqid."', object_class: '". strtolower($object_class) ."', mclass: '". strtolower($mclass)."', token: '". $_SESSION['_TOKEN']."', width: ". $width.",domain: '". $domain ."'".$this->_getFieldInFormEnvString($env_data).", height: ". $height."});\"";

			if (!empty($autocomplete))
			{
				$object_name	= '';
				$field_name		= '';
				if(preg_match('/^(.*)\.(.*)$/', $autocomplete, $matches))
				{
					list($ereg, $object_name, $field_name)=$matches;
				}
				else
				{
					$object_name = $autocomplete;
				}

				if(!ORM::getObjectInstance($object_name)->$field_name->search_disabled)
				{
					?><script>
						$(function(){
							$( "#<?= str_replace(array('/', '.', ':'),'_',$input_name).'reference' ?>" ).autocomplete({
								source: function( request, response ) {
									$.ajax({
										url: "./index.php?action=<?=$object_name?>.autocomplete&field=<?=$field_name?>&search="+$('#<?= str_replace(array('/', '.', ':'), '_',$input_name).'reference' ?>').val()+"&token=<?= $_SESSION['_TOKEN'] ?>&type_feedback=pk<?= $domain ?>",
										dataType: "json",
										success: function( json )
										{
											response (json.data);
										}
									});
								},
								minLength: 2,
								autoFocus: 1,
								select: function( event, ui )
								{
									$('#<?= str_replace(array('/', '.', ':'),'_',$input_name) ?>').val(ui.item.object_id);
									$('#<?= str_replace(array('/', '.', ':'),'_',$input_name) ?>').change();
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

			?><table class="reference_table">
				<tr>
					<td>
						<input name="<?= str_replace('/','_',$input_name).'old' ?>" type="hidden" id="<?= str_replace(array('/', '.', ':'),'_',$input_name).'old' ?>" value="<?php echo $value['reference'] ?>">
						<input name="crypt/<?= $input_name ?>" type="hidden" id="<?= str_replace(array('/', '.', ':'),'_',$input_name) ?>" value="<?= $crypted_value ?>" <?= $parameters ?>>
						<input placeholder="<?= (empty($autocomplete) ? 'Cliquez pour sélectionner' : ORM::getObjectInstance($object_name)->{$field_name}->name) ?>" <?php if (empty($autocomplete)){ echo ' readonly="readonly"'; } ?> name="<?= str_replace('/','_',$input_name).'reference' ?>" id="<?= str_replace(array('/', '.', ':'),'_',$input_name).'reference' ?>" <?= $class ?> class="search_input" value="<?= $value['reference']; ?>" <?php if (empty($autocomplete)){ echo $onclick; } ?> style='-webkit-box-sizing: border-box;-moz-box-sizing:border-box;box-sizing:border-box;width: 100%;<?= (empty($autocomplete) ? ';cursor:pointer' : '') ?>'>
					</td>
					<td id="<?= str_replace('/','_',$input_name).'_reset' ?>" style="width: 20px; white-space: nowrap; <?= !($value['reference']!='' && !$this->field->required) ? 'display:none': '' ?>">
						<img onClick="reset_reference('<?= str_replace(array('/', ':'),'_',$input_name) ?>'); $('#<?= str_replace(array('/', '.', ':'),'_',$input_name).'_reset' ?>').hide();" src="./library/killi/images/reset.png" style='cursor: pointer;vertical-align:middle'/>
					</td>
					<td style="width: 16px;">
						<img <?= $onclick; ?> id="<?= str_replace(array('/', '.', ':'),'_',$input_name).'_button_popup' ?>"  style="cursor: pointer;vertical-align: middle" src="./library/killi/images/gtk-find.png"/>
					</td>

				</tr>
			</table>
			<?php
		}

		return TRUE;
	}
}
