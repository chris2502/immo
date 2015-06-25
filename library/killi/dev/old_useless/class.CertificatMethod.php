<?php

class CertificatMethod extends Common
{
	//.....................................................................
	public function getReferenceString(array $id_list, array &$reference_list)
	{
		foreach ($id_list as $key)
		{
			$reference_list[$key] = $key;
		}

		return TRUE;
	}
}
