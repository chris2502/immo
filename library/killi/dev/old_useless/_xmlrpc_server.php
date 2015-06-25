<?php

require_once('./library/killi/class/class.DbLayer.php');
require_once('./library/killi/class/class.ObjectDefinition.php');
require_once('./library/killi/include/config.php');
require_once('./include/config.php');

function __autoload($classname)
{
	$classfile = './class/class.' . $classname . '.php';
	$killiclassfile = './library/killi/class/class.' . $classname . '.php';

	if(file_exists($classfile))
	{
		require_once($classfile);
	}
	elseif(file_exists($killiclassfile))
	{
		require_once($killiclassfile);
	}
}

/**
 * Execution dynamique d'une fonction/methode enregistre en base
 * @param string $token (Token ID obtenu avec xmlrpc_login)
 * @param string $object (<NomObj>Method si appel de methode)
 * @param string $fn (Nom de la function ou methode)
 * @param array $params
 * @return l'appel de fonction
 */

function xmlrpc_execute($token, $object, $fn, $params)
{
	global $hDB;

	if(!$token)
	    return NULL;

	$query = "SELECT COUNT(1) FROM " . DBSI_DATABASE . ".xmlrpc_functions_expose WHERE name LIKE '" . $fn . "'";

	if($object !== NULL)
	{
		$query .= " AND object LIKE '" . $object . "'";
	}

	$hDB->db_select($query, $result, $numrows);

	if($numrows == 0)
	{
		return NULL;
	}
	else
	{
		if($object !== NULL)
		{
			$fncall = array(new $object, sprintf("xmlrpc_%s", $fn));
		}
		else
		{
			$fncall = sprintf("xmlrpc_%s", $fn);
		}
	}

	return call_user_func($fncall, $token, $params);
}

/**
 * Tentative de login
 * @param string $user
 * @param string $password
 * @return le token
 */

function xmlrpc_login($user, $password)
{
	global $hDB;

	$query = "SELECT killi_user_id, actif FROM " . DBSI_DATABASE . ".killi_user WHERE login = '" . Security::secure($user) . "' AND password = '" . Security::secure($password) . "'";

	if($hDB->db_select($query, $result, $numrows) != TRUE)
	{
		return FALSE;
	}


	if($numrows == 1)
	{
		$row = $result->fetch_assoc();

		$result->free();

		if(!$row['actif'])
		{
			return FALSE;
		}

		$user_id = $row["killi_user_id"];

		$query = "SELECT token FROM " . DBSI_DATABASE . ".xmlrpc_session WHERE user_id = " . $user_id;
	
		if($hDB->db_select($query, $result, $numrows) != TRUE)
		{
			return FALSE;
		}


		if($numrows == 1)
		{
			$row = $result->fetch_assoc();
			$result->free();
			return $row["token"];
		}

		$result->free();
		$token = md5(uniqid());

		$query = "INSERT INTO " . DBSI_DATABASE . ".xmlrpc_session (user_id, token, time) VALUES (" . Security::secure($user_id) . ", '" . Security::secure($token) . "', CURRENT_TIMESTAMP())";

		if($hDB->db_execute($query, $affected_rows) !== TRUE)
		{
			return FALSE;
		}

		return $token;
	}

	$result->free();

	return FALSE;
}

$server = new SxmlrpcServer('XMLRPC SERVER');

$server->expose("xmlrpc_execute");
$server->expose("xmlrpc_login");

echo $server->handle();

unset($server);
exit();


