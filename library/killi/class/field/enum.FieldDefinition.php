<?php

/**
 *  @class EnumFieldDefinition
 *  @Revision $Revision: 4561 $
 *
 */

class EnumFieldDefinition extends ExtendedFieldDefinition
{
	public $render = 'enum';
	//.....................................................................
	public function __construct($values = NULL)
	{
		if ($values === NULL)
		{
			$values = array ();
		}

		$this->setValues ( $values );
	}
	//.....................................................................
	/**
	 * Création du field en mode chainé
	 * @return EnumFieldDefinition
	 */
	public static function create($values = NULL)
	{
		$class_name = get_called_class ();

		return new $class_name ( $values );
	}
	//.....................................................................
	public function setValues(array $values)
	{
		$this->type = $values;

		return $this;
	}
	//.....................................................................
	/**
	 * Cast la valeur après lecture par l'ORM
	 * @param mixed $value
	 * @return boolean
	 */
	public function outCast(&$value)
	{
		if ($value === NULL)
		{
			$value = array (
				'value' => NULL
			);

			return TRUE;
		}

		if(!isset($this->type [$value]))
		{
			throw new Exception('Value "' . $value . '" doesn\'t exists in the enumeration !');
		}

		$value = array (
			'value' => $value,
			'reference' => $this->type [$value]
		);
		return TRUE;
	}
}
