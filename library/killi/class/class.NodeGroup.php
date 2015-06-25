<?php

/**
 *  @class NodeGroup
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNodeGroup
{
	public $table		= 'killi_workflow_node_group';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'killi_workflow_node_group_id' ;
	public $reference	= 'name';
	//---------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->killi_workflow_node_group_id = new PrimaryFieldDefinition();

		$this->name = TextFieldDefinition::create(32)
				->setLabel('Libellé')
				->setRequired(TRUE);

		$this->color = EnumFieldDefinition::create()
				->setLabel('Couleur')
				->setRequired(TRUE)
				->setValues(array(
					'lightgrey'       => 'lightgrey',
					'lightpink'       => 'lightpink',
					'lightblue'       => 'lightblue',
					'lightgoldenrod1' => 'lightgoldenrod1',
					'IndianRed3'      => 'IndianRed3',
					'Peru'            => 'Peru',
					'SteelBlue'       => 'SteelBlue',
					'Tan'             => 'Tan',
					'Teal'            => 'Teal',
					'SlateBlue'       => 'SlateBlue',
					'Sienna'          => 'Sienna'
				));
	}
}
