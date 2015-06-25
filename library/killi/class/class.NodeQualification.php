<?php

/**
 *  @class NodeQualification
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNodeQualification
{
	public $table		= 'killi_node_qualification';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'qualification_id';
	public $reference	= 'nom';
	//---------------------------------------------------------------------
	public function setDomain()
	{
		if( isset( $_GET[ 'workflow_node_id' ] ) && is_numeric( $_GET[ 'workflow_node_id' ] ) )
		{
			$this->object_domain[] = array('node_id','=',$_GET[ 'workflow_node_id' ]) ;
		}

		$this->object_domain[] = array('obsolete', 'Is Null');

		return TRUE;
	}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->qualification_id = new PrimaryFieldDefinition();

		$this->nom = TextFieldDefinition::create(64)
				->setLabel('Qualification')
				->setRequired(TRUE);

		$this->node_id = Many2oneFieldDefinition::create('Node')
				->setLabel('Noeud')
				->setRequired(TRUE);

		$this->workflow_id = RelatedFieldDefinition::create('node_id','workflow_id');

		$this->obsolete = BoolFieldDefinition::create()
				->setLabel('Obsolète')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);
	}
}
