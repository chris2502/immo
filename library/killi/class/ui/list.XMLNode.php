<?php

class ListXMLNode extends XMLNode
{
	public $data_key;

	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if(isset( $_GET[ 'workflow_node_id']))
		{
			$this->attributes = $structure['attributes'];
			
			if($this->getNodeAttribute('show_wfcommentaire','1') == '1')
			{
				$pk_field = array('tag' => 'html', 'markup' => 'text', 'value' => array(), 'attributes' => array('empty' => '1', 'string' => 'Commentaire', 'name' => 'comment', 'width' => '300px'));
				array_unshift($structure['value'], $pk_field);
			}

			if($this->getNodeAttribute('show_wfqualification','1') == '1')
			{
				$pk_field = array('tag' => 'html', 'markup' => 'selector', 'value' => array(), 'attributes' => array('empty' => '1', 'string' => 'Qualification', 'name' => 'qualification_id', 'object' => 'nodequalification'));
				array_unshift($structure['value'], $pk_field);
			}
		}

		if(isset($_SESSION['_USER']) && in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']))
		{
			if(isset($structure['attributes']['object']))
			{
				$object = $structure['attributes']['object'];
				$pk_field = array('tag' => 'html', 'markup' => 'field', 'value' => array(), 'attributes' => array('label'=>'ID','type'=>'int', 'render'=>'int', 'attribute' => ORM::getObjectInstance($object)->primary_key, 'search' => '1', 'reference' => '0','id'=>'search_pk'));
				array_unshift($structure['value'], $pk_field);
			}
		}

		parent::__construct($structure, $parent, $view);
	}

	//.........................................................................
	private function _renderMacroFilter($view_name)
	{
		//---On recup les macro filtres
		$macro_filtre_list = array();
		ORM::getORMInstance('macrofilter')->browse($macro_filtre_list,$num,array('descript'),array(array('view_name','=',$view_name)));

		Security::crypt(0,$crypt_zero);
		Security::crypt($view_name,$crypt_macro_filter_view_name);

		?>
		<input type="hidden" name="crypt/macro_filter_view_name" value="<?= $crypt_macro_filter_view_name ?>"/>
		<table class="navigator">
			<tr>
				<td style="width: 50%;">
					Macro filtre :
					<select name="crypt/macro_filter" id="crypt_macro_filter" style="width: 350px;" onchange="return document.search_form.submit();">
						<option value="<?= $crypt_zero ?>"></option>
						<?php
						foreach($macro_filtre_list as $macro_filter_id=>$macro_filter)
						{
							$selected =((isset($_POST['macro_filter'])) && ($_POST['macro_filter']==$macro_filter_id))?'selected':'';
							Security::crypt($macro_filter_id,$crypt_macro_filter_id);
							?><option <?= $selected ?> value="<?= $crypt_macro_filter_id ?>"><?= $macro_filter['descript']['value'] ?></option><?php
						}
						?>
					</select>
				</td>
				<?php
				//---Creation des macro filtres par admin uniquement
				if (in_array(ADMIN_PROFIL_ID,$_SESSION['_USER']['profil_id']['value']))
				{
				?>
				<td style="width: 50%; text-align: right;">
					Description du macro filtre : <input name="macro_filter_description" style="width: 300px;"/>
					<button onclick="return document.search_form.submit();" value="1" name="save_macro_filter" style="width: 150px;">Enregistrer</button>
				</td>
				<?php
				}
				?>
			</tr>
		</table>
		<?php

		return TRUE;
	}

