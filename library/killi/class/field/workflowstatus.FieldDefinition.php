<?php

/**
 *  @class WorkflowStatusFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */

class WorkflowStatusFieldDefinition extends ExtendedFieldDefinition
{
	public $type = 'workflow_status';
	public $is_db_column = FALSE;

	//.....................................................................
	/**
	 * Champs de type workflow_status
	 * @param string $workflow
	 * @param string $key = 'id'
	 */
	public function __construct($workflow = NULL, $key = 'id', $pk_relation = NULL)
	{
		$this->setWorkflow ( $workflow );
		$this->setFieldRelation ( $key );
		$this->setPKRelation ( $pk_relation );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return WorkflowStatusFieldDefinition
	 */
	public static function create($workflow = NULL, $key = 'id', $pk_relation = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $workflow, $key, $pk_relation );
	}
	//.....................................................................
	/**
	 * Précise si le champs peut être édité en vue formulaire ou création et à distance via NJB
	 *
	 * @param boolean $editable
	 * @return ExtendedFieldDefinition
	 */
	public function setEditable($editable = TRUE)
	{
		$this->editable = FALSE;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis la colonne de l'objet courant qui contient la clé du token
	 *
	 * @param string $pk_relation
	 * @return ExtendedFieldDefinition
	 */
	public function setPKRelation($pk_relation)
	{
		$this->pk_relation = $pk_relation;

		return $this;
	}
}
