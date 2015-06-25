<?php

/**
 *  @class Alert
 *  @Revision $Revision: 4227 $
 *
 *  Cette classe permet l'affichage des messages à l'utilisateur.
 */

class Alert
{
	/**
	 *  Défini un message d'information à montrer à l'utilisateur.
	 */
	public static function info($title, $message, $target = 'global')
	{
		$key = md5($message);
		Debug::info($title . ' : ' . $message);
		$_SESSION['_ALERT']['info'][$target][$title][$key] = $message;
	}

	/**
	 *  Défini un message d'erreur à montrer à l'utilisateur.
	 */
	public static function error($title, $message, $target = 'global')
	{
		$key = md5($message);
		Debug::error($title . ' : ' . $message);
		$_SESSION['_ALERT']['error'][$target][$title][$key] = $message;
	}

	/**
	 *  Défini un message d'attention à montrer à l'utilisateur.
	 */
	public static function warning($title, $message, $target = 'global')
	{
		$key = md5($message);
		Debug::warn($title . ' : ' . $message);
		$_SESSION['_ALERT']['warning'][$target][$title][$key] = $message;
	}

	/**
	 *  Défini un message de réussite à montrer à l'utilisateur.
	 */
	public static function success($title, $message, $target = 'global')
	{
		$key = md5($message);
		Debug::log($title . ' : ' . $message);
		$_SESSION['_ALERT']['success'][$target][$title][$key] = $message;
	}

	/**
	 *  Retourne vrai s'il y a des erreurs déclarés dans les alertes.
	 */
	public static function containsErrors()
	{
		return isset($_SESSION['_ALERT']['error']) && count($_SESSION['_ALERT']['error']) > 0;
	}

	/**
	 *  Retourne vrai s'il y a des succès déclarés dans les alertes.
	 */
	public static function containsSuccess()
	{
		return isset($_SESSION['_ALERT']['success']) && count($_SESSION['_ALERT']['success']) > 0;
	}
}
