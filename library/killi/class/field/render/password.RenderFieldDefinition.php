<?php

/**
 *  @class PasswordRenderFieldDefinition
 *  @Revision $Revision: 4451 $
 *
 */

class PasswordRenderFieldDefinition extends textRenderFieldDefinition
{
	public $html5_type = 'password';

	public function renderFilter($name, $selected_value)
	{
		return TRUE;
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		if(is_string($value['value']))
		{
			$value['value'] = mb_substr($value['value'],0,1).str_pad("",mb_strlen($value['value'])-2,"*").mb_substr($value['value'],-1,1);
		}
		if(!empty($this->field->crypt_method))
		{
			$value['value']='( Crypté ... )';
		}

		parent::renderValue($value, $input_name, $field_attributes);
	}
	public function renderInput($value, $input_name, $field_attributes)
	{
		if(!empty($this->field->crypt_method))
		{
			$this->field->required=0;
			$field_attributes['placeholder']='Crypté ...';
			$value['value']='';
		}
		parent::renderInput($value, $input_name, $field_attributes);
	}
}
