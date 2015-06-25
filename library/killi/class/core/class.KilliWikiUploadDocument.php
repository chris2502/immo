<?php

/**
 *  @class KilliWikiUploadDocument
 *  @Revision $Revision: 4139 $
 *
 */

class KilliWikiUploadDocument
{
	private static $allowed_types = array(
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/tiff'
	);

	public function upload()
	{
		$exaction = explode('.', $_GET['exaction']);
		$object = $exaction[0];
		$document_type_name = 'wiki_image';
		//Type de document = wiki_image
		$hORM = ORM::getORMInstance('documenttype', true);
		$hORM->browse($liste_documenttype, $nb, array('name'), array(array('object', '=', $object), array('name','=',$document_type_name)));
		if ($nb == 0)
		{
			$data['name'] = $document_type_name;
			$data['object'] = $object;
			$data['rulename'] = 'wikimage';
			$data['obsolete'] = 0;
			$hORM->create($data, $type_document_id);
		}
		else
		{
			$document_type = current($liste_documenttype);
			$type_document_id = $document_type['document_type_id']['value'];
		}


		DocumentLocal::create($object, TRUE, $id_document, FALSE, TRUE, 'document', $type_document_id);
		ORM::getORMInstance('document')->readOne($id_document, $document, array('hr_name', 'mime_type'));
		if (!in_array($document['mime_type']['value'], self::$allowed_types))
		{
			throw new Exception("This type of document is not allowed.", 1);
		}
 		$response['src'] = $document['hr_name']['value'];
		echo json_encode($response);
		return TRUE;
	}

	public function getImage($hr_name, &$base_64)
	{
		ORM::getORMInstance('document')->browse($liste_document, $nb, array('file_name'), array(array('hr_name', '=', $hr_name)));
		if (sizeof($liste_document) == 0)
		{
			throw new Exception("Img doesn't exist !", 1);
		}
		$document = current($liste_document);
		if (!file_exists($document['file_name']['value']))
		{
			throw new Exception("Img not found !", 1);
		}
		$base_64 = base64_encode(file_get_contents($document['file_name']['value']));
		return TRUE;
	}
}
