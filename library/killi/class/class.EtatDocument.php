<?php

/**
 *  @class EtatDocument
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliEtatDocument
{
	public $table		= 'etat_document';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'etat_document_id';
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->etat_document_id = new PrimaryFieldDefinition();
		
		$this->nom = TextFieldDefinition::create()
				->setLabel('État')
				->setEditable(FALSE);
	}
}
