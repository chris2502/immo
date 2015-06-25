<?php

/**
 *  @class ButtonformXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class ButtonformXMLNode extends XMLNode
{
	public function open()
	{
		$action   = $this->getNodeAttribute('action');
		$mainobj  = $this->getNodeAttribute('object');
		$string   = $this->getNodeAttribute('string');
		$key	  = $this->getNodeAttribute('key');
		$env_attr = $this->getNodeAttribute('env', '');
		$always_visible = ($this->getNodeAttribute('always_visible', '0') == '1');

		if($this->_edition == TRUE || $always_visible)
		{
			$raw = explode('.', $action);
			$obj = strtolower($raw[0]);
			$action = "./index.php?action=" . $action . "&token=" . $_SESSION['_TOKEN'] . "&view=create";
			$id = $_GET['primary_key'];
			$data = $this->_data_list[$mainobj][$id];

			$bid = array();
			$hORM = ORM::getORMInstance($obj);
			$hORM->search($bid, $num, array(array($key, '=', $id)));

			if(!empty($env_attr))
			{
				$envs = explode(',', $env_attr);

				foreach($envs as $e)
				{
					$env = explode('=', $e);
					$action .= "&crypt/" . $env[0] . "=";
					list($envobj, $envattr) = explode('.', $env[1]);
					if(preg_match("/^[a-z\_]+$/", $envobj) && isset($data[$envattr]))
					{
						Security::crypt($data[$envattr]['value'], $crypt_value);
					}
					else
					{
						throw new Exception("Invalid env attribute");
					}

					$action .= $crypt_value;
				}
			}

			if(empty($bid) || $always_visible)
			{
				?><button <?= $this->style() ?> type="button" onClick="return window.open('<?= $action; ?>', 'popup_buttonform_<?= $this->id ?>', config='height=600, width=800, toolbar=no, scrollbar=yes');" style="border: solid 2px #000000; width: 180px;"><?= $string; ?></button><?php
			}
		}
	}

	public function close()
	{

	}
}
