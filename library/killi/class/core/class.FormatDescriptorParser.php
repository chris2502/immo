<?php

/**
 *
 *  @class FormatDescriptorParser
 *  @Revision $Revision: 4285 $
 *
 */

class FormatDescriptorParser
{
	//---------------------------------------------------------------------
	public function parseData($format,$data,&$error_list,$standardize_return=false)
	{
		//---Il faut des data
		if ($data===NULL)
		{
			$error_list[] = 'No variable "data" into POST !';
			return TRUE;
		}

		$this->_checkRequired($data,$format,$error_list,$standardize_return);
		$this->_checkFormat($data,$format,$error_list);

		return TRUE;
	}
	//---------------------------------------------------------------------
	private function _checkRequired($data,$format,&$errors,$standardize_return=false)
	{
		foreach($format as $field_name=>$value)
		{
			//---Eval
			if ((is_string($value['requis'])) && (substr($value['requis'],0,5)==='eval:'))
			{
				$value['requis'] = eval('return '.substr($value['requis'],5).';');
			}

			//---Si requis
			if ((is_bool($value['requis'])) && $value['requis']===TRUE)
			{
				if( !array_key_exists($field_name,$data) )
				{
					if ($standardize_return)
					{
						$errors[][$field_name] = 'Champ requis.';
					}
					else
					{
						$errors[] = '"'.$field_name.'" requis';
					}
				}
			}
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
	private function _checkFormat($data,$format,&$errors)
	{
		foreach( $data as $key => $value )
		{
			if ( isset($format[$key]) === false )
			{
				$errors[][$key] = 'clé non connue : '  . $key;
			}
			else
			{
				if( is_null($value) )
				{
					continue ;
				}
				elseif( is_array( $value ) === TRUE )
				{
					for( $i = 0 ; $i < count( $value ) ; $i++ )
					{
						$this->_checkFormat( array( $key => $value[ $i ] ) , $format, $errors ) ;
					}
				}
				else
				{
					if( is_string( $format[$key]['format'] ) )
					{
						if(substr($format[$key]['format'],0,6)==='regex:')
						{
							if (preg_match(substr($format[$key]['format'],6),$value)==0)
							{
								$errors[][$key] = 'format invalide !';
							}
						}
						elseif(substr($format[$key]['format'],0,5)==='func:')
						{
							if(!function_exists(substr($format[$key]['format'],5)))
							{
								$error[][$key] = 'fonction invalide !';
							}
							elseif(call_user_func(substr($format[$key]['format'],5),$value) !== TRUE)
							{
								$error[][$key] = 'format invalide !';
							}
						}
					}
					elseif ( is_array( $format[$key]['format'] ) )
					{
						if (!in_array($value,$format[$key]['format']))
						{
							$errors[][$key] = 'n\'est pas une valeur connue !';
						}
					}
				}
			}
		}

		return TRUE;
	}
	//---------------------------------------------------------------------
}

