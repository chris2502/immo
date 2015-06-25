<?php

/**
 *  @class NodeLink
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliNodeLink
{
	public $table		= 'killi_node_link';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'link_id' ;
	public $reference	= 'label';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//---------------------------------------------------------------------
	public function __construct()
	{
		$this->link_id = new PrimaryFieldDefinition();

		$this->label = TextFieldDefinition::create(32)
				->setLabel('Libellé');

		$this->input_node = Many2oneFieldDefinition::create('Node')
				->setLabel('Noeud d\'entrée')
				->setRequired(TRUE);

		$this->input_node_name = RelatedFieldDefinition::create('input_node','node_name')->setLabel('Noeud d\'entrée');
		$this->input_workflow = RelatedFieldDefinition::create('input_node','workflow_id')->setLabel('Workflow d\'entrée');
		$this->input_workflow_name = RelatedFieldDefinition::create('input_node','workflow_name')->setLabel('Workflow d\'entrée');

		$this->output_node = Many2oneFieldDefinition::create('Node')
				->setLabel('Noeud d\'arrivée')
				->setRequired(TRUE);

		$this->output_node_name = RelatedFieldDefinition::create('output_node','node_name')->setLabel('Noeud d\'arrivée');
		$this->output_workflow = RelatedFieldDefinition::create('output_node','workflow_id')->setLabel('Workflow d\'arrivée');
		$this->output_workflow_name = RelatedFieldDefinition::create('output_node','workflow_name')->setLabel('Workflow d\'arrivée');

		$this->traitement_echec = BoolFieldDefinition::create()
				->setLabel('Traitement en échec')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);

		$this->display_average = BoolFieldDefinition::create()
				->setLabel('Afficher la durée moyenne')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);

		$this->deplacement_manuel = BoolFieldDefinition::create()
				->setLabel('Déplacement manuel')
				->setDefaultValue(TRUE)
				->setRequired(TRUE);

		$this->hidden = BoolFieldDefinition::create()
				->setLabel('Caché')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);

		$this->constraints = TextareaFieldDefinition::create()
				->setLabel('Contraintes');

		$this->constraints_label = TextareaFieldDefinition::create()
				->setLabel('Descriptif des contraintes');

		$this->constraints_padlock = TextareaFieldDefinition::create()
				->setLabel('Types de documents à verrouiller');

		$this->constraints_qualification = BoolFieldDefinition::create()
				->setRequired(TRUE)
				->setDefaultValue(FALSE)
				->setLabel('Qualification requise');

		if (defined('USE_LINK_RIGHTS') && USE_LINK_RIGHTS)
		{
			$this->mandatory_comment = BoolFieldDefinition::create()
				->setLabel('Commentaire obligatoire')
				->setDefaultValue(FALSE)
				->setRequired(TRUE);

			$this->full_reference = BoolFieldDefinition::create()
				->setLabel('Commentaire')
				->setFunction('NodeLink', 'setFullReference');

			$this->reference = 'full_reference';
		}
	}
}
