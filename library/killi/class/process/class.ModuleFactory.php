<?php

/**
 *  @class ModuleFactory
 *  @Revision $Revision: 3647 $
 *
 */

class ModuleFactory
{
	/**
	 * Éxécute et retourne le module en cours pour le token_id passé en paramètre.
	 * Fait avancer le process si la méthode checkNext l'autorise.
	 */
	public static function getModule($token_id)
	{
		do
		{
			$hORMToken  = ORM::getORMInstance('processtokendata');

			$token_data = array();
			$hORMToken->read($token_id, $token_data, NULL);

			$classname = $token_data['class_name']['value'];

			$module_instance = new $classname($token_id, $token_data);
			$module_instance->execute();
		}
		while ($module_instance->checkNext());

		return $module_instance;
	}
}
