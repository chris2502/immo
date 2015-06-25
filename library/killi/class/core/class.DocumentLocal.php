<?php

/**
 *  @class DocumentLocal
 *  @Revision $Revision: 4668 $
 *
 */

class DocumentLocal
{
	private static $allowed_types = array(
		'application/x-rar',
		'application/vnd.ms-office',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/zip',
		'application/dxf',
		'application/pdf',
		'application/msword',
		'application/octet-stream',
		'application/rtf',
		'application/vnd.ms-excel',
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/tiff',
		'message/rfc822',
		'text/plain',
		'text/csv',
		'text/x-c',
		'text/x-mail',
		'text/rtf',
		'application/CDFV2-corrupt' //Documents outlook corrompus
	);

	//.........................................
	public static function create($object_doc_ref, $eraseTemp=TRUE,&$id_document=NULL,$conserve_name=FALSE,$check_mimetype=TRUE, $input_name='document', $document_type=NULL)
	{
		if (!isset($_FILES[$input_name]) || (isset($_FILES[$input_name]['error']) && $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE ))
		{
			return TRUE;
		}

		$subtargetFile = '/tmp/' . uniqid() . $_FILES[$input_name]['name'];

		if(is_null($document_type))
		{
			$object_doc_type = $_POST['document_type'];
		}
		else
		{
			$object_doc_type = $document_type;
		}

		$object_doc_ref_ids = (is_array($_POST['document_object_ref_id']) ? $_POST['document_object_ref_id'] : array($_POST['document_object_ref_id']));

		if(is_dir($_FILES[$input_name]['tmp_name']))
		{
			throw new Exception('copy ne gere pas les dossiers');
		}

		if(!copy($_FILES[$input_name]['tmp_name'], $subtargetFile))
		{
			throw new Exception('Cannot copy file from ' . $_FILES[$input_name]['tmp_name'] . ' to ' . $subtargetFile);
		}

		$mime_type = mime_content_type($subtargetFile);
		$pathinfo = pathinfo($subtargetFile);
		
		if (($check_mimetype == TRUE) && (!in_array($mime_type, self::$allowed_types)))
		{
			throw new Exception('Type de fichier invalide ('.$mime_type.')');
			//$_SESSION['_ERROR_LIST']['Upload de document'] = 'Type de fichier invalide ('.$mime_type.')';
			//UI::quitNBack(); // NE PLUS REDIRIGER, SINON BUG SUR LES APPELS DES METHODES JSON DES WEBSERVICES.
		}
		
		$hDOCTYPEORM = ORM::getORMInstance('documenttype');
		$hDOCORM	 = ORM::getORMInstance('document');
		
		$photo_mime_type_array = array (
			'image/gif'  ,
			'image/jpeg' ,
			'image/png'  ,
			'image/tiff'); //kkpotivi 20130315

		foreach ($object_doc_ref_ids as $object_doc_ref_id)
		{
			$id_document = 0;
			$doctype	 = array();
			$exdocs	  = array();

			$hDOCTYPEORM->browse($doctype, $total_record, array('rulename'), array(array('document_type_id', '=', $object_doc_type)));
			
			$datas   = array(
				'document_type_id' => $object_doc_type,
				'object'		   => $object_doc_ref,
				'object_id'		=> $object_doc_ref_id,
				'mime_type'		=> $mime_type
			);

			$hDOCORM->create($datas, $id_document);

			if ($id_document == 0)
			{
				throw new Exception('Document not created');
			}

			// --- Mise en place de l'ID Store ---
			// Déterminer le nombre de chiffres, l'arrondir à un multiple de deux.
			$len = strlen($id_document);
			$len = ($len%2 != 0)? $len + 1 : $len;
			// Remplissage avec zéros initiaux si nécessaire (minimum : 4).
			$new_id_doc = str_pad($id_document, (($len < 4)? 4 : $len), '0', STR_PAD_LEFT);
			// Création de l'idstore (profondeur maxi : deux sous-répertoires).
			$idstore = wordwrap(substr($new_id_doc, 0, 4), 2, '/', true);

			if(defined('DOCUMENT_ROOT'))
			{
				$targetPath = DOCUMENT_ROOT . LOCAL_FILESTORE . '/' . $idstore;
			}
			else
			{
				$targetPath = $_SERVER['DOCUMENT_ROOT'] . LOCAL_FILESTORE . '/' . $idstore;
			}

			
			if (isset($pathinfo['extension']))
			{
				$extension = '.' . $pathinfo['extension'];
			}
			else
			{
				$extension = '';
			}

			
			if (in_array($mime_type, $photo_mime_type_array))
			{
				$targetFile = $targetPath . '/' . $id_document.$extension;
			}
			else
			{
				$targetFile = $targetPath . '/' . $id_document;
			}

			if (!is_dir($targetPath))
			{
				mkdir($targetPath, 0755, true);
			}

			if(!copy($subtargetFile, $targetFile))
			{
				throw new Exception('File creation aborted');
			}

			$pref_hr_name = sprintf("%s%s", $doctype[$object_doc_type]['rulename']['value'], date('Y'));
			$hDOCORM->search($exdocs, $total_record, array(array('object', 'like', $object_doc_ref), array('object_id', '=', $object_doc_ref_id), array('hr_name', 'like', $pref_hr_name . '%')));
			$nbexdocs  = count($exdocs);

			if ($conserve_name == FALSE)
			{
				$hr_name = sprintf("%s_%s%s", $pref_hr_name, ++$nbexdocs, $extension);
			}
			else
			{
				$hr_name = $_FILES[$input_name]['name'];
			}

			$datas = array(
				'size'	  => filesize($targetFile),
				'file_name' => $targetFile,
				'hr_name'   => $hr_name,
				'users_id'  => (isset($_SESSION['_USER']['killi_user_id']['value']))? $_SESSION['_USER']['killi_user_id']['value'] : NULL
			);

			$hDOCORM->write($id_document, $datas);
		}

		if ($eraseTemp === TRUE)
		{
			unlink($_FILES[$input_name]['tmp_name']);
		}

		return TRUE;
	}

