<?php

namespace Killi\Core\ORM\Exception;

/**
 *  Exception lorsque la requête de recherche nécessite d'effectuer des jointures qui rentrent en conflit avec d'autres jointures.
 *
 *  @package killi
 *  @exception JoinConflictException
 *  @Revision $Revision: 4435 $
 */

class JoinConflictException extends \Exception {}