	public function open()
	{
		global $search_view_num_records, $ui_theme;

		$raw = explode('.', $_GET['action']);
		$action = $raw[1];
		unset($raw);

		//$id_table = preg_replace('/\./', '_', uniqid('table_', true));

		$object_to_use	= $this->getNodeAttribute('object');
		$hInstance		= ORM::getObjectInstance($object_to_use);

		$primary_key	= $this->getNodeAttribute('key', $hInstance->primary_key);
		$this->data_key = $primary_key;
		$css_class		= $this->getNodeAttribute('css_class', '');

		$export_mode	= $this->getNodeAttribute('export', '0');
		$export			= $export_mode != '0';
		$macro_filter	= $this->getNodeAttribute('macro_filter', '');

		?><form name="search_form" id="search_form" method="post"><?php
		?><input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN'] ?>"/><?php


		if ($this->_view != 'selection' && $export === TRUE && $export_mode == '2')
		{
			$export_field_list = array();
			foreach ($hInstance as $attribute_name => $attribute)
			{
				if (!($attribute instanceof FieldDefinition))
				{
					continue;
				}

				if ($attribute->function === FALSE)
				{
					continue;
				}

				if ($attribute->extract_csv !== TRUE)
				{
					continue;
				}

				Rights::getRightsByAttribute ( $object_to_use, $attribute_name, $read, $write );

				if ($read !== TRUE)
				{
					continue;
				}

				if ($attribute_name == $hInstance->primary_key)
				{
					continue;
				}

				$export_field_list[$attribute_name] = $attribute->name;
			}

			natsort($export_field_list);
			?>
			<div id="export_columns_<?php echo $this->id; ?>" style="display:none;">
				<h3>Sélection des champs à inclure dans l'export.</h3>
				<br />
				<hr />
				<a href="javascript:void(0);" onclick="$('input[type=checkbox][id^=<?php echo $this->id; ?>]').attr('checked', false);">Décocher tout</a> / 
				<a href="javascript:void(0);" onclick="$('input[type=checkbox][id^=<?php echo $this->id; ?>]').attr('checked', true);">Cocher tout</a>
				<br />
				<br />
				<div style="height:300px;overflow-y:scroll;">
					<input type="hidden" id="column_selection_<?php echo $this->id; ?>" name="column_selection" value="1" />
					<table>
						<?php
						foreach ($export_field_list as $sql_name => $display_name)
						{
						?>
						<tr>
							<td>
								<input id="<?php echo $this->id.'_'.$sql_name; ?>" type="checkbox" name="export_column[]" value="<?php echo $sql_name; ?>" checked="checked"/>
							</td>
							<td>
								<label for="<?php echo $this->id.'_'.$sql_name; ?>"/><?php echo $display_name; ?></label>
							</td>
						</tr>
						<?php
						}
						?>
					</table>
				</div>
			</div>
			<?php
		}

		//---Si macro filtre
		if (!empty($macro_filter))
		{
			$this->_renderMacroFilter($macro_filter);
		}

		?><table cellspacing="1" class="table_list tablesorter <?php echo $css_class ?>" id='<?php echo $this->id ?>'><thead class="ui-widget-header"><?php
		?><tr class="listing-header" style='height:20px'><?php

		/**
		 *  Génération de l'en-tête des titres de colonnes.
		 */

		$this->start_c_sort = 0;
		$this->end_c_sort = 0;
		if ($this->_view != 'selection')
		{
			$this->start_c_sort++;
			?><th class="ui-widget-header" colspan="2"></th><?php

			$this->start_c_sort++;
			?><th class="ui-widget-header"></th><?php
		}
		else
		{
			if (isset($_GET['m2m_action']))
			{
				$this->start_c_sort++;
				?><th class="ui-widget-header"></th><?php
			}
			else
			{
				$this->start_c_sort++;
				?><th class="ui-widget-header" colspan="2"></th><?php
			}
		}
		$this->end_c_sort = $this->start_c_sort;

		foreach($this->_childs AS $child)
		{
			$child_id = $child->getNodeAttribute('id','');
			$width = $child->getNodeAttribute('width','');
			
			if($child_id == 'search_pk')
			{
				$child->attributes['attribute'] = $child->getNodeAttribute('attribute', $this->data_key);
			}
			
			switch($child->name)
			{
				case 'text':
					?><th class="ui-widget-header" style="width: 10px;"><?php echo $child->getNodeAttribute('string') ?></th><?php
					$this->end_c_sort++;
					break;
				case 'selector':
				case 'reference':
				case 'function':
					?><th class="ui-widget-header"><?php echo $child->getNodeAttribute('string') ?></th><?php
					$this->end_c_sort++;
					break;
				case 'field':
					$label = $child->getLabel();
					$tooltip_value = $child->getNodeAttribute('tooltip','');
					?><th class="ui-widget-header"><?php
					if(!empty($tooltip_value))
					{
						?><span title="<?= $tooltip_value ?>" class="tooltip_link"><?php	
					}
					echo $label;
					if(!empty($tooltip_value))
					{
						?></span><?php	
					}
					?></th><?php
					$this->end_c_sort++;
					break;
			}
		}

		?><th class="ui-widget-header"></th></tr><?php

		if($action != 'historic')
		{
			?><tr><?php
			if($this->_view != 'selection')
			{
				?><th class="ui-widget-header" colspan="2"><?php

				if ($export == TRUE)
				{
					if ($export_mode == '1')
					{
						$url = './index.php?action='.$object_to_use.'.export_csv&token='.$_SESSION['_TOKEN'];

						foreach($_GET as $k=>$g)
						{
							if(substr($k,0,6)=='crypt/' || $k == 'action' || $k == 'token')
							{
								continue;
							}
							
							Security::crypt($g, $cg);
							
							$url .= '&crypt/'.$k.'='. $cg;
						}

					?>
						<a style='cursor:pointer' onclick="document.search_form.action='<?php echo $url; ?>';document.search_form.submit();document.search_form.action='';"><img border="0" src="./library/killi/images/export.gif" style="vertical-align: middle;"></a>
					<?php
					}
					elseif ($export_mode == '2')
					{
						$url = '';

						foreach($_GET as $k=>$g)
						{
							if(substr($k,0,6)=='crypt/' || $k == 'action' || $k == 'token')
							{
								continue;
							}
							
							Security::crypt($g, $cg);
							
							$url .= '&crypt/'.$k.'='. $cg;
						}

					?>
						<a style='cursor:pointer' onclick="export_csv_btn('<?php echo $this->id; ?>', '<?php echo $object_to_use; ?>', '<?php echo $url; ?>')"><img border="0" src="./library/killi/images/export.gif" style="vertical-align: middle;"></a>
					<?php
					}
				}
				?></th><?php

				?><th class="ui-widget-header" style="width: 10px;"><input id="check_all_value" type="checkbox" onchange="checkAll(this)" style="vertical-align: middle;"></th><?php
			}
			else
			{
				if (isset($_GET['m2m_action']))
				{
					?><th class="ui-widget-header"></th><?php
				}
				else
				{
					?><th class="ui-widget-header" colspan="2"></th><?php
				}
			}

			/**
			 *  Génération des filtres.
			 */

			foreach($this->_childs AS $child)
			{
				switch($child->name)
				{
					case 'text':
					case 'function':
					case 'reference':
						?><th class="ui-widget-header"></th><?php
						break;
					case 'selector':
						$attribute = $child->getNodeAttribute('attribute', '');
						if(empty($attribute))
						{
							$obj	= $child->getNodeAttribute('object');
							$name	= $child->getNodeAttribute('name');

							$hSORM = ORM::getORMInstance($obj);
							$object_id_list = array();
							$hSORM->search($object_id_list, $num_object);

							$hSInstance = ORM::getControllerInstance($obj);
							$reference_list = array();
							$hSInstance->getReferenceString($object_id_list,$reference_list);

							?><th class="ui-widget-header"><?php
							?><select class='search_input' <?php if(empty($reference_list)) echo' style="display:none"'; ?> id="crypt_<?php echo $name; ?>_all_value" onchange="changeAll('<?php echo $name; ?>')"><?php

							?><option value=""></option><?php

							foreach($reference_list as $id => $reference)
							{
								$crypt_id = NULL;
								Security::crypt($id, $crypt_id);
								?><option value="<?php echo $crypt_id; ?>"><?php echo $reference; ?></option><?php
							}
							?></select></th><?php
						}
						else
						{
							?><th class="ui-widget-header"></th><?php
						}
						break;
					case 'field':
						?><th class="ui-widget-header" <?= ($child->getNodeAttribute('id','')=='search_pk'?'style="width:1px"':'') ?> ><?php
						$search = $child->getNodeAttribute('search', '0') == '1';
						if($search)
						{
							$child->renderFilter();
						}
						?></th><?php
						break;
				}
			}

			?><th class="ui-widget-header" style='width:16px'><?php
			?><div style="background: url('./library/killi/images/gtk-find.png') no-repeat top center; cursor: pointer; width: 16px; height: 16px; border:  none;background-color:transparent ! important;" onclick='document.search_form.submit();'></div><?php
			?></th></tr><?php
		}
		?></thead><tbody><?php

	}

