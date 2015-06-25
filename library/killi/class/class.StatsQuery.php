<?php

/**
 *  @class StatsQuery
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliStatsQuery
{
	public $table		= 'killi_stats_query';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'query_id';
	public $order		= array('ordre');
	public $reference	= 'nom';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->query_id = new PrimaryFieldDefinition();

		$this->cat_id = Many2oneFieldDefinition::create('StatsCategory')
				->setLabel('Catégorie')
				->setRequired(TRUE);

		$this->type_id = Many2oneFieldDefinition::create('StatsQueryType')
				->setLabel('Type')
				->setRequired(TRUE);

		$this->parent_id = IntFieldDefinition::create()
				->setLabel('Parent')
				->setRequired(TRUE);

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Nom')
				->setRequired(TRUE);

		$this->query_name = TextFieldDefinition::create(64)
				->setLabel('Name')
				->setRequired(TRUE);

		$this->query_sql = TextareaFieldDefinition::create()
				->setLabel('SQL')
				->setRequired(TRUE);

		$this->object = TextFieldDefinition::create(64)
				->setLabel('Objet')
				->setRequired(TRUE);

		$this->param_object = TextFieldDefinition::create(64)
				->setLabel('Object Param')
				->setRequired(TRUE);

		$this->ordre = IntFieldDefinition::create()
				->setLabel('Ordre')
				->setRequired(TRUE);
	}
}
