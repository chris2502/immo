<?php

/**
 *  Script de génération des tables de logs.
 *
 *  @Revision $Revision: 4419 $
 *
 */

error_reporting(E_ALL);

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

include_once('config.php');
include_once('../../include/exception.defaults.php');
include_once('../../class/core/class.DbLayer.php');

$usage = 'Usage : '.$_SERVER['PHP_SELF'].' -d|--database DATABASE -t|--table TABLE[,TABLE,...] -o|--out OUTPUT_SQL_FILE' . PHP_EOL;

$database	= NULL;
$table		= NULL;
$outfile    = NULL;

for($a = 1; $a < $_SERVER['argc']; $a++)
{
	switch($_SERVER['argv'][$a])
	{
		case '-d' :
		case '--database' :
			$database = $_SERVER['argv'][++$a];
			break;
		case '-t' :
		case '--table' :
			$table = $_SERVER['argv'][++$a];
			break;
		case '-o' :
		case '--out' :
			$outfile = $_SERVER['argv'][++$a];
			break;
		case '-v':
			echo 'Version $Revision: 4419 $', PHP_EOL;
		case '-h':
		default:
			echo $usage;
			exit(1);
	}
}

if(empty($database))
{
	echo $usage;
	exit(1);
}

DEFINE('DISPLAY_ERRORS', TRUE);

DEFINE('DONT_SET_NAMES', FALSE);

// Génère une erreur lors des warnings SQL, si DISPLAY_ERRORS
DEFINE('THROW_SQL_WARNINGS', TRUE);

// Définition de la tolérance de capture des requêtes éxecutées plusieurs fois; la valeur 0 affiche toutes les requêtes
DEFINE('TRACE_DUPLICATE_QUERIES_TOLERANCE', 50);

// Définition de la tolérance de capture des requêtes lentes; en millisecondes; la valeur 0 affiche toutes les requêtes
DEFINE('TRACE_SLOW_QUERIES_TIME_TOLERANCE', 100);

$dbconfig = array(
	'dbname'   => $database,
	'charset'  => 'utf8',

	'rw' => array(
		'host'  => DBSI_HOSTNAME,
		'user'  => DBSI_USERNAME,
		'pwd'   => DBSI_PASSWORD,

		'ctype' => NULL
	),
);

$hDB = new DbLayer($dbconfig);
$hDB->db_start();
$hDB->fail_on_warning();

$hDB->db_execute('SET storage_engine=INNODB;');
$hDB->fail_on_warning();

/**
 * Récupération de la liste des tables.
 */

$table_list = array();
$result = NULL;
$hDB->db_select('SHOW TABLES;', $result);
$hDB->fail_on_warning();

while($row = $result->fetch_array())
{
	$table_list[$row[0]] = $row[0];
}
$result->free();

$table_to_analyse = $table_list;

if($table !== NULL)
{
	if (strpos($table, ',') > 0){
		$param_table_list = explode(',', $table);
	}
	else{
		$param_table_list = array($table => $table);
	}

	foreach($param_table_list as $table){
		if(!isset($table_list[$table]))
		{
			throw new Exception('La table "' . $table . '" n\'existe pas dans la base "' . $database . '"');
		}
	}

	$table_to_analyse = $param_table_list;

}

/**
 * Récupération de la description des tables.
 */

$table_desc = array();

foreach($table_to_analyse AS $t)
{
	$end = substr($t, -4);
	if($end == '_log')
	{
		unset($table_to_analyse[$t]);
		continue;
	}

	if(isset($table_list[$t.'_log']))
	{
		$result = NULL;
		$hDB->db_select('DESC ' . $t . '_log', $result);
		while($row = $result->fetch_assoc())
		{
			$field_name = $row['Field'];
			$table_desc[$t . '_log'][$field_name] = $row;
		}
		$result->free();
	}

	$result = NULL;
	$hDB->db_select('DESC ' . $t, $result);
	while($row = $result->fetch_assoc())
	{
		$field_name = $row['Field'];
		$table_desc[$t][$field_name] = $row;
	}
	$result->free();
}

/**
 * Analyse des actions à mener.
 */
