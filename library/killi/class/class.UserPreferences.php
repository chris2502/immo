<?php

/**
 *  @class UserPreferences
 *  @Revision $Revision: 4580 $
 *
 */

abstract class KilliUserPreferences
{
	public $table		= 'killi_user_preferences';
	public $database	 = RIGHTS_DATABASE;
	public $primary_key;
	//---------------------------------------------------------------------
	public function setDomain() {}
	//-------------------------------------------------------------------------
	public function __construct()
	{
		$this->primary_key = ORM::getObjectInstance('user')->primary_key;
		
		$this->{$this->primary_key} = ExtendsFieldDefinition::create('User');
		
		$this->ui_theme = EnumFieldDefinition::create()
				->setLabel('Thème de l\'UI')
				->setValues(array(
					''		 	=> 'Classique (gris)',
					'redmond'  	=> 'Redmond (bleu)',
					'humanity' 	=> 'Humanity (beige)',
					'blitzer'  	=> 'Blitzer (rouge)',
					'terminal' 	=> 'Terminal (noir et vert)',
					'carbon'   	=> 'Carbone (noir)',
					'pinky'		=> 'Pinky (rose)',
					'aristo'	=> 'Aristo (its design)',
					'halloween'	=> 'Halloween (scary killi)',
					'free'		=> 'Free (the one)'
				));
		
		$this->items_per_page = EnumFieldDefinition::create()
				->setLabel('Nombre d \'éléments par page')
				->setDefaultValue(200)
				->setRequired(TRUE)
				->addConstraint('Constraints::checkMinMaxInteger(20,250)')
				->setValues(array(
					'20' => '20',
					'50' => '50',
					'100' => '100',
					'150' => '150',
					'200' => '200'
				));
				
		if(isset($this->items_per_page->type[UI_THEME]))
		{
			$this->items_per_page->type[UI_THEME].=' (Par défaut)';
		}
		
		$this->unlocked_header = BoolFieldDefinition::create()
				->setLabel('En-tête flottant')
				->setDefaultValue(TRUE);
	}
}
