<?php

/*
 * La classe stream permet de détecter l'écriture dans un stream (handler d'un fopen, d'un fsocketopen, etc)
 */
class DisplayStream
{
	/*
	 * Ouverture du stream, obligatoire
	 */
	function stream_open($path, $mode, $options, &$opened_path)
	{
		return true;
	}

	/*
	 * Lorsque quelque chose est écrit dans la stream
	 */
	public function stream_write($data)
	{
		// redirection par défaut (proxy)
		echo $data;

		// obligatoire !
		return strlen($data);
	}

	/*
	 * Lorsque des headers sont écrits dans le stream
	 */
	static function HandleHeaderLine( $curl, $header_line )
	{
		// uniquement ce qui décrit le contenu (Attention aux erreurs de protocol HTTP)
		if(preg_match('/^Content-(type|disposition):/i', $header_line))
		{
			// redirection par défaut (proxy)
			header($header_line);
		}

		// obligatoire !
		return strlen($header_line);
	}
}