$query_list = array();
foreach($table_to_analyse AS $t)
{
	echo $t, ':', PHP_EOL;

	$table = $table_desc[$t];

	// Ajout des champs manquants à la table

	$query = <<<EOF
--
-- Génération des champs pour le log de la table $t
--
EOF;

	$query_list[] = $query;

	if(!isset($table['users_id']))
	{
		echo 'Ajout du champ users_id à la table ', $t, PHP_EOL;
		$query = 'ALTER TABLE `'.$t.'` ADD COLUMN `users_id` int(10) UNSIGNED DEFAULT NULL;';
		$query_list[] = $query;
		$table['users_id'] = array('Type' => 'int(10) unsigned', 'Null' => 'Yes', 'Default' => NULL);
	}

	if($table['users_id']['Null'] != 'Yes' && $table['users_id']['Default'] != NULL)
	{
		echo 'Modification du champ users_id à la table ', $t, PHP_EOL;
		$query = 'ALTER TABLE `'.$t.'` CHANGE `users_id` `users_id` int(10) UNSIGNED NULL DEFAULT NULL;';
		$query_list[] = $query;
		$table['users_id'] = array('Type' => 'int(10) unsigned', 'Null' => 'Yes', 'Default' => NULL);
	}

	if(!isset($table['date_creation']))
	{
		echo 'Ajout du champ date_creation à la table ', $t, PHP_EOL;
		$query = 'ALTER TABLE `'.$t.'` ADD COLUMN `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP;';
		$query_list[] = $query;
		$table['date_creation'] = array('Type' => 'timestamp', 'Null' => 'Yes', 'Default' => 'CURRENT_TIMESTAMP');
	}

	if(!isset($table['date_modification']))
	{
		echo 'Ajout du champ date_modification à la table ', $t, PHP_EOL;
		$query = 'ALTER TABLE `'.$t.'` ADD COLUMN `date_modification` timestamp DEFAULT "0000-00-00 00:00:00";';
		$query_list[] = $query;
		$table['date_modification'] = array('Type' => 'timestamp', 'Null' => 'Yes', 'Default' => '0000-00-00 00:00:00');
	}

	// Création / Vérification / Modification table de log
	$query = <<<EOF


--
-- Génération/Correction de la table de log pour la $t
--
EOF;

	$query_list[] = $query;
	if(!isset($table_desc[$t.'_log']))
	{
		echo 'Création d\'une table de log pour la table ', $t, PHP_EOL;
		$query_list[] = query_create_table_log($table, $t);

		$query_list[] = PHP_EOL . PHP_EOL;

		$pk = NULL;
		foreach($table AS $field_name => $field)
		{
			if($field['Key'] == 'PRI')
			{
				$pk = $field_name;
				break;
			}
		}

		if($pk != NULL)
		{
			echo 'Création du trigger de log pour la table ', $t, PHP_EOL;
			$query_list[] = query_create_triggers($table, $database, $t, $pk);
		}
	}
	else
	{
		$log_table = $table_desc[$t . '_log'];

		// Compare table/table_log
		foreach($table AS $field_name => $field)
		{
			if($field['Key'] == 'PRI')
			{
				$pk = $field_name;

// 				if ($pk != 'log_id')
// 				{
// 					echo 'Changement du nom de la clé primaire de ', $field_name, ' en log_id', PHP_EOL;
// 					$query = 'ALTER TABLE `'.$t.'_log` CHANGE `'.$pk.'` log_id '.$field['Type'].';';
// 					$query_list[] = $query;
// 				}
			}
			// Colonne manquante dans le log
			if(!isset($log_table[$field_name]))
			{
				echo 'Ajout de la colonne ', $field_name, ' à la table de log', PHP_EOL;
				$default = $field['Default'];
				if ($default == NULL){
					$default = 'NULL';
				}
				elseif ($default != 'CURRENT_TIMESTAMP' && strtolower($field['Type']) == 'timestamp'){
					$default = '"'.$default.'"';
				}
				elseif (! ctype_digit($default) && $default != 'CURRENT_TIMESTAMP'){
					$default = '\''.$default.'\'';
				}
				$query = 'ALTER TABLE `'.$t.'_log` ADD COLUMN `'.$field_name.'` ' .$field['Type'].' '.(strtoupper($field['Null'])=='NO' ? 'NOT NULL' : '').' DEFAULT '.$default.';';
				$query_list[] = $query;
				continue;
			}

			$log_field = $log_table[$field_name];

			// Différence de Type ou de Nullable
			if ($field['Type'] != $log_field['Type'] || $field['Null'] != $log_field['Null'])
			{
				if($field['Type'] != $log_field['Type'])
				{
					echo 'Type différent pour la même colonne "', $field_name, '" : ', $field['Type'], ' != ', $log_field['Type'], PHP_EOL;
				}

				if($field['Null'] != $log_field['Null'])
				{
					echo 'Différence sur l\'attribut Nullable de la colonne "', $field_name, '" : ', $field['Null'], ' != ', $log_field['Null'], PHP_EOL;
				}

				$query = 'ALTER TABLE `'.$t.'_log` CHANGE COLUMN `'.$field_name.'` `'.$field_name.'` '.$field['Type'].(strtoupper($field['Null'])=='NO' ? ' NOT NULL' : '').';';
				$query_list[] = $query;
			}

		} // foreach - compare

		if($pk != NULL)
		{
			echo 'Création du trigger de log pour la table ', $t, PHP_EOL;
			$query_list[] = query_create_triggers($table, $database, $t, $pk);
		}
	} // else - création/vérification table log
} // foreach table


// Sortie

$sql = implode (PHP_EOL, $query_list);
//echo $sql . PHP_EOL;
if ($outfile != NULL){
	file_put_contents($outfile, $sql);
}





/**
 * Retourne la requete de création de la table de log
 * @param array  $table       Les infos de la table (association string:field_name => array:field_infos)
 * @param string $table_name  Nom de la table d'origine
 * @return string
 */
function query_create_table_log($table, $table_name)
{
	$query  = 'CREATE TABLE `'.$table_name.'_log` ('.PHP_EOL;
	$query .= '`log_id` int(10) unsigned NOT NULL ';
	foreach($table AS $field_name => $field)
	{
		$default = $field['Default'];
		if ($default == NULL){
			$default = 'NULL';
		}
		else
		if ($default != 'CURRENT_TIMESTAMP' && strtolower($field['Type']) == 'timestamp')
		{
			$default = '"'.$default.'"';
		}
		else
		if (! ctype_digit($default) && $default != 'CURRENT_TIMESTAMP')
		{
			$default = '\''.$default.'\'';
		}

		$nullable = (strtoupper($field['Null'])=='NO' ? 'NOT NULL' : '');
		$default = ($nullable == 'NOT NULL' && $default == 'NULL') ? '' : ' DEFAULT '. $default;
		$query .= ','.PHP_EOL.'`'.$field_name.'` ' .$field['Type'].' '.$nullable.$default;
	}
	$query.= PHP_EOL.') ENGINE=InnoDB DEFAULT CHARSET=utf8;' . PHP_EOL;

	$query .= 'ALTER TABLE `'.$table_name.'_log` ADD PRIMARY KEY (`log_id`);' . PHP_EOL;
	$query .= 'ALTER TABLE `'.$table_name.'_log` MODIFY `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT;' . PHP_EOL;

	return $query;
}

/**
 * Retourne les requetes de création des triggers
 * @param array  $table       Les infos de la table (association string:field_name => array:field_infos)
 * @param string $table_name  Nom de la table d'origine
 * @return string
 */
function query_create_triggers($table, $database, $table_name, $primary_key)
{
	$trg_insert = <<<EOF
--
-- Trigger a l'insertion dans la table ${table_name}
--
DROP TRIGGER IF EXISTS `trg_${table_name}_insert`;
DROP TRIGGER IF EXISTS `tgr_${table_name}_insert`;
DELIMITER //
CREATE TRIGGER `tgr_${table_name}_insert` BEFORE INSERT ON `${table_name}`
FOR EACH ROW BEGIN
  IF NEW.users_id IS NULL THEN
    SET NEW.users_id=@users_id;
  END IF;
END
//
DELIMITER ;


EOF;

	$trg_update = <<<EOF
--
-- Trigger a la mise a jour dans la table ${table_name}
--
DROP TRIGGER IF EXISTS `trg_${table_name}_update`;
DROP TRIGGER IF EXISTS `tgr_${table_name}_update`;
DELIMITER //
CREATE TRIGGER `tgr_${table_name}_update` BEFORE UPDATE ON `${table_name}`
 FOR EACH ROW BEGIN
  DECLARE log_id integer;
  IF NEW.users_id IS NULL THEN
    SET NEW.users_id=@users_id;
  END IF;
  SET NEW.date_modification=NOW() ;
SELECT `AUTO_INCREMENT` INTO log_id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '${database}' AND TABLE_NAME = '${table_name}_log';
INSERT INTO ${table_name}_log SELECT log_id, ${table_name}.* FROM ${table_name} WHERE ${primary_key}=OLD.${primary_key};
END
//
DELIMITER ;


EOF;

	$trg_delete = <<<EOF
--
-- Trigger a la suppression dans la table ${table_name}
--
DROP TRIGGER IF EXISTS `trg_${table_name}_delete`;
DROP TRIGGER IF EXISTS `tgr_${table_name}_delete`;
DELIMITER //
CREATE TRIGGER `tgr_${table_name}_delete` BEFORE DELETE ON `${table_name}`
 FOR EACH ROW BEGIN
  DECLARE log_id integer;
SELECT `AUTO_INCREMENT` INTO log_id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '${database}' AND TABLE_NAME = '${table_name}_log';
INSERT INTO ${table_name}_log SELECT log_id, ${table_name}.* FROM ${table_name} WHERE ${primary_key}=OLD.${primary_key};
END
//
DELIMITER ;


EOF;

	$query = $trg_insert . PHP_EOL . $trg_update . PHP_EOL . $trg_delete;

	return $query;
	/*
	 * CREATE TRIGGER IF NOT EXISTS nom_du_trigger BEFORE|AFTER UPDATE|INSERT|DELETE ON table_name FOR EACH ROW
	 * [BEGIN]
	 * INSERT INTO table_log
	 * SET col_name = col_value,
	 * ...
	 * [END]
	 *
	 */
}
