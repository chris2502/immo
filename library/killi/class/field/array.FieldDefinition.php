<?php

/**
 *  @class ArrayFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class ArrayFieldDefinition extends ExtendedFieldDefinition
{
	public function __construct($object_relation = NULL)
	{
		$this->setObjectRelation ( $object_relation );
	}
}
