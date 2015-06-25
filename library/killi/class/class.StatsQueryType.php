<?php

/**
 *  @class StatsQueryType
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliStatsQueryType
{
	public $table		= 'killi_query_type';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'query_type_id';
	public $order		= array('ordre');
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->query_type_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Catégorie')
				->setRequired(TRUE);
	}
}
