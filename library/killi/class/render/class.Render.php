<?php

interface RenderInterface
{
	public function render();
	public function getContentType();
}

abstract class Render implements RenderInterface
{
	protected $_content;

	public function setContent($content)
	{
		$this->_content = $content;
		return $this;
	}
}