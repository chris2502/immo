<?php

/**
 *  @class AjaxCommon
 *  @Revision $Revision: 2654 $
 *
 */

abstract class AjaxCommon
{
	protected $response;
	protected $user_id;

	public function __construct()
	{
		$this->reponse = "";
		$this->user_id = (isset($_SESSION['_USER']['killi_user_id']['value']))? $_SESSION['_USER']['killi_user_id']['value'] : '0';
	}

	public function call()
	{
		if(isset($_POST['__func']))
		{
			if(!is_callable(array($this, $_POST['__func'])))
			{
				throw new Exception("Cannot call ajax method");
			}

			$this->{$_POST['__func']}();

			if (!isset($_SESSION['_ERROR_LIST']))
			{
				global $hDB;
				$hDB->db_commit();
			}

			die($this->response);
		}
	}
}
