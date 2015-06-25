<?php

/**
 *  @class PasswordFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class PasswordFieldDefinition extends TextFieldDefinition
{
	public $extract_csv = FALSE;
	public $crypt_method = NULL;
	public function setCryptMethod($method)
	{
		if(!empty($method) && function_exists($method))
		{
			$this->required=0;
			$this->crypt_method=$method;
		}
		return $this;
	}
}
