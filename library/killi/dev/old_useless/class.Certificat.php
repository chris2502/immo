<?php

class Certificat
{
    //---Required
	public $description  = "Certificat Utilisateur";
    public $table        = 'killi_certificat';
    public $primary_key  = 'id_certificat';
	public $database     = RIGHTS_DATABASE;

    // application/x-x509-ca-cert
	function __construct()
	{
		$this->id_certificat = new FieldDefinition(
			$this,
			null,
			'Id certificat',
			'primary key',
			true,
			array()
		);

		$this->killi_user_id = new FieldDefinition(
			$this,
			null,
			'Id utilisateur',
			'many2one:User',
			true,
			array()
		);

		$this->duree = new FieldDefinition(
			$this,
			null,
			'Validité du certificat',
			'int',
			true,
			array()
		);
	}
}
