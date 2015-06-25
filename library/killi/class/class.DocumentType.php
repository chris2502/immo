<?php

/**
 *  @class DocumentType
 *  @Revision $Revision: 4214 $
 *
 */

abstract class KilliDocumentType
{
	public $table			= 'document_type';
	public $database		= RIGHTS_DATABASE;
	public $primary_key		= 'document_type_id';
	public $reference		= 'name';
	//---------------------------------------------------------------------
	function setDomain()
	{
		$this->object_domain[] = array('obsolete','=','0');

		return TRUE;
	}
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->document_type_id = new PrimaryFieldDefinition();

		$this->name = TextFieldDefinition::create(64)
				->setLabel('Nom')
				->setRequired(TRUE);

		$this->rulename = TextFieldDefinition::create(64)
				->setLabel('Règle')
				->setRequired(TRUE);

		$this->object = TextFieldDefinition::create(64)
				->setLabel('Object')
				->setRequired(TRUE);

		$this->obsolete = BoolFieldDefinition::create()
				->setLabel('Obsolète')
				->setDefaultValue(FALSE);
	}
}
