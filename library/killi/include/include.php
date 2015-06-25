<?php

/**
 *  Fichier d'inclusion et de déclaration à l'autoload.
 *
 *  @Revision $Revision: 4581 $
 *
 */

if(!version_compare ( phpversion (), '5.5.0', '>=' ))
{
	die('PHP 5.5 minimum requis.');
}

require(KILLI_DIR . '/vendor/autoload.php');

//---Process duration and memory counter
$start_time = microtime(TRUE);
$start_memory = memory_get_usage();

/**
 * Inclusion de la configuration
 */
if (file_exists('./include/config.php'))
{
	include('./include/config.php');
}

/**
 * Configuration par défaut
 */
require(KILLI_DIR . '/include/config.defaults.php');

/**
 * Gestionnaire d'Exception et exception par défaut et applicatives
 */
require(KILLI_DIR . '/class/core/class.Exception.php');
require(KILLI_DIR . '/include/exception.defaults.php');

if (file_exists('./include/exception.php'))
{
	include('./include/exception.php');
}

/**
 * Inclusion de l'autoload.
 */
require(KILLI_DIR . '/class/core/class.Autoload.php');

/**
 * Chargement dynamique des fichiers de KILLI.
 */

Autoload::setFacade(array(
	//'Application' => 'Killi\Core\Application\Application',
	//'Request' => 'Killi\Core\Application\Http\Request',
	// Ligne à décommenter pour activer le nouvel ORM
	'ORM' => 'Killi\Core\ORM\ORMService',
	'Route' => 'Killi\Core\Route\RouteService',
	'Log' => 'Killi\Core\Logger\LoggerService',
));

Log::instance()->setLogger(new Killi\Core\Logger\FileLogger(LOG_FILE));

// core
include_classes(KILLI_DIR . '/class/core/');

// referentiel d'objet
include_killi_classes(KILLI_DIR . '/class/');

// core
include_classes(KILLI_DIR . '/class/render/');

// Objets cachés
ORM::declareObject('UserPreferences',FALSE);
ORM::declareObject('Object',FALSE);
ORM::declareObject('Attribute',FALSE);
ORM::declareObject('LinkRights',FALSE);
ORM::declareObject('MenuRights',FALSE);
ORM::declareObject('NodeType',FALSE);
ORM::declareObject('Notification',FALSE);
ORM::declareObject('NotificationUser',FALSE);
ORM::declareObject('Priority',FALSE);
ORM::declareObject('AndroidAppError',FALSE);
ORM::declareObject('ApplicationToken',FALSE);

// droits spéciaux
Rights::lockObjectRights('Notification', 'create', FALSE);
Rights::lockObjectRights('Notification', 'delete', FALSE);
Rights::lockObjectRights('Notification', 'view', TRUE);
Rights::lockObjectRights('UserPreferences', 'create', FALSE);
Rights::lockObjectRights('UserPreferences', 'delete', FALSE);
Rights::lockObjectRights('UserPreferences', 'view', TRUE);
Rights::lockObjectRights('ImageAnnotation', 'create', TRUE);
Rights::lockObjectRights('ImageAnnotation', 'delete', TRUE);

include(KILLI_DIR . '/include/toolbox.php');

if (file_exists('./include/toolbox.php'))
{
	include('./include/toolbox.php');
}

if(file_exists('./class/'))
{
	include_classes('./class/');
}

if (!file_exists('./class/class.UI.php'))
{
	Autoload::declareContentClass('UI', 'class UI extends KilliUI {}');
}

if (!file_exists('./class/class.JSONMethod.php'))
{
	Autoload::declareContentClass('JSONMethod', 'class JSONMethod extends KilliJSONMethod {}');
}

if (!file_exists('./class/class.CronMethod.php'))
{
	Autoload::declareContentClass('CronMethod', 'class CronMethod extends KilliCron {}');
}

if (file_exists('./include/index_include_start.php'))
{
	require('./include/index_include_start.php');
}

//---Reporting
if(is_dir('./reporting/class'))
{
	include_classes('./reporting/class/');
}

//---Workflow
if(is_dir('./workflow/class'))
{
	include_classes('./workflow/class/');
}

//---Module process
include_classes(KILLI_DIR . '/class/process/');
