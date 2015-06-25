<?php

/**
 *  @class KeyXMLNode
 *  @Revision $Revision: 4676 $
 *
 */

class ListingXMLNode extends XMLNode
{
	protected $_listing_title = '';

	public $object_instance;
	public $data_key;

	static $draggable_loaded = FALSE;
	static $listing_inline_create = FALSE;

	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		parent::__construct($structure, $parent, $view);
		$data_src	= $this->getNodeAttribute('data_src');
		$subid		= str_replace('.', '_', $data_src);
		$this->id	= $this->name.'_'.$this->getNodeAttribute('id', $subid);
	}

	public function render($data_list, $view)
	{
		$this->_data_list	= &$data_list;
		$this->_view		= $view;

		if(!$this->check_render_condition())
		{
			return FALSE;
		}

		global $ui_theme;
		$class			= $this->getNodeAttribute('object');
		$hInstance		= ORM::getObjectInstance($class, FALSE);
		$this->object_instance	= $hInstance;

		$this->_listing_title = '(Objet '.get_class($hInstance).')';

		$data_key				= $this->getNodeAttribute('key', $hInstance->primary_key);
		$this->data_key			= $data_key;
		$data_src				= $this->getNodeAttribute('data_src');
		$title = '';
		$this->_getStringFromAttributes($title);
		if(!empty($title))
		{
			$this->_listing_title = $title;
		}
		$action					= $this->getNodeAttribute('action', $class.'.edit');
		$set_target				= $this->getNodeAttribute('target', 'popup');
		$create_width			= $this->getNodeAttribute('create_width', '1000px');
		$create_height			= $this->getNodeAttribute('create_height', '400px');
		$unlink_attr			= $this->getNodeAttribute('unlink', '0');
		$select_attr			= $this->getNodeAttribute('select', '0');
		$create_attr			= $this->getNodeAttribute('create', '0');
		$edit					= $this->getNodeAttribute('edit', '1') == '1';
		$domain					= $this->getNodeAttribute('domain', '');
		$environment			= $this->getNodeAttribute('env', '');
		$get_vars				= $this->getNodeAttribute('get', '');
		$m2m_object				= $this->getNodeAttribute('m2m_object', '');
		$autoscrolling			= ($this->getNodeAttribute('autoscrolling', '0') == '1');
		$pagination_show		= $this->getNodeAttribute('pagination', NULL);
		$draggable				= $this->getNodeAttribute('draggable', '0') == '1';
		$drop_callback			= $this->getNodeAttribute('drop_callback', NULL);
		$drag_callback			= $this->getNodeAttribute('drag_callback', NULL);
		$css_class				= $this->getNodeAttribute('css_class', NULL);
		$export					= $this->getNodeAttribute('export', FALSE);
		$lazy_loading			= $this->getNodeAttribute('lazy_loading', '0') == '1';

		$this->id_content_table = $this->id.'_content';

		// Désactivation du lazy_loading sur appel ajax.
		if(!empty($_GET['render_node']))
		{
			$lazy_loading = false;
		}

		// droit de création
		{
			$create = ($create_attr != '0' && $this->_edition && $hInstance->create); // bool
		}

		// droit de lecture
		{
			$edit = ($edit && $hInstance->view); // bool
		}

		// droit de selection many2many
		{
			$select = false;
			if($select_attr != '0')
			{
				$select = true;

				if($select_attr == '1')
				{
					$parent_obj = $this->getParent('form')->getNodeAttribute('object');
					$m2m_select_action = $parent_obj.'.add'.get_class($hInstance);
				}
				else
				{
					$m2m_select_action = $select_attr;
				}
			}

			$select = ($select && $this->_edition); // bool

			if($select == TRUE)
			{
				/* Vérification des droits de création sur l'objet de type many2many. */

				if(!empty($m2m_object))
				{
					$M2MObject = ORM::getObjectInstance($m2m_object, FALSE);
				}
				else
				{
					$parent_obj = $this->getParent('form')->getNodeAttribute('object');
					try
					{
						$M2MObject = ORM::getObjectInstance($parent_obj . $class, FALSE);
					}
					catch(UndeclaredObjectException $e)
					{
						try
						{
							$M2MObject = ORM::getObjectInstance($class.$parent_obj, FALSE);
						}
						catch(UndeclaredObjectException $e)
						{
							throw new Exception('Impossible de determiner l\'objet de laison ('.$parent_obj.'/'.$class.')');
						}
					}
				}

				$select = $select && $M2MObject->create;
			}
		}

		// droit de suppression
		{
			$unlink = ($unlink_attr != '0' && $this->_edition); //bool

			if($unlink == TRUE)
			{
				/* Vérification des droits de suppression sur l'objet de type many2many ou de l'objet listé si ce n'est pas un objet de laison */

				$is_m2m=false;

				if(!empty($m2m_object))
				{
					$M2MObject = ORM::getObjectInstance($m2m_object, FALSE);
					$is_m2m=true;
				}
				else
				{
					$parent_obj = $this->getParent('form')->getNodeAttribute('object');
					try
					{
						$M2MObject = ORM::getObjectInstance($parent_obj . $class, FALSE);
						$is_m2m=true;
					}
					catch(UndeclaredObjectException $e)
					{
						try
						{
							$M2MObject = ORM::getObjectInstance($class.$parent_obj, FALSE);
							$is_m2m=true;
						}
						catch(UndeclaredObjectException $e)
						{
							$M2MObject = ORM::getObjectInstance($class, FALSE);
						}
					}
				}

				if($unlink_attr == '1')
				{
					if($is_m2m)
					{
						$parent_obj = $this->getParent('form')->getNodeAttribute('object');
						$m2m_unlink_action = $parent_obj.'.unlink'.get_class($hInstance);
					}
					else
					{
						$m2m_unlink_action = $class.'.unlink&refresh=1';
					}
				}
				else
				{
					$m2m_unlink_action = $unlink_attr;
				}

				$unlink = $unlink && $M2MObject->delete;
			}
		}

		/* Définition du thème */
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

		/* Gestion du domaine */
		if (!empty($domain))
		{
			$raw = explode('=', $domain);

			$raw1 = explode('.',$raw[0]);
			$raw2 = explode('.',$raw[1]);

			//---Cryp pk
			if (count($raw2)==1)
			{
				$domain=serialize(array(array($raw1[1],"=",$raw2[0])));
			}
			else
			{
				$domain=serialize(array(array($raw1[1],"=",$this->_current_data[$raw2[1]]['value'])));
			}

			Security::crypt($domain,$domain);
			$domain = '&crypt/domain='.$domain;
		}

		$enable_pagination = false;
		$search_available = FALSE;

		// données paginées
		if(strpos($data_src, '.'))
		{
			$enable_pagination 	   = true;

			if(isset($_GET['render_listing_page']))
			{
				$current_page = $_GET['render_listing_page'];
			}
			else if(isset($_COOKIE['rlp_'.$this->id]))
			{
				$current_page = $_COOKIE['rlp_'.$this->id];
			}
			else
			{
				$current_page = 0;
			}

			$data_src_content	  = array();
			$total_pagination	   = 0;
			list($object, $method) = explode('.',$data_src);

			if($pagination_show === NULL)
			{
				$enable_pagination = false;
				$pagination_show = 0;
				$current_page = null;
			}

			/**
			 * Vérifie si le bloc listing est filtrable.
			 */
			if(method_exists($object.'Method', $method))
			{
				/**
				 * Vérifie que la méthode a bien l'attribue $filters
				 */
				$m = new ReflectionMethod($object.'Method', $method);
				$params = $m->getParameters();
				foreach($params AS $p)
				{
					if($p->name == 'filters')
					{
						$search_available = TRUE;
						break;
					}
				}

				/**
				 * Vérifie qu'il existe au moins un champ filtrable.
				 */
				$found = false;
				foreach($this->_childs AS $child)
				{
					if($child->name == 'field')
					{
						$search = $child->getNodeAttribute('search', '0') == '1';
						if($search)
						{
							$found = true;
							break;
						}
					}
				}

				$search_available = $search_available && $found;
			}

			if(!$lazy_loading)
			{
				if(method_exists($object.'Method', $method))
				{
					ORM::getControllerInstance($object)->browse_listing($data_src_content, $total_pagination, $method, $pagination_show, $current_page);

					if($current_page*$pagination_show > $total_pagination && $total_pagination>0)
					{
						$current_page = ceil($total_pagination/$pagination_show)-1;
						ORM::getControllerInstance($object)->browse_listing($data_src_content, $total_pagination, $method, $pagination_show, $current_page);
					}
				}
				else
				{
					throw new Exception('La methode de pagination '.$object.'Method->'.$method.' n\'existe pas');
				}

				$this->_data_list[$data_src] = $data_src_content;
			}
		}
		else
		if(!array_key_exists($data_src,$this->_data_list))
		{
			throw new Exception("$data_src doesnt exists in \$data");
		}

		/* Génération du lien pour le Many2Many. */
		$add_new_onclick = '';
		if($select == TRUE)
		{
			if(!isset($_GET['crypt/primary_key']))
			{
				throw new Exception('L\'attribut select ne peut être utilisé qu\'avec des relations de type many2many.');
			}
			$main_crypt_id  = $_GET['crypt/primary_key'];

			/* Génération de la liste des id déjà présent dans la liste. */
			// TODO: Gérer le cas avec les blocs listings paginés.
			$add_new_onclick_ids = '';
			if(is_array($this->_data_list[$data_src]))
			{
				foreach($this->_data_list[$data_src] AS $object)
				{
					/* Cryptage de la clé primaire des objets liés. */
					Security::crypt($object[$data_key]['value'], $crypt_value);
					$add_new_onclick_ids .= "&crypt/selected_ids[]=".$crypt_value;
				}
			}
			/* Génération du lien 'select'. */
			$add_new_onclick = 'onclick="return window.open(\''
							 . './index.php?action=' . $class . '.edit'
							 . '&token='.$_SESSION['_TOKEN']
							 . '&view=selection'
							 . '&input_name='.$class.'/'.$data_key
							 . '&m2m_action='.$m2m_select_action
							 . '&crypt/primary_key='.$main_crypt_id
							 . $add_new_onclick_ids
							 . $domain
							 . '\',\'popup_'.rand(10000,99999)
							 . '\', config=\'height='.$create_height.', width='.$create_width.', toolbar=no, scrollbars=yes\')"';
		}

		//---Si droits creation sur object
		/* Génération du lien pour le create */
		$create_new_onclick='';
		if($create == TRUE)
		{
			//---On traite les variables ENV
			$env_args='';
			$env_input = array();
			if (!empty($environment))
			{
				$env_list  = explode(',', $environment);

				foreach($env_list as $env)
				{
					$raw		= explode('=',$env);
					$attr_name	= $raw[0];
					$env		= $raw[1];
					$raw		= explode('.',$env);
					$env_src	= $raw[0];

					if(isset($raw[1]))
					{
						if(array_key_exists($env_src, $this->_data_list))
						{
							$env_attribute	= $raw[1];
							$raw			= array_slice($this->_data_list[$env_src],0,1);
							if(empty($raw))
							{
								throw new Exception('L\'attribut \'env\' n\'est pas de la forme \'attribut_dest=objet.attribut_src\' !');
							}
							$env_data		= $raw[0];

							//---Crypt data

							Security::crypt($env_data[$env_attribute]['value'],$crypt);
							$env_args.="&crypt/$attr_name=$crypt";

							$env_input[$attr_name][$env_attribute] = $crypt;
						}
						else
							throw new Exception(sprintf("Erreur lors de la definition de votre block listing env %s introuvable", $env_src));
					}
					else
					{
						Security::crypt($env_src,$crypt);
						$env_args.="&crypt/$attr_name=$crypt";
					}

				}
			}

			/* Génération du lien de 'create' */
			$create_new_onclick = 'onclick="return window.open(\''
								. './index.php?action='.$class.'.edit'
								. $env_args
								. '&token='.$_SESSION['_TOKEN']
								. '&view=create'
								. '&input_name='.$class.'/'.$data_key
								. $domain
								. '\',\'popup_'.rand(1000000,9999999)
								. '\', config=\'height='.$create_height.', width='.$create_width.', toolbar=no, scrollbars=yes\')"';
		}

		//---Liste des GET à ajouter
		/* Génération de la liste des variables GET à passer en paramètres lors d'un edit. */
		$get_to_add = '';
		if (!empty($get_vars))
		{
			$gl = explode(';',$get_vars);

			foreach($gl as $v1)
			{
				$raw = explode('.', $v1);

				$get_class = $raw[0];
				if(!isset($raw[1]))
				{
					throw new Exception('get doit respecter le format : objet.attribut !');
				}
				$get_attr  = $raw[1];

				$raw = reset($this->_data_list[$get_class]);

				if(!isset($raw[$get_attr]['value']))
				{
					throw new Exception('L\'attribut ' . $get_attr . ' de l\'objet ' . $get_class . ' est vide !');
				}
				Security::crypt($raw[$get_attr]['value'], $crypt_value);

				$get_to_add .= 'crypt/' . $get_class . '/' . $get_attr . '=' . $crypt_value . '&';
			}
		}

		//---Liste des fields enfants
		$total = array();

		?><div id="<?= $this->id; ?>" <?php echo $drop_callback != NULL ? 'data-index="0"' : '' ?> class="listing<?php echo ($css_class!=NULL) ? ' '.$css_class : '' ?>"<?php echo ($autoscrolling) ? ' style="table-layout:fixed;"' : '' ?>><?php
			?><div class="listing_header ui-widget-header ui-state-hover cl"><?php
				?><div class="listing_action l"><?php
					if($export && strpos($data_src, '.') && !empty($this->_data_list[$data_src]))
					{
						$header = array();

						foreach($this->_childs as $key => $node)
						{
							if ($node->name === 'field')
							{
								$header[] = array('object' => $node->getNodeAttribute('object', NULL, TRUE), 'attribute' => $node->getNodeAttribute('attribute'));
							}
						}

						list($object, $method) = explode('.', $data_src);

						Security::crypt(serialize($header), $crypt_header);

						$url_export = './index.php?'.$_SERVER['QUERY_STRING'].'&action=' . $object . '.export_listing&method='.$method.'&crypt/header='.$crypt_header;

						if($pagination_show !== NULL)
						{
							$url_export .= '&pagination='.$pagination_show;
						}

						?><a href="<?= $url_export ?>"><img border="0" src="./library/killi/images/export.gif" style='vertical-align: middle;float:left;margin-left:5px;margin-right:5px'/></a><?php
					}

					if($search_available && $pagination_show !== NULL)
					{
						?><a href="#" onclick="$('#<?= $this->id; ?>').find('tr.listing_filters').toggle();"><img src="./library/killi/images/gtk-find.png" style="cursor: pointer;vertical-align: middle;"></a><?php
					}

				?></div><?php

				?><div class="listing_pagination r"><?php
				?><ul><?php

					if ($create === TRUE)
					{
						?><li><?php
						?><a <?= $create_new_onclick ?> href="#" style="vertical-align: sub;"><img width="16px" border="0" alt="create" src="./library/killi/images/new.gif"></a><?php

						if($enable_pagination)
						{
							?><span style="padding:5px">&#9632;</span><?php
						}
						?></li><?php
					}
					if ($select === TRUE)
					{
						?><li><?php
						?><a <?= $add_new_onclick ?> href="#" style="vertical-align: sub;"><img width="16px" border="0" alt="add" src="./library/killi/images/select.gif"></a><?php

						if($enable_pagination)
						{
							?><span style="padding:5px">&#9632;</span><?php
						}
						?></li><?php
					}

					if($enable_pagination)
					{
						$position   	= ($total_pagination>0) ?  ($current_page*$pagination_show)+1 : 0;
						$to 			= (($position+$pagination_show)>$total_pagination) ? $total_pagination : ($position + $pagination_show)-1 ;
						$last_index 	= $total_pagination == $pagination_show ? 0 : ceil($total_pagination/$pagination_show);
						$next_index 	= ($current_page<$last_index) ? $current_page + 1 : ($total_pagination-1);
						$previous_index	= ($current_page>0) ? $current_page - 1 : 0;

						?><li><div>[<?= $position ?>; <?= $to ?> / <?= number_format($total_pagination,0,',',' '); ?>]</div></li><?php

						if($last_index>1)
						{
							if($current_page > 0)
							{
								?><li><button type="button" onclick="javascript:ajax_listing_pagination('<?= $this->id; ?>',0,<?= $pagination_show; ?>);">1</button></li><?php
								?><li><button type="button" onclick="javascript:ajax_listing_pagination('<?= $this->id; ?>',<?= $previous_index; ?>,<?= $pagination_show; ?>);">Précédent</button></li><?php
							}

							?><li><button disabled><?= ($current_page+1); ?></button></li><?php

							if($current_page < ($last_index-1))
							{
								?><li><button type="button" onclick="javascript:ajax_listing_pagination('<?= $this->id; ?>',<?= $next_index; ?>,<?= $pagination_show; ?>);">Suivant</button></li><?php
								?><li><button type="button" onclick="javascript:ajax_listing_pagination('<?= $this->id; ?>',<?= $last_index-1; ?>,<?= $pagination_show; ?>);"><?= $last_index; ?></button></li><?php
							}
						}
					}
				?></ul><?php
				?></div><?php

				?><div class="listing_title"><?php
					echo $this->_listing_title;
				?></div><?php

			?></div><?php
			?><div class="listing_content"><?php

			$c_end=1;

			/**
			 * Contenu
			 */

			if ($autoscrolling)
			{
				?><div style="width:100%;overflow-x:auto;"><?php
			}

			?><table id="<?= $this->id_content_table; ?>" class="tablesorter table_list table table-striped table-hover table-bordered table-condensed" cellspacing="0" style="width:100%;"><?php
			?><thead class="ui-widget-header"><?php

			/**
			 * Affichage des noms de colonnes
			 */

			?><tr style='height:18px;text-align:left' class='listing-header'><?php

			if ($draggable == TRUE)
			{
				$c_end++;
				?><th></th><?php
			}

			if ($drop_callback != NULL && $this->_edition)
			{
				$c_end++;
				?><th class="drag-header"></th><?php
			}

			?><th colspan="2" style="width: 60px;">&nbsp;#</th><?php

				foreach($this->_childs AS $child)
				{
					$column_title = '';
					$column_visibility = '';
					switch($child->name)
					{
						case 'hidden':
							$column_visibility = ' style="display:none" ';
						case 'field':
							$column_title = $child->getNodeAttribute('string', '');
							if(empty($column_title))
							{
								$child_object = $child->getNodeAttribute('object', ATTRIBUTE_HAS_NO_DEFAULT_VALUE, TRUE);
								$default_label = ATTRIBUTE_HAS_NO_DEFAULT_VALUE;
								if(!empty($child_object))
								{
									$hInstance2 = ORM::getObjectInstance($child_object, FALSE);
									$attribute = $child->getNodeAttribute('attribute');
									if(!is_object($hInstance2->$attribute))
									{
										throw new Exception($attribute . ' n\'est pas un attribut de l\'objet ' . get_class($hInstance2));
									}
									$default_label = $hInstance2->$attribute->name;
								}
								$column_title = $child->getNodeAttribute('string', $default_label);
							}

						default:
							$c_end++;

							$column_title = $child->getNodeAttribute('string', $column_title);

							?><th<?= $this->style() ?><?= $column_visibility ?>><?= $column_title ?></th><?php
							break;
					}
				}

				?><th style="width: 15px;"></th><?php
			?></tr><?php

			/**
			 * Affichage des filtres
			 */
			?><tr class="listing_filters"><?php

			if ($draggable == TRUE)
			{
				$c_end++;
				?><th></th><?php
			}

			if ($drop_callback != NULL && $this->_edition)
			{
				$c_end++;
				?><th class="drag-header"></th><?php
			}

			?><th colspan="2" style="width: 60px;"></th><?php

				foreach($this->_childs AS $child)
				{
					$column_visibility = '';
					switch($child->name)
					{
						case 'hidden':
							$column_visibility = ' style="display:none" ';
						case 'field':
							?><th><?php
							$search = $child->getNodeAttribute('search', '0') == '1';
							if($search)
							{
								$child->renderFilter();
							}
							?></th><?php
							break;
						default:
							$c_end++;

							?><th<?= $this->style() ?><?= $column_visibility ?>></th><?php
							break;
					}
				}

				?><th style="width: 15px;"></th><?php
			?></tr><?php


			/**
			 * Affichage des datas.
			 */
			?></thead><tbody <?php if($drop_callback != NULL){ echo 'class="drop-target"'; } ?>><?php

			$i=0;

			if($lazy_loading)
			{
				?><tr class='tr_no_data'><td colspan="<?= 4+count($this->_childs) ?>" style="text-align: center; padding: 20px 0;"><img src="./library/killi/images/loader.gif"></td></tr><?php
			}
			else
			//----Si pas de data
			if (count($this->_data_list[$data_src])==0)
			{
				?><tr class='tr_no_data'><td colspan="<?= 4+count($this->_childs) ?>" style="text-align: center; font-size: 18px;"><br />- PAS DE DONNEES -<br /><br /></td></tr><?php
			}
			else
			if(is_array($this->_data_list[$data_src]))
			{
				foreach($this->_data_list[$data_src] as $o_id => $object)
				{
					//---Crypt primary key
					Security::crypt($object[$data_key]['value'], $crypt_value);

					switch($set_target)
						{
							case 'popup':
								$target='';
								$url = "javascript: void(0);";
								$popup_url = "./index.php?action=$action&inside_popup=1&view=form&token=".$_SESSION['_TOKEN']."&crypt/primary_key=$crypt_value&$get_to_add";
								$onclick = "onclick=\"return window.open('".$popup_url."','popup_".rand(1000000,9999999)."', config='height=600, width=800, toolbar=no, scrollbars=yes')\"";
								break;
							case 'blank':
								$target = '_blank';
								$url = "./index.php?action=$action&view=form&token=".$_SESSION['_TOKEN']."&crypt/primary_key=$crypt_value&$get_to_add";
								$onclick = '';
								break;
							case 'parent':
								$target = '_parent';
								$url = "./index.php?action=$action&view=form&token=".$_SESSION['_TOKEN']."&crypt/primary_key=$crypt_value&$get_to_add";
								$onclick = '';
								break;
							case 'self':
								$target = '_self';
								$url = "./index.php?action=$action&view=form&token=".$_SESSION['_TOKEN']."&crypt/primary_key=$crypt_value&$get_to_add";
								$onclick = '';
								break;
						}

					?><tr class="<?php if ($drop_callback != NULL && $this->_edition) { echo  ' drag_'. $class; }?>">
					<?php
						if ($drop_callback && $this->_edition)
						{
							?>
							<td class="drag-handle"></td>
							<?php
						}
					?>
						<td style="width: 15px;vertical-align:middle !important" class="listing-line-counter"><?php echo "#".($i + 1); ?></td><?php

						if ($draggable == TRUE)
						{
							Security::crypt($object[$data_key]['value'],$crypt_value);
							?><td style="width: 16px;"><img id="<?= 'drag_'.$class.'_'.$crypt_value ?>" src="./library/killi/images/drag_hand.gif"/></td><?php
						}

						if($edit === TRUE)
						{
							?><td style="width: 15px;vertical-align:middle !important" class="listing-icon-edit"><a target="<?= $target ?>" <?= $onclick ?> href="<?= $url ?>"><?php echo $edit_icon; ?></td><?php
						}
						else
						{
							?><td style="width: 15px;vertical-align:middle !important">&nbsp;</td><?php
						}

						foreach($this->_childs as $child)
						{
							$child->setAttribute('inside_listing', '1');
							$child->setData($this->_data_list[$data_src][$o_id]);

							switch($child->name)
							{
								case 'field':
								case 'hidden':
									$node_name = $child->name;
									$column_visibility = NULL;
									$child->real_key = $o_id;
									$child->src = $data_src;

									$attribute = $child->getNodeAttribute('attribute');
									$field_object = $child->getNodeAttribute('object', ATTRIBUTE_HAS_NO_DEFAULT_VALUE, TRUE);

									$total_attr = $child->getNodeAttribute('total', '0') == '1';
									if ($total_attr)
									{
										if (!isset($total[$attribute]))
											$total[$attribute] = 0;

										$total[$attribute] += $object[$attribute]['value'];
									}

									if ($node_name === 'hidden')
									{
										$column_visibility = ' ;display:none ';
									}
								default:
									if (!isset($field_object))
									{
										$field_object = NULL;
									}
									?><td class="<?= $field_object . '_' . $attribute ?>" style='vertical-align:middle !important<?=$column_visibility ?>'><?php
										$child->render($data_list, $view);
									?></td><?php
									break;
							}
						}

						//---Si suppression
						if ($unlink == TRUE)
						{
							$id_dialog = uniqid();

							Security::crypt($object[$data_key]['value'],$crypt);

							?><td style="text-align: center"><a href="javascript:dial_<?= $id_dialog ?>();"><?php echo $unlink_icon; ?></a></td><?php
							?><div style='display:none' id="dialog-confirm_<?= $id_dialog ?>" title="Suppression"><?php

									if($is_m2m)
									{
										$url = $m2m_unlink_action.'&crypt/key='.$crypt.'&crypt/primary_key='.$_GET['crypt/primary_key'];

										?><p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span><b>Attention !</b> Vous êtes sur le point de supprimer définitivement cette liaison.<br/>Êtes-vous sûr de vouloir continuer ?</p><?php
									}
									else
									{
										// crypt/key pour la retro
										$url = $m2m_unlink_action.'&crypt/primary_key='.$crypt.'&crypt/key='.$crypt;
										?><p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span><b>Attention !</b> Vous êtes sur le point de supprimer définitivement cet élément (<?= ucfirst($class) ?>)<br/>Êtes-vous sûr de vouloir continuer ?</p><?php
									}

									?></div><?php

									?><script>
										function dial_<?= $id_dialog ?>(){
											$( "#dialog-confirm_<?= $id_dialog ?>" ).dialog({
												resizable: false,
												modal: true,
												buttons: {
													"Supprimer": function() {
														window.location.href='./index.php?action=<?= $url ?>&token=<?= $_SESSION['_TOKEN'] ?>';
													},
													'Annuler': function() {
														$( this ).dialog( "close" );
													}
												}
											});
										}
									</script><?php

						}
						else
						{
							?><td class="listing-empty-td">&nbsp;</td><?php
						}

					?></tr><?php
					$i++;
				}
			}

		/* INLINE INSERTION */

		if(defined('INLINE_CREATE') && INLINE_CREATE && $create && !empty($env_input))
		{
			/*
			if(isset($_SESSION['_POST']))
			{
				echo display_array($_SESSION['_POST']);
				echo 'input/'.$class;
			}*/
			?>
			<tr class="listing-tr-inline-create h"><td><?php
			foreach($env_input AS $current_attr_key => $object_m2o_attr_list)
			{
				foreach($object_m2o_attr_list AS $object_m2o_attr => $object_m2o_value)
				{
					?><input type="hidden" name="crypt/input/<?php echo $class;?>/<?php echo $object_m2o_attr; ?>[]" value="<?php echo $object_m2o_value; ?>"/><?php
				}
			}
			?></td><td class="listing-btn-remove-item"><i class="fa fa-minus"></i></td><?php

			foreach($this->_childs AS $child)
			{
				echo '<td>';
				$child->renderInput();
				echo '</td>';
			}
			?></tr><?php
		}

		?></tbody><?php

		if (count($total)>0)
		{
			?><tfoot><?php
				?><tr>

				<th class="ui-widget-header ui-state-hover" style="border: solid 0px;">&nbsp;</th>
				<th class="ui-widget-header ui-state-hover" style="border: solid 0px;">&nbsp;</th><?php

				foreach($this->_childs as $child)
				{
					if ($child->name==='field')
					{
						$total_attr = $child->getNodeAttribute('total', '0') == '1';
						if ($total_attr)
						{
							$format = $child->getNodeAttribute('format', '%s');
							$unit	= $child->getNodeAttribute('unit', '');
							$attribute = $child->getNodeAttribute('attribute');
							?><th style="border:none" class="ui-widget-header ui-state-hover">Total : <?php printf($format." %s", $total[$attribute], $unit) ?></th><?php
						}
						else
						{
							?><th class="ui-widget-header ui-state-hover" style="border: solid 0px;">&nbsp;</th><?php
						}
					}
				}

				//---Si mode = edition ---> affiche le bouton de suppression
				if ($this->_edition===TRUE)
				{
					?><th class="ui-widget-header ui-state-hover" style="border: solid 0px;">&nbsp;</th><?php
				}

					?><th class="ui-widget-header ui-state-hover" style="border: solid 0px;">&nbsp;</th>
				</tr><?php
				?></tfoot><?php
		}

		?></table><?php

		if ($autoscrolling):?></div><?php endif;

		/**
		 * Fin de contenu
		 */

		?></div></div><?php
		if(defined('INLINE_CREATE') && INLINE_CREATE && $create && !empty($env_input))
		{
			?>
			<div class="listing-btn-add-item l">
				<span style="font-size: 12px; color:#0D0;"><i class="fa fa-plus"></i></span> Ajouter un item
			</div>
			<div class="cl"></div>
			<?php

			if (!self::$listing_inline_create)
			{
				self::$listing_inline_create = TRUE;
				?><script src="./library/killi/js/listing-inline-create.js"></script><?php
			}
		}

		//---Draggable
		if ($drop_callback != NULL && !self::$draggable_loaded)
		{
			self::$draggable_loaded = TRUE;

			?><script type="text/javascript">
			var dragAndDrop = {
				setDraggable: function(object)
				{
					object.draggable(
					{
						start: function(event, ui)
						{
							$(ui.helper).addClass("drag-helper");
							$(ui.helper).find('input').each(function()
							{
								$(this).attr('value', $(this).val());
							});
						},
						revert: function (droppableObj)
						{
							if (droppableObj === false)
							{
								return true;
							}
							else
							{
								return false;
							}
						},
						snap: true,
						handle: '.drag-handle',
						helper: 'clone'
					});
				},
				setDroppable: function(object, callback)
				{
					object.droppable(
					{
						drop: callback,
						activeClass: "drop-hover",
						hoverClass: "drop-active"
					});
				}
			};
			</script><?php
		}

		if ($drop_callback != NULL && $this->_edition)
		{
			?>
			<script type="text/javascript">
			$(document).ready(function()
			{
				dragAndDrop.setDraggable($("#<?= $this->id_content_table; ?> tr"));

				dragAndDrop.setDroppable($("#<?= $this->id; ?>"), <?=$drop_callback; ?>);
			});
			</script>
			<?php
		}

		//---Draggable
		if ($draggable == TRUE)
		{
			?><script type="text/javascript">
				$(document).ready(function()
				{<?php

				if(is_array($this->_data_list[$data_src]))
				{
					foreach($this->_data_list[$data_src] as $object)
					{
						Security::crypt($object[$data_key]['value'],$crypt_value);
						?>$("<?= '#drag_'.$class.'_'.$crypt_value ?>").draggable({revert: true, snap: true});
						<?php
					}
				}
			?>});
			</script><?php
		}

		if($lazy_loading)
		{
			?><script type="text/javascript">
			var f = function()
			{
				ajax_listing_pagination('<?= $this->id; ?>',0,<?= $pagination_show; ?>);
			};
			push_loading(f);
			</script>
			<?php

			/* Chargement du reste du listing par le lazy_loading. */
			return TRUE;
		}

		if($search_available)
		{
			?><script type="text/javascript">
				var tr = $('#<?= $this->id; ?>').find('tr.listing_filters');
				tr.data('filter_enabled', true);
				tr.find('input,select,textarea').each(function() {
					$(this).keydown(function(e) {
						if ( e.keyCode == 13 )
						{
							console.log('keyDown: <?= $this->id; ?>');
							e.preventDefault();
							ajax_listing_pagination_wait('<?= $this->id; ?>');
							ajax_listing_pagination('<?= $this->id; ?>',0,0);
						}
					});
				});
				</script>
			<?php
		}

		if(count($this->_data_list[$data_src])>1 && !$enable_pagination)
		{
			?><script>$(document).ready(function()
			{
				$('#<?php echo $this->id_content_table; ?>').tablesorter({
					 headers: {
							<?= $c_end--; ?>: {
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
}
