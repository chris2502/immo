<?php

namespace Killi\Core\Database\Driver\Mongo;

/**
 *  Classe de connection à un serveur MongoDB.
 *
 *  @package killi
 *  @class MongoDBLayer
 *  @Revision $Revision: 4432 $
 */

use \MongoClient;

class MongoDBLayer
{
	protected static $_instances = NULL;
	protected $_db = NULL;

	protected function __construct()
	{
		if(!defined('MONGODB'))
		{
			throw new Exception('MONGODB not defined !');
		}

		$instance = new MongoClient(MONGODB); // connexion
		$instance->setReadPreference(MongoClient::RP_NEAREST, array());
		$this->_db = $instance;
	}

	public static function getInstance()
	{
		if(self::$_instances === NULL)
		{
			self::$_instances = new MongoDBLayer();
		}
		return self::$_instances;
	}

	public function getDatabase($database_name)
	{
		return $this->_db->$database_name;
	}
}
