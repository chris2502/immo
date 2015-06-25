<?php

namespace Killi\Core\ORM\Exception;

/**
 *  Exception lorsque l'objet ne peut pas être insérés (par exemple : champs obligatoire non saisis ou encore contrainte de clé étrangères).
 *
 *  @package killi
 *  @exception InsertErrorException
 *  @Revision $Revision: 4435 $
 */

class InsertErrorException extends \Exception {}
