<?php


die('config.dist.php NE DOIT PAS être utilisé comme fichier de configuration, éditez plutôt le config.php de l\'applicatif !!');



/*/////////////////////////////////////
//        KILLI CONFIGURATION        //
/////////////////////////////////////*/



/* DEBUG && DEV */


// Affiche les erreurs PHP, les warnings SQL et active les outils de capture (TRACE_DUPLICATE_QUERIES et TRACE_SLOW_QUERIES)
DEFINE('DISPLAY_ERRORS', FALSE);

// Génère une erreur lors des warnings SQL, si DISPLAY_ERRORS
DEFINE('THROW_SQL_WARNINGS', TRUE);

// Définition de la tolérance de capture des requêtes éxecutées plusieurs fois; la valeur 0 affiche toutes les requêtes
DEFINE('TRACE_DUPLICATE_QUERIES_TOLERANCE', 0);

// Définition de la tolérance de capture des requêtes lentes; en millisecondes; la valeur 0 affiche toutes les requêtes
DEFINE('TRACE_SLOW_QUERIES_TIME_TOLERANCE', 100);

// Alerte mail en cas d'erreur critique
DEFINE('ERROR_EMAILS','chuck.norris@gmail.com');

// Sujet du mail d'alerte
DEFINE('ERROR_MAIL_SUBJECT','ERROR FTTH V3');

// Dossier du fichier de log
DEFINE('LOG_FILE', './log/error.log');

// Tolère les erreurs de contraintes d'intégrité (absence de clé étrangère, clé primaire dupliquée, etc)
DEFINE('TOLERATE_MISMATCH', FALSE);

// Log les actions pour statistiques
DEFINE('LOG_USER_ACTION',FALSE);

// Activate the firephp debugger
DEFINE('FIREPHP_ENABLE', FALSE);

// Lève des exceptions en cas de détection de faille de sécurité (lors de passage de paramètre)
DEFINE('CHECK_SECURITY_ISSUE', FALSE);




/* PROFILS NATIFS */


// Administrateur
DEFINE('ADMIN_PROFIL_ID',1);

// Lecture seule
DEFINE('READONLY_PROFIL_ID',2);

// SYSTEM
DEFINE('SYSTEM_PROFIL_ID',3);




/* CONFIG */


// Définition du fuseau horaire
date_default_timezone_set('UTC');

// Encodage par défaut
mb_internal_encoding('UTF-8');

// Désactivation du cache des css et des js
// forcé à TRUE si DISPLAY_ERRORS est à TRUE aussi
DEFINE('DISABLE_CACHE',false);

// Titre de l'application Killi
DEFINE('HEADER_MESSAGE','My Killi application');

// Déscription de l'application Killi
DEFINE('HEADER_DESCRIPTION','Killi rocks');

// Action par défaut
DEFINE('HOME_PAGE','user.home');

// Thème
DEFINE('UI_THEME',NULL);

// Message d'erreur publié en cas d'erreur critique
DEFINE('PUBLIC_ERROR_MESSAGE','<b><font color=\'#BB0000\'>Une erreur critique s\'est produite.</font></b><br /><br />Un technicien a &eacute;t&eacute; averti de l\'incident.');

// Page d'erreur publié en cas d'erreur critique
DEFINE('ERROR_PAGE_FILE','./include/error_page.html');

// Nombre de lignes affichées dans les listing d'objet
DEFINE('SEARCH_VIEW_NUM_RECORDS', 200);

// Dossier de stockage des fichiers uploadés
DEFINE('LOCAL_FILESTORE', '/doc');

// Libère la session PHP lors des exports CSV pour permetre à l'utilisateur de continuer à travailler pendant la génération de gros fichiers
DEFINE('DO_NOT_LOCK_SESSION_ON_EXTRACT', FALSE);

// Serveur OPENERP
DEFINE('OPENERP_DATABASE','test');
DEFINE('OPENERP_USER','chuck');
DEFINE('OPENERP_PASSWORD','n0RR1s');
DEFINE('OPENERP_FILESTORE','/usr/lib/openerp-server/filestore');

// Lock des fichiers
DEFINE('LOCK_DIR', './log');
DEFINE('LOCK_SUFFIX', '.lock');
DEFINE('LOCK_TIMEOUT', 300);

// Paramêtres SQL
DEFINE('DBSI_DATABASE', 'my_killi_app' );
DEFINE('DBSI_CHARSET', 'utf8' );
DEFINE('DBSI_MASTER_HOSTNAME','localhost');
DEFINE('DBSI_MASTER_USERNAME','chuck');
DEFINE('DBSI_MASTER_PASSWORD','n0RR1s');

// Si FALSE, l'encodage de la connexion est défini par DBSI_CHARSET, sinon par la valeur par défaut du serveur
DEFINE('DONT_SET_NAMES', FALSE);

//...




// Application de la config SQL

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
	),

	// lecture seule sans transaction SQL
	'r' => array(
		'host'  => DBSI_MASTER_HOSTNAME,
		'user'  => DBSI_MASTER_USERNAME,
		'pwd'   => DBSI_MASTER_PASSWORD,

		'ctype' => NULL // ou 'persistent' pour une connexion persistante
	)

);
