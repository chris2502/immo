<?php

/**
 *  Classes Decrivant un objet
 *
 *  @package killi
 *  @class ObjectDefinition
 *  @Revision $Revision: 4469 $
 */

abstract class ObjectDefinition
{
	public $table	   = NULL;
	public $description = 'My object';
	public $primary_key = NULL;
	public $database	= NULL;

	protected $_field_list = NULL;

	public function __construct()
	{
		$this->defineFields();
	}

	protected function defineField($field_name, $field_definition)
	{
		// Valeurs par défaut.
		// Pour un champ de type "primary key", la valeur de "required" est par défaut à TRUE.
		// Quand un champ est calculé par fonction, la valeur de "editable" est par défaut à FALSE.
		$required	  = (isset($field_definition['required']))?	$field_definition['required'] :	($field_definition['type'] == 'primary key');
		$default_value = (isset($field_definition['default']))?	 $field_definition['default'] :	 NULL;
		$a_constraints = (isset($field_definition['constraints']))? $field_definition['constraints'] : array();
		$domain		= (isset($field_definition['domain']))?	  $field_definition['domain'] :	  NULL;
		$editable	  = (isset($field_definition['editable']))?	$field_definition['editable'] :	!isset($field_definition['function']);
		$function	  = (isset($field_definition['function']))?	$field_definition['function'] :	NULL;
		$description   = (isset($field_definition['description']))? $field_definition['description'] : '';
		// Déclaration complète du FieldDefinition
		$this->$field_name = new FieldDefinition(
			$this,
			$default_value,
			$field_definition['name'],
			$field_definition['type'],
			$required,
			$a_constraints,
			$domain,
			$editable,
			$function,
			$description
		);

		return TRUE;
	}

	protected function defineFields()
	{
		// Get class namespace and base name.
		$nspath = Autoload::getClassNamespacePath(get_class($this));
		$cfname = Autoload::getClassFileName(get_class($this));
		// XML file
		$xml_filename = './object_definition'.$nspath.'/'.strtolower($cfname).'.xml';
		if ((is_null($this->_field_list) || !is_array($this->_field_list)) && file_exists($xml_filename))
		{
			$oDom = new DOMDocument();
			$oDom->load($xml_filename);
			$oXpath	= new DOMXPath($oDom);
			// Query all children from root "fields" tag.
			$oNodeList = $oXpath->query('//fields/*');
			$this->_field_list = array();
			foreach ($oNodeList as $oNode)
			{
				$field = array();
				// The tag name of each child is the name of the FieldDefinition variable.
				// Each attribute will be parsed as a parameter of FieldDefinition constructor.
				// Any stranger attribute will just be ignored : no warnings, no errors.
				foreach ($oNode->attributes as $oAttr)
				{
					$value = $oAttr->nodeValue;
					// True or False
					if ($value == '1' || $value == '0')
					{
						$value = ($value == '1');
					}
					// Each "array" attribute will be detected and parsed from JSON data,
					// since we want to handle associative arrays as well.
					// JSON data in XML files has to be single-quoted, a str_replace is done
					// afterwards to fix this.
					if (!empty($value) && $value[0] == '{' && $value[strlen($value)-1] == '}')
					{
						$value = json_decode(str_replace("'", '"', $value), TRUE);
					}
					$field[$oAttr->nodeName] = $value;
				}
				$this->_field_list[$oNode->nodeName] = $field;
			}
		}

		if (is_array($this->_field_list))
		{
			foreach ($this->_field_list as $field_name => $field_definition)
			{
				$this->defineField($field_name, $field_definition);
			}
		}
	}
}
