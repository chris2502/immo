<?php

/**
 *  @class TypeJetonMethod
 *  @Revision $Revision: 672 $
 *
 */

class KilliTypeJetonMethod extends Common
{
	public static function generateKilliURL($object, $method, &$url)
	{
		$url = NULL;
		
		if(isset($_SERVER['HTTPS']))
		{
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}
		$server = $scheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
		
		$url = $server.'?action='.$object.'.'.$method.'&token='.$_SESSION['_TOKEN'];
	}
	
	public function forgeTypeJeton(&$name, &$object, &$method, &$url)
	{
		if (empty($method))
		{
			$method = 'edit';
		}
		if (empty($url))
		{
			self::generateKilliURL($object, $method, $url);
		}
	}
	
	public function create($data, &$id, $ignore_duplicate = FALSE)
	{
		$this->forgeTypeJeton($data['name'], $data['object'], $data['method'], $data['url']);
		
		parent::create($data, $id);
		
		return TRUE;
	}
	
	public function write($data)
	{
		$this->forgeTypeJeton($_POST['typejeton/name'], $_POST['typejeton/object'], $_POST['typejeton/method'], $_POST['typejeton/url']);
		
		parent::write($data);
		
		return TRUE;
	}
}