<?php
	class PartieLocal{
		public $table		= 'partie_local';
		public $database	= 	RIGHTS_DATABASE;
		public $primary_key	= 'partie_local_id';
		public $reference	= 'partie';
	
		function __construct() {
			$this->partie_local_id=PrimaryFieldDefinition::create();
			$this->partie=TextFieldDefinition::create(11)
			->setLabel('Partie de local');
			$this->mise_en_vente_id=Many2oneFieldDefinition::create('MiseEnVente', 'mise_en_vente_id');
			
		}
	}
?>