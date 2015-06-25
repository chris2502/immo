<?php

/**
 *  @class StatsValue
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliStatsValue
{
	public $table		= 'killi_stats_value';
	public $database	= RIGHTS_DATABASE;
	public $primary_key	= 'value_id';
	public $reference	= 'value';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->value_id = new PrimaryFieldDefinition();

		$this->report_date = DatetimeFieldDefinition::create()
				->setLabel('Date')
				->setRequired(TRUE);

		$this->query_id = Many2oneFieldDefinition::create('StatsQuery')
				->setLabel('Query')
				->setRequired(TRUE);

		$this->value = IntFieldDefinition::create()
				->setLabel('Valeur')
				->setRequired(TRUE);

		$this->param_value = IntFieldDefinition::create()
				->setLabel('Param Value')
				->setRequired(TRUE);
	}
}
