<?php
	function objectIDCorresponding()
	{
		//---Si action = adresse ---> on traite primary key
		if (isset($_GET['action']))
		{
			$raw = explode('.',$_GET['action']);
			$object_action = $raw[0];
		} 
		
		//---Process PK if needed
		if (isset($object_action))
		{
			if ($object_action=='adresse')
			{
				if (isset($_GET['primary_key']))
				{
					$hInstance = new AdresseMethod;
					$hInstance->processingRenvoiAddress($_GET['primary_key'], $id_to);
					
					Security::crypt($id_to, $crypt_id_to);
					$_GET['primary_key']       = $id_to;
					$_GET['crypt/primary_key'] = $crypt_id_to;
				}
			}
		}

		return TRUE;
	}