	public function close()
	{
		?></tbody></table></form><?php
		$object = $this->getNodeAttribute('object');

		$total_number_object  = UI::getTotalObject();

		if (count($this->_data_list[$object]) > 1 && count($this->_data_list[$object]) == $total_number_object)
		{
			?><script>$(document).ready(function()
			{
				$('#<?php echo $this->id ?>').tablesorter({
					 headers: {
						 <?php
						 for($i=0; $i!=$this->start_c_sort; $i++)
						 {
							echo $i;
							?>
							: {
								sorter: false
							},
							<?php
						 }
						 ?>
							<?php echo $this->end_c_sort ?>: {
								sorter: false
							}
						}
					});
			});
			</script>
			<?php
		}

		return TRUE;
	}

	public function render($data_list, $view)
	{
		if($view != 'search' && $view != 'selection')
		{
			return true;
		}

		global $search_view_num_records, $ui_theme;

		/* Parameters. */
		$this->_data_list = &$data_list;
		$this->_view = $view;

		if ($ui_theme !== NULL)
		{
			$edit_icon   = '<span class="ui-icon ui-icon-pencil">&nbsp;</span>';
			$unlink_icon = '<span class="ui-icon ui-icon-trash">&nbsp;</span>';
		}
		else
		{
			$edit_icon   = '<img class="icone_edit" border="0" src="./library/killi/images/edit.png">';
			$unlink_icon = '<img border="0" width="16px" src="./library/killi/images/gtk-delete.png">';
		}

		$raw = explode('.', $_GET['action']);
		$action = $raw[1];
		unset($raw);

		$object			= strtolower($this->getNodeAttribute('object'));
		$hInstance = ORM::getObjectInstance($object);
		$primary_key_attribute	= $this->getNodeAttribute('key', $hInstance->primary_key);
		$selectable		= $this->getNodeAttribute('selectable', '');
		$delete_status	= $hInstance->delete == '1';

		if(!array_key_exists($object, $this->_data_list))
		{
			throw new Exception('Impossible de trouver ' . $object . ' dans le tableau data_list');
		}

		/* Recursive Rendering. */
		echo '<!-- Open ', $this->name, ' -->', PHP_EOL;
		$this->open();

		//---Si pas d'object
		if(count($this->_data_list[$object]) == 0)
		{
			?><tr><td style="text-align: center;" colspan="<?php echo (4+count($this->_childs)) ?>"><br />- PAS DE DONNEES -<br /><br /></td></tr><?php
		}

		if(isset($_GET['m2m_action']))
		{
			Security::crypt($_GET['primary_key'],$crypt_pk);
			?><input type="hidden" name="crypt/primary_key" value="<?php echo $crypt_pk ?>"><?php
		}

		//---Parcours des objects
		$this->_index = (isset($_GET['index']) && intval($_GET['index']) >= 0)?intval($_GET['index']): 0;
		$position = $this->_index * $search_view_num_records;

		if($this->_view === 'selection' && !isset($_GET['m2m_action']))
		{
			//---On recup la reference HR
			$hInstance = ORM::getControllerInstance($object);
			$reference_list = array();
			$id_list = array_keys($this->_data_list[$object]);
			if(!empty($id_list))
			{
				$hInstance->getReferenceString($id_list, $reference_list);
				unset($hInstance);
				if(count($id_list) != count($reference_list))
				{
					throw new Exception('La methode getReferenceString de l\'objet ' . $object . ' ne retourne pas le bon nombre de référence.');
				}
			}
		}

		foreach($this->_data_list[$object] AS $real_key=>$current_data)
		{
			if(!isset($current_data[$primary_key_attribute]))
			{
				throw new Exception("Le tableau _current_data ne contient pas la primary key, _data_list a peut être été modifié à la volée !!");
			}

			$current_id = $current_data[$primary_key_attribute]['value'];

			//---Cryp pk
			$crypt_pk = NULL;
			Security::crypt($current_id, $crypt_pk);
			?><tr><?php

			//---Get list for view type
			$new_view_type_get_list = "?";
			foreach($_GET as $key2 => $value)
			{
				if (($key2!='view') && ($key2!='index') && ($key2!='workflow_node_id') && ($key2!='crypt/workflow_node_id') && ($key2!='crypt/primary_key') && ($key2!='primary_key') && ($key2!='domain'))
				{
					if(is_array($value))
					{
						foreach($value as $val)
						{
							$new_view_type_get_list.= $key2.'[]='.$val.'&';
						}
					}
					else
					{
						$new_view_type_get_list.= $key2.'='.$value.'&';
					}
				}
			}

			/**
			 * Rendu des premières colonnes.
			 */
			if($this->_view === 'selection')
			{
				$s = $_GET['input_name'];

				if(!isset($_GET['m2m_action']))
				{
					$d = addslashes(str_replace(array('"', "\r", "\n"), array(' ',' ', ' '), $reference_list[$real_key]));
					if(UI::getTotalObject() == 1)
					{
						echo '<script>javascript:sendReferenceToParent(\'', $crypt_pk, '\',\'', $d, '\',\'', $s, '\')</script>';
					}

					?><td style="width: 30px;"><a onClick="return sendReferenceToParent('<?php echo $crypt_pk ?>','<?php echo $d ?>','<?php echo $s ?>')" href="#">#<?php echo ($position+1) ?></a></td><td><?php
				}
				else
				{
					?><td style="width: 30px;"><input class="selected" name="crypt/selected[]" value="<?php echo $crypt_pk; ?>" type="checkbox"<?php echo (isset($_GET['selected_ids']) && is_array($_GET['selected_ids']) && in_array($current_id, $_GET['selected_ids']) ? ' disabled checked' : ''); ?>></td><?php
				}
			}
			else
			{
				/* Index dans le listing. */
				?><td style="width: 30px;">#<?php echo ($position+1) ?></td><?php

				/* Mode édition de l'objet. */
				if($action != 'historic')
				{
					$url_edit = './index.php' . $new_view_type_get_list . 'view=form' . (isset($_GET['index']) ? '&index=' . $_GET['index'] : '') . '&crypt/primary_key='. $crypt_pk;
					?><td style="width: 15px;"><a href="<?php echo $url_edit; ?>"><?php echo $edit_icon; ?></a></td><?php
				}
				else
				{
					?><td style="width: 15px;"></td><?php
				}

				/* Bouton d'action */
				?><td style='text-align:center'><?php
				$disabled = false;
				if ($selectable != NULL)
				{
					$raw = explode('.', $selectable);
					$disabled = $current_data[$raw[1]]['value'] != true;
				}

				$listing_selection = array();
				if(isset($_POST['listing_selection']))
				{
					$listing_selection = $_POST['listing_selection'];
				}

				if(isset($_SESSION['_POST']))
				{
					if(isset($_SESSION['_POST']['listing_selection']))
					{
						$listing_selection = $_SESSION['_POST']['listing_selection'];
					}
				}

				?><input style="vertical-align: middle;" class="multi_selector" name="crypt/listing_selection[]" value="<?php echo $crypt_pk ?>" <?php echo $disabled?'DISABLED':'' ?><?php echo (in_array($current_id, $listing_selection) ? ' checked' : ''); ?> type="checkbox"/><?php
				?></td><?php
			}

			/**
			 * Rendu des colonnes de l'objet.
			 */
			foreach($this->_childs AS $child)
			{
				switch($child->name)
				{
					case 'text':
					case 'reference':
					case 'selector':
					case 'field':
						?><td><?php
						$child->setAttribute('inside_listing', '1');
						$child->setAttribute('readonly', '1');
						$child->setData($this->_data_list[$object][$real_key]);
						$child->current_id = $current_id;
						$child->real_key = $real_key;
						$child->src = $object;
						$child->render($this->_data_list, $view);
						?></td><?php
						break;
				}
			}

			/**
			 * Rendu des dernières colonnes.
			 */
			if($this->_view != 'selection')
			{
				?><td style="text-align: center; width: 10px;"><?php
				//---On regarde si on a le droit de supprimer l'object
				if($delete_status === TRUE)
				{
					?><a href="javascript:dial_delete_confirm('<?= $crypt_pk ?>','<?php echo $object ?>' );"><?php echo $unlink_icon; ?></a><?php
				}
				?></td><?php
			}
			else
			{
				?><td colspan="2"/><?php
			}

			?></tr><?php
			$position++;
		}
		$this->close();
		echo '<!-- Close ', $this->name, ' -->', PHP_EOL;
	}
}
