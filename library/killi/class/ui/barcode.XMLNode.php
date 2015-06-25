<?php

/**
 *  @class BarcodeXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class BarcodeXMLNode extends XMLNode
{
	public function open()
	{
		$attributes = $this->attributes;
		$hInstance = ORM::getObjectInstance($attributes['object']);

		$label = $hInstance->$attributes['attribute']->name;
		
		echo "<table class=\"field\" cellspacing=\"2\" cellpadding=\"1\">
			<tr>\n";

		if ((!isset($attributes['nolabel'])))
		{
			echo "<td class=\"field_label\">".$label." : </td>\n";
		}
		echo "<td class=\"field_td\">\n";

		$barCode = new code_barre();
		if(isset($this->attributes['height']))
		{
			$barCode->HEIGHT=$this->attributes['height'];
		}else{
			$barCode->HEIGHT=40;
		}
		if(isset($this->attributes['width']))
		{
			$barCode->WIDTH=$this->attributes['width'];
		}else{
			$barCode->WIDTH=0;
		}
		if(isset($this->attributes['format']))
		{
			$barCode->setFiletype($this->attributes['format']);
		}

		if(isset($this->attributes['readable']) && ( empty($this->attributes['readable']) || $this->attributes['readable']=='false' || $this->attributes['readable']=='N' || $this->attributes['readable']=='0' || $this->attributes['readable']===false))
		{
				$barCode->setText('');
		}
		if(isset($this->attributes['text']))
		{
			switch($this->attributes['text'])
			{
				case 'reference' :
					$barCode->setText($this->_current_data[$this->attributes['attribute']]['reference']);
				break;
				case 'value' :
					$barCode->setText('AUTO');
				break;
				case '' :
					$barCode->setText('');
				break;
				default :
					$barCode->setText($this->attributes['text']);
				break;
			}
		}else{
			$barCode->setText($this->_current_data[$this->attributes['attribute']]['value']);
		}

		if(isset($this->attributes['code']))
		{
			switch($this->attributes['code'])
			{
				case 'reference' :
					$barCode->setCode(strtoupper($this->_current_data[$this->attributes['attribute']]['reference']));
				break;
				case 'value' :
					$barCode->setCode(strtoupper($this->_current_data[$this->attributes['attribute']]['value']));
				break;
				default :
					$barCode->setCode(strtoupper($this->attributes['code']));
				break;
			}
		}else{
			$barCode->setCode(strtoupper($this->_current_data[$this->attributes['attribute']]['value']));
		}


		if(isset($this->attributes['type']))
		{
			$barCode->setType($this->attributes['type']);
		}else{
			$barCode->setType('C39');
		}

		if(!isset($this->attributes['showCodeType']) || $this->attributes['showCodeType']===false || $this->attributes['showCodeType']=='N' || $this->attributes['showCodeType']=='0' || $this->attributes['showCodeType']=='false')
		{
			$barCode->hideCodeType();
		}

		if(isset($this->attributes['color']))
		{
			if(isset($this->attributes['bgcolor']))
			{
				$barCode->setColors($this->attributes['color'],$this->attributes['bgcolor']);
			}else{
				$barCode->setColors($this->attributes['color']);
			}
		}
		$barCode->checkCode();
		$barCode->encode();

		$imgParam='';

		if(isset($this->attributes['zoom']) && $this->attributes['zoom']>=1)
		{
			$imgWidth = imagesx($barCode->IH);
			$imgHeight = imagesy($barCode->IH);
			$imgParam.=' height="'.intval($imgHeight*$this->attributes['zoom']).'"';
			$imgParam.=' width="'.intval($imgWidth*$this->attributes['zoom']).'"';
		}

		echo(' <img '.$imgParam.' src="'.$barCode->showInlineSrc().'"/>');
		echo "</td>\n</tr>\n</table>\n";
	}
}
