<?php

/**
 *  @class LinkRights
 *  @Revision $Revision: 4214 $
 *
 */

abstract class KilliLinkRights
{
	public $table  = 'killi_link_rights';
	public $database	= RIGHTS_DATABASE;
	public $primary_key  = 'link_rights_id';
	public $reference = 'link_rights_id';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->link_rights_id = new PrimaryFieldDefinition();

		$this->link_id = Many2oneFieldDefinition::create('NodeLink')
				->setLabel('Lien')
				->setRequired(TRUE);

		// related de link_id
		$this->label = RelatedFieldDefinition::create('link_id','label')->setLabel('Lien');
		$this->deplacement_manuel = RelatedFieldDefinition::create('link_id','deplacement_manuel');
		if (defined('USE_LINK_RIGHTS') && USE_LINK_RIGHTS)
		{
			$this->mandatory_comment = RelatedFieldDefinition::create('link_id','mandatory_comment');
		}
		$this->traitement_echec = RelatedFieldDefinition::create('link_id','traitement_echec');
		$this->input_node = RelatedFieldDefinition::create('link_id','input_node');
		$this->input_node_name = RelatedFieldDefinition::create('link_id','input_node_name');
		$this->input_workflow = RelatedFieldDefinition::create('link_id','input_workflow');
		$this->input_workflow_name = RelatedFieldDefinition::create('link_id','input_workflow_name');
		$this->output_node = RelatedFieldDefinition::create('link_id','output_node');
		$this->output_node_name = RelatedFieldDefinition::create('link_id','output_node_name');
		$this->output_workflow = RelatedFieldDefinition::create('link_id','output_workflow');
		$this->output_workflow_name = RelatedFieldDefinition::create('link_id','output_workflow_name');

		$this->killi_profil_id = Many2oneFieldDefinition::create('Profil')
				->setLabel('Code')
				->setRequired(TRUE);

		$this->move = BoolFieldDefinition::create()
				->setLabel('Move')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);
	}

}
