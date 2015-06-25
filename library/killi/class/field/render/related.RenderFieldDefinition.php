<?php

/**
 *  @class RelatedRenderFieldDefinition
 *  @Revision $Revision: 4469 $
 *
 */

class RelatedRenderFieldDefinition extends RenderFieldDefinition
{
	protected $_render = NULL;

	public function __construct(XMLNode $node, FieldDefinition $field)
	{
		parent::__construct($node, $field);

		//---On recup le type de l'attribut
		$hInstance = ORM::getObjectInstance($field->objectName);

		//---On instancie l'objet de la relation
		$raw_type = $field->object_relation;
		$raw_type2 = $field->related_relation;
		$hRelatedInstance = ORM::getObjectInstance($hInstance->$raw_type->object_relation);

		$new_field = $hRelatedInstance->$raw_type2;

		/* Appel du renderFieldDefinition approprié. */
		$render_name		= $new_field->render . 'RenderFieldDefinition';

		if(!Autoload::loadClass($render_name))
		{
			throw new Exception('Pas de render pour de type : ' . $render_name );
		}

		$this->_render = new $render_name($node, $new_field);
	}

	public function renderFilter($name, $selected_value)
	{
		return $this->_render->renderFilter($name, $selected_value);
	}

	public function renderValue($value, $input_name, $field_attributes)
	{
		return $this->_render->renderValue($value, $input_name, $field_attributes);
	}

	public function renderInput($value, $input_name, $field_attributes)
	{
		return $this->_render->renderInput($value, $input_name, $field_attributes);
	}
}
