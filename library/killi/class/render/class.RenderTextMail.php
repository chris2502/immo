<?php

class RenderTextMail extends RenderText
{

	public function __construct()
	{
		$this->setDoctype(FALSE);
	}

	public function renderBody()
	{
		foreach ($this->_content as $k => $data)
		{
			if (is_string($data))
			{
				echo $data . "\r\n";
			}

			if (is_array($data) && isset($data['label']) && isset($data['text']))
			{
				echo $data['label'] . " : " . $data['text'] . "\r\n";
			}
		}
	}
}