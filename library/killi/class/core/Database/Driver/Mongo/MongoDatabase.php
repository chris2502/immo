<?php

namespace Killi\Core\Database\Driver\Mongo;

/**
 *  Classe de gestion d'une base de données MongoDB.
 *
 *  @package killi
 *  @class MongoDatabase
 *  @Revision $Revision: 4432 $
 */

use Killi\Core\Database\Driver\Mongo\MongoDbLayer;

class MongoDatabase
{
	protected static $_databases = NULL;
	protected $_db = NULL;
	protected $_db_name = '';

	protected function __construct($database)
	{
		$this->_db_name = $database;
		$this->_db = MongoDBLayer::getInstance()->getDatabase($database);
	}

	public static function getInstance($database)
	{
		if(!isset(self::$_databases[$database]))
		{
			self::$_databases[$database] = new MongoDatabase($database);
		}

		return self::$_databases[$database];
	}

	public function getCollection($collection)
	{
		return $this->_db->$collection;
	}

	public function getStats()
	{
		$db = $this->_db->command(array('dbStats' => 1));
		if($db['ok'] != '1')
		{
			throw new Exception('Unable to retrieve stats from collection ' . $this->_db_name);
		}
		return $db;
	}
}
