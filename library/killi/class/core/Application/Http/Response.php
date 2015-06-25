<?php

namespace Killi\Core\Application\Http;

/**
 * Classe contenant la sortie.
 *
 * @class  Response
 * @Revision $Revision: 4576 $
 *
 */

class Response
{
	/**
	 * Retourne vrai si le header a déjà été transmis.
	 */
	public function headerSent()
	{
		return headers_sent();
	}

	/**
	 * Envoi l'en-tête de réponse HTTP.
	 */
	public function sendHeaders()
	{
		if($this->headerSent())
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Envoi le contenu de la page.
	 *
	 */
	public function send()
	{

	}
}
