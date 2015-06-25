<?php

/**
 *  @class Node
 *  @Revision $Revision: 4624 $
 *
 */

abstract class KilliNode
{
	public $table		= 'killi_workflow_node';
	public $database	= RIGHTS_DATABASE;
	public $primary_key = 'workflow_node_id' ;
	public $order		= array('workflow_id','etat');
	//---------------------------------------------------------------------
	public function setDomain()
	{
		//---Si pas admin ---> filtrage des noeuds
		if (isset ( $_SESSION ['_USER'] ))
		{
			if (! in_array ( ADMIN_PROFIL_ID, $_SESSION ['_USER'] ['profil_id'] ['value'] ))
			{
				$table = array ( RIGHTS_DATABASE . '.killi_node_rights' );

				$join_list = array ( RIGHTS_DATABASE . '.killi_workflow_node.workflow_node_id=' . RIGHTS_DATABASE . '.killi_node_rights.node_id' );

				$filter = array (
					array ( RIGHTS_DATABASE . '.killi_node_rights.allow', '=', '1' ),
					array ( RIGHTS_DATABASE . '.killi_node_rights.profil_id','in', $_SESSION ['_USER'] ['profil_id'] ['value'] )
				);

				$this->domain_with_join = array (
					'table' => $table,
					'join' => $join_list,
					'filter' => $filter
				);
			}
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	function __construct()
	{
		$this->workflow_node_id = new PrimaryFieldDefinition();

		$this->etat = TextFieldDefinition::create(64)
				->setLabel('Libellé')
				->setRequired(TRUE);

		$this->node_name = TextFieldDefinition::create(32)
				->setLabel('Nom interne')
				->setRequired(TRUE);

		$this->commande = TextFieldDefinition::create(255)
				->setLabel('Commande (Script, Quantité ou Point d\'entrée)');

		$this->object = TextFieldDefinition::create(64)
				->setLabel('Objet')
				->setRequired(TRUE);

		$this->interface = TextFieldDefinition::create(64)
				->setLabel('Interface');

		$this->workflow_id = Many2oneFieldDefinition::create('Workflow')
				->setLabel('Workflow')
				->setRequired(TRUE);

		$this->workflow_name = RelatedFieldDefinition::create('workflow_id','workflow_name');

		$this->type_id = Many2oneFieldDefinition::create('NodeType')
				->setLabel('Type')
				->setRequired(TRUE);

		$this->killi_workflow_node_group_id = Many2oneFieldDefinition::create('NodeGroup')
				->setLabel('Groupe de noeud');

		$this->ordre = IntFieldDefinition::create()
				->setLabel('Ordre')
				->setDefaultValue(0)
				->setRequired(TRUE);

		$this->population = IntFieldDefinition::create()
				->setLabel('Population')
				->setEditable(FALSE)
				->setFunction('Node', 'setPopulation');

		$this->allow = BoolFieldDefinition::create()
				->setLabel('Lecture')
				->setVirtual(TRUE);

		$this->obsolete = BoolFieldDefinition::create()
			->setLabel('Obsolete');
	}
}
