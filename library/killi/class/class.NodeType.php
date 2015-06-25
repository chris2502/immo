<?php

/**
 *  @class NodeType
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNodeType
{
	public $table		= 'killi_node_type';
	public $database	 = RIGHTS_DATABASE;
	public $primary_key = 'node_type_id' ;
	public static $reference = 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->node_type_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Nom')
				->setRequired(TRUE);
	}
}
