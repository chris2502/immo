<?php

/**
 *
 *  @class ExtendedFieldDefinition
 *  @Revision $Revision: 4589 $
 *
 */

abstract class ExtendedFieldDefinition extends FieldDefinition
{
	/**
	 * Constructeur
	 *
	 * @return ExtendedFieldDefinition
	 */
	function __construct()
	{
		return $this;
	}
	//.....................................................................
	/**
	 * Retourne le label du champs
	 * Si le champs est un related sans label, on récupère le label du parent
	 *
	 * @return string
	 */
	public function getRelatedName()
	{
		if($this->objectName == NULL)
		{
			$message = 'Pas d\'objectName pour cet objet pour un attribut de type : ' . get_called_class() . '. ';
			$message .= print_r($this, TRUE);
			throw new Exception($message);
		}

		$object_attribute = $this->attribute_name;
		$hInstance = ORM::getObjectInstance ( $this->objectName );

		while ( ! isset ( $hInstance->$object_attribute->name ) || $hInstance->$object_attribute->name == NULL )
		{
			if ($hInstance->$object_attribute->type == 'primary key')
			{
				return $hInstance->$object_attribute->objectName;
			}

			if ($hInstance->$object_attribute->type != 'related')
			{
				return $hInstance->$object_attribute->attribute_name;
			}

			if(empty($hInstance->$object_attribute->object_relation))
			{
				throw new Exception(sprintf("pas de relation sur objet %s attributs : %s ", $this->objectName, $object_attribute));
			}

			$m2o_attr = $hInstance->$object_attribute->object_relation;
			$object_attribute = $hInstance->$object_attribute->related_relation;


			$hInstance = ORM::getObjectInstance ( $hInstance->$m2o_attr->object_relation );
		}

		return $hInstance->$object_attribute->name;
	}
	//.....................................................................
	/**
	 * Méthode magique pour accéder aux attributs privés ou dynamiques
	 *
	 * @param string $name
	 * @throws Exception
	 * @return string
	 */
	public function __get($name)
	{
		if ($name == 'name')
		{
			return $this->getRelatedName ();
		}

		if ($name == 'type' || $name == 'render')
		{
			return strtolower(str_replace('FieldDefinition', '', get_called_class()));
		}
		
		// retrocompatibilité
		if ($name == 'type_relation')
		{
			return $this->type;
		}

		throw new Exception ( "Unkown property $name in ".var_export($this,true) );
	}
	//.....................................................................
	/**
	 * Retourne la valeur à afficher dans les extracts CSV
	 *
	 * @return string
	 */
	 public function extract($value)
	 {
	 	if (isset ( $value ['reference'] ) && $value ['value']  != NULL)
	 	{
	 		if (is_array ( $value ['reference'] ))
	 		{
	 			return join ( ',', $value ['reference'] );
	 		}
	 		else
	 		{
	 			return $value ['reference'];
	 		}
	 	}
	 	else if (is_array ( $value ['value'] ))
	 	{
	 		return join ( ',', $value ['value'] );
	 	}
	 	else
	 	{
	 		return $value ['value'];
	 	}
	 }
	//.....................................................................
	/**
	 * Définis le label du champs (affiché sur les vues UI et dans les CSV)
	 *
	 * @param string $label
	 * @return ExtendedFieldDefinition
	 */
	public function setLabel($label)
	{
		$this->name = $label;

		return $this;
	}
	//.....................................................................
	/**
	 * Précise si le champs est requis ou non
	 *
	 * @param boolean $required
	 * @return ExtendedFieldDefinition
	 */
	public function setRequired($required = TRUE)
	{
		$this->required = ($required === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Définis la valeur par défaut du champs
	 *
	 * @param mixed $defaul_value
	 * @return ExtendedFieldDefinition
	 */
	public function setDefaultValue($defaul_value)
	{
		$this->default_value = $defaul_value;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis la valeur par défaut du champs
	 *
	 * @param mixed $defaul_value
	 * @return ExtendedFieldDefinition
	 */
	public function setTooltip($tooltip)
	{
		$this->description = $tooltip;

		return $this;
	}
	//.....................................................................
	/**
	 * Précise si le champs peut être édité en vue formulaire ou création et à distance via NJB
	 *
	 * @param boolean $editable
	 * @return ExtendedFieldDefinition
	 */
	public function setEditable($editable = TRUE)
	{
		$this->editable = ($editable === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Définis l'objet cible (many2one, many2many, one2one, one2many, extend, etc)
	 *
	 * @param string $object_relation
	 * @return ExtendedFieldDefinition
	 */
	public function setObjectRelation($object_relation)
	{
		$this->object_relation = strtolower($object_relation);

		return $this;
	}
	//.....................................................................
	/**
	 * Définis le champs utilisé pour les relations de type related
	 *
	 * @param string $object_relation
	 * @return ExtendedFieldDefinition
	 */
	public function setFieldRelation($field_relation)
	{
		$this->related_relation = $field_relation;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis le champs distant utilisé pour les relations de type extends
	 *
	 * @param string $pk_relation
	 * @return ExtendedFieldDefinition
	 */
	public function setFocusedRelation($pk_relation)
	{
		$this->pk_relation = $pk_relation;

		return $this;
	}
	//.....................................................................
	/**
	 * Indique le workflow dans lequel récupérer le statut de l'objet
	 *
	 * @param string $workflow
	 * @return ExtendedFieldDefinition
	 */
	public function setWorkflow($workflow)
	{
		$this->object_relation = $workflow;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis l'expression SQL pour le champs calculé
	 *
	 * @param string $sql_function
	 * @return ExtendedFieldDefinition
	 */
	public function setSQLAlias($sql_function)
	{
		$this->sql_alias = $sql_function;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis le controleur et la méthode pour le champs calculé
	 *
	 * @param string $object
	 * @param string $function
	 * @return ExtendedFieldDefinition
	 */
	public function setFunction($object, $function)
	{
		$this->function = $object . '::' . $function;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis le champs comme étant virtuel (peuplé et enregistré manuelement)
	 *
	 * @param boolean $virtual
	 * @return ExtendedFieldDefinition
	 */
	public function setVirtual($virtual = TRUE)
	{
		$this->is_virtual = ($virtual === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Définis les domaines à appliquer sur le champs (Ecrase les anciennes valeurs)
	 *
	 * @param array $domain
	 * @return ExtendedFieldDefinition
	 */
	public function setDomain(array $domain)
	{
		$this->domain = $domain;

		return $this;
	}
	//.....................................................................
	/**
	 * Ajoute un domaine sur le champs
	 *
	 * @param array $domain
	 * @return ExtendedFieldDefinition
	 */
	public function addDomain(array $domain)
	{
		if (! is_array ( $this->domain ))
		{
			$this->setDomain ( array () );
		}

		$this->domain [] = $domain;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis les dépendances du champs (Ecrase les anciennes valeurs)
	 *
	 * @param array $domain
	 * @return ExtendedFieldDefinition
	 */
	public function setDependencies(array $dependencies)
	{
		$this->dependencies = $dependencies;

		return $this;
	}
	//.....................................................................
	/**
	 * Ajoute une dépendances du champs
	 *
	 * @param string $domain
	 * @return ExtendedFieldDefinition
	 */
	public function addDependencies($dependencies)
	{
		if (! is_array ( $this->dependencies ))
		{
			$this->setDependencies ( array () );
		}

		$this->dependencies [] = $dependencies;

		return $this;
	}
	//.....................................................................
	/**
	 * Définis les contraintes à appliquer sur le champs (Ecrase les anciennes valeurs)
	 *
	 * @param array $constraint
	 * @return ExtendedFieldDefinition
	 */
	public function setConstraint(array $constraint)
	{
		$this->constraint_list = $constraint;

		return $this;
	}
	//.....................................................................
	/**
	 * Ajoute une contrainte sur le champs
	 *
	 * @param string $constraint
	 * @return ExtendedFieldDefinition
	 */
	public function addConstraint($constraint)
	{
		if (! is_array ( $this->constraint_list ))
		{
			$this->setConstraint ( array () );
		}

		$this->constraint_list [] = $constraint;

		return $this;
	}
	//.....................................................................
	/**
	 * Précise si le champs doit etre intégré aux exports CSV
	 *
	 * @param boolean $extract
	 * @return ExtendedFieldDefinition
	 */
	public function setExtractCSV($extract = TRUE)
	{
		$this->extract_csv = ($extract === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 *
	 * @return ExtendedFieldDefinition
	 */
	public static function create()
	{
		$class_name = get_called_class ();

		return new $class_name ();
	}
	//.....................................................................
	/**
	 * Cast la valeur avant utilisation dans l'ORM
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function inCast(&$value)
	{
		return TRUE;
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		$value = array (
			'value' => $value
		);

		return TRUE;
	}
	//.....................................................................
	/**
	 * Vérifie la valeur envoyée par l'utilisateur
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function secure($value)
	{
		return TRUE;
	}
	//.....................................................................
	/**
	 * Précise si l'ORM doit éviter de récuperer automatiquement les réferences.
	 *
	 * @param boolean $no_reference
	 * @return ExtendedFieldDefinition
	 */
	public function setNoReference($no_reference = TRUE)
	{
		$this->no_reference = ($no_reference === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Précise si UI doit automatiquement généré en liens vers l'objet distant.
	 *
	 * Uniquement pour les Many2one.
	 *
	 * @param boolean $auto_remote_link
	 * @return ExtendedFieldDefinition
	 */
	public function setAutoRemoteLink($auto_remote_link = TRUE)
	{
		$this->auto_remote_link = ($auto_remote_link === TRUE);

		return $this;
	}
	//.....................................................................
	/**
	 * Indique le renderFieldDefinition à utiliser
	 *
	 * @param string $render
	 * @return ExtendedFieldDefinition
	 */
	public function setRender($render)
	{
		$this->render = $render;

		return $this;
	}
	//.....................................................................
	/**
	 * Précise s'il est possible d'effectuer une recherche sur ce champ
	 *
	 * @param boolean $search_disabled
	 * @return ExtendedFieldDefinition
	 */
	public function disableSearch($search_disabled = TRUE)
	{
		$this->search_disabled = ($search_disabled === TRUE);

		return $this;
	}
}
