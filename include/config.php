<?php
require_once ('config.dist.php');

DEFINE('DISPLAY_ERRORS', TRUE);
DEFINE('STRICT_MODE', TRUE);

DEFINE('ADMIN_PROFIL_ID',1);
DEFINE('READONLY_PROFIL_ID',2);

DEFINE ( 'THROW_SQL_WARNINGS', FALSE );

DEFINE('DBSI_DATABASE', 'suivi_immo' );
DEFINE('RIGHTS_DATABASE', DBSI_DATABASE );
DEFINE('DBSI_CHARSET', 'utf8' );
DEFINE('DBSI_MASTER_HOSTNAME','localhost');
DEFINE('DBSI_MASTER_USERNAME','root');
DEFINE('DBSI_MASTER_PASSWORD','g6xA24B');

$dbconfig = array(
	'dbname'   => DBSI_DATABASE,
	'charset'  => DBSI_CHARSET,
	'users_id' => (isset($_SESSION['_USER']) && isset($_SESSION['_USER']['killi_user_id']) ? $_SESSION['_USER']['killi_user_id']['value'] : NULL),

	// lecture/écriture transactionnelle
	'rw' => array(
		'host'  => DBSI_MASTER_HOSTNAME,
		'user'  => DBSI_MASTER_USERNAME,
		'pwd'   => DBSI_MASTER_PASSWORD,

		'ctype' => NULL // ou 'persistent' pour une connexion persistante
	)
);

