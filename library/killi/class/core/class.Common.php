<?php

/**
 *  @class Common
 *  @Revision $Revision: 4658 $
 *
 */

/*. require_module 'standard'; .*/

abstract class Common
{
	protected $_hDB	 = NULL ;   //---Handle sur une connexion MySQL active

	public $object_name = null;

	public function __call($name, $arguments)
	{
		if(substr($name,0,6)=='unlink')
		{
			$action		  = explode('.',$_GET['action']);
			$m2m_object_name = substr($name,6);
			$m2mobject	   = ORM::getM2MObject($action[0], $m2m_object_name);

			if($m2mobject === NULL)
			{
				throw new Exception('Impossible de determiner l\'objet de laison ('.$action[0].'/'.$m2m_object_name.')');
			}

			$hORM				= ORM::getORMInstance($m2mobject);
			$object_instance	 = ORM::getObjectInstance($action[0]);
			$m2m_object_instance = ORM::getObjectInstance($m2m_object_name);
			$m2m_object		  = array();

			$hORM->search(
				$m2m_object,
				$num,
				array(
					array($object_instance->primary_key,'=',$_GET['primary_key']),
					array($m2m_object_instance->primary_key,'=',$_GET['key'])
				)
			);

			if(isset($m2m_object[0]))
			{
				$hORM->unlink($m2m_object[0]);
			}

			UI::goBackWithoutBrutalExit();

			return TRUE;
		}
		else if(substr($name,0,3)=='add')
		{
			$action		  = explode('.',$_GET['action']);
			$m2m_object_name = substr($name, 3);

			$m2mobject = ORM::getM2MObject($action[0], $m2m_object_name);

			if($m2mobject === NULL)
			{
				throw new Exception('Impossible de determiner l\'objet de laison ('.$action[0].'/'.$m2m_object_name.')');
			}

			$hORM				= ORM::getORMInstance($m2mobject);
			$object_instance	 = ORM::getObjectInstance($action[0]);
			$m2m_object_instance = ORM::getObjectInstance($m2m_object_name);

			foreach($_POST['selected'] as $id)
			{
				$hORM->create(
					array(
						$object_instance->primary_key	 => $_POST['primary_key'],
						$m2m_object_instance->primary_key => $id
					)
				);
			}

			UI::refreshOpener();

			return TRUE;
		}

		throw new BadUrlException('Method "' . $name . '" not implemented in "' . get_class($this) . '" !');

		return FALSE;
	}
	//....................................................................
	public function autocomplete(&$result)
	{
		$result = array (
			'data' => array ()
		);

		if (isset ( $_GET ['field'] ) && ! empty ( $_GET ['field'] ) && isset ( $_GET ['search'] ) && ! empty ( $_GET ['search'] ))
		{
			$filter = array ( array ( $_GET ['field'], 'like', strval ( $_GET ['search'] ) . '%' ) );

			if(isset($_GET ['domain']) && !empty($_GET ['domain']))
			{
				$filter = array_merge($filter, unserialize($_GET ['domain']));
			}

			ORM::getORMInstance ( $this->object_name )->browse ( $object_list, $num, array ( $_GET ['field'] ), $filter, NULL, NULL, 10 );

			foreach ( $object_list as $object_id => $object )
			{
				Security::crypt ( $object_id, $crypt_primary_key );

				$result ['data'][]=array (
					'object_id' => $crypt_primary_key,
					'label' => $object [$_GET ['field']] ['value']
				);
			}
		}

		return true;
	}
	//....................................................................
	public function getTemplateFilename()
	{
		// If object has its own class path
		$namespace = Autoload::getClassNamespace($this->object_name);
		if ($namespace != NULL)
		{
			$namespacePath = Autoload::getClassNamespacePath($this->object_name);
			$classFilename = strtolower(Autoload::getClassFileName($this->object_name));
			$template_name = $namespacePath . '/' . $classFilename . '.xml';
			return $template_name;
		}

		$template_name = $this->object_name . '.xml';

		// surcharge des templates
		if(!file_exists('./template/'. $template_name))
		{
			$template_name = '../' . KILLI_DIR . 'template/' . $template_name;
		}

		return $template_name;
	}
	//....................................................................
	public function getFilters(&$filter_list = NULL)
	{
		if($filter_list === NULL)
		{
			$filter_list = array ();
		}

		foreach ( $_REQUEST as $key => $value )
		{
			if (substr ( $key, 0, 7 ) !== 'search/')
			{
				continue;
			}

			if (substr ( $key, - 3, 3 ) === '/op')
			{
				continue;
			}

			if (!is_array ( $value ))
			{
				$value = trim ( $value );
			}

			$raw = explode ( '/', $key );
			$attribute = ORM::getObjectInstance ( $raw [1] )->$raw [2];

			$attribute->inCast ( $value );
			
			if(!isset($_REQUEST [$key . '/op']))
			{
				if (!is_array ( $value ))
				{
					$_REQUEST [$key . '/op'] = '=';
				}
				else
				{
					$_REQUEST [$key . '/op'] = 'IN';
				}
			}

			if($_REQUEST [$key . '/op'] == 'NULL' || $_REQUEST[$key . '/op'] == 'NOT NULL')
			{
				$filter_list [] = array (
					$raw [2],
					'IS ' . $_REQUEST[$key . '/op']
				);

				continue;
			}

			if(empty($value) && $value !== '0')
			{
				continue;
			}

			$operator = $_REQUEST [$key . '/op'];

			if (!is_array ( $value ))
			{
				$value = Security::secure ( $value );
			}
			else
			{
				foreach($value AS $k=>$v)
				{
					$value[$k] = Security::secure($v);
				}
			}

			$filter_list [] = array (
				$raw [2],
				$operator,
				$value
			);
		}

		return $filter_list;
	}
	//....................................................................
	public function export_csv($view, $fields_to_export = NULL, $export_name = NULL)
	{
		$csv_object = ORM::getObjectInstance ( $this->object_name );

		$selected_fields = array();
		$field_to_select = false;
		if (isset($_POST['column_selection']) && $_POST['column_selection'] == '1')
		{
			$field_to_select = true;
			foreach ($_POST['export_column'] as $column)
			{
				$selected_fields[$column] = true;
			}
		}

		// selection des fields
		if ($fields_to_export === NULL)
		{
			$fields = array (
				$csv_object->primary_key => 'Référence'
			);

			foreach ( $csv_object as $attribute_name => $attribute )
			{
				if (! ($attribute instanceof FieldDefinition))
				{
					continue;
				}

				if ($field_to_select && !isset($selected_fields[$attribute_name]))
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

				Rights::getRightsByAttribute ( $this->object_name, $attribute_name, $read, $write );

				if ($read !== TRUE)
				{
					continue;
				}

				if ($attribute_name == $csv_object->primary_key)
				{
					continue;
				}

				$fields [$attribute_name] = $attribute->name;
			}
		}
		else
		{
			$fields = array ();
			foreach ( $fields_to_export as $field_to_export )
			{
				$fields [$field_to_export] = $csv_object->$field_to_export->name;
			}
		}

		// filtrage
		$this->getFilters ( $filter_list );

		// CSV
		$csv = new CSVWriter ( "/tmp/" . uniqid ( $this->object_name, TRUE ), $fields );
		$csv->auto_unlink = TRUE;

		header ( 'Pragma: private' );
		header ( 'Content-Type: application/octet-stream; charset=utf-8' );
		header ( 'Content-Disposition: attachment; filename="'. ($export_name == NULL ? 'export_' . $this->object_name : $export_name).'.csv"' );

		// preparation : on ferme la session si DO_NOT_LOCK_SESSION_ON_EXTRACT pour permetre d'ouvrir d'autres pages, on active le gc et on laisse le script tourner à l'infini
		if(defined('DO_NOT_LOCK_SESSION_ON_EXTRACT') && DO_NOT_LOCK_SESSION_ON_EXTRACT === TRUE)
		{
			session_write_close();
		}
		ini_set ( 'max_execution_time', 0 );
		gc_enable();

		// boucle
		$index = 0;
		do
		{
			gc_collect_cycles ();

			$object_list = array ();
			ORM::getORMInstance ( $this->object_name )->browse ( $object_list, $total_object_list, array_keys ( $fields ), $filter_list, NULL, $index, 200 );

			$reference_list = array ();
			ORM::getControllerInstance ( $this->object_name )->getReferenceString ( array_keys ( $object_list ), $reference_list );

			foreach ( $object_list as $object_id => $object )
			{
				if (! array_key_exists ( $object_id, $reference_list ))
				{
					$reference_list [$object_id] = '';

					new NonBlockingException ( "Reference manquante pour object:[$class] id:[$object_id]" );
				}

				$object [$csv_object->primary_key] ['reference'] = $reference_list [$object_id];

				$line = array ();
				foreach ( $fields as $key => $field_name )
				{
					if (! isset ( $object [$key] ))
					{
						$line [$key] = NULL;
						continue;
					}
					
					$line [$key] = $csv_object->$key->extract($object [$key]);
				}

				// écriture de la ligne
				$csv->writeLine ( $line );
			}

			$index ++;

			// on affiche et on vide le tampon
			echo $csv->getFileContent ();
			$csv->clear ();
		}
		while ( ! empty ( $object_list ) );

		return TRUE;
	}
	//....................................................................
	protected function json_out(array $data)
	{
		header('Content-type: application/json');
		echo json_encode($data);
		return TRUE;
	}
	//....................................................................
	public function write($data)
	{
		if(!is_array($data))
		{
			throw new Exception("data must be an array");
		}
		$class = str_replace('Method','',get_class($this));

		//---On recup les data
		$data = array();
		$inline_create = array();
		foreach($_POST as $key=>$value)
		{
			if (strtolower(substr($key,0,1+strlen($class)))===strtolower($class).'/')
			{
				$attr = substr($key,1+strlen($class));
				$raw = explode('/',$attr);

				if (count($raw)>1)
				{
					continue;
				}

				if(isset(ORM::getObjectInstance($class)->$attr))
				{
					Rights::getRightsByAttribute($class, $attr, $read, $write);

					if(!$write)
					{
						continue;
					}
				}

				$data[substr($key,1+strlen($class))] = $value;
			}
			else
			if (strtolower(substr($key,0,1+strlen('input')))===strtolower('input').'/')
			{
				/* Création en inline sur les blocs listings. */
				$raw = explode('/',$key);
				$object = $raw[1];
				$attribute = $raw[2];
				$inline_create[$object][$attribute] = $value;
			}
		}

		//---Typing data
		foreach($data as $key=>$value)
		{
			$raw = explode('/',$key);

			if (count($raw)>1)
			{
					continue;
			}
			elseif (strpos($key, '\\') === FALSE)
			{
				if ($value == '' )
				{
					$value = NULL;
				}

				$data[$key] = $value;
			}
		}

		$object = ORM::getObjectInstance($class);
		if (isset($_POST[$object->primary_key]))
		{
			$primary_key = $_POST[$object->primary_key];
		}
		elseif (isset($_POST['primary_key']))
		{
			$primary_key = $_POST['primary_key'];
		}
		else
		{
			$_SESSION['_ERROR_LIST']['Écriture'] = 'Pas d\'identifiant spécifié.';
			UI::quitNBack();
		}

		$hORM = ORM::getORMInstance($class);
		$hORM->write($primary_key, $data);

		/**
		 *  Enregistrements des éléments éditables dans les blocs listings.
		 */
		if(defined('INLINE_CREATE') && INLINE_CREATE)
		{
			$first = reset($inline_create);
			$attributes = array_keys($first);
			$values = array_values(reset($first));
			$nb_records = count($values);
			foreach($inline_create AS $object => $data_list)
			{
				$hInstance = ORM::getObjectInstance($object);
				$hCtrl = ORM::getControllerInstance($object);
				for($i = 0; $i < $nb_records; $i++)
				{
					$data = array();
					foreach($attributes AS $attr)
					{
						$value = $data_list[$attr][$i];
						if($hInstance->$attr->secureSet($value) === FALSE)
						{
							Alert::error('Erreur', join('<br>', $hInstance->$attr->constraint_error));
							$_SESSION['_POST']	   = $_POST;
							UI::quitNBack();
						}
						$data[$attr] = $value;
					}
					$pk = NULL;
					$hCtrl->create($data, $pk);
				}
			}
		}

		/*
		 * Enregistrement des documents fournis par le docuploader.
		 */
		$this->_docuploader();

		return TRUE;
	}
	//....................................................................
	public function panel($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		$data = array();
		return TRUE;
	}
	//....................................................................
	public function quickSearch()
	{
		if (!isset($_SESSION['_USER']))
		{
			$this->json_out(array('url' => './index.php', 'message' => 'Votre session a expiré, redirection vers la page d\'authentification...'));
		}
		$object	= strtolower(str_replace('Method', '', get_class($this)));
		$attribute = null;
		foreach ($_POST as $k => $v)
		{
			if (substr($k, 0, 10) == 'attribute/')
			{
				$attribute = substr($k, 10);
				break;
			}
		}
		if (is_null($attribute))
		{
			$this->json_out(array('error' => 'Attribut de l`objet non spécifié.'));
		}
		else
		{
			$primary_key = ORM::getObjectInstance($object)->primary_key;
			$object_list = array();
			ORM::getORMInstance($object, TRUE)->browse(
				$object_list,
				$num_object,
				array($primary_key),
				array(array($attribute, '=', $_POST['attribute/'.$attribute]))
			);
			if ($num_object > 1)
			{
				$this->json_out(array('error' => 'Plusieurs résultats correspondent à votre critère de recherche.'));
			}
			elseif ($num_object < 1)
			{
				$this->json_out(array('error' => 'Aucun résultat ne correspond à votre critère de recherche.'));
			}
			else
			{
				$cur = each($object_list);
				$pk  = end($cur);
				Security::crypt($pk, $crypt_pk);
				$url =	'./index.php?'.
						'action='.$object.'.edit'.
						'&view=form'.
						'&crypt/primary_key='.$crypt_pk.
						'&token='.$_SESSION['_TOKEN'];
				$this->json_out(array('url' => $url, 'message' => 'Correspondance trouvée ! Redirection en cours...'));
			}
		}
		exit();
	}
	//....................................................................
	public function edit($view, &$data, &$total_object_list, &$template_name = NULL)
	{
		if ($view == 'panel')
		{
			$this->panel ( $view, $data, $total_object_list, $template_name );
			return TRUE;
		}

		if (isset ( $this->local ) && $this->local === true)
		{
			$data = array ();
			return TRUE;
		}

		if ($view == 'form')
		{
			try
			{
				$object_list=array();
				ORM::getORMInstance ( $this->object_name )->search ( $object_list, $total_record, array ( array ( ORM::getObjectInstance ( $this->object_name )->primary_key, '=', $_GET ['primary_key'] ) ) );

				if (empty ( $object_list ))
				{
					ORM::getORMInstance ( $this->object_name )->read ( $_GET ['primary_key'], $object_list );

					// Si le read n'a pas levé d'exception, c'est que l'element a été trouvé, mais le search avec domaine a limité sa lecture, donc on leve une NoRightsException
					throw new NoRightsException ( 'domain constraint for object ' . $this->object_name . '[' . $_GET ['primary_key'] . ']' );
				}

				ORM::getORMInstance ( $this->object_name )->read ( array ( $_GET ['primary_key'] ), $data [$this->object_name] );
			}
			catch ( MismatchObjectException $e )
			{
				throw new ObjectDoesNotExistsException ( ' [' . $this->object_name . ':' . $_GET ['primary_key'] . '] ' . $e->getMessage () );
			}
			catch ( Exception $e )
			{
				throw $e;
			}

			$total_object_list = 1;

			return TRUE;
		}
		else if ($view == 'search' || $view == 'selection')
		{
			//---Utiliser macro filtre
			if ((isset ( $_POST ['macro_filter'] )) && (trim ( $_POST ['macro_filter'] ) != ''))
			{
				ORM::getORMInstance ( 'macrofilter' )->read ( $_POST ['macro_filter'], $macro_filter );
	
				$filters = unserialize ( $macro_filter ['filter'] ['value'] );
	
				foreach ( $filters as $key => $value )
				{
					$_POST [$key] = $value;
				}
			}
	
			//---Sauvegarde macro filter
			if ((isset ( $_POST ['save_macro_filter'] )) && ($_POST ['save_macro_filter'] == 1))
			{
				if (trim ( $_POST ['macro_filter_description'] ) == '')
					$_SESSION ['_WARNING_LIST'] ['Macro Filtre'] = 'Impossible de sauvegarder le filtre macro. Il n\'a pas de description !';
				else
				{
					$data = array ();
					$data ['description'] = $_POST ['macro_filter_description'];
					$data ['view'] = $_POST ['macro_filter_view_name'];
					$filter_data = array ();
	
					foreach ( $_POST as $key => $value )
						if (((substr ( $key, 0, 7 ) == 'search/') || (substr ( $key, 0, 5 ) == 'sort/') || (substr ( $key, 0, 14 ) == 'sort_priority/')) && (trim ( $value ) != ''))
							$filter_data [$key] = $value;
	
					$data ['filter'] = serialize ( $filter_data );
	
					ORM::getORMInstance ( 'macrofilter' )->create ( $data );
				}
			}
	
			//---Si node_id --> on recup le bon template
			if (isset ( $_GET ['workflow_node_id'] ))
			{
				ORM::getORMInstance ( 'node' )->read ( $_GET ['workflow_node_id'], $node, array ( 'interface' ) );
	
				$raw = explode ( '.', $node ['interface'] ['value'] );
				$className = $raw [0];
				$namespacePath = Autoload::getClassNamespacePath ( $className );
				if ($namespacePath != NULL)
				{
					$namespace = Autoload::getClassNamespace ( $className );
					$template_name = '../workflow/template/' . $namespacePath . '/' . substr ( $node ['interface'] ['value'], strlen ( $namespace ) );
				}
				else
				{
					$template_name = '../workflow/template/' . $node ['interface'] ['value'];
				}
			}
	
			$fields = NULL;
			if ($template_name !== null && file_exists ( './template/' . $template_name ))
			{
				// ouverture du template
				libxml_use_internal_errors ( true );
				if (($xml = simplexml_load_file ( './template/' . $template_name, 'SimpleXMLElement', LIBXML_NOEMPTYTAG )) === FALSE)
				{
					foreach ( libxml_get_errors () as $error )
						throw new Exception ( "$error->message" );
				}
				libxml_use_internal_errors ( false );
	
				UI::_getXMLNode ( $xml, $xml_node_list );
	
				$lock = false;
				$fields = array ();
	
				// récuperation des attributs listés
				foreach ( $xml_node_list as $node )
				{
					if ($node ['name'] == 'start_list')
					{
						$lock = true;
						if (isset ( $node ['attributes'] ['selectable'] ))
						{
							$selectable = explode ( '.', $node ['attributes'] ['selectable'], 2 );
	
							if (count ( $selectable ) == 2)
							{
								$fields [] = $selectable [1];
							}
							else
							{
								$fields [] = $node ['attributes'] ['selectable'];
							}
						}
					}
	
					if ($node ['name'] == 'start_field' && $lock)
					{
						$fields [] = $node ['attributes'] ['attribute'];
					}
	
					if ($node ['name'] == 'end_list' && $lock)
						break;
				}
			}
	
			global $search_view_num_records;
	
			//---Create filters
			$filter_list = array();
			$this->getFilters ( $filter_list );
	
			$process_cache_total_count = true; // false = désactivé
	
			if (DISABLE_APC_COUNT_CACHE || isset($_GET['workflow_node_id']) || !empty($filter_list))
			{
				$process_cache_total_count = false;
			}
	
			$has_cache_total_count = false;
	
			$hInstance = ORM::getObjectInstance ( $this->object_name );
			$has_cache_total_count = (isset ( $hInstance->cache_total_count ) && $hInstance->cache_total_count === true);
			$hORM = ORM::getORMInstance ( $this->object_name, ! ($has_cache_total_count && $process_cache_total_count) );
	
			$object_list = array ();
			$total_object_list = 0;
			$index = (isset ( $_GET ['index'] ) && ( int ) $_GET ['index'] >= 0 ? $_GET ['index'] : 0);
	
			$hORM->browse ( $object_list, $total_object_list, $fields, $filter_list, NULL, $index, $search_view_num_records );
	
			if ($has_cache_total_count && $process_cache_total_count)
			{
				$total_object_list = $hORM->getCachedTotalCount ();
			}
	
			if ($index * $search_view_num_records > $total_object_list)
			{
				$index = floor ( $total_object_list / $search_view_num_records );
	
				$hORM->browse ( $object_list, $null, $fields, $filter_list, NULL, $index, $search_view_num_records );
			}
			
			$data [$this->object_name] = $object_list;
			
			return TRUE;
		}
		else
		{
			// abstract view
			$data = array ();
			return TRUE;
		}
	}
	//....................................................................
	public function unlink($id_list)
	{
		//---On recup le nom de la classe
		$class = str_replace('Method','',get_class($this));

		$hORM = ORM::getORMInstance($class);

		try {
			if(is_array($id_list))
			{
				foreach($id_list AS $id)
				{
					$hORM->unlink($id);
				}
			}
			else
			{
				$hORM->unlink($id_list);
			}
		}
		catch(CantDeleteException $e)
		{
			throw new CannotDeleteException($e->getMessage());
		}
		catch (Exception $e)
		{
			throw $e;
		}


		return TRUE;
	}

	//....................................................................
	public function create($data,&$id,$ignore_duplicate=false)
	{
		//---On recup le nom de la classe
		$class = str_replace('Method','',get_class($this));
		$hInstance = ORM::getObjectInstance($class);

		//---Typing data
		foreach($data as $key=>$value)
		{
			if (isset($hInstance->$key))
			{
				$data[$key] = $value;
			}
		}

		try {
			ORM::getORMInstance($class)->create($data,$id,$ignore_duplicate);
		}
		catch(InsertErrorException $e)
		{
			throw new CannotInsertObjectException($e->getMessage());
		}
		catch (Exception $e)
		{
			throw $e;
		}

		if(isset($_GET['redirect']) && $_GET['redirect'] == '0')
		{
			return TRUE;
		}

		$classname = strtolower($class);
		$xml_template = 'template/'.$classname.'.xml';

		$redirect = false;

		// vérification si attribut redirect dans le create du template xml
		if(file_exists($xml_template))
		{
			libxml_use_internal_errors(true);
			if (($xml = simplexml_load_file($xml_template, 'SimpleXMLElement',LIBXML_NOEMPTYTAG))===FALSE)
			{
				throw new Exception('Cannot open file ' . $xml_template);
			}

			libxml_use_internal_errors(false);

			if( isset($xml->create['redirect']) && $xml->create['redirect'] == '1')
			{
				Security::crypt($id, $crypt_id);
				$redirect = '"index.php?action='.$classname.'.edit&token='.$_SESSION['_TOKEN'].'&view=form&crypt/primary_key='.$crypt_id.'";';
			}

		}

		if( $redirect !== FALSE)
		{
			UI::refreshOpener($redirect);
		}

		return TRUE;
	}

	public function wizard($data = array())
	{
		/* Récupération de la configuration depuis le template. */
		$template_name = $this->getTemplateFilename();
		$_GET['mode'] = 'edition'; // TODO: En attendant d'avoir un truc plus propre.
		$hUI = UI::getInstance('./template/' . $template_name);
		$wizard_list = $hUI->getNodesByName('wizard');

		if(empty($wizard_list))
		{
			throw new Exception('Pas de wizard disponible pour cet objet !');
		}

		$wizard = reset($wizard_list);
		$steps = $wizard->getChildren();

		/* Récupération de l'UUID du wizard. */
		$uuid = uniqid();
		if(isset($_POST['wizard_uuid']))
		{
			$uuid = $_POST['wizard_uuid'];
		}
		$wizard->uuid = $uuid;

		/* Récupération de l'étape. */
		$step = 0;
		if(isset($_POST['step']))
		{
			$step = $_POST['step'];
		}

		/* Vérification via le validateur. */
		$way = 0;
		if(isset($_POST['submitBtn']))
		{
			if($_POST['submitBtn'] == 'following')
			{
				$way = 1;
			}
			if($_POST['submitBtn'] == 'preceding')
			{
				$way = -1;
			}
			if($_POST['submitBtn'] == 'terminate')
			{
				UI::quitNBack($this->object_name . '.edit', TRUE);
			}
		}

		$child = $steps[$step+$way];
		$validator = $child->getNodeAttribute('validator', NULL);

		$next_step = $step + $way;
		if($validator !== NULL)
		{
			$result = call_user_func_array($validator, array($uuid, &$next_step, &$data));
			if($result === TRUE)
			{
				$step = $next_step;
			}
			else
			{
				do
				{
					global $hDB;
					$hDB->db_rollback();
					// Rollback
					$next_step--;

					if ($next_step < 0)
					{
						UI::quitNBack();
					}

					$child = $steps[$next_step];
					$validator = $child->getNodeAttribute('validator', NULL);

					if($validator === NULL)
					{
						break;
					}
					$result = call_user_func_array($validator, array($uuid, &$next_step, &$data));

				} while(!$result);
			}
		}
		else
		{
			$step = $next_step;
		}
		$wizard->step = $step;

		$hUI->renderAll('wizard', $data);
		return TRUE;
	}

