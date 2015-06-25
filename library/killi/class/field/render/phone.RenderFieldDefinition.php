<?php

/**
 *  @class DatetimeRenderFieldDefinition
 *  @Revision $Revision: 4587 $
 *
 */

class PhoneRenderFieldDefinition extends TextRenderFieldDefinition
{
	public function renderValue($value, $input_name, $field_attributes)
	{
		if( $value['value'] === NULL )
		{
			?><div></div><?php

			return TRUE;
		}
		
		if(preg_match('#^\+33#', $value['value']))
		{
			$value['value'] = '(+33) '.wordwrap('0'. substr($value['value'],3), 2, " ",true);
		}

		parent::renderValue($value, $input_name, $field_attributes);

		return TRUE;
	}
}
