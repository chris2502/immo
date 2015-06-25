<?php

/**
 *  @class Document
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliDocument
{
	public $table			= 'document';
	public $database		= RIGHTS_DATABASE;
	public $primary_key		= 'document_id';
	public $reference		= 'hr_name';
	//-------------------------------------------------------------------------
	public function setDomain()
	{
		if(isset($_POST['search/document/object_link']) && !empty($_POST['search/document/object_link']))
		{
			$this->object_domain[] = array('object','=',$_POST['search/document/object_link']);
		}
	}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->document_id = new PrimaryFieldDefinition();

		$this->document_type_id = Many2oneFieldDefinition::create('DocumentType')
				->setLabel('Type de document')
				->setEditable(FALSE);

		$this->document_type_name = RelatedFieldDefinition::create('document_type_id','name')->setLabel('Type de document');

		$this->etat_document_id = Many2oneFieldDefinition::create('EtatDocument')
				->setLabel('État')
				->setDefaultValue(ETAT_DOCUMENT_NON_VERIFIE);

		$this->object = TextFieldDefinition::create()
				->setLabel('Objet Lié')
				->setEditable(FALSE);

		$this->object_id = IntFieldDefinition::create()
				->setLabel('Objet ID')
				->setEditable(FALSE);

		$this->mime_type = TextFieldDefinition::create()
				->setLabel('Type MIME')
				->setEditable(FALSE);

		$this->size = IntFieldDefinition::create()
				->setLabel('Taille')
				->setEditable(FALSE);

		$this->file_name = TextFieldDefinition::create()
				->setLabel('Nom du fichier')
				->setEditable(FALSE)
				->setExtractCSV(FALSE);

		$this->hr_name = TextFieldDefinition::create()
				->setLabel('Nom')
				->setEditable(FALSE);

		$this->users_id = Many2oneFieldDefinition::create('User')
				->setLabel('Uploadé par')
				->setEditable(FALSE);

		$this->date_creation = DatetimeFieldDefinition::create()
				->setLabel('Date d\'upload')
				->setEditable(FALSE);

		$this->used_as_padlock = BoolFieldDefinition::create()
				->setLabel('Utilisable comme verrou')
				->setEditable(FALSE);

		$this->file_found = BoolFieldDefinition::create()
				->setLabel('Fichier présent')
				->setEditable(FALSE)
				->setFunction('Document', 'setFileFound');

		$this->object_link = TextFieldDefinition::create()
				->setLabel('Objet lié')
				->setEditable(FALSE)
				->setFunction('Document', 'setObject');
	}
}
