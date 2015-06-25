<?php

/**
 *  @class Rights
 *  @Revision $Revision: 4669 $
 *
 */
class Rights
{
	// cache
	private static $attribute_read = array ();
	private static $attribute_write = array ();
	private static $rights_list = array ();
	private static $lock_object = array ();

	/**
	 * Modifie un droit sur un attribut.
	 * N'enregistre rien en base.
	 *
	 * @param string $object_name Objet
	 * @param string $attribute_name Attribut
	 * @param boolean $read Lecture
	 * @param boolean $write Ecriture
	 * @param array $profil_id_list Profils
	 * @return boolean Succès
	 */
	public static function setAttributeRight($object_name, $attribute_name, $read = null, $write = null, array $profil_id_list = null)
	{
		if ($profil_id_list === NULL)
		{
			$profil_id_list = isset ( $_SESSION ['_USER'] ) ? $_SESSION ['_USER'] ['profil_id'] ['value'] : array ();
		}

		$uid = md5 ( strtolower ( $object_name ) . '+' . strtolower ( $attribute_name ) . '+' . implode ( ',', $profil_id_list ) );

		if ($read !== NULL)
		{
			self::$attribute_read [$uid] = $read;
		}

		if ($write !== NULL)
		{
			self::$attribute_write [$uid] = $write;
		}

		return TRUE;
	}
	//.....................................................................
	public static function rightIsEditable($object_name, $right)
	{
		$value = TRUE;

		if (isset ( self::$lock_object [strtolower ( $object_name )] ))
		{
			$value = ! isset ( self::$lock_object [strtolower ( $object_name )] [$right] );
		}

		return $value;
	}
	//.....................................................................
	public static function lockObjectRights($object_name, $right, $value)
	{
		self::$lock_object [strtolower ( $object_name )] [$right] = $value;

		return TRUE;
	}
	//.....................................................................
	public static function getCreateDeleteRightsFromProfilIDByObject(array $profil_id_list, &$rights_list)
	{
		$uid = md5 ( implode ( ',', $profil_id_list ) );

		// utilisation du cache
		if (isset ( self::$rights_list [$uid] ))
		{
			$rights_list = self::$rights_list [$uid];
			return TRUE;
		}

		$rights_list = array ();

		// valeurs par défaut sans profil
		if (empty ( $profil_id_list ))
		{
			foreach ( ORM::$_objects as $object_name => $object )
			{
				if ($object ['rights'] == true)
				{
					$rights_list [$object_name] ['create'] = FALSE;
					$rights_list [$object_name] ['delete'] = FALSE;
					$rights_list [$object_name] ['view'] = FALSE;
				}
			}

			// cache
			self::$rights_list [$uid] = $rights_list;

			return TRUE;
		}

		global $hDB;
		$hDB->db_select ( "select object_name, `create`, `delete`, `view`, profil_id from " . RIGHTS_DATABASE . ".killi_objects_rights where profil_id in (" . join ( ',', $profil_id_list ) . ")", $result );

		while ( $row = $result->fetch_assoc () )
		{
			// correction des enregistrements invalides
			if (READONLY_PROFIL_ID !== NULL && $row ['profil_id'] == READONLY_PROFIL_ID)
			{
				$row ['create'] = FALSE;
				$row ['delete'] = FALSE;
			}

			$object_name = strtolower ( $row ['object_name'] );

			// enregistrement invalide
			if (! array_key_exists ( $object_name, ORM::$_objects ))
			{
				continue;
			}

			// droits par défaut avant affectation
			if (! isset ( $rights_list [$object_name] ))
			{
				$rights_list [$object_name] ['create'] = FALSE;
				$rights_list [$object_name] ['delete'] = FALSE;
				$rights_list [$object_name] ['view'] = FALSE;
			}

			// affectation par bonus
			$rights_list [$object_name] ['create'] = ($row ['create'] == TRUE) || $rights_list [$object_name] ['create'] == TRUE;
			$rights_list [$object_name] ['delete'] = ($row ['delete'] == TRUE) || $rights_list [$object_name] ['delete'] == TRUE;
			$rights_list [$object_name] ['view'] = ($row ['view'] == TRUE) || $rights_list [$object_name] ['view'] == TRUE;
		}
		$result->free ();

		// droits par défaut (si aucun enregistrement en base)
		// le profil lecture seule voit tout
		foreach ( ORM::$_objects as $object_name => $object )
		{
			if (! isset ( $rights_list [$object_name] ) && $object ['rights'] == true)
			{
				$rights_list [$object_name] ['create'] = FALSE;
				$rights_list [$object_name] ['delete'] = FALSE;
				$rights_list [$object_name] ['view'] = (READONLY_PROFIL_ID !== NULL && in_array ( READONLY_PROFIL_ID, $profil_id_list ));
			}
		}

		// les admins voient tout
		foreach ( $rights_list as &$rights )
		{
			$rights ['view'] = in_array ( ADMIN_PROFIL_ID, $profil_id_list ) || $rights ['view'];
		}

		// lock
		foreach ( $rights_list as $object_name => &$rights )
		{
			if (isset ( self::$lock_object [$object_name] ))
			{
				$rights ['create'] = isset ( self::$lock_object [$object_name] ['create'] ) ? self::$lock_object [$object_name] ['create'] : $rights ['create'];
				$rights ['delete'] = isset ( self::$lock_object [$object_name] ['delete'] ) ? self::$lock_object [$object_name] ['delete'] : $rights ['delete'];
				$rights ['view'] = isset ( self::$lock_object [$object_name] ['view'] ) ? self::$lock_object [$object_name] ['view'] : $rights ['view'];
			}
		}

		// cache
		self::$rights_list [$uid] = $rights_list;
		
		return TRUE;
	}
	//.....................................................................
	public static function getRightsByAttribute($object_name, $attribute_name, &$read, &$write, array $profil_id_list = null)
	{
		if ($profil_id_list === null)
		{
			$profil_id_list = isset ( $_SESSION ['_USER'] ) ? $_SESSION ['_USER'] ['profil_id'] ['value'] : array ();
		}

		$object_name = strtolower ( $object_name );
		$asked_uid = md5 ( $object_name . '+' . strtolower ( $attribute_name ) . '+' . implode ( ',', $profil_id_list ) );

		// utilisation du cache
		if (isset ( self::$attribute_read [$asked_uid] ) && isset ( self::$attribute_write [$asked_uid] ))
		{
			$read = self::$attribute_read [$asked_uid];
			$write = self::$attribute_write [$asked_uid];

			return TRUE;
		}

		$hInstance = ORM::getObjectInstance ( $object_name );

		$attribute_name_list = array ();
		$lower_attribute_name_list = array ();
		foreach ( $hInstance as $attr_name => $attribute )
		{
			if ($attribute instanceof FieldDefinition)
			{
				$uid = md5 ( $object_name . '+' . strtolower ( $attr_name ) . '+' . implode ( ',', $profil_id_list ) );

				$attribute_name_list [$uid] = $attr_name;
				$lower_attribute_name_list [] = strtolower ( $attr_name );
			}
		}

		if (! array_key_exists ( $asked_uid, $attribute_name_list ))
		{
			throw new Exception ( 'Impossible de récuperer les droits de l\'attribut \'' . $attribute_name . '\' sur l\'objet \'' . $object_name . '\' : n\'est pas un FieldDefinition' );
		}

		// valeurs par défaut sans profil
		if (empty ( $profil_id_list ))
		{
			foreach ( $attribute_name_list as $uid => $attribute_name )
			{
				// cache
				if (! isset ( self::$attribute_read [$uid] ))
				{
					self::$attribute_read [$uid] = FALSE;
				}

				if (! isset ( self::$attribute_write [$uid] ))
				{
					self::$attribute_write [$uid] = FALSE;
				}
			}

			$read = self::$attribute_read [$uid];
			$write = self::$attribute_write [$uid];

			return TRUE;
		}

		// les admins lisent et modifient tout
		if (in_array ( ADMIN_PROFIL_ID, $profil_id_list ))
		{
			foreach ( $attribute_name_list as $uid => $attribute_name )
			{
				if (! isset ( self::$attribute_read [$uid] ))
				{
					self::$attribute_read [$uid] = TRUE;
				}

				if (! isset ( self::$attribute_write [$uid] ))
				{
					self::$attribute_write [$uid] = TRUE;
				}
			}
		}
		else
		{
			// pas admin, donc calcul des droits


			global $hDB;
			$hDB->db_select ( "select `object_name`, `attribute_name`, `read`, `write` from " . RIGHTS_DATABASE . ".killi_attributes_rights where object_name=\"" . Security::secure ( $object_name ) . "\" and profil_id in (" . join ( ',', $profil_id_list ) . ")", $result );

			while ( $row = $result->fetch_assoc () )
			{
				// enregistrement invalide
				if (! in_array ( strtolower ( $row ['attribute_name'] ), $lower_attribute_name_list ))
				{
					continue;
				}

				$uid = md5 ( strtolower ( $row ['object_name'] ) . '+' . strtolower ( $row ['attribute_name'] ) . '+' . implode ( ',', $profil_id_list ) );

				// droits par défaut avant affectation
				if (! isset ( self::$attribute_read [$uid] ))
				{
					self::$attribute_read [$uid] = FALSE;
					self::$attribute_write [$uid] = FALSE;
				}

				// affectation par bonus
				self::$attribute_read [$uid] = ($row ['read'] == TRUE || self::$attribute_read [$uid] == TRUE);
				self::$attribute_write [$uid] = ($row ['write'] == TRUE || self::$attribute_write [$uid] == TRUE);
			}
			$result->free ();

			// droits par défaut (si aucun enregistrement en base)
			foreach ( $attribute_name_list as $uid => $attribute_name )
			{
				if (! isset ( self::$attribute_read [$uid] ))
				{
					self::$attribute_read [$uid] = TRUE;
				}

				if (! isset ( self::$attribute_write [$uid] ))
				{
					self::$attribute_write [$uid] = FALSE;
				}
			}
		}

		// override
		foreach ( $attribute_name_list as $uid => &$attribute_name )
		{
			$attribute = $hInstance->$attribute_name;

			// override possible
			$attribute->setEditable ( $attribute->editable );

			if (! $attribute->editable)
			{
				self::$attribute_write [$uid] = FALSE;
			}
		}

		$read = self::$attribute_read [$asked_uid];
		$write = self::$attribute_write [$asked_uid];

		return TRUE;
	}
	//.....................................................................
	static public function getCreateDeleteViewStatus($object, &$create, &$delete, &$view)
	{
		$object = strtolower ( $object );
		$profil_id_list = isset ( $_SESSION ['_USER'] ['profil_id'] ['value'] ) ? $_SESSION ['_USER'] ['profil_id'] ['value'] : array ();

		self::getCreateDeleteRightsFromProfilIDByObject ( $profil_id_list, $rights_list );

		// l'objet n'existe pas
		// ou la gestion des droits est désactivée pour l'objet et il n'y a pas d'enregistrement en base, on donne les droits de lecture par défaut
		if (! isset ( $rights_list [$object] ))
		{
			$create = false;
			$delete = false;
			$view = ((READONLY_PROFIL_ID !== NULL && in_array ( READONLY_PROFIL_ID, $profil_id_list )) || in_array ( ADMIN_PROFIL_ID, $profil_id_list ));

			// lock
			if (isset ( self::$lock_object [$object] ))
			{
				$create = isset ( self::$lock_object [$object] ['create'] ) ? self::$lock_object [$object] ['create'] : $create;
				$delete = isset ( self::$lock_object [$object] ['delete'] ) ? self::$lock_object [$object] ['delete'] : $delete;
				$view = isset ( self::$lock_object [$object] ['view'] ) ? self::$lock_object [$object] ['view'] : $view;
			}
			
			return TRUE;
		}

		$create = $rights_list [$object] ['create'];
		$delete = $rights_list [$object] ['delete'];
		$view = $rights_list [$object] ['view'];

		return TRUE;
	}
	//.....................................................................
	public static function clearCache()
	{
		self::$attribute_read = array ();
		self::$attribute_write = array ();
		self::$rights_list = array ();
	}
	//.....................................................................
	public static function clearLock()
	{
		self::$lock_object = array ();
	}
}
