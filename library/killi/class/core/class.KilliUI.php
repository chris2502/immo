<?php

/**
 *  Classe de génération de contenu HTML à partir des fichiers de templates XML.
 *
 *  @class KilliUI
 *  @Revision $Revision: 4592 $
 *
 */

/*. require_module 'standard';  .*/
/*. require_module 'libxml';	.*/
/*. require_module 'simplexml'; .*/
/*. require_module 'mbstring';  .*/

//require_once __DIR__.'/./class.Common.php';
define('ATTRIBUTE_HAS_NO_DEFAULT_VALUE', 'Attribute has no default value');

class StandardTag
{
	public $id		= '';
	public $style	= '';

	/**
	 * @param string $attr_name
	 * @param string $value
	 * @return string
	 */
	public function renderStrAttr($attr_name, $value)
	{
		if($value != '')
			return $attr_name . '="' . $value . '"';
		else
			return '';
	}

	/**
	 * @param string $attr_name
	 * @param boolean $value
	 * @return string
	 */
	public function renderBoolAttr($attr_name, $value)
	{
		return ($value === TRUE)?$attr_name:'';
	}
}

class EventableTag extends StandardTag
{
	public $onclick = /*.(string).*/ NULL;
}

class ButtonTag extends EventableTag
{
	#public $disabled = FALSE;
	public $disabled = NULL;
	public $name	 = '';
	public $type	 = '';
	public $content  = '';
	public $class 	 = '';

	public $raw_style = '';

	/**
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('<button %s %s %s %s %s %s %s >%s</button>',
			$this->class,
			$this->renderStrAttr("id", $this->id),
			$this->raw_style,
			$this->renderStrAttr("onclick", $this->onclick),
			$this->renderBoolAttr("disabled", $this->disabled),
			#$this->disabled,
			$this->name,
			$this->renderStrAttr("type", $this->type),
			$this->content);
	}
}

abstract class KilliUI
{
	public $_node_list				= array();
	public $_data_list				= NULL;
	public static $_form_object		= NULL;
	public $_node_hierarchy			= NULL;
	public $_total_number_object	= 0;
	public static $total_number_object	= 0;
	public $_node_index				= 0;
	public $_edition				= FALSE;
	public $_view					= 'search';
	public static $_refresh_data	= array();
	public static $_start_time_render = 0.0;

	private static $_instances		= array();
	private $_hDB;

	protected static $_rendered_page = FALSE;
	protected static $_theme	=	UI_THEME;

	/**
	 * @return void
	 * @throws Exception
	 */
	public function __construct()
	{
		global $hDB; //---On la recup depuis l'index ;-)
		$this->_hDB = $hDB;

		if (isset($_GET['view']))
			$this->_view = strval($_GET['view']);

		if (isset($_GET['mode']) && ($_GET['mode']==='edition'))
			$this->_edition = TRUE;

		if ($this->_view==='create')
			$this->_edition = TRUE;

		if ($this->_view==='panel')
			$this->_edition = TRUE;

		$this->_index = (isset($_GET['index']) && intval($_GET['index']) >= 0)?intval($_GET['index']): 0;
	}

	//.........................................................................
	public static function getInstance($template_file = NULL)
	{
		/* Ancien comportement maintenu. */
		if($template_file === NULL)
		{
			global $hUI;
			/* Si une UI globale est déja chargé, on la retourne. */
			if($hUI !== NULL)
			{
				return $hUI;
			}

			/* S'il existe au moins une instance déjà existante, on la retourne. */
			if(!empty(self::$_instances))
			{
				return reset(self::$_instances);
			}

			/* Enfin, si aucune instance n'est présente, on en génère une. */
			$hUI = new UI();
			return $hUI;
		}

		if(!is_string($template_file) || empty($template_file))
		{
			throw new Exception("template_file must be a non-empty string");
		}

		$path = realpath($template_file);
		if($path === FALSE)
		{
			throw new Exception('The template file ' . $template_file . ' doesn\'t exists or havn\'t read access !');
		}

		if(!isset(self::$_instances[$path]))
		{
			self::$_instances[$path] = new UI();
			self::$_instances[$path]->load($path);
		}
		return self::$_instances[$path];
	}

	/**
	 * Permet de définir le thème de l'interface.
	 *
	 * @param string $theme
	 */
	public static function setTheme($theme)
	{
		if(!file_exists(KILLI_DIR . 'css/'.$theme.'/jquery.ui.'.$theme.'.all.css'))
		{
			return FALSE;
		}
		self::$_theme = $theme;
		return TRUE;
	}

	/**
	 * Retourne le nom du thème utilisé par l'interface.
	 *
	 * @return string
	 */
	public static function getTheme()
	{
		return self::$_theme;
	}

	/**
	 * Charge un template dans UI
	 */
	public function load($template_file)
	{
		$root = TemplateLoader::load($template_file);
		$this->_node_hierarchy = new HTMLXMLNode($root[0], NULL, $this->_view);
	}

	/**
	 * Effectue le rendu complet de la page
	 */
	public function renderAll($view, $data_list = array(), $total_number_object = 0)
	{
		$this->_view = $view;
		if($this->_node_hierarchy === NULL)
		{
			throw new Exception('renderAll must be call after load method !');
		}

		self::$total_number_object	= $total_number_object;

		//---Copie locale et privée des données de rendu
		$this->_data_list			= &$data_list;
		$this->_total_number_object = $total_number_object;

		if(isset($_GET['crypt/selected_ids']))
		{
			setcookie('_selected', join(',', $_GET['crypt/selected_ids']));
		}

		self::$_start_time_render = microtime(true);
		$this->_node_hierarchy->render($data_list, $this->_view);
		self::$_start_time_render = (microtime(true) - self::$_start_time_render);

		self::$_rendered_page = TRUE;

		return TRUE;
	}

	/**
	 * Effectue le rendu d'un node spécifique à partir de son ID
	 */
	public function renderNodeId($node_id, $object_list = array())
	{
		/* Rendu du sous noeud. */
		$sub_node = $this->_node_hierarchy->getNodeById($node_id);
		if(!$sub_node instanceof XMLNode)
		{
			throw new Exception('Impossible de trouver le XMLNode "' . $node_id . '" !');
		}
		$sub_node->render($object_list, $this->_view);
		return TRUE;
	}

	/**
	 * Effectue le rendu de l'ensemble des nodes ayant le node name spécifié.
	 */
	public function renderNodeName($node_name, $object_list = array())
	{
		/* Rendu du sous noeud. */
		$sub_nodes = $this->_node_hierarchy->getNodesByName($node_name);
		foreach($sub_nodes AS $sub_node)
		{
			if(!$sub_node instanceof XMLNode)
			{
				throw new Exception('Impossible de trouver le XMLNode "' . $node_name . '" !');
			}
			$sub_node->render($object_list, $this->_view);
		}
		return TRUE;
	}

	/**
	 * Récupère le noeud racine de l'arbre des XMLNode
	 */
	public function getRootNode()
	{
		return $this->_node_hierarchy;
	}

	/**
	 * Récupère les noeuds de l'arbre des XMLNode ayant le nom $node_name
	 */
	public function getNodesByName($node_name)
	{
		return $this->_node_hierarchy->getNodesByName($node_name);
	}

	public static function isRendered()
	{
		return self::$_rendered_page;
	}

