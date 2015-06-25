<?php

/**
 *  @class DocumentCommentaire
 *  @Revision $Revision: 172 $
 *
 */

abstract class KilliDocumentCommentaire extends KilliCommentaire
{
	public function setDomain()
	{
		parent::setDomain();
		$this->object_domain[] = array('object','=', 'document');
	}
}
