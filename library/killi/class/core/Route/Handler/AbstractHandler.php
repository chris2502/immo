<?php

namespace Killi\Core\Route\Handler;

/**
 *  Classe abstraite des handlers
 *
 *  @package killi
 *  @class AbstractHandler
 *  @Revision $Revision: 4527 $
 */

abstract class AbstractHandler
{
	public $object = NULL;
	public $method = NULL;
	public $request = NULL;

	protected function in() {}

	protected function dispatch() {}

	protected function out() {}
}
