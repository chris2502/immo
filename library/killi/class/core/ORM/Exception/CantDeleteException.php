<?php

namespace Killi\Core\ORM\Exception;

/**
 *  Exception lorsque l'objet ne peut pas être supprimé (par exemple sur une contrainte de clé étrangère).
 *
 *  @package killi
 *  @exception CantDeleteException
 *  @Revision $Revision: 4435 $
 */

class CantDeleteException extends \Exception {}
