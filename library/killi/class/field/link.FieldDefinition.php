<?php

/**
 *  @class LinkFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class LinkFieldDefinition extends ExtendedFieldDefinition
{
	public function __construct($object_relation = NULL)
	{
		$this->setObjectRelation ( $object_relation );
	}
}