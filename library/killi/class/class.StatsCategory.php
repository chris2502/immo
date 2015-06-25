<?php

/**
 *  @class StatsCategory
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliStatsCategory
{
	public $table			= 'killi_stats_category';
	public $database		= RIGHTS_DATABASE;
	public $primary_key		= 'stats_cat_id';
	public $order		   = array('ordre');
	public $reference		= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->stats_cat_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Catégorie')
				->setRequired(TRUE);
	}
}
