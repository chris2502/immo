<?php

namespace Killi\Core\ORM\Handler;

/**
 *  Classe abstraite des handlers
 *
 *  @package killi
 *  @class AbstractHandler
 *  @Revision $Revision: 4428 $
 */

abstract class AbstractHandler implements HandlerInterface
{
	protected $_orm_handler		= NULL;
	protected $_object_name		= NULL;
	protected $_object			= NULL;

	protected $_count_total		= FALSE;
	protected $_with_domain		= TRUE;

	public function __construct($ORMHandler)
	{
		$this->_orm_handler = $ORMHandler;
	}

	public function setObject($object)
	{
		$class_name				= get_class($object);
		$this->_object			= $object;
		$this->_object_name		= $class_name;

		return TRUE;
	}

	public function setWithDomain($with_domain = TRUE)
	{
		$this->_with_domain		= $with_domain;
		return TRUE;
	}

	public function setCountTotal($count_total = FALSE)
	{
		$this->_count_total		= $count_total;
		return TRUE;
	}

	abstract public function boot();
}