	//.........................................................................
	/**
	 * static
	 * Recharge la page hôte
	 */
	public static function refreshOpener($redirect=null, $closeActiveWindow = TRUE)
	{
		if(KILLI_SCRIPT)
		{
			return TRUE;
		}

		if(isset($_GET['redirect']) && $redirect == null)
		{
			$redirect = $_GET['redirect'];
		}
		?>
		<html>
			<head></head>
			<script>
		<?php
			if($redirect!=null)
			{
			?>
				if(window.opener)
				{
					window.opener.location.href="<?=$redirect?>";
					<?php
					if($closeActiveWindow == TRUE)
					{
					   ?>
					   window.close();
					   <?php
					}
					?>
				}
				else window.location.href="<?=$redirect?>";
			<?php
			}
			else
			{
			?>
				window.opener.location.reload();
				<?php
				if($closeActiveWindow == TRUE)
				{
				   ?>
				   window.close();
				   <?php
				}
				?>
			<?php
			}
		?>
			</script>
			<body></body>
		</html>
		<?php
	}

	/**
	 * Quitte et retourne à la page précédente.
	 * Empêche une erreur critique si le HTTP_REFERER n'est pas défini.
	 * Permet de définir une action par défaut en cas d'absence de HTTP_REFERER
	 * ou de forcer une rediretion vers l'action souhaitée.
	 * N'effectue pas de commit par défaut pour le hDB global.
	 *
	 * @param string $action action par défaut en cas d'absence de HTTP_REFERER
	 * @param boolean $noReferer Utilisation de l'action par défaut au détriment de HTTP_REFERER
	 * @param boolean $use_commit Effectuer ou non un commit sur le hDB global
	 */
	public static function quitNBack($action=HOME_PAGE, $noReferer = FALSE, $use_commit = FALSE)
	{
		if ($use_commit)
		{
			global $hDB;
			$hDB->db_commit();
			Mailer::commit();
		}
		Performance::stop();
		self::goBackWithoutBrutalExit($action, $noReferer);
		exit(0);
	}

	/**
	 * Ferme le popup (création par ex)
	 * Sans die ni commit, l'execution se poursuit, faire un return pour revenir au niveau de l'index si besoin
	 *
	 */
	public static function closePopUp()
	{
		if(KILLI_SCRIPT)
		{
			return TRUE;
		}

		?>
			<html>
				<head></head>
				<script>
					self.close();
				</script>
				<body></body>
			</html>
		<?php

		return TRUE;
	}

