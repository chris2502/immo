<?php

/**
 *  @class One2manyFieldDefinition
 *  @Revision $Revision: 4557 $
 *
 */

class One2manyFieldDefinition extends ExtendedFieldDefinition
{
	public $is_db_column = FALSE;
	public $_order_relation = NULL;
	public $render = 'many2many';
	//.....................................................................
	//.....................................................................
	public function __construct($object_relation = NULL, $focused_field = NULL)
	{
		$this->setObjectRelation ( $object_relation );
		$this->setFieldRelation ( $focused_field );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return One2manyFieldDefinition
	 */
	public static function create($object_relation = NULL, $focused_field = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $object_relation, $focused_field );
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
	 * Défini l'ordre de tri
	 *
	 * @param  array $order
	 * @return One2manyFieldDefinition
	 */
	public function setOrderRelation(array $order)
	{
		if (! is_array($order))
		{
			throw new Exception('$order must be an array');
		}
		$this->_order_relation = $order;
	
		return $this;
	}
	
}
