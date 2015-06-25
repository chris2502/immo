<?php

/**
 *  @class HiddenXMLNode
 *  @Revision $Revision: 3778 $
 *
 */

class HiddenXMLNode extends XMLNode
{
	public function open()
	{		
		$name = '';
		$attribute = $this->getNodeAttribute('attribute');
		$object = $this->getNodeAttribute('object', false, true);

		$this->_getInputName($name);
		$split_name = explode ('/', $name);
		$sname = $split_name[0];

		if(count($split_name) > 1)
		{
			$sname = $split_name[1];
		}

		$default = '';
		if (isset($_GET[$sname]))
		{
			$default = $_GET[$sname];
		}
		else // l'attribut value n'est pas obligatoire
		if(isset($this->_current_data[$attribute]['value']))
		{
			$default = $this->_current_data[$attribute]['value'];
		}
		else
		if(isset($this->_data_list[$attribute]['value']))
		{
			
			$default = $this->_data_list[$attribute]['value'];
		}
		elseif($object AND isset($this->_data_list[$object]))
		{
			$row = reset($this->_data_list[$object]);
			$k = key($this->_data_list[$object]);
			if (isset($this->_data_list[$object][$attribute]))
			{
				$default = $this->_data_list[$object][$attribute]['value'];
			}
			elseif (is_numeric($k) AND isset($row[$attribute]))
			{
				$default = $row[$attribute]['value'];
			}
		}


		$value = $this->getNodeAttribute('value', $default);

		if(strncmp($value, 'eval:', 5) == 0)
		{
			$raw	= explode(':', $value);
			$string = $raw[1];
			eval("\$value=$string;");
		}

		//---Crypt value
		Security::crypt($value, $crypt_value);

		?><input value="<?= $crypt_value ?>" type="hidden" name="crypt/<?= $name?>" id="crypt_<?= $name ?>" /><?php

		return TRUE;
	}
}
