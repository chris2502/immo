<?php

class RenderText extends Render
{
	public function render()
	{
		if (!is_string($this->_content))
		{
			throw new Exception("Content must be a string.");
		}
		$txt = $this->_content;
		return $txt;
	}

	public function getContentType()
	{
		return 'text/plain';
	}

	public function setDoctype($bool)
	{
		$this->_doctype = $bool;
		
		return $this;
	}
}