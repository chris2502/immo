<?php

/**
 *  @class ButtonenvXMLNode
 *  @Revision $Revision: 3913 $
 *
 */

class ButtonenvXMLNode extends XMLNode
{
	public function open()
	{
		$title = $this->getNodeAttribute('string');
		$env   = $this->getNodeAttribute('env');
		$icon  = $this->getNodeAttribute('icon', '');
		$width = $this->getNodeAttribute('width', '');
		if(!empty($icon))
		{
			$icon = '<img style="float:left;" src="' . $icon . '"/>';
		}
		if(!empty($width))
		{
			$width = 'style="width: ' . $width . '"';
		}

		$uri = $_SERVER['REQUEST_URI'];
		list($page, $params) = explode('?', $uri);
		$param_tmp_list = explode('&', $params);
		$param_list = array();
		foreach ($param_tmp_list as $pair)
		{
			list($k, $v) = explode('=', $pair);
			$param_list[$k] = $v;
		}

		$env_tmp_list = explode(',', $env);
		$env_list = array();
		$env_to_delete_list = array();
		foreach ($env_tmp_list as $pair)
		{
			if (substr($pair, 0, 1) == '-')
			{
				$env_to_delete_list[substr($pair, 1)] = true;
			}
			else
			{
				list($k, $v) = explode('=', $pair);
				$env_list[$k] = $v;
			}
		}

		foreach ($env_list as $key => $value)
		{
			if (!isset($param_list[$key]))
			{
				$param_list[$key] = $value;
			}
		}

		$param_string_list = array();
		foreach ($param_list as $key => &$value)
		{
			if (isset($env_to_delete_list[$key]))
			{
				continue;
			}

			if (isset($env_list[$key]))
			{
				$value = $env_list[$key];
			}

			$param_string_list[] = $key.'='.$value;
		}

		$param_string = implode('&', $param_string_list);

		$uri  = $page.'?'.$param_string;
		$host = $_SERVER['HTTP_HOST'];
		if ($_SERVER['SERVER_PORT'] == 443)
		{
			$prefix = 'https://';
		}
		else
		{
			$prefix = 'http://';
		}
?>
		<button type="button" onclick="document.location.href='<?= $prefix.$host.$uri ?>'" <?= $width ?>><?= $icon ?><?= $title ?></button>
<?php
		return TRUE;
	}

	public function close()
	{
		return TRUE;
	}
}