	//---------------------------------------------------------------------
	public static function checkAttributesDependencies($class_name, array &$object_list, array $field_list, $force = false)
	{
		if (empty($object_list) || empty($field_list))
		{
			return TRUE;
		}

		$object = reset($object_list);

		if(!$force)
		{
			$fields_to_gather_list = array();
			foreach ($field_list as $field)
			{
				if (!isset($object[$field]))
				{
					$fields_to_gather_list[] = $field;
				}
			}

			if (empty($fields_to_gather_list))
			{
				return true;
			}
		}

		Debug::printAtEnd('Utilisation de récupération de dépendances manquantes dans '.$class_name.' : ' . join(', ', $field_list));

		$hORM = ORM::getORMInstance($class_name);
		$object_bis_list = array();
		$hORM->read(array_keys($object_list), $object_bis_list, $field_list);

		foreach ($object_bis_list as $object_bis_id => $object_bis)
		{
			foreach ($field_list as $field_name)
			{
				$object_list[$object_bis_id][$field_name] = $object_bis[$field_name];
			}
		}
		return TRUE;
	}

	//.....................................................................
	function __construct()
	{
		global $hDB; //---On la recup depuis l'index ;-)
		$this->_hDB = &$hDB;

		$this->object_name = strtolower(substr(get_class($this),0,-6));
	}
	//.....................................................................
	public function postRead($original_object_list,&$object_list)
	{
		$object_list = $original_object_list;

		return TRUE;
	}
	//.....................................................................
	public function postUnlink($deleted_id)
	{
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function preRead($original_id_list, &$id_list,$original_fields,&$fields)
	{
		$id_list = $original_id_list;
		$fields  = $original_fields;

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function postCreate($original_id, &$id)
	{
		$id = $original_id;

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function preCreate($original_data, &$data)
	{
		$data = $original_data;

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function preUnlink($original_id, &$id)
	{
		$id = $original_id;

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function preWrite($object_id, $original_data, &$data)
	{
		$data = $original_data ;

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function postWrite($object_id, $affected)
	{
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function historic()
	{
		$classname = strtolower(str_replace('Method','',get_class($this)));
		$template_name = $classname.'.xml';

		$object				 = ORM::getObjectInstance($classname);
		$object->object_domain  = array(array($object->primary_key, '=', $_GET['primary_key']));
		$object->table		 .= '_log';
		$object->primary_key	= 'log_id';
		$object->log_id		 = new PrimaryFieldDefinition();

		$this->edit('search', $data, $total_object_list, $template_name);

		global $hUI;

		$hUI = new UI();
		$hUI->render('./template/' . $template_name, $total_object_list, $data);

		unset($hUI);

		return TRUE;
	}
	//---------------------------------------------------------------------
	public static function setQualification(&$object_list)
	{
		foreach($object_list as &$object)
		{
			$object['qualification_id']['value']	= null;
		}

		if (!isset($_GET[ 'workflow_node_id']))
		{
			return TRUE;
		}

		global $hDB;

		$hDB->db_select('Select workflow_token_id, id, nom '.
				'From '.
				'killi_workflow_token, '.
				'killi_node_qualification '.
				'Where '.
				'killi_workflow_token.qualification_id = killi_node_qualification.qualification_id And '.
				'killi_workflow_token.node_id = "' . Security::secure($_GET[ 'workflow_node_id']) . '" And '.
				'killi_workflow_token.id In (' . join(',',array_keys($object_list)) . ') ', $result);

		while(($row = $result->fetch_assoc()))
		{
			Security::crypt($row['workflow_token_id'], $crypt_wt_id);

			$object_list[$row['id']]['qualification_id']['value'] = $row['nom'];
			$object_list[$row['id']]['qualification_id']['url']   = './index.php?action=workflowtoken.edit&view=form&crypt/primary_key='.$crypt_wt_id;
		}

		$result->free();

		return TRUE;
	}
	//---------------------------------------------------------------------
	public static function setCommentaire(&$object_list)
	{
		foreach($object_list as &$object)
		{
			$object['commentaire']['value']	= NULL;
		}

		if (!isset($_GET[ 'workflow_node_id']))
		{
			return TRUE;
		}

		global $hDB;

		$hDB->db_select('Select id, workflow_token_id, commentaire '.
				'From killi_workflow_token '.
				'Where '.
				'node_id = "' . Security::secure($_GET[ 'workflow_node_id']) . '" And '.
				'id In ('.implode(', ', array_keys($object_list)).') ', $result);

		while($row = $result->fetch_assoc())
		{
			Security::crypt($row['workflow_token_id'], $crypt_wt_id);

			$object_list[$row['id']]['commentaire']['value'] = $row['commentaire'];
			$object_list[$row['id']]['commentaire']['url']   = './index.php?action=workflowtoken.edit&view=form&crypt/primary_key='.$crypt_wt_id;
		}

		$result->free();

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function getCalendarEvents()
	{
		$data = array();
		$this->_getCalendarEventsData($data,$_GET['from'],$_GET['to'],$_GET['field_from'],$_GET['field_to'],$_GET['label']);

		$class = str_replace('Method','',get_class($this));

		$json_data = array();
		foreach($data as $object_id=>$object)
		{
			Security::crypt($object_id,$crypt_object_id);

			$json_data[] = array('id'=>strtolower($class).'/'.$crypt_object_id.'/'.$_GET['field_from'].'/'.$_GET['field_to'],
								 'title'=>$object[$_GET['label']]['value'],
								 'start'=>is_numeric($object[$_GET['field_from']]['value']) ? date('Y-m-d H:i',$object[$_GET['field_from']]['value']) : $object[$_GET['field_from']]['value'],
								 'end'=>is_numeric($object[$_GET['field_to']]['value']) ?  date('Y-m-d H:i',$object[$_GET['field_to']]['value']) : $object[$_GET['field_to']]['value'],
								 'ignoreTimezone'=>TRUE,
								 'url'=>'./index.php?action='.strtolower($class).'.edit&inside_popup=1&view=form&token='.$_SESSION['_TOKEN'].'&crypt/primary_key='.$crypt_object_id,
								 'allDay'=>FALSE);
		}

		echo json_encode($json_data);

		return TRUE;
	}
	//---------------------------------------------------------------------
	private function _getCalendarEventsData(array &$data,$from,$to,$field_from,$field_to,$label)
	{
		$class = str_replace('Method','',get_class($this));
		$hORM = ORM::getORMInstance($class);

		$object_list = array();
		$hORM->browse($data,$num,array($field_from,$field_to,$label),array(array($field_from,'>=',is_numeric($from) ? date('Y-m-d H:i:s',$from) : $from),
																		   array($field_to,'<',is_numeric($to) ?  date('Y-m-d H:i:s',$to) : $to)));

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function flexigrid(&$data, &$total_object_list, $offset, $limit, $order)
	{
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function planning(&$data, $start, $end, &$object, &$primary_key, &$arg_list=array())
	{
		/* Récupération des objets et construction du résultat. */
		$hObj = ORM::getObjectInstance($object);
		$primary_key = $hObj->primary_key;
		$field_prefix = (isset($hObj->field_prefix))? $hObj->field_prefix : '';

		$hORM = ORM::getORMInstance($object);
		$data = array();
		$arg_list[] = array($field_prefix.'date', 'between', $start, $end);
		$hORM->browse($data, $num, NULL, $arg_list, NULL, 0, 1000);

		$object_id_list = array();
		foreach($data AS $k => $v)
		{
			$object_id_list[] = $k;
		}

		$objectMethod = ORM::getControllerInstance($object);
		if(!method_exists($objectMethod,'getReferenceString'))
		{
			throw new Exception('La methode getReferenceString n\'est pas implémenté dans l\'objet ' . $objMtxt);
		}

		$reference_list = array();
		$objectMethod->getReferenceString($object_id_list, $reference_list);

		foreach($data AS $k => $v)
		{
			$data[$k][$primary_key]['reference'] = $reference_list[$k];
		}

		return TRUE;
	}
	// Possibilite export vers format iCal
	//Synthaxe : $events = array(array('event_id','start_date','end_date','summary','rec_type','event_pid','event_length','description));
	public function export_ical()
	{
		require_once './library/killi/library/iCal.php';
		$events = unserialize(base64_decode($_GET['iCalCalendarEvents_src']));
		$filters = (array) json_decode($_GET['iCalCalendarFilters_src']);
		$start = date('Y-m-d H:i:s',$filters['start']);
		$stop = date('Y-m-d H:i:s',$filters['end']);
		$primary_key = null;
		$current_object = $_GET['iCalCalendarCurrentObject'];
		$event = array();
		$args = array();
		foreach ($filters as $filters_key_lvl_1 => $filters_value_lvl_1)
		{
			if ($filters_key_lvl_1 != 'method' && $filters_key_lvl_1 != 'start' && $filters_key_lvl_1 != 'end')
			{
				$filters_value_lvl_1 = explode(' ',$filters_value_lvl_1);
				$id_list = array();
				foreach ($filters_value_lvl_1 as $filters_key_lvl_2 => $filters_value_lvl_1)
				{
					Security::decrypt($filters_value_lvl_1,$id);
					$id_list[$id] = $id;
				}
				$args[] = array($filters_key_lvl_1, 'in', $id_list);
			}
		}
		foreach ($events as $events_value_lvl_1)
		{
			$this->planning($events_value_lvl_1,$start,$stop,$current_object,$primary_key,$args);
			foreach ($events_value_lvl_1 as $events_key_lvl_2 => $events_value_lvl_2)
			{
				// Definition elements
				$event_id = $current_object.'_'.$events_key_lvl_2;
				$event_start = $events_value_lvl_2['date']['date_str'].' '.$events_value_lvl_2['debut']['value'];
				$event_fin = $events_value_lvl_2['date']['date_str'].' '.$events_value_lvl_2['fin']['value'];
				$event_rec_type = null;
				$event_pid = $event_id;
				$event_length = null;
				// Necessaire
				$event_summary = $events_value_lvl_2[$primary_key]['reference'];
				$event_description = $event_summary;
				// Elements
				$event[] = array('event_id'=>$event_id,'start_date'=>$event_start,'end_date'=>$event_fin,'summary'=>$event_summary,'rec_type'=>$event_rec_type,'event_pid'=>$event_pid,'event_length'=>$event_length,'description'=>$event_description);
			}
		}
		// Creation du fichier et insertion/format des donnees
		$name = $current_object.'_'.date('YmdHis');
		$export = new ICalExporter();
		$export->setTitle($name);
		$ical_calendar = $export->toICal($event);
		// Download du fichier
		header('Content-disposition: attachment; filename='.$name.'.ics');
		header('Content-Type: text/calendar');
		echo $ical_calendar;
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function refresh(&$data, $fields)
	{
		$class  = str_replace('Method','',get_class($this));
		$object = ORM::getObjectInstance($class);
		$hORM   = ORM::getORMInstance($class);

		foreach($fields AS $num => $field)
		{
			$fields_list = array();
			$total = 0;
			$attr  = $field['attribute'];
			$key   = $field['key'];
			$hORM->browse($fields_list, $total, array($attr), array(array($object->primary_key, '=', $field['parent_object_id'])));
			if(isset($fields_list[$field['parent_object_id']][$attr]['value']))
			{
				$field_render_class = $object->$attr->render . 'RenderFieldDefinition';

				foreach($field AS $a => $v)
				{
					$attributes[$a] = $v;
				}

				if(!isset($attributes['object']))
				{
					$attributes['object'] = $field['parent_object'];
				}

				class_exists('KilliUI'); // Juste pour chargé killi UI.

				$structure = array('markup' => 'field', 'attributes' => $attributes, 'value' => array());
				$node = new FieldXMLNode($structure);
				$render = new $field_render_class($node, $object->$attr);

				$value = $fields_list[$field['parent_object_id']][$attr];

				ob_start();
				if(!isset($field['input_name']))
				{
					$field['input_name'] = $attributes['object'] . '/' . $attributes['attribute'] . '/' . $key;
				}
				$render->renderValue($value, $field['input_name'], array());
				$rendering = ob_get_clean();
				$data[$key] = $rendering;
			}
			else
			{
				$data[$key] = 'Error: Unable to retrieve data.';
			}
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function wysiwyg_upload_image()
	{
		// Convertir l'image en base64 et retourner le résultat au client.
		$file = reset($_FILES);
		$image_contents = file_get_contents($file['tmp_name']);
		$return = array(
			'type'   => $file['type'],
			'base64' => base64_encode($image_contents)
		);
		// echo 'data:image/'.$file['type'].';base64,'.base64_encode($image_contents);
		echo json_encode($return);
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function validate_uploaded_file($file_data, &$error_message)
	{
		return TRUE;
	}
	//---------------------------------------------------------------------
	protected function _docuploader($object_id = NULL, &$id_document = NULL)
	{
		$doctype_list  = array();
		$document_type = null;
		$hInstance	 = ORM::getObjectInstance($this->object_name);
		// On enregistre les éventuels documents.

		if(empty($_FILES))
		{
			return false;
		}

		$upload_ok = false;
		foreach ($_FILES as $input_name => $file_info_list)
		{
			if (substr($input_name, 0, 10) != 'docupload_')
			{
				// Si le document ne vient pas du docuploader.
				continue;
			}

			// ID du noeud XML d'origine du docuploader.
			$current_xmlnode_id = substr($input_name, 10);

			if(empty($file_info_list['name']))
			{
				throw new Exception('Erreur de données invalides à l\'upload du document !');
			}

			$id_document = array();
			foreach ($file_info_list['name'] as $idx => $file_name)
			{
				if (empty($file_name))
				{
					//throw new Exception('Aucun nom de fichier dans les data $_FILES !');
					continue;
				}

				if($file_info_list['error'][$idx] != 0)
				{
					Alert::error('Upload : '.$file_name, 'Erreur pendant l\upload du fichier, veuillez recommencer.');
					continue;
				}

				if (isset($hInstance->extension2doctype))
				{
					// Déduction automatique du type de document (document_type_id) en fonction de l'extension du fichier uploadé.
					$ext = strtolower(pathinfo($file_info_list['name'][$idx], PATHINFO_EXTENSION));
					if (!isset($hInstance->extension2doctype[$ext]))
					{
						Alert::error('Upload : '.$file_name, 'L\'extension "*.'.$ext.'" n\'est pas prise en charge par l\'objet "'.$this->object_name.'".');
						continue;
					}
					$doc_type_id = $hInstance->extension2doctype[$ext];
				}
				else
				if (isset($hInstance->mime2doctype))
				{
					// Déduction automatique du type de document (document_type_id) en fonction du type MIME du fichier uploadé.
					$mime = $file_info_list['type'][$idx];
					if (!isset($hInstance->mime2doctype[$mime]))
					{
						Alert::error('Upload : '.$file_name, 'Le type MIME "'.$mime.'" n\'est pas pris en charge par l\'objet "'.$this->object_name.'".');
						continue;
					}
					$doc_type_id = $hInstance->mime2doctype[$mime];
				}
				else
				{
					$doc_type_id = $_POST['doctype_'.$current_xmlnode_id][$idx];
				}

				if (!is_numeric($doc_type_id))
				{
					// Si on n'a pas de doc type.
					Alert::error('Upload : '.$file_name, 'Aucun type de document défini.');
					continue;
				}

				// Récupération des infos du doctype.
				if (isset($doctype_list[$doc_type_id]))
				{
					$document_type = $doctype_list[$doc_type_id];
				}
				else
				{
					ORM::getORMInstance('documenttype')->read($doc_type_id, $document_type, array('rulename'));
					$doctype_list[$doc_type_id] = $document_type;
				}

				$_POST['document_object_ref_id'] = $object_id;
				// DocumentLocal: Besoin de cet index pour définir l'ID de l'objet en cours.
				if($object_id === NULL)
				{
					$_POST['document_object_ref_id'] = $_POST['document_object_ref_id_'.$current_xmlnode_id];
				}

				// DocumentLocal: Le tableau $_FILES en version multiple n'est pas pris en charge.
				// On créé une nouvelle entrée qui pourra l'être sans problème.
				$_FILES[$input_name.'__'.$idx] = array(
					// Champs par défaut de $_FILES
					'name'			 => $file_name,
					'type'			 => $file_info_list['type'][$idx],
					'tmp_name'		 => $file_info_list['tmp_name'][$idx],
					'error'			=> $file_info_list['error'][$idx],
					'size'			 => $file_info_list['size'][$idx],
					// Champs spécifiques Killi2
					'document_type_id' => $doc_type_id,
					'object_id'		=> $_POST['document_object_ref_id']
				);

				// Vérification du préfixe du nom du document en corrélation avec le type_id.
				// Si l'objet cible n'a pas l'attribut $doctype_check_prefix, cette vérification est zappée.
				if (isset($hInstance->doctype_check_prefix))
				{
					foreach($hInstance->doctype_check_prefix as $prefix)
					{
						if (substr(strtoupper($file_name), 0, strlen($prefix)) == $prefix &&
							strtoupper($document_type['rulename']['value']) != $prefix)
						{
							Alert::error('Upload : '.$file_name, 'Upload du document impossible. Incohérence entre le nom du fichier et son type ('.$prefix.'<>'.$document_type['rulename']['value'].') !');
							continue 2;
						}
					}
				}

				// Vérification de l'existence d'une fonction de validation sur l'objet en cours
				$error_message = '';
				$check_validation = $this->validate_uploaded_file($_FILES[$input_name.'__'.$idx], $error_message);

				if (!$check_validation)
				{
					Alert::error('Upload : '.$file_name, $error_message);
					continue;
				}

				DocumentLocal::create($this->object_name, TRUE, $id_document[], TRUE, FALSE, $input_name.'__'.$idx, $doc_type_id);
				$upload_ok = true;
			}
		}
		return $upload_ok;
	}
	//---------------------------------------------------------------------
	public function moveEvent(&$data, $key, $allDay, $dayDelta, $minDelta)
	{
		$class = str_replace('Method','',get_class($this));
		$hORM  = ORM::getORMInstance($class);
		$hObj  = ORM::getObjectInstance($class);
		$field_prefix = (isset($hObj->field_prefix))? $hObj->field_prefix : '';

		$hORM->read($key, $event, array($field_prefix.'date', $field_prefix.'debut', $field_prefix.'fin'));

		$event[$field_prefix.'date']['value'] = date('Y-m-d', (is_numeric($event[$field_prefix.'date']['value']) ? $event[$field_prefix.'date']['value'] : strtotime($event[$field_prefix.'date']['value'])) + ($dayDelta*3600*24));

		if($minDelta != 0)
		{
			$ts = strtotime($event[$field_prefix.'debut']['value']);
			$event[$field_prefix.'debut']['value'] = date('H:i:s', $ts + $minDelta*60);

			$ts = strtotime($event[$field_prefix.'fin']['value']);
			$event[$field_prefix.'fin']['value'] = date('H:i:s', $ts + $minDelta*60);
		}

		$eventData = array();
		foreach($event AS $k => $arrayvalue)
		{
			$eventData[$k] = $arrayvalue['value'];
		}

		$hORM->write($key, $eventData);

		$data['success'] = TRUE;
		return TRUE;
	}
	//---------------------------------------------------------------------
	public function resizeEvent(&$data, $key, $dayDelta, $minDelta)
	{
		$class = str_replace('Method','',get_class($this));
		$hORM  = ORM::getORMInstance($class);
		$hObj  = ORM::getObjectInstance($class);
		$field_prefix = (isset($hObj->field_prefix))? $hObj->field_prefix : '';
		$hORM->read($key, $event, array($field_prefix.'date', $field_prefix.'debut', $field_prefix.'fin'));

		if(is_numeric($event[$field_prefix.'date']['value']))
		{
			$event[$field_prefix.'date']['value'] = date('Y-m-d', $event[$field_prefix.'date']['value']);// + ($dayDelta*3600*24));
		}

		if($minDelta != 0)
		{
			$ts = strtotime($event[$field_prefix.'fin']['value']);
			$event[$field_prefix.'fin']['value'] = date('H:i:s', $ts + $minDelta*60);
		}

		$eventData = array();
		foreach($event AS $k => $arrayvalue)
		{
			$eventData[$k] = $arrayvalue['value'];
		}

		$hORM->write($key, $eventData);

		$data['success'] = TRUE;
		return TRUE;
	}

	public function getReferenceString(array $id_list, array &$reference_list)
	{
		$class = str_replace('Method', '', get_class($this));

		$object = ORM::getObjectInstance($class);
		$reference = NULL;
		if(isset($class::$reference))
		{
			$reference = $class::$reference;
		}
		else
		if(isset($object->reference))
		{
			$reference = $object->reference;
		}

		if(!is_string($reference))
		{
			throw new NoReferenceAttributeException('Pas d\'attributs reference ou de méthode getReferenceString pour l\'objet '.$class);
		}
		/*
		if(DISPLAY_ERRORS)
		{
			$bt=debug_backtrace(false);
			$trace = '';
			foreach($bt AS $call_id => $call)
			{
				if(isset($call['file']) && isset($call['line']) && isset($call['function']))
				{
					$trace .= $call['file'] . ' : ' . $call['line'] . ' (' . $call['function'] . ')<br>';
				}
			}
			Debug::printAtEnd($trace . 'Optimisation possible de l\'ORM pour la récupération du champ : ' . $reference . ' de l\'objet ' . $class . '<br>');
		}*/
		if(TOLERATE_MISMATCH || isset($object->json))
		{
			foreach($id_list AS $id)
			{
				$reference_list[$id] = '- REF NOT FOUND -';
			}
		}

		$hORM = ORM::getORMInstance($class);
		$data_list = array();
		$hORM->read($id_list, $data_list, array($reference));
		foreach($data_list as $key=>$value)
		{
			$ref = $value[$reference]['value'];
			if(is_array($object->$reference->type))
			{
				$reference_list[$key] = $object->$reference->type[$ref];
			}
			else
			{
				$reference_list[$key] = $ref;
			}
		}
		unset($id_list);
		unset($class);

		return TRUE;

	}

	public function browse_listing(&$data_src_content, &$total_pagination, $method, $pagination = NULL, $offset = NULL, $orders = NULL)
	{
		$filters = array();
		$this->getFilters($filters);
		return $this->$method( $data_src_content, $total_pagination, $pagination, $offset, $filters);
	}

	//---------------------------------------------------------------------
	public function export_listing()
	{
		$method = $_GET ['method'];
		list ( $object, $null ) = explode ( '.', $_GET ['action'] );

		$data_src_content = array ();
		$total_pagination = 0;

		// lecture
		if (empty ( $_GET ['pagination'] ))
		{
			ORM::getControllerInstance ( $object )->browse_listing($data_src_content, $total_pagination, $method);
		}
		else
		{
			if(!is_numeric($_GET['pagination']))
			{
				throw new Exception('Wrong value for pagination !');
			}

			$pagination = (int)$_GET['pagination'];

			$offset = 0;
			do
			{
				$sub_data_src_content = array ();

				ORM::getControllerInstance ( $object )->browse_listing($sub_data_src_content, $total_pagination, $method, $pagination, $offset);

				$data_src_content = array_merge ( $data_src_content, $sub_data_src_content );

				$offset ++;
			}
			while ( $offset * $pagination < $total_pagination );
		}

		// header
		$fields = array();
		$headers = unserialize($_GET ['header']);
		$mapping=array();

		foreach($headers as $header )
		{
			$fields[$header['attribute']] = ORM::getObjectInstance($header['object'], FALSE)->{$header['attribute']}->name;
			$mapping[$header['attribute']] = $header['object'];
		}

		$csv = new CSVWriter ( "/tmp/" . uniqid ( $method, TRUE ), $fields );
		$csv->auto_unlink = TRUE;

		header ( 'Pragma: private' );
		header ( 'Content-Type: application/octet-stream; charset=utf-8' );
		header ( 'Content-Disposition: attachment; filename="export_' . strtolower ( $object ) . '_' . strtolower ( $method ) . '.csv"' );

		// écriture
		foreach ( $data_src_content as $object )
		{
			$line = array ();

			foreach ( $fields as $key => $field_name )
			{
				if (! isset ( $object [$key] ))
				{
					$line [$key] = NULL;
					continue;
				}
				
				$line [$key] = ORM::getObjectInstance($mapping[$key], FALSE)->$key->extract($object [$key]);
			}

			$csv->writeLine ( $line );
		}

		echo $csv->getFileContent ();

		return TRUE;
	}
	public function getMultiSelectorDatas()
	{
		if (empty($_GET['term']) || empty($_GET['object']) || empty($_GET['maxresults'])  || empty($_GET['id'])  || empty($_GET['reference']) )
		{
			throw new Exception("Cette fonction doit être appelée par le multiselector !", 1);
		}

		$object		= $_GET['object'];
		$query		= $_GET['term'];
		$max		= $_GET['maxresults'];
		$id_name	= $_GET['id'];
		$reference	= $_GET['reference'];

		if (!ORM::isObjectExists($object))
		{
			echo json_encode( array( 'error' => 'L\'objet '.$object.' n\'existe pas dans l\'application.' ) );
			exit(0);
		}

		$args[] = array($reference, 'LIKE', $query.'%');

		if (isset($_GET['data_list']))
		{
			$exclusion_id_list = array();
			foreach ($_GET['data_list'] as $c_id)
			{
				$id = '';
				Security::decrypt($c_id, $id);
				$exclusion_id_list[] = $id;
			}

			$args[] = array($id_name, 'NOT IN', $exclusion_id_list);
		}

		$fields = array($id_name);

		$return_list = array();
		ORM::getORMInstance($object)->browse(
			$return_list,
			$total,
			array($id_name, $reference),
			$args,
			NULL, NULL,
			$max
		);

		if (empty($return_list))
		{
			exit(0);
		}

		$result = array();
		$count = 0;
		foreach ($return_list as $r)
		{
			$r_id = '';
			Security::crypt($r[$id_name]['value'], $r_id);

			$result[$count]['id']    = $r_id;
			$result[$count]['value'] = $r[$_GET['reference']]['value'];

			$count++;
		}

		echo json_encode($result);
		exit(0);
	}
}
