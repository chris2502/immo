<?php

/**
 *  @class PrimaryFieldDefinition
 *  @Revision $Revision: 4558 $
 *
 */

class PrimaryFieldDefinition extends ExtendedFieldDefinition
{
	public $type = 'primary key';
	public $render = 'many2one';
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
}
