<?php

namespace Killi\Core\Route;

/**
 *  Classe de routage des requêtes
 *
 *  @package killi
 *  @class RouteService
 *  @Revision $Revision: 4478 $
 */

class RouteService
{
	private static $_handler = NULL;
	
	public static function init ()
	{
		if(KILLI_SCRIPT)
		{
			self::$_handler = new Handler\CliHandler($this);
		}
		else
		{
			self::$_handler = new Handler\HttpHandler($this);
		}
	}
	
	public static function in ()
	{
		self::$_handler->in();
	}
	
	public static function dispatch()
	{
		self::$_handler->dispatch();
	}
	
	public static function out ()
	{
		self::$_handler->out();
	}
	
	public static function getObject()
	{
		return self::$_handler->object;
	}
	
	public static function getMethod()
	{
		return self::$_handler->method;
	}
	
	public static function getRequest()
	{
		$request = self::$_handler->request;
		
		if(!is_array($request))
		{
			$request = array();
		}
		
		return $request;
	}
	
	public static function getController()
	{
		return self::$_handler->object.'Method';
	}
}