	//.........................................
	public static function store($file,$document_type,$object,$object_id,&$id_document=NULL,$conserve_name=FALSE,$check_mimetype=TRUE,$mime_type=NULL)
	{
		if(!file_exists($file))
		{
			return FALSE;
		}

		if(is_dir($file))
		{
			throw new Exception('Impossible de stocker un dossier !');
		}

		$filename = basename($file);
		$subtargetFile = $file;
		$object_doc_type = $document_type;
		$object_doc_ref_ids = (is_array($object_id) ? $object_id : array($object_id));

		if($mime_type === NULL)
		{
			$mime_type = mime_content_type($file);
		}
		$pathinfo = pathinfo($file);

		if (($check_mimetype == TRUE) && (!in_array($mime_type, self::$allowed_types)))
		{
			throw new Exception('Type de fichier invalide ('.$mime_type.')');
			//return FALSE;
		}

		foreach($object_doc_ref_ids as $object_doc_ref_id)
		{
			$id_document = 0;
			$doctype = array();
			$hDOCTYPEORM = ORM::getORMInstance('documenttype');
			$hDOCORM = ORM::getORMInstance('document');
			$hDOCTYPEORM->browse($doctype, $total_record, array('rulename'), array(array('document_type_id', '=', $object_doc_type)));
			$datas   = array(
				'document_type_id' => $object_doc_type,
				'object'		   => $object,
				'object_id'		=> $object_doc_ref_id,
				'mime_type'		=> $mime_type
			);
			$hDOCORM->create($datas, $id_document);

			if ($id_document == 0)
			{
				throw new Exception('Document not created');
			}

			// --- Mise en place de l'ID Store ---
			// Déterminer le nombre de chiffres, l'arrondir à un multiple de deux.
			$len = strlen($id_document);
			$len = ($len%2 != 0)? $len + 1 : $len;
			// Remplissage avec zéros initiaux si nécessaire (minimum : 4).
			$new_id_doc = str_pad($id_document, (($len < 4)? 4 : $len), '0', STR_PAD_LEFT);
			// Création de l'idstore (profondeur maxi : deux sous-répertoires).
			$idstore = wordwrap(substr($new_id_doc, 0, 4), 2, '/', true);

			if(defined('DOCUMENT_ROOT'))
			{
				$targetPath = DOCUMENT_ROOT . LOCAL_FILESTORE . '/' . $idstore;
			}
			else
			{
				$targetPath = $_SERVER['DOCUMENT_ROOT'] . LOCAL_FILESTORE . '/' . $idstore;
			}

			$targetFile = $targetPath . '/' . $id_document;
			$extension = '';
			if (isset($pathinfo['extension']))
			{
				$extension = '.' . $pathinfo['extension'];
			}
			$photo_mime_type_array = array (
				'image/gif',
				'image/jpeg',
				'image/png',
				'image/tiff'); //kkpotivi 20130315

			if (in_array($mime_type, $photo_mime_type_array))
			{
				$targetFile = $targetPath . '/' . $id_document.$extension;
			}

			if (!is_dir($targetPath))
			{
				mkdir($targetPath, 0755, true);
			}

			if(!copy($subtargetFile, $targetFile))
			{
				throw new Exception('File creation aborted');
			}

			$size		= filesize($targetFile);
			$exdocs		= array();
			$pref_hr_name = sprintf("%s%s", $doctype[$object_doc_type]['rulename']['value'], date('Y'));
			$hDOCORM->search($exdocs, $total_record, array(array('object', '=', $object), array('object_id', '=', $object_doc_ref_id), array('hr_name', 'like', $pref_hr_name . '%')));
			$nbexdocs  = count($exdocs);

			if ($conserve_name == FALSE)
			{
				$hr_name = sprintf("%s_%s%s", $pref_hr_name, ++$nbexdocs, $extension);
			}
			else
			{
				$hr_name = $filename;
			}

			$datas = array(
				'size'	  => $size,
				'file_name' => $targetFile,
				'hr_name'   => $hr_name,
				'users_id'  => (isset($_SESSION['_USER']['killi_user_id']['value']))? $_SESSION['_USER']['killi_user_id']['value'] : NULL
			);
			$hDOCORM->write($id_document, $datas);
		}

		return TRUE;
	}
}
