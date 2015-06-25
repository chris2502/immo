<?php

/**
 *  Classe de définition d'un attribut sur un objet.
 *
 *  @package killi
 *  @class FieldDefinition
 *  @Revision $Revision: 4618 $
 *
 */

class AttributeNotFoundException extends Exception
{
	public function __construct($object, $attribute)
	{
		parent::__construct('Attribut "' . $attribute . '" non trouvé dans l\'objet "' . $object . '"');
	}
}

abstract class FieldDefinition
{
	//public $attribute_name = NULL; // Défini par l'ORM lors du chargement de l'objet.
	public $objectName;
	public $default_value = NULL;
	public $required = FALSE;
	public $constraint_list = array();
	public $constraint_error = array();
	public $domain = array();
	public $editable = TRUE;

	public $is_db_column = TRUE;	// Est un champ dans la table de l'objet
	public $is_virtual	= FALSE;	// Le champ n'est ni en base, ni calculé par l'ORM.

	public $function = NULL;		// Le champ est calculé par PHP
	public $sql_alias = NULL;		// Le champ est calculé par MySQL

	public $description = '';
	public $object_relation=NULL;
	public $related_relation=NULL;
	public $pk_relation=NULL;
	public $read  = FALSE;
	public $write = FALSE;
	public $extract_csv = TRUE;
	public $search_disabled = FALSE;
	public $dependencies = array();
	public $no_reference = FALSE;
	public $auto_remote_link = FALSE;

	//.....................................................................
	/**
	 * Retourne l'instance de l'attribut fondamental
	 *
	 * @param object $object_instance Instance de l'objet qui contient le FieldDefinition
	 * @param string $attribute_name Nom de l'attribut
	 * @return FieldDefinition
	 */
	public function getFondamental(&$object_instance = NULL, &$attribute_name= NULL)
	{
		$fondamental = $this;

		while($fondamental->type == 'related')
		{
			$object_relation = $fondamental->object_relation;
			$related_relation = $fondamental->related_relation;

			$object = ORM::getObjectInstance($fondamental->objectName);
			if(!isset($object->$object_relation))
			{
				throw new AttributeNotFoundException($fondamental->objectName, $object_relation);
			}
			$fondamental = $object->$object_relation;

			if($fondamental->type != 'related')
			{
				$object = ORM::getObjectInstance($fondamental->object_relation);
				if(!isset($object->$related_relation))
				{
					throw new AttributeNotFoundException($fondamental->object_relation, $related_relation);
				}
				$fondamental = $object->$related_relation;
			}
		}

		$object_instance = ORM::getObjectInstance($fondamental->objectName);
		$attribute_name = $fondamental->attribute_name;

		return $fondamental;
	}
	//.....................................................................
	public function secureSet($value)
	{
		if (!is_array($value))
		{
			$value = trim($value);
		}

		//---Check required
		if ($this->required == TRUE && ($value === '' || $value === NULL))
		{
			$this->constraint_error[] = "Le champ est requis !";
			return FALSE;
		}

		//---Check empty
		if ($value === '' || $value === NULL)
		{
			return TRUE;
		}

		//---Check if data is UTF-8
		if (!is_array($value))
		{
			$encoding = mb_detect_encoding($value);
			if (($encoding!="ASCII") && ($encoding!="UTF-8"))
			{
				$this->constraint_error[] = "Le champ est mal encodé !";
				return FALSE;
			}
		}

		if($this->secure($value) === FALSE)
		{
			return FALSE;
		}

		//---Check constraints
		$constraint_err = 0;
		foreach($this->constraint_list as $constraint)
		{
			//---Decoupe Constraints::checkSize(3,16)
			$raw = explode('::',$constraint);
			if(count($raw) < 2)
			{
				throw new Exception("Erreur lors du parsing de la contrainte ".$constraint);
			}
			$class = $raw[0];

			preg_match_all("/^([a-zA-Z]+)\((.*)\)$/", $raw[1], $raw2);
			if(array_key_exists(0, $raw2[1]))
			{
				$params = array();
				$params[] = $value;

				foreach(explode(',',$raw2[2][0]) as $param)
				{
					if ($param!=NULL)
						$params[] = $param;
				}

				$constraint_error = NULL;
				$params[] = &$this->constraint_error;

				if (call_user_func_array(array($raw[0], $raw2[1][0]),$params)!=True)
					$constraint_err ++;
			}
			else
			{
				throw new Exception("Impossible de parser la contrainte ".$constraint);
			}
		}

		if($constraint_err)
			return FALSE;

		//---All test OK
		$this->value = $value;

		return True;
	}

	public function isDbColumn()
	{
		return $this->is_db_column == TRUE && !$this->isVirtual() && !$this->isFunction() && !$this->isSQLAlias();
	}

	public function isVirtual()
	{
		return $this->is_virtual == TRUE;
	}

	public function isFunction()
	{
		return $this->function !== NULL;
	}

	public function isSQLAlias()
	{
		return $this->sql_alias != NULL;
	}
}
