<?php

/**
 *  @class ProcessModule
 *  @Revision $Revision: 3647 $
 *
 */

abstract class KilliProcessModule
{
	public $table		= 'killi_process_module';
	public $primary_key = 'module_id';
	public $database	= RIGHTS_DATABASE;
	public $reference	= 'name';
	//-------------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->module_id = new PrimaryFieldDefinition();

		$this->process_id	= Many2OneFieldDefinition::create('Process')
				->setLabel('Process')
				->setRequired(TRUE);

		$this->class_id = Many2oneFieldDefinition::create('typemodule')
				->setLabel('Classe')
				->setRequired(TRUE);

		$this->name = TextFieldDefinition::create(32)
				->setRequired(TRUE)
				->setLabel('Nom');

		$this->data = JsonFieldDefinition::create()
				->setLabel('Data');

		$this->x = IntFieldDefinition::create()
				->setLabel('X');

		$this->y = IntFieldDefinition::create()
				->setLabel('Y');

		$this->delai = IntFieldDefinition::create()
				->setLabel('Délai (en heures)');

		$this->visibility_user = BoolFieldDefinition::create()
				->setDefaultValue(0)
				->setLabel('Visibilité à l\'utilisateur');

		$this->visibility_profile = BoolFieldDefinition::create()
				->setDefaultValue(0)
				->setLabel('Visibilité au profil');

		$this->visibility_company = BoolFieldDefinition::create()
				->setDefaultValue(0)
				->setLabel('Visibilité à l\'entreprise');

		$this->class_name = RelatedFieldDefinition::create('class_id')
				->setLabel('Class')
				->setFieldRelation('class_name');

		$this->process_name = RelatedFieldDefinition::create('process_id')
				->setLabel('Nom du process')
				->setFieldRelation('name');

		$this->process_internal_name = RelatedFieldDefinition::create('process_id')
				->setLabel('Nom interne du process')
				->setFieldRelation('internal_name');
	}
}
