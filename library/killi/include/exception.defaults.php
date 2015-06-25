<?php

/**
 *  Fichier de déclaration des Exceptions.
 *
 *  @Revision $Revision: 4536 $
 *
 */

// Exception native (sortie au format JSON)
class JSONException 				extends Exception {}

// Exception native (non bloquante, envoie de mail et log d'erreur sans interpution d'éxecution)
// A instancier sans faire de throw :
//
// new NonBlockingException("Erreur de déplacement de token de $from vers $to");
//
class NonBlockingException  		extends Exception
{
	public function __construct($e)
	{
		if($e instanceof Exception)
		{
			ExceptionManager::exceptionHandler($e);
		}
		else
		{
			$this->message=$e;

			ExceptionManager::exceptionHandler($this);
		}
	}
}

// Exception native (non critique, affichage d'un message d'erreur, envoie de mail et log, erreur bloquante)
class UserException 				extends Exception
{
	public $user_message = 'Votre requête n\'a pas pu être traitée (Erreur non gérée).';
}

// WORKFLOW
class CanNotBeFoundTokenException extends Exception {
	public $node_id;
	public $object_id;

	public function __construct($node_id, $object_id)
	{
		$this->node_id = $node_id;
		$this->object_id = $object_id;
		parent::__construct(sprintf("Impossible de trouver le token (node_id = %s, id = %s)", $node_id, $object_id));
	}
}

// PROFIL
class NoProfilException extends Exception{};

// ORM
class MismatchObjectException extends Exception
{
	public $mismatch_element;
}
class CantDeleteException extends Exception{};
class ObjectException extends Exception{};
class UndeclaredObjectException extends Exception{};
class InsertErrorException extends Exception{};
class JoinConflictException extends Exception{};
class ORMInternalError extends Exception {};

// COMMON
class NoReferenceAttributeException extends Exception{};
class NotImplementedException extends BadMethodCallException{};

// DBLAYER
class SQLException extends Exception {};
class SQLWarningException extends Exception {};
class SQLConnectionException extends Exception {};
class SQLOperationException extends Exception {};
class SQLDuplicateException extends SQLException
{
	public $user_message = 'La création/mise à jour des données viole une contrainte d\'unicité !';
}
class SQLCreateUpdateException extends SQLException
{
	public $user_message = 'Impossible de créer/modifier cet objet : d\'autres objets y font encore référence !';
}
class SQLDeleteUpdateException extends SQLException
{
	public $user_message = 'Impossible de supprimer/modifier cet objet : d\'autres objets y font encore référence !';
}
class SQLCustomException extends SQLException
{
	public $user_message = 'Interruption pour motif personnalisé.';

	public function __construct($custom_message)
	{
		$this->user_message = $custom_message;
	}
}

/**
 * Une exception à lancer en cas d'appel incorrecte à une fonction
 * __call, __get ou __set.
 *
 * @author boutillon
 *
 */
class OverloadException extends Exception
{
	const NOT_MEMBER = 'La fonction %s->%s() n\'est pas déclarée.';
	const NOT_PROPERTY = 'La propriété %s->%s n\'est pas déclarée.';
	const NO_ARGUMENT_EXCEPTION = 'Une [OverloadException] sans argument a été lancée par %s->%s';

	public function __construct($error = OverloadException::NO_ARGUMENT_EXCEPTION)
	{
		$trace =  debug_backtrace();
		$args = array(	get_class($trace[1]['object']),
						$trace[1]['args'][0]);
		$error = vsprintf($error, $args);
		parent::__construct($error);
	}
}

// CURL
class CurlException extends Exception
{
	public $curl;
}

// UI
class NoPropertyException extends Exception{};
class TemplateFileDoesntExistsException extends Exception{};
class XmlTemplateErrorException extends Exception{};


// Exceptions utilisateurs basiques
// $user_message est le message d'erreur affiché à l'utilisateur,
// le message d'erreur critique envoyé par mail est quant à lui celui renvoyé par l'Exception :
//
// throw new BadUrlException("template $template_name not found");
//
class BadUrlException 				extends UserException
{
	public $user_message = 'L\'adresse saisie n\'est pas correcte.';
}
class ObjectDoesNotExistsException 	extends UserException
{
	public $user_message = 'L\'objet cible n\'existe pas.';
}
class NoRightsException 			extends UserException
{
	public $user_message = 'Vous n\'êtes pas autorisé à consulter cet objet.';
}
class NoLinkRightsException 		extends UserException
{
	public $user_message = 'Vous n\'êtes pas autorisé à effectuer cette action.';
}
class NoTemplateException 			extends UserException
{
	public $user_message = 'Il n\'est pas prévu d\'afficher cet objet.';
}
class NativeJSONBrowsingException 	extends UserException
{
	public $user_message = 'Le flux de donnée n\'est pas accessible actuellement.';
}
class CannotDeleteException 		extends UserException
{
	public $user_message = 'Impossible de supprimer cet objet.';
}
class CannotInsertObjectException 	extends UserException
{
	public $user_message = 'Impossible de créer cet objet (doublon ou dépendance non satisfaite).';
}
//
