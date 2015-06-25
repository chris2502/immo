<?php

class RenderHTML extends Render
{

//  protected $_logo = '/library/killi/images/logofree.png';
	protected $_logo = 'https://subscribe.free.fr/login/images/logofree.png';

	protected $_title;

	protected $_doctype = TRUE;

	public function __construct()
	{

	}

	public function getContentType()
	{
		return 'text/html';		
	}

	public function setDoctype($bool)
	{
		$this->_doctype = $bool;
		
		return $this;
	}

	public function setTitle($title)
	{
		$this->_title = $title;

		return $this;		
	}
	
	public function render()
	{
		ob_start();

		if (!is_object($this->_content))
		{
			$this->_content = (object) $this->_content;
		}

		if ($this->_doctype)
		{
			?><!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
		}
		?><html xmlns="http://www.w3.org/1999/xhtml"><?php

			$this->renderHeader();

			$this->renderBody();

		?></html><?php

		$render = ob_get_clean();

		return $render;
	}
}