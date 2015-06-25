<?php

/**
 *  Index:
 *  Ce fichier est le point d'entrée du framework pour l'interface web.
 *
 *  @Revision $Revision: 4602 $
 *
 */

//---Profiler : Need to be at top of execution.
define('PROFILER', FALSE);
if(PROFILER && function_exists('xhprof_enable'))
{
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}

// TODO: Dégager ça dès que $dbconfig
session_name(md5($_SERVER['PHP_SELF']));
@session_start();

define('KILLI_DIR', './library/killi/');
require(KILLI_DIR . '/include/include.php');

$hDB = new DbLayer($dbconfig);
$hDB->db_start();

// ---- Nouveau système de chargement d'une appli Killi
Debug::info('Démarrage du framework', 'Killi');

$app = new \Killi\Core\Application\Application(__DIR__);

/**
 * Initialisation de l'application.
 * Chargements :
 *   - Exception
 *   - Configuration
 *   - Logging
 */
$app->runBootstraps(array(
	'\Killi\Core\Application\Bootstrap\HandleExceptions'
));

/**
 * Définition des middlewares.
 */
$app->setMiddlewares(array(
	'\Killi\Core\Application\Middleware\SessionMiddleware',			// Chargement de la session
	'\Killi\Core\Application\Middleware\DebugTraceMiddleware',		// Affichage des traces et debug
	'\Killi\Core\Application\Middleware\JsonRequestMiddleware',		// Traitement des requêtes Json
	'\Killi\Core\Application\Middleware\OAuthMiddleware',			// Protocole d'authentification OAuth
	'\Killi\Core\Application\Middleware\AuthMiddleware',			// Authentification standard
	'\Killi\Core\Application\Middleware\UserPreferencesMiddleware',	// Chargement des préférences utilisateurs
	'\Killi\Core\Application\Middleware\ObjectRightsMiddleware',	// Chargement des droits.
));

/**
 * Déclenchement de l'application
 * Actions :
 *   - Request
 *   - Session
 *   - Authentification
 *   - Routage
 *   - Exécution
 *   - ...
 *
 */
$response = $app->start(
	$request = \Killi\Core\Application\Http\Request::capture()
);

$response->send();

$app->stop($request, $response);

///
/// ---- Fin de l'exécution d'une appli Killi.
///