	/**
	 * Définit le header pour retourner à la page précédente.
	 * Empêche une erreur critique si le HTTP_REFERER n'est pas défini.
	 * Permet de définir une action par défaut en cas d'absence de HTTP_REFERER
	 * ou de forcer une rediretion vers l'action souhaitée.
	 *
	 * @param string $action action par défaut en cas d'absence de HTTP_REFERER
	 * @param boolean $noReferer Utilisation de l'action par défaut au détriment de HTTP_REFERER
	 */
	public static function goBackWithoutBrutalExit($action=HOME_PAGE, $noReferer = FALSE)
	{
		if(KILLI_SCRIPT)
		{
			return TRUE;
		}

		if (!$noReferer && isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']))
		{
			header('Location: '.$_SERVER['HTTP_REFERER']);
		}
		else
		{
			header('Location: ./index.php?action='.$action.'&token='.(isset($_SESSION['_TOKEN'])?$_SESSION['_TOKEN']:''));
		}
	}

	/**
	 * Retourne le nombre total d'objet.
	 */
	public static function getTotalObject()
	{
		return self::$total_number_object;
	}

	public static function setRefreshField($refresh_time, $key, $config)
	{
		self::$_refresh_data[$refresh_time][$key] = $config;
		return TRUE;
	}

	/**
	 * Méthode qui permet d'appeler la création d'un XMLNode à l'ancienne ;-)
	 */
	public static function call_node($node_name, $open = 'open', $current_node = array(), $current_data = array())
	{
		$hUI = self::getInstance();
		$hUI->_current_node = $current_node;
		$hUI->_current_data = $current_data;

		if(isset($current_node['attributes']['object']))
		{
			$hUI::$_form_object = $current_node['attributes']['object'];
		}

		if($open == 'open')
		{
			$method_name = '_render_start_' . $node_name;
		}
		else
		{
			$method_name = '_render_end_' . $node_name;
		}

		if(method_exists($hUI, $method_name))
		{
			$hUI->$method_name();
		}
	}

	/**
	 * @param string $xml_template
	 * @param int $total_number_object
	 * @param array $object_list
	 * @param boolean $full
	 * @return boolean
	 * @throws Exception
	 */
	public function render($xml_template,$total_number_object,$object_list,$full=TRUE)
	{
		/* Chargement du template. */
		self::$_start_time_render = microtime(true);
		$this->load($xml_template);

		/**
		 * Ancien code à nettoyer
		 */
		self::$total_number_object	= $total_number_object;

		//---Copie locale et privée des données de rendu
		$this->_data_list			= &$object_list;
		$this->_total_number_object = $total_number_object;

		if(isset($_GET['crypt/selected_ids']))
		{
			setcookie('_selected', join(',', $_GET['crypt/selected_ids']));
		}

		//----Get XML struct
		if(file_exists($xml_template) && is_file($xml_template))
		{
			libxml_use_internal_errors(true);
			if (($xml = simplexml_load_file($xml_template, 'SimpleXMLElement',LIBXML_NOEMPTYTAG))===FALSE)
			{
				foreach(libxml_get_errors() as $error)
					throw new Exception("message : $error->message - file : $error->file - line : $error->line" );

			}
			libxml_use_internal_errors(false);

		}
		else
		{
			throw new NoTemplateException($xml_template.' doesnt exists');
		}

		self::_getXMLNode($xml, $this->_node_list);

		/**
		 * Pour les listings ajax.
		 */
		if(isset($_GET['render_node']))
		{
			/* Rendu du sous noeud. */
			$sub_node = $this->_node_hierarchy->getNodeById($_GET['render_node']);
			if(!$sub_node instanceof XMLNode)
			{
				throw new Exception('Impossible de trouver le XMLNode "' . $_GET['render_node'] . '" !');
			}
			$sub_node->render($object_list, $this->_view);
			return TRUE;

			/*
			foreach($this->_node_list as $key=>$node)
			{
				if(
				   (isset($node['id_end']) && $node['id_end']<$_GET['render_node'])
				|| (isset($node['id_end']) && $node['index']>$this->_node_list[$_GET['render_node']]['id_end'])
				){
					$this->_node_list[$key]['switch']=true;
					$this->_node_list[$node['id_end']]['switch']=true;
				}
			}
			*/
		}

		if (isset($_SESSION['_USER']) && (in_array(ADMIN_PROFIL_ID,$_SESSION['_USER']['profil_id']['value'])) && ($this->_view==='search' || $this->_view==='selection') )
		{
			$tmp_node = $this->_node_list ;
			$this->_node_list = array() ;

			for( $i = 0 ; $i < count( $tmp_node ) ; $i++ )
			{
				$this->_node_list[] = $tmp_node[ $i ] ;
				if( isset( $this->_node_list[ $i ][ 'name' ] ) && $this->_node_list[ $i ][ 'name' ] === 'start_list' )
				{
					//---PK
					$this->_node_list[] = array(
						'name'	   => 'start_field',
						'node_name'	   => 'field',
						'children'	   => array(),
						'id_parent'	   => null,
						'index'=>count($this->_node_list),
						'attributes' => array(
							'search'  => '1',
							'name'  => 'search_pk',
							'reference'  => '0',
							'attribute'   => ORM::getObjectInstance($this->_node_list[ $i ]['attributes']['object'])->primary_key,
							'object' => $this->_node_list[ $i ]['attributes']['object']
						)
					) ;

					$this->_node_list[] = array(
						'name'	   => 'end_field',
						'attributes' => array()
					) ;
				}
			}
		}

		if( isset( $_GET[ 'workflow_node_id'] ) )
		{
			$tmp_node = $this->_node_list ;

			// on recherche un wf button
			$has_button = FALSE;
			$inside_list = FALSE;
			for( $i = 0 ; $i < count( $tmp_node ) ; $i++ )
			{
				$isset_node_list_i_name = isset($this->_node_list[$i]['name']);

				if($isset_node_list_i_name && $this->_node_list[ $i ][ 'name' ] === 'start_list' )
				{
					$inside_list=TRUE;
					continue;
				}

				if(!$inside_list)
				{
					continue;
				}

				if($isset_node_list_i_name && $this->_node_list[ $i ][ 'name' ] === 'end_list' )
				{
					break;
				}

				if($isset_node_list_i_name  && $this->_node_list[ $i ][ 'name' ]!=='start_field' && $this->_node_list[ $i ][ 'name' ]!=='end_field')
				{
					$has_button = TRUE;
					break;
				}

				unset($isset_node_list_i_name);

			}

			if($has_button)
			{
				$this->_node_list = array() ;

				for( $i = 0 ; $i < count( $tmp_node ) ; $i++ )
				{
					$this->_node_list[] = $tmp_node[ $i ] ;
					if( isset( $this->_node_list[ $i ][ 'name' ] ) && $this->_node_list[ $i ][ 'name' ] === 'start_list' )
					{
						//---Menu de qualification
						$this->_node_list[] = array(
											'name'	   => 'start_selector',
											'node_name'	   => 'selector',
											'children'	   => array(),
											'id_parent'	   => null,
											'index'=>count($this->_node_list),
											'attributes' => array(
															'empty'  => 1,
															'string' => 'Qualification',
															'name'   => 'qualification_id',
															'object' => 'nodequalification'
														)
						) ;

						$this->_node_list[] = array(
											'name'	   => 'end_selector',
											'attributes' => array()
						) ;

						//---Champs text de commentaire
						$this->_node_list[] = array(
											'name'	   => 'start_text',
											'node_name'	   => 'text',
											'children'	   => array(),
											'id_parent'	   => null,
											'index'=>count($this->_node_list),
											'attributes' => array(
															'empty'  => 1,
															'string' => 'Commentaire',
															'name'   => 'comment',
															'width'  => '300px'
														)
						) ;

						$this->_node_list[] = array(
											'name'	   => 'end_text',
											'attributes' => array()
						) ;
					}

				}
			}
		};

		/**
		 * Rendu avec le nouveau système de rendu.
		 */
		$this->_node_hierarchy->render($object_list, $this->_view);

		self::$_start_time_render = (microtime(true) - self::$_start_time_render);

		self::$_rendered_page = TRUE;

		return TRUE;
	}
	//-------------------------------------------------------------------------
	static function _getXMLNode($xml,&$nodes,$id_parent=null,$with_restricted=FALSE)
	{


		foreach($xml as $key=>$value)
		{
			$id_start=count($nodes);

			$node = array();
			$node['index'] = count($nodes);
			$node['name'] = "start_".$key;
			$node['id_parent'] = $id_parent;
			$node['node_name'] = $key;
			$node['attributes']=array();
			$node['children']=array();

			//---Get attributes
			$attrs = array();
			if($value->attributes())
			{
				foreach($value->attributes() as $attr_key=>$attr_value)
				{
					$attrs[(string)"$attr_key"] = (string)$attr_value;
				}

				$node['attributes'] = $attrs;
			}
			else
			{
				if (!is_object($value[0]))
					$node['value'] = $value[0];
			}

			if (!self::_check_profile_node($node))
			{
				$node['restricted_view'] = true;
				if (!$with_restricted)
				{
					continue;
				}
			}
			else
			{
				$node['restricted_view'] = false;
			}


			if(!(isset($node['attributes']['object']) || isset($node['attributes']['type'])) && $key === 'field')
			{
				$parent = current($value->xpath('parent::*'));
				while($parent && !in_array($parent->getName(), array('create','list','listing','form')))
				{
					$parent = current($parent[0]->xpath('parent::*'));
				}

				$node['attributes']['object'] = (string)$parent['object'];
			}


			$nodes[] = $node;
			if($id_parent!=null)
			{
				$nodes[$id_parent]['children'][] = $id_start;
			}

			self::_getXMLNode($value, $nodes, $id_start, $with_restricted);

			$node = array();
			$node['index'] = count($nodes);
			$node['name'] = "end_".$key;
			$node['node_name'] = $key;
			$node['attributes'] = array();
			$node['id_start'] = $id_start;
			$node['id_parent'] = $id_parent;
			$nodes[$id_start]['id_end'] = $node['index'];

			$nodes[] = $node;
		}
	}
	//.....................................................................
	protected function getNodeAttribute($attribute, $default_value = ATTRIBUTE_HAS_NO_DEFAULT_VALUE)
	{
		if(empty($attribute))
		{
			throw new Exception('getNodeAttribute need not empty attribute.');
		}

		if($default_value == ATTRIBUTE_HAS_NO_DEFAULT_VALUE)
		{
			if(!array_key_exists('attributes', $this->_current_node) || !array_key_exists($attribute, $this->_current_node['attributes']))
			{
				throw new XmlTemplateErrorException(substr($this->_current_node['name'], 6) . ' tag need attribute : ' . $attribute);
			}
		}

		if(isset($this->_current_node['attributes'][$attribute]))
		{
			return $this->_current_node['attributes'][$attribute];
		}

		return $default_value;
	}
	//.........................................................................
	private function _getIdFromAttributes(&$id)
	{
		if(array_key_exists('attributes', $this->_current_node) && array_key_exists('id', $this->_current_node['attributes']))
			$id = $this->_current_node['attributes']['id'];
	}
	//.....................................................................
	private function _attributesToStyle($base_style = array())
	{
		$style = '';

		foreach($base_style AS $rule => $value)
		{
			$style .= $rule . ':' . $value . ';';
		}

		if (isset($this->_current_node['attributes']['attrs']))
		{
			//preg_match_all('/[^,]+(\(?[^\)]+\))?[^,]+(\(?[^\)]+\))?/',  $this->_current_node['attributes']['attrs'], $matches);
			//$raw = $matches[0];

			$raw = explode(',',$this->_current_node['attributes']['attrs']);

			foreach($raw as $attrs)
			{
				$matches = array() ;
				if(preg_match( '/^([^:]+)\:([^:]+):([^;]+)\;(.*)$/', $attrs, $matches) != 0 )
				{
					$hObject = ORM::getObjectInstance($matches[1]);
					if(isset( $hObject->primary_key ) === true)
					{
						// gestion des multi-style
						$styles = explode(';', $matches[4]); // color:red;border:3px;height:100px
						foreach($styles as $sub_style)
						{
							$sub_style=explode(':', $sub_style,2); // color:red

							$compare_location = 'value';

							if(property_exists($hObject, $matches[2]) && $hObject->$matches[2]->type=="workflow_status")
							{
								$compare_location='workflow_node_id';
							}

							if(property_exists($hObject, $matches[2]) && ($hObject->$matches[2]->type=="time" || $hObject->$matches[2]->type=="date"))
							{
								$compare_location='timestamp';
							}

							if( isset( $this->_current_data[$hObject->primary_key][ 'value' ]  ) === false )
							{
								if( isset( $this->_data_list[ $matches[ 1 ] ] ) === true )
								{
									$data = array_slice( $this->_data_list[ $matches[ 1 ] ], 0 ) ;
									$valueToCompare = (isset($data[$matches[2]][$compare_location]))? $data[$matches[2]][$compare_location] : '';
								}
							}
							elseif( isset( $this->_current_data[ $matches[ 2 ] ][ $compare_location ] ) === true )
							{
								$valueToCompare = $this->_current_data[$matches[2]][$compare_location];
							}
							else
							{
								$valueToCompare = '';
							}

							if(!is_array($valueToCompare))
							{
								$valueToCompare = array($valueToCompare);
							}

							$global_isOk = null;
							foreach($valueToCompare as $current_valueToCompare)
							{
								$isOk = false;

								if(preg_match('/^\{(.+)\}$/i',$matches[3], $null)!=0)
								{
									eval('$matches[3]='.substr($matches[3],1,-1).';');
								}

								if (isset($matches[3]))
								{
									if (preg_match('/(\(lt\)|\(gt\)|\(\!\)){1}(.*)/i', $matches[3], $arMatches) != 0)
									{
										switch ($arMatches[1])
										{
											case '(!)':
												$isOk = !(strcmp($arMatches[2],$current_valueToCompare)===0);
												break;
											case '(lt)':
												$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare < $arMatches[2]);
												break;
											case '(gt)':
												$isOk = is_numeric($current_valueToCompare) && ($current_valueToCompare > $arMatches[2]);
												break;
											default:
												throw new Exception('Unrecognized operator.');
												break;
										}
									}
									else
									{
										$isOk = (strcmp($current_valueToCompare,$matches[3])===0);
									}
								}

								$global_isOk = (($global_isOk===null || $global_isOk===TRUE) && $isOk===TRUE);
							}
							if ($global_isOk===true)
							{
								$style .= $sub_style[0] . ':' . $sub_style[1] . ';';
							}
						}
					}
				}
			}
		}

		//@todo optimsation des css pris en charge ?

		if (isset($this->_current_node['attributes']['color']))
			$style .= 'color: '. $this->_current_node['attributes']['color'] . ' !important;';

		if (isset($this->_current_node['attributes']['display']))
			$style .= 'display: ' . $this->_current_node['attributes']['display'] . ' !important;';

		if (isset($this->_current_node['attributes']['background-color']))
			$style .= 'background-color: ' . $this->_current_node['attributes']['background-color'] . ' !important;';

		if (isset($this->_current_node['attributes']['width']))
			$style .= 'width: ' . $this->_current_node['attributes']['width'] . ' !important;';

		if (isset($this->_current_node['attributes']['height']))
			$style .= 'height: ' . $this->_current_node['attributes']['height'] . ' !important;';

		if (isset($this->_current_node['attributes']['text-align']))
			$style .= 'text-align: ' . $this->_current_node['attributes']['text-align'] . ' !important;';

		if (isset($this->_current_node['attributes']['padding']))
			$style .= 'padding: ' . $this->_current_node['attributes']['padding'] . ' !important;';

		if (isset($this->_current_node['attributes']['border']) && !isset($this->_current_node['attributes']['border_color']))
			$style .= 'border: ' . $this->_current_node['attributes']['border'] . ' !important;';

		if (isset($this->_current_node['attributes']['border']) && isset($this->_current_node['attributes']['border_color']))
			$style .= 'border: ' . $this->_current_node['attributes']['border'] . 'px solid ' . $this->_current_node['attributes']['border_color'] . ' !important;';

		if (isset($this->_current_node['attributes']['border_color']) && !isset($this->_current_node['attributes']['border']))
			$style .= 'border : 1px solid ' . $this->_current_node['attributes']['border_color'] . ' !important;';

		//----------------------------------------------------------------
		// ajouter attribut attrs_type dans fichier xml
		if (isset($this->_current_node['attributes']['attrs_type']))
		{
			// Ajouts des class css pour document
			if( $this->_current_node['attributes']['attrs_type'] == 'document')
			{
					if(isset($this->_current_data[$this->_current_node['attributes']['attribute']]['value']))
					{
							$value_attr = $this->_current_data[$this->_current_node['attributes']['attribute']]['value'];
							$style .= '" class="document_etat document_' . strtolower($value_attr);
					}
			}
		}
		//----------------------------------------------------------------

		if($style=='')
		{
			return null;
		}

		return ' style="'.$style.= '"';
	}
	//.....................................................................
	private function _render_start_navigator()
	{
		if($this->_view === 'panel')
			return true;

		if($this->_view === 'wizard')
			return true;

		if(!array_key_exists('attributes', $this->_current_node))
		{
			throw new XmlTemplateErrorException('navigator must have an object attribute');
		}

	   	global $search_view_num_records;

	   	$raw					= explode('.',$_GET['action']);
	   	$object_name			= $raw[0];
	   	$hInstance				= ORM::getObjectInstance($object_name);
 		$attributes				= $this->_current_node['attributes'];
		$previous_index			= ($this->_index>0) ? $this->_index - 1 : 0;
		$position				= ($this->_total_number_object>0) ?  ($this->_index*$search_view_num_records)+1 : 0;
		$new_get_list			= '?';
		$new_mode_get_list		= '?';
		$new_view_type_get_list = '?';
		$edition_disabled		= ($this->_edition === TRUE)?'DISABLED':'';
		$enregistrer_status		= ($this->_edition === FALSE)?'DISABLED':'';

		$create_allow = $this->getNodeAttribute('create', '1') == '1' && $hInstance->create;
		$delete_allow = $this->getNodeAttribute('unlink', '1') == '1' && $hInstance->delete;
		$search_allow = $this->getNodeAttribute('search', '1') == '1';
		$edit_allow = $this->getNodeAttribute('edit', '1') == '1';
		$save_allow = $this->getNodeAttribute('save', '1') == '1';
//		$create_allow = (isset($this->_current_node['attributes']['create']) && $this->_current_node['attributes']['create']==0)?FALSE:$hInstance->create;
//		$delete_allow = (isset($this->_current_node['attributes']['unlink']) && $this->_current_node['attributes']['unlink']==0)?FALSE:$hInstance->delete;

		//---Get list for moving into records
		foreach($_GET as $key => $value)
		{
			//--Si déjà en version crypté . . . on passe
			if(!isset($_GET['crypt/'.$key]))
			{
				if($key != 'index' && $key != 'primary_key' && $key != 'selected_ids')
				{
					if(!is_array($value))
					{
							$new_get_list.= $key.'='.$value.'&';
					}
					else
					{
						foreach($value as $val)
						{
							$new_get_list.= $key.'[]='.$val.'&';
						}
					}
				}

				//---Get list for mode
				if ($key != 'mode')
				{
					if(!is_array($value))
					{
							$new_mode_get_list.= $key.'='.$value.'&';
					}
					else
					{
						foreach($value as $val)
						{
							$new_mode_get_list.= $key.'[]='.$val.'&';
						}
					}

				}
				//---Get list for view type
				if ($key != 'view' && $key != 'crypt/primary_key')
				{
					if(!is_array($value))
					{
							$new_view_type_get_list.= $key.'='.$value.'&';
					}
					else
					{
						foreach($value as $val)
						{
							$new_view_type_get_list.= $key.'[]='.$val.'&';
						}
					}
				}
			}
		}

		?><table class="navigator ui-widget-header ui-state-hover"><tr><?php

		if ($this->_view === 'search')
		{


			  if ($create_allow)
			  {
			  	?><td class="killi-button-container" style="width:120px;"><?php
				  ?><button id="bouton_create" onclick="return window.open('./index.php?action=<?= $object_name ?>.edit&view=create&token=<?= $_SESSION['_TOKEN'] ?>','popup_<?= rand(1000000,9999999) ?>','height=600, width=800, toolbar=no, scrollbars=yes')"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Créer</div></button><?php
			  	?></td><?php
			  }
			  ?><td class="other_buttons"><?php

			   $this->_display_listing_button();

			  ?></td><?php


		}
		else if ($this->_view === 'create')
		{
			if($hInstance->create)
			{
				// Les boutons "Enregistrer" et "Annuler" ne s'affichent que si l'objet l'autorise.
				// La paramètre "create" des templates n'est pas pris en compte.
				if (!isset($_GET['inside_popup']) || (isset($_GET['inside_popup']) && $_GET['inside_popup'] == 1))
				{
					?><td class="killi-button-container" style="width: 120px;"><button <?= $enregistrer_status ?> id="bouton_save" onclick="submitMain();"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Enregistrer</div></button></td><?php
					?><td class="killi-button-container" style="width: 120px;"><button id="bouton_delete" onclick="<?php if(isset($_GET['crypt/primary_key'])) { ?>self.location.href='./index.php<?= $new_view_type_get_list ?>view=search';<?php } else { ?>self.close();<?php } ?>"><div style="background-image: url('./library/killi/images/false.png');background-repeat: no-repeat;background-position: 2px center;">Annuler</div></button></td><?php
				}
				else
				{
					// Pour l'enregistrement : header location avec test $_POST[inside_popup] a tester dans la method pour linstant.
					?><td class="killi-button-container" style="width: 120px;"><button <?= $enregistrer_status ?> id="bouton_save" onclick="submitMain();"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Enregistrer</div></button></td><?php
					?><td class="killi-button-container" style="width: 120px;"><button id="bouton_delete" onclick="self.location.href='./index.php?action=<?= $_GET['action']?>&token=<?= $_GET['token']?>';"><div style="background-image: url('./library/killi/images/false.png');background-repeat: no-repeat;background-position: 2px center;">Annuler</div></button></td><?php
				}
			}
		}
		else
		{
			if ($this->_view == 'form')
			{
				if($search_allow)
				{
					?><td class="killi-button-container" style="width:120px;"><button id="bouton_search" onclick="self.location.href='./index.php<?= $new_view_type_get_list ?>view=search'"><div style="background-image: url('./library/killi/images/gtk-find.png');background-repeat: no-repeat;background-position: 2px center;">Rechercher</div></button></td><?php
				}

				$readonly=false;

				if((isset($_SESSION['_USER']['profil_id']) && (READONLY_PROFIL_ID!==NULL && array(READONLY_PROFIL_ID)==array_keys(array_flip($_SESSION['_USER']['profil_id']['value'])))))
				{
					$readonly=true;
				}

				if((!$readonly && $save_allow) || $object_name=='userpreferences')
				{
					?><td class="killi-button-container" style="width:120px;"><button <?= $enregistrer_status ?> id="bouton_save" onclick="boutonEnregistrer();"><div style="background-image: url('./library/killi/images/save.png');background-repeat: no-repeat;background-position: 2px center;">Enregistrer</div></button></td><?php
				}

				if(!$readonly && $edit_allow)
				{
					?><td class="killi-button-container"  style="width:120px;"><button <?= $edition_disabled ?> id="bouton_edition" onclick="self.location.href='./index.php<?= $new_mode_get_list.'mode=edition' ?>'"><div style="background-image: url('./library/killi/images/edit.png');background-repeat: no-repeat;background-position: 2px center;">Edition</div></button></td><?php
				}

				if (isset($hInstance->calendar) && ($hInstance->calendar===TRUE))
				{
					?><td class="killi-button-container" style="width:120px;"><button <?= $edition_disabled ?> id="bouton_edition" onclick="self.location.href='./index.php<?= $new_view_type_get_list ?>view=calendar'"><div style="background-image: url('./library/killi/images/calendar.gif');background-repeat: no-repeat;background-position: 2px center;">Calendrier</div></button></td><?php
				}

				if(!$readonly && $edit_allow)
				{
					?><td class="killi-button-container" style="width:120px;"><button <?= $enregistrer_status ?> id="bouton_cancel" onclick="self.location.href='./index.php<?= $new_mode_get_list ?>'"><div style="background-image: url('./library/killi/images/false.png');background-repeat: no-repeat;background-position: 2px center;">Annuler</div></button></td><?php
				}

				if ($create_allow)
				{
					?><td class="killi-button-container" style="width:120px;"><button id="bouton_create" onclick="return window.open('./index.php<?= $new_view_type_get_list ?>view=create&token=<?= $_SESSION['_TOKEN'] ?>','popup_<?= rand(1000000,9999999) ?>','height=600, width=800, toolbar=no, scrollbars=yes')"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Créer</div></button></td><?php
				}

				if ($delete_allow)
				{

					?><td class="killi-button-container" style="width:120px;"><?php
						?><button id="button_delete" onclick="dial();"><div style="background-image: url('./library/killi/images/delete.gif');background-repeat: no-repeat;background-position: 2px center;">Supprimer</div></button><?php
						?><script type="text/javascript">
							function dial(){
								$( "#dialog-confirm" ).dialog({
									resizable: false,
									modal: true,
									buttons: {
										"Supprimer": function() {
											self.location.href='./index.php?action=<?= $object_name ?>.unlink&crypt/primary_key=<?= $_GET['crypt/primary_key'] ?>&token=<?= $_SESSION['_TOKEN'] ?>&inside_popup=<?= (isset($_GET['inside_popup'])) ? '1' : '0'; ?>'
										},
										'Annuler': function() {
											$( this ).dialog( "close" );
										}
									}
								});
							}
						</script><?php

						?><div style='display:none' id="dialog-confirm" title="Suppression"><?php
							?><p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span><b>Attention !</b> Vous êtes sur le point de supprimer définitivement cet élément (<?= ucfirst($object_name) ?>).<br/>Êtes-vous sûr de vouloir continuer ?</p><?php
						?></div><?php
					?></td><?php
				}

				?><td class="other_buttons"><?php

				$this->_display_listing_button();

				?></td><?php
			}
			elseif (isset($_GET['m2m_action']))
			{
				?><td class="killi-button-container" style="width:120px;"><?php
				?><button id="bouton_select" onclick="document.search_form.action='./index.php?action=<?= $_GET['m2m_action'] ?>&token=<?= $_SESSION['_TOKEN'] ?>&crypt/primary_key=<?= $_GET['crypt/primary_key'] ?>';document.search_form.submit();"><div style="background-image: url('./library/killi/images/select.gif');background-repeat: no-repeat; background-position: 2px center;">Selectionner</div></button><?php

				if ($create_allow)
				{
					?><button id="bouton_create" onclick="javascript:window.open('./index.php<?= $new_view_type_get_list ?>view=create&token=<?= $_SESSION['_TOKEN'] ?>','','height=400,width=1000,toolbar=no,scrollbar=yes')"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Créer</div></button><?php
				}

				?></td><?php
			}
			else
			{
				if($create_allow)
				{
					?>
						<td class="killi-button-container" style="width:120px;">
							<button id="bouton_create" onclick="javascript:window.open('./index.php<?= $new_view_type_get_list ?>view=create&token=<?= $_SESSION['_TOKEN'] ?>','','height=400,width=1000,toolbar=no,scrollbar=yes')"><div style="background-image: url('./library/killi/images/new.gif');background-repeat: no-repeat;background-position: 2px center;">Créer</div></button>
						</td>
					<?php
				}
			}
		}

		?><td>&nbsp;</td><?php

		if (($this->_view==='form') && (isset($hInstance->log) && ($hInstance->log==TRUE)))
		{
			?><td style="width: 16px;"><div onclick="javascript:window.open('./index.php?action=<?= $object_name ?>.historic&inside_popup=1&crypt/primary_key=<?= $_GET['crypt/primary_key'] ?>&token=<?= $_SESSION['_TOKEN'] ?>','historic_window','height=400,width=1000,toolbar=no,scrollbar=yes');"><img border="0" src="./library/killi/images/historic.gif"/></div></td><?php
		}

		if ($this->_view==='search' || $this->_view==='selection')
		{
			$to 		= (($position+$search_view_num_records)>$this->_total_number_object) ? $this->_total_number_object : ($position + $search_view_num_records)-1 ;
		  	$last_index = $this->_total_number_object == $search_view_num_records ? 0 : floor($this->_total_number_object/$search_view_num_records);
			$next_index = ($this->_index<$last_index) ? $this->_index + 1 : ($this->_total_number_object-1);

		  	?><td class="pagination"><span>[<?= $position ?>; <?= $to ?> / <?= number_format($this->_total_number_object,0,',',' '); ?>]</span><?php

		   	if($last_index!=0)
		   	{
			   	if($this->_index > 0)
			   	{
			   		?><button onclick='javascript:document.search_form.action="<?= './index.php'.$new_get_list.'index=0' ?>";document.search_form.submit()'>1</button><?php
			   		?><button onclick='javascript:document.search_form.action="<?= './index.php'.$new_get_list.'index='.$previous_index ?>";document.search_form.submit()'>Précédent</button><?php
			   	}

			   	?><button disabled><?= ($this->_index+1); ?></button><?php

			   	if($this->_index < $last_index)
			   	{
			   		?><button onclick='javascript:document.search_form.action="<?= './index.php'.$new_get_list.'index='.$next_index ?>";document.search_form.submit()'>Suivant</button><?php
			   		?><button onclick='javascript:document.search_form.action="<?= './index.php'.$new_get_list.'index='.$last_index ?>";document.search_form.submit()'><?= ($last_index+1); ?></button><?php
			   	}
		   	}

		   	?></td><?php
		}

		?></tr></table><?php

		return TRUE;
	}
	//.........................................................................
	private static function _check_profile_node($node)
	{
		if (isset($node['attributes']['profiles']))
		{
			$authorizedProfiles = explode(',', $node['attributes']['profiles']);
			foreach ($authorizedProfiles as $userProfile)
			{
				if (substr($userProfile, 0, 1) === "!")
				{
					$userProfile = constant(substr($userProfile, 1));
					if (in_array($userProfile, $_SESSION['_USER']['profil_id']['value']))
					{
						return false;
					}
					return true;
				}
				$userProfile = constant($userProfile);
				if (in_array($userProfile, $_SESSION['_USER']['profil_id']['value']))
				{
					return true;
				}
			}
		}
		else if(isset($node['attributes']['users']))
		{
			$authorizedUsers = explode(',', $node['attributes']['users']);
			foreach ($authorizedUsers as $login)
			{
				if ($_SESSION['_USER']['login']['value'] == $login)
				{
					return true;
				}
			}
		}
		else
		{
			return true;
		}
		return false;
	}
	//.........................................................................
	private function _render_start_reporting_listing()
	{
		$bNoZeroStats = (isset($_POST['nozerostats']) && $_POST['nozerostats'] == '1');
		$message	  = $this->_current_node['attributes']['string'];
		$param_object = $this->_current_node['attributes']['param_object'];
		$bExport	  = (isset($this->_current_node['attributes']['export']) && $this->_current_node['attributes']['export'] == '1');

		//---On parcour les noeuds jusqu'au _render_end_listing
		$listing_node = array();
		$cc = count($this->_node_list);
		for ($this->_node_index=$this->_node_index; $this->_node_index<$cc; $this->_node_index++)
		{
			$this->_current_node = $this->_node_list[$this->_node_index];

			$listing_node[] = $this->_current_node;

			if ($this->_current_node['name']=='end_reporting_listing')
				break;
		}

		//---Construction du tableau de donnee
		$data = array();
		$data_previous_week = array();

		foreach($listing_node as $key=>$node)
		{
			if (substr($node['name'],0,16)==="start_reporting_")
			{
				switch($node['name'])
				{
					case('start_reporting_query');

						//---Selected week
						$data[$node['attributes']['name']] = array();

						if (isset($_POST['week_period']))
							$week = $_POST['week_period'];
						else
							$week = date('W');

						//---Construction query
						$diw = getDaysInWeek ($week, date('Y'));

						$from = $diw[0];
						$to   = $diw[6]+(24*60*60);

						$query = "select value,param_value
								  from killi_stats_value,killi_stats_query
								  where (killi_stats_query.query_id=killi_stats_value.query_id)
								  and (killi_stats_query.query_name=\"".$node['attributes']['name']."\")
								  and (UNIX_TIMESTAMP(report_date)>=$from)
								  and (UNIX_TIMESTAMP(report_date)<$to)
								  group by param_value
								  order by report_date";

						$this->_hDB->db_select($query,$result,$num);

						for ($i=0; $i<$num;$i++)
						{
							$row = $result->fetch_assoc();
							$data[$node['attributes']['name']][$row['param_value']] = $row['value'];
						}

						$result->free();

						//--- week - 1
						if (isset($_POST['delta_week_period']))
							$week = $_POST['delta_week_period'];
						else
						{
							$week = date('W')-1;
							$_POST['delta_week_period'] = $week;
						}

						//---Construction query
						$diw = getDaysInWeek ($week, date('Y'));

						$from = $diw[0];
						$to   = $diw[6]+(24*60*60);

						$query = "select value,param_value
								  from killi_stats_value,killi_stats_query
								  where (killi_stats_query.query_id=killi_stats_value.query_id)
								  and (killi_stats_query.query_name=\"".$node['attributes']['name']."\")
								  and (UNIX_TIMESTAMP(report_date)>=$from)
								  and (UNIX_TIMESTAMP(report_date)<$to)
								  group by param_value
								  order by report_date";

						$this->_hDB->db_select($query,$result,$num);

						for ($i=0; $i<$num;$i++)
						{
							$row = $result->fetch_assoc();
							$data_previous_week[$node['attributes']['name']][$row['param_value']] = $row['value'];
						}

						$result->free();

						break;
				}
			}
		}

		global $module;
		$curAction = strtolower(substr($module, 0, strlen($module)-6));
		?>
		<script>
		function export_stats()
		{
			var oForm = document.getElementById('reporting_form');
			oForm.action = './index.php?action=<?php echo $curAction; ?>.reportingexportcsv&token=<?php echo $_SESSION['_TOKEN']?>';
			oForm.submit();
			oForm.action = '';
		}
		</script>


		<table cellspacing="0" class="listing_table" style="width: 100%;>"><?php

				?><tr class="ui-widget-header ui-state-hover" style='height:20px'><?php
					?><th class="box_header leftcorner_alternate" style="width: 1px;"></th><?php
					?><th class="box_header leftcorner" style="width: 2px;"></th><?php
					?><th class="box_header leftcorner_alternate" style="width: 1px;"></th><?php
					?><th class="box_header leftcorner" style="width: 1px;"></th><?php

					?><th class="box_header"><?= $message ?></th>
				</tr>
				<tr>
					<td colspan="5">


						<table class="tablesorter table_list" cellspacing="0" style="width:100%;"><?php
					?><thead class="ui-widget-header"><?php

					?><tr style='height:18px;text-align:left' class='listing-header'>

						<th style="width: 60px;">
							<?php if ($bExport):?>
							<a href="javascript:void(0);" onclick="export_stats();">
								<img border="0" src="./library/killi/images/export.gif" />
							</a>
							<?php endif;?>
						</th>

						<?php
								foreach($listing_node as $key=>$node)
								{
									if (substr($node['name'],0,16)==="start_reporting_")
									{
										switch($node['name'])
										{
											case('start_reporting_param_object'):
												?><th>#</th><?php
												break;

											case('start_reporting_query');

												$hORM = ORM::getORMInstance('statsquery');
												$stats_query_list = array();
												$hORM->browse($stats_query_list,$num,array('nom'),array(array('query_name','=',$node['attributes']['name'])));
												$row = array_slice($stats_query_list,0,1);
												if (!isset($row[0]))
												{
													throw new Exception('Unable to find query '.$node['attributes']['name']);
												}
												$stats_query= $row[0];

												?><th><?= $stats_query['nom']['value'] ?></th><?php
												break;
										}
									}
								}
								?>
						</tr>
						</thead>
						<tbody>
							<?php
							//---On recup la liste des objet parametriques
							$param_object_id_list = array();
							$hORM = ORM::getORMInstance(strtolower($param_object));
							$arArgs = array();
							$arSort = NULL;
							if (strtolower($param_object) == 'user')
							{
								$arArgs[] = array('actif', '=', 1);
								$arSort = array('nom', 'prenom');
								$selectedId = '';
								if (isset($_POST['crypt/presta_id']) && !empty($_POST['crypt/presta_id'])) {
									Security::decrypt($_POST['crypt/presta_id'], $selectedId);
									$arArgs[] = array('partner_id', '=', $selectedId);
								}
							}
							$hORM->search($param_object_id_list,$num,$arArgs,$arSort);

							$hMethod = ORM::getControllerInstance($param_object);
							$param_object_reference_list = array();
							$hMethod->getReferenceString($param_object_id_list, $param_object_reference_list);

							$i=0;
							foreach($param_object_reference_list as $param_object_id=>$param_object_reference)
							{
								Security::crypt($param_object_id,$crypt_primary_key);

								if ($bNoZeroStats)
								{
									$hasContents = false;
									foreach ($listing_node as $key=>$node)
									{
										if ($node['name'] == 'start_reporting_query' && (isset($data[$node['attributes']['name']][$param_object_id]) || isset($data_previous_week[$node['attributes']['name']][$param_object_id])))
										{
											$attrn = $node['attributes']['name'];
											if (isset($node['attributes']['delta']))
											{
												if (isset($data_previous_week[$attrn][$param_object_id]) &&
													$data_previous_week[$attrn][$param_object_id] != 0 &&
													isset($data[$attrn][$param_object_id]) && (
													(($node['attributes']['delta'] == 1 || $node['attributes']['delta'] == 3) && (100 * (($data[$attrn][$param_object_id] - $data_previous_week[$attrn][$param_object_id]) / $data_previous_week[$attrn][$param_object_id])) != 0) ||
													(($node['attributes']['delta'] == 2 || $node['attributes']['delta'] == 3) && ($data[$attrn][$param_object_id] - $data_previous_week[$attrn][$param_object_id]) != 0)))
												{
													$hasContents = true;
													break;
												}
											}
											else
											{
												$hasContents = true;
												break;
											}
										}
									}

									if (!$hasContents)
									{
										continue;
									}
								}



								?><tr>
									<td>#<?= ++$i ?></td>

									<?php
									foreach ($listing_node as $key=>$node)
									{
										if (substr($node['name'],0,16)==="start_reporting_")
										{
											switch($node['name'])
											{
												case('start_reporting_param_object'):
													?><td><a href="./index.php?action=<?= $param_object ?>.edit&amp;token=<?= $_SESSION['_TOKEN'] ?>&amp;view=form&amp;crypt/primary_key=<?= $crypt_primary_key ?>"><?= htmlentities($param_object_reference, ENT_QUOTES, 'UTF-8') ?></a></td><?php
													break;

												case('start_reporting_query'):

													//---Unit
													$unit='';

													if (isset($node['attributes']['unit']))
														$unit = $node['attributes']['unit'];

													$value = '-';

													if (isset($data[$node['attributes']['name']][$param_object_id]))
														$value = $data[$node['attributes']['name']][$param_object_id];

													if ($value=='-')
														$unit='';

													?>
													<td>
														<?= str_replace(' ','&nbsp;',(str_pad($value.' '.$unit,5,' '))) ?>

														<?php
														//---Delta
														if (isset($node['attributes']['delta']) &&
															isset($data_previous_week[$node['attributes']['name']][$param_object_id]) &&
															isset($data[$node['attributes']['name']][$param_object_id]) &&
															$data_previous_week[$node['attributes']['name']][$param_object_id] > 0)
														{
															$diff = ($data[$node['attributes']['name']][$param_object_id] - $data_previous_week[$node['attributes']['name']][$param_object_id]);
															$percent = 100*($diff / $data_previous_week[$node['attributes']['name']][$param_object_id]);

															$color = "#0000FF";
															$signe = "+";

															if ($percent > 0 || $diff > 0)
															{
																$color = "#00AA00";
																$signe = '+';
															}
															elseif ($percent < 0 || $diff < 0)
															{
																$color = "#FF0000";
																$signe = '';
															}
															switch ($node['attributes']['delta'])
															{
																case 1:
																	echo	'<span style="font-weight:bold;color:'.$color.'">'.
																				'('.$signe.' '.sprintf('%.02f', $percent).' %)'.
																			'</span>';
																	break;
																case 2:
																	echo	'<span style="font-weight:bold;color:'.$color.'">'.
																				'('.$signe.' '.$diff.')'.
																			'</span>';
																	break;
																case 3:
																	echo	'<span style="font-weight:bold;color:'.$color.'">'.
																				'('.$signe.' '.sprintf('%.02f', $percent).' %) ('.$signe.' '.$diff.')'.
																			'</span>';
																	break;
															}
														}
														?>

													</td>
													<?php
													break;
											}
										}
									}
									?>

								</tr>


								<?php
							}

							?>


								</tbody>
								<tfoot>
								<tr>
									<td></td>
									<?php
									foreach($listing_node as $key=>$node)
									{
										if (substr($node['name'],0,16)==="start_reporting_")
										{
											switch($node['name'])
											{
												case('start_reporting_param_object'):
													?>
													<td>
														<?= count($param_object_reference_list) ?>
													</td>
													<?php
													break;

												case('start_reporting_query'):
													?>
													<th style="border:none;white-space: nowrap;" class='ui-widget-header ui-state-hover'>
														<?php
														if ((isset($node['attributes']['total'])) && ($node['attributes']['total']==1))
														{
															$total = 0;

															//---On parcour les object param
															foreach($param_object_reference_list as $param_object_id => $param_object)
															{
																if (isset($data[$node['attributes']['name']][$param_object_id]))
																{
																	$total += $data[$node['attributes']['name']][$param_object_id];
																}
															}

															//---Si delta
															if ((isset($node['attributes']['delta'])) && ($node['attributes']['delta']==1))
															{
																$previous_total = 0;

																//---On parcour les object param
																foreach($param_object_reference_list as $param_object_id => $param_object)
																{
																	if (isset($data_previous_week[$node['attributes']['name']][$param_object_id]))
																	{
																		$previous_total += $data_previous_week[$node['attributes']['name']][$param_object_id];
																	}
																}

																echo $total;

																$percent = 0;
																$signe = '';
																$color = "#0000FF";

																if ($previous_total>0 && $total>0)
																{
																	$percent = 100*(($total - $previous_total) / $previous_total);

																	if ($percent>0)
																	{
																		$color="#00AA00";
																		$signe='+';
																	}
																	else if ($percent<0)
																	{
																		$color="#FF0000";
																	}
																}

																$delta = $total - $previous_total;

																echo " <font color=\"".$color."\"><b>(".$signe." ";
																printf('%.03f',$percent);
																echo " %)</b></font><br />";
																echo "$signe $delta unités";
															}
														}
														else
														{
															echo "";
														}
														?>
													</th>
													<?php
													break;
											}
										}
									}
									?>
								</tr>
								</tfoot>
						</table>
				</td>
			</tr>

		</table>
		<?php

		return TRUE;
	}
	//.........................................................................
	private function _display_listing_button()
	{
		$inside_list = FALSE;

		foreach($this->_node_list AS $node)
		{
			$this->_current_node = $node;
			if(array_key_exists('attributes', $this->_current_node) && array_key_exists('id',$this->_current_node['attributes']) && array_key_exists('__HTML', $this->_data_list) && array_key_exists($this->_current_node['attributes']['id'], $this->_data_list['__HTML']))
			{
				$this->_current_node['attributes'] = array_merge($this->_current_node['attributes'],$this->_data_list['__HTML'][$this->_current_node['attributes']['id']]);
				$node['attributes']['disabled']=(!empty($this->_current_node['attributes']['disabled']) && $this->_current_node['attributes']['disabled']==1)?1:0;
			}

			switch($node['name'])
			{
				case('start_list'):
					$inside_list = TRUE;
					break;
				case('end_list'):
					$inside_list = FALSE;
					break;
				default:
					break;
			}

			$this->_getIdFromAttributes($id);

			/*$this->_getIdFromAttributes($id);*/

			if (($inside_list===TRUE) && ($node['name']==='start_workflow_button'))
			{
				$raw_destination				= explode('.',$node['attributes']['destination']);
				if (sizeof($raw_destination) != 2)
				{
					throw new Exception('Le format de la chaine destination est mauvais ('.$node['attributes']['destination'].')');
				}

				$confirm_str = '';
				$confirm_str_end = '';
				if (isset($node['attributes']['confirm']) && !empty($node['attributes']['confirm']))
				{
					$confirm_str = 'if (confirm(\''.$node['attributes']['confirm'].'\')) {';
					$confirm_str_end = '}';
				}

				$hORM_workflow					= ORM::getORMInstance('workflow');
				$workflow_id_list_destination	= array();
				$node_id_list_destination		= array();

				$hNodeInstance = ORM::getObjectInstance('node');
				unset($hNodeInstance->domain_with_join); // Dangerous ???
				$hORM_node = ORM::getORMInstance('node');

				$hORM_workflow -> search($workflow_id_list_destination , $num , array(array('workflow_name' , '=' , $raw_destination[0])));
				if(count($workflow_id_list_destination)>0)
				{
					$hORM_node	 -> search($node_id_list_destination	 , $num , array(array('node_name'	 , '=' , $raw_destination[1])	, array('workflow_id' , '=' , $workflow_id_list_destination[0])));


					if(count($node_id_list_destination) !== 1)
					{
						throw new Exception(sprintf('Impossible de trouver le node %s dans le workflow %s', $raw_destination[1], $workflow_id_list_destination[0]));
					}
					else
					{
						Security::crypt($node_id_list_destination[0],$crypt_destination_node_id);
						$button			= new ButtonTag();

						if(isset($node['attributes']['id']))
						{
							$button->id		=  $node['attributes']['id'];
						}
						$button->raw_style = $this->_attributesToStyle();
						if(isset($node['attributes']['disabled']))
						{
							$button->disabled  = $node['attributes']['disabled']==1;
						}
						$button->type		= 'button';
						$button->content	= $node['attributes']['string'];

						if(isset($_GET['crypt/workflow_node_id']))
						{
							$button->onclick = $confirm_str.'document.search_form.action=\'./index.php?action=workflow.move_token&crypt/origin_node='.$_GET['crypt/workflow_node_id'].'&crypt/destination_node='.$crypt_destination_node_id.'&token='.$_SESSION['_TOKEN'].'\';document.search_form.submit();'.$confirm_str_end;
						}
						else
						{
							if(!isset($node['attributes']['source']))
							{
								/**
								 * On tente de determiner la source
								 */

								if(isset($_GET['primary_key']))
								{
									ORM::getORMInstance('workflowtoken', TRUE)->browse($token_list, $num, array('node_id', 'node_name', 'workflow_name'), array(array('id', '=', $_GET['primary_key'])));

									if($num != 1)
									{
										error_log("Impossible de trouver la source du workflow_button");
										$button->disabled = TRUE;
									}
									else
									{
										$token = current($token_list);
										$node['attributes']['source'] = $token['workflow_name']['value'].'.'.$token['node_name']['value'];
									}
								}
							}

							if(isset($node['attributes']['source']))
							{
								$raw_source					= explode('.',$node['attributes']['source']);
								if (sizeof($raw_source) != 2)
								{
									throw new Exception('Le format de la chaine source est mauvais ('.$node['attributes']['source'].')');
								}
								$workflow_id_list_source	= array();
								$node_id_list_source		= array();

								$hORM_workflow -> search( $workflow_id_list_source , $num , array(array('workflow_name' , '=' , $raw_source[0])));
								$hORM_node	 -> search( $node_id_list_source	 , $num , array(array('node_name'	 , '=' , $raw_source[1])	, array('workflow_id' , '=' , $workflow_id_list_source[0])));


								Security::crypt($node_id_list_source[0],$crypt_workflow_node_id);
								$button->onclick = $confirm_str.'document.main_form.action=\'./index.php?action=workflow.move_token&crypt/origin_node='.$crypt_workflow_node_id.'&crypt/destination_node='.$crypt_destination_node_id.'&token='.$_SESSION['_TOKEN'].'\';document.main_form.submit();'.$confirm_str_end;
							}
						}
						echo $button;
					}
				}
				else
				{
					throw new Exception("Impossible de trouver le workflow de destination ".$raw_destination[0]);
				}
			}
			else if ((($inside_list===TRUE) && ($node['name']==='start_button')) && (($this->_view==='search') or (array_key_exists('forcedisplay', $node['attributes']) &&  $node['attributes']['forcedisplay'] ==='1')))
			{
				$button			= new ButtonTag();
				$action			=  array_key_exists('crypt/primary_key', $_GET)   ?  "./index.php?action=".$node['attributes']['action']."&token=".$_SESSION['_TOKEN']."&crypt/primary_key=".$_GET['crypt/primary_key']  :  "./index.php?action=".$node['attributes']['action']."&token=".$_SESSION['_TOKEN'];
				$onclick		=  $this->_view === 'form'						?  "document.main_form.action='".$action."';document.main_form.submit();;document.main_form.action=''"									 :  "document.search_form.action='".$action."';document.search_form.submit();document.search_form.action=''";
				$button->name	=  array_key_exists('name', $node['attributes'])  ?  'name="'.$node['attributes']['name'].'"' : '';

				if (isset($node['attributes']['name']))
				{
					if(array_key_exists($node['attributes']['name'], $this->_data_list))
					{
						$button->disabled = $this->_data_list[$node['attributes']['name']]['disabled'];
					}
				}

				$button->id			= $id;
				$button->raw_style	= $this->_attributesToStyle();
				$button->type		= "button";
				$button->content	= $node['attributes']['string'];
				$button->class		= '';

				//POUET
				if (isset($node['attributes']['icon']))
				{
					$button->class	= 'class="killi-icon killi-icon-'.$node['attributes']['icon'].'"';
				}

				if(array_key_exists('confirm', $node['attributes']) && $node['attributes']['confirm'] !== '')
				{
					$unic_confirm_id = 'func_'.md5(rand(1000000,9999999)).'_confirm()';
					$button->onclick = $unic_confirm_id;
					?>
					<script>
					function <?=$unic_confirm_id?>{
						$( "<div style='display:none' title='Confirmation'>"
								+"<p><span class='ui-icon ui-icon-alert' style='float: left; margin: 0 7px 20px 0;'></span><b>Attention !</b> <?= preg_replace('/\\\\n/', '<br/>', $node['attributes']['confirm']) ?></p>"
								+"</div>")
						.dialog({
							resizable: false,
							modal: true,
							buttons: {
								"Confirmer": function() {
									<?=$onclick?>;
								},
								'Annuler': function() {
									$( this ).dialog( "close" );
								}
							}
						});
					}
					</script>
					<?php
				}
				else
					$button->onclick   = $onclick;

				echo $button;
			}
		}
		return TRUE;
	}
}
