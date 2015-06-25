<?php

namespace Killi\Core\ORM;

/**
 *  Classe de chargement et d'initialisation des controleurs
 *
 *  @package killi
 *  @class ControllerManager
 *  @Revision $Revision: 4433 $
 */

use \Autoload;
use \Debug;

use \UndeclaredObjectException; // Utilisation de l'exception globale plutôt que celle défini dans le namespace en attendant la migration complète.

trait ControllerManager
{
	private static $_ctrl_instances = array();

	//---------------------------------------------------------------------
	/**
	* Retourne une instance du controleur d'un objet (objectMethod) en chargeant le
	* controleur à la demande.
	*
	* @param string $object_name Nom de l'objet dont on veut le controleur.
	* @throws Exception
	* @return Object objectMethod
	*/
	static function getControllerInstance($object_name)
	{
		if (is_object($object_name))
		{
			$object_name = get_class($object_name);
		}

		if(isset(self::$_ctrl_instances[$object_name]))
		{
			return self::$_ctrl_instances[$object_name];
		}

		Debug::log('Instanciate new controler '.$object_name. 'Method');

		$controller = $object_name . 'Method';

		if(!class_exists($controller))
		{
			if(Autoload::loadClass($controller) == FALSE)
			{
				throw new UndeclaredObjectException('Controller for object ' . $object_name . ' not found !');
			}
		}

		return self::$_ctrl_instances[$object_name] = new $controller;
	}
}
