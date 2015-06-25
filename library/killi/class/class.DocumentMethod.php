<?php

/**
 *  @class DocumentMethod
 *  @Revision $Revision: 4601 $
 *
 */

abstract class KilliDocumentMethod extends Common
{
	public function getDocContent()
	{
		$response = NULL;

		$pk = $_GET['primary_key'];

		$mime_type = 'application/pdf';
		if (!empty($_GET['mime_type']))
		{
			$mime_type = $_GET['mime_type'];			
		}
		
		$doc = '';
		ORM::getORMInstance('document')->read($pk, $doc, array('file_name', 'hr_name'));

		if (!file_exists($doc['file_name']['value']))
		{
			header('Content-type: '.$mime_type);
			echo "Impossible de trouver le fichier";
			exit(0);
		}

		header('Content-type: '.$mime_type);
		echo file_get_contents($doc['file_name']['value']);
		exit(0);
	}
	
	public function getContent($document_id, $return_handler = FALSE)
	{
		$obj = ORM::getObjectInstance($this->object_name);
		
		// document distant
		if(isset($obj->json))
		{
			$curl = new KilliCurl($obj->json['path']. 'index.php?action=json.download');
		
			if (isset($obj->json['login']))
			{
				$curl->setUser($obj->json['login'], $obj->json['password']);
			}
			else
			{
				if(isset($_SESSION['_USER']))
				{
					$curl->setUser($_SESSION['_USER']['login']['value'], $_SESSION['_USER']['password']['value']);
				}
			}
		
			if (isset($obj->json['ssl']) && !empty($obj->json['ssl']))
			{
				$curl->setSSL($obj->json['cert'], $obj->json['cert_pwd']);
			}

			if (isset($obj->json['object']))
			{
				$distant_object = $obj->json['object'];
			}
			else
			{
				$distant_object = strtolower($this->_object_name);
			}
			
			$curl->object = $distant_object;
			$curl->document_id = $document_id;
		
			// type de streamer
			$handler = $curl->setStream($return_handler ? 'FileStream' : 'DisplayStream');

			return $curl->request();
		}
		
		// document local
		ORM::getORMInstance($this->object_name)->read($document_id, $document, array('file_name','mime_type','hr_name'));
		
		$fp = fopen($document['file_name']['value'], 'r');
		
		if($return_handler)
		{
			return $fp;
		}
		
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $document['mime_type']['value']);
		
		if(!(isset($_GET['show_file']) && $_GET['show_file']=='1'))
		{
			header('Content-disposition: attachment; filename="' . $document['hr_name']['value'] . '"');
		}
		
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Expires: 0');

		while (!feof($fp))
		{
			echo fgets($fp);
		}
		fclose($fp);
		
		return $fp;
	} 

	public function edit($view,&$data,&$total_object_list,&$template_name=NULL)
	{
		if ($view=='create' || $view=='search')
		{
			//true ajouté par christian. à effacer
			$hORM=ORM::getObjectInstance('document');
			$hORM->document_type_id->editable=true;
			$hORM->etat_document_id->editable=true;
			$hORM->object->editable=true;
			$hORM->object_id->editable=true;
			$hORM->mime_type->editable=true;
			$hORM->file_name->editable=true;
			$hORM->size->editable=true;
			$hORM->hr_name->editable=true;
			$hORM->document_type_id->editable=true;
			$hORM->users_id->editable=true;
			$hORM->document_type_id->editable=true;
			$hORM->date_creation->editable=true;
			$hORM->file_found->editable=true;
			$hORM->object_link->editable=true;
	
			//echo "<pre>"; print_r($hORM); echo "</pre>"; die();
			return parent::edit($view,$data,$total_object_list,$template_name);
		}

		$this->getContent($_GET['primary_key']);
		
		die();
	}

	public function read()
	{
		$pk = $_POST['primary_key'];
		// Check rights
		Rights::getCreateDeleteViewStatus('document', $c, $d, $v);
		if (!$v)
		{
			echo 'ERROR:Vous n\'avez pas les droits de visualisation sur les documents.';
			exit();
		}
		// Read document from DB
		ORM::getORMInstance('document')->read($pk, $document, array('file_name', 'hr_name'));
		$path_inf = pathinfo($document['hr_name']['value']);
		// Check extension
		if ($path_inf['extension'] != 'csv')
		{
			echo 'ERROR:Format de fichier incorrect';
			exit();
		}
		// Parse document
		$return = array();
		$fp = fopen($document['file_name']['value'], 'r');
		while (($row = fgetcsv($fp, 0, ';')) !== FALSE)
		{
			// Fix charset problem with json_encode.
			foreach ($row as &$col)
			{
				$col = iconv('Windows-1252', 'UTF-8', $col);
			}
			$return[] = $row;
		}
		fclose($fp);
		// Return JSON data
		$this->json_out($return);
	}

	public static function setObject(&$doc_list)
	{
		self::checkAttributesDependencies('document', $doc_list, array('object','object_id'));

		foreach($doc_list as $id=>&$doc)
		{
			if($doc['object_id']['value'])
			{
				Security::crypt($doc['object_id']['value'], $crypted_id);

				$doc['object_link']['value']=$doc['object']['value'].' ['.$doc['object_id']['value'].']';
				$doc['object_link']['url']='index.php?action='.$doc['object']['value'].'.edit&view=form&crypt/primary_key='.$crypted_id;
			}
			else
			{
				Security::crypt($id, $crypted_id);

				$doc['object_link']['value']=$doc['object']['value'].' ['.$id.']';
				$doc['object_link']['url']='index.php?action='.$doc['object']['value'].'.edit&view=form&crypt/primary_key='.$crypted_id;
			}
		}

		return true;
	}

	public static function setFileFound(&$doc_list)
	{
		self::checkAttributesDependencies('document', $doc_list, array('file_name'));

		foreach($doc_list as $id=>&$doc)
		{
			$doc['file_found']['value'] = file_exists($doc['file_name']['value']);
		}

		return true;
	}

	public static function lost()
	{
		ORM::init ();

		global $hDB;

		$offset = 0;
		$limit = 1000;
		
		$hDB->db_select ( "SELECT count(document_id) as total FROM document ", $result );
		
		$total = $result->fetch_assoc ();
		$total = $total['total'];
		
		$result->free();

		$last_progression = NULL;
		
		while ( true )
		{
			$hDB->db_select ( "SELECT document_id, file_name, hr_name, object_id, object FROM document limit " . $offset * $limit . " , " . $limit, $result, $num );

			if ($num == 0)
			{
				return true;
			}

			while ( $row = $result->fetch_assoc () )
			{
				if (! file_exists ( $row ['file_name'] ))
				{
					try
					{
						//$hDB->db_execute("delete from document where document_id = " . $row['document_id']);
						//$hDB->db_commit();
					}
					catch ( Exception $e )
					{
						echo '[Erreur] : '.$e->getMessage () . PHP_EOL;
					}
					echo '[' . $row ['object'] . ':' . $row ['object_id'] . '] : ' . $row ['hr_name'] . PHP_EOL;
				}
			}
			
			$result->free();

			$offset ++;
			
			$current_progression = round((($offset*$limit)/$total)*100);
			
			if($last_progression != $current_progression)
			{
				echo  $current_progression. '% ... ('.memory_get_usage(TRUE).')' . PHP_EOL;
				
				$last_progression = $current_progression;
			}
		}
	}
}
