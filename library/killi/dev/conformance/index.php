<?php

header('Content-type:text/html; charset=utf-8');

?>
<style>
	body { margin:0; font-family:Arial; font-size:12px; }
	.msg, .info { border-radius:4px; border:1px solid #666; padding:5px; margin:2px; }
	li { list-style-type: none; }
	h1 { margin:5px; }
	ul { padding:0; }
	.error { background-color:#ff8888; }
	.fatal { background-color:#C88CFF; }
	.warning { background-color:#FFB937; }
	.info { background-color:#EAEAEA; }
	.notice { background-color:#DDE4D5; }
	.fail { background-color:red; color:white; }
	.strict { background-color:#6DF3DE; }
	.score { position: absolute; right: 20; top: 10; }
	.sql-comment { font-size: 16px; color: #777; }
</style>
<?php

function dump_error($message)
{
	global $score;

	echo '<div class="msg error"><b>Modèle objet</b> : '.$message.'</div>';
	$score+=20;
}
function dump_warning($message)
{
	global $score;

	echo '<div class="msg warning"><b>Modèle SQL</b> : '.$message.'</div>';
	$score+=10;
}
function dump_notice($message)
{
	global $score;

	echo '<div class="msg notice"><b>Note</b> : '.$message.'</div>';
}
function dump_fatal($message)
{
	global $score;

	echo '<div class="msg fatal"><b>Intégrité</b> : '.$message.'</div>';
	$score+=50;
}
function dump_fail($message)
{
	echo '<div class="msg fail"><b>Erreur interne</b> : '.$message.'</div>';
}
function dump_strict($message)
{
	global $score;

	echo '<div class="msg strict"><b>STRICT_MODE</b> : '.$message.'</div>';
	$score+=80;
}
function dump_template_error($message)
{
	global $score;

	echo '<div class="msg error"><b>Template</b> : '.$message.'</div>';
	$score+=20;
}
function dump_template_warning($message)
{
	global $score;

	echo '<div class="msg warning"><b>Template</b> : '.$message.'</div>';
	$score+=10;
}



$score=0;

define('KILLI_DIR', './library/killi/');

if(!file_exists(KILLI_DIR . './include/include.php'))
{
	?><div style='padding:10px'>
			<h3>Creez un lien symbolique pour exécuter le test de conformance depuis la racine de l'applicatif :
			<br/>
			<br/>&lt; ln -s ./library/killi/dev/conformance/index.php conformance.php</h3>
		</div>
	<?php
	die();
}

require(KILLI_DIR . './include/include.php');

ExceptionManager::enable();

$tests = array(
	'fk'=>array('title'=>'Présence des contraintes de clés étrangères','enabled'=>true),
	'keywords'=>array('title'=>'Mots clés réservés','enabled'=>true),
	'prop'=>array('title'=>'Présence des proprietés de basiques','enabled'=>true),
	'index'=>array('title'=>'Présence des tables et des index','enabled'=>true),
	'attr'=>array('title'=>'Test des attributs','enabled'=>true),
	'relations'=>array('title'=>'Test des relations','enabled'=>true),
	'default'=>array('title'=>'Test des valeurs par défaut et des nullables','enabled'=>true),
	'engine'=>array('title'=>"Test de l'encodage et du moteur de stockage",'enabled'=>true),
	'strict'=>array('title'=>'STRICT_MODE','enabled'=>true),
	'note'=>array('title'=>'Notes et commentaires','enabled'=>false),
	'tmpl'=>array('title'=>'Templates','enabled'=>true)
);

foreach($tests as $test_name=>$test)
{
	if(!isset($_GET[$test_name]))
	{
		$_GET[$test_name] = $test['enabled'];
	}
}

?>
<div class='info'>
	<h1>Killi Conformance Test v1 (killi <?= KILLI_VERSION ?>) - <?= HEADER_MESSAGE ?></h1>
	<h3>
		<span style='color:<?= (function_exists('apc_fetch') || function_exists('opcache_reset'))?'green':'red'; ?>'>APC/OPcache</span>
	</h3>
	<ul>
<?php

foreach($tests as $test_name=>$test)
{
	?><li><input onchange='refresh()' id='<?= $test_name; ?>' type='checkbox' <?php if($_GET[$test_name]==true){ echo 'checked=\'checked\''; } ?>/><label for='<?= $test_name; ?>'><?= $test['title']; ?></label></li><?php
}

?>
	</ul>
</div>
<script>
function refresh()
{
	params=[];

	params_list=[<?= '\''.implode('\', \'',array_keys($tests)).'\'' ?>];

	for(var i=0;i!=params_list.length;i++)
	{
		params.push(params_list[i]+'='+(document.getElementById(params_list[i]).checked?'1':'0'));
	}

	location.href='?'+params.join('&');

}

</script>
<?php

// chargement des index et des contraintes d'intégrités

$hDB = new DbLayer($dbconfig);

try
{
	$hDB->db_execute("set global innodb_stats_on_metadata=0");
}
catch (Exception $e) {}

function load_db_stats($db)
{
	global $db_struct, $hDB, $views, $dbs;

	$dbs[]=$db;

	$hDB->db_select("select TABLE_NAME,COLUMN_NAME,COLUMN_KEY,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME from information_schema.COLUMNS where TABLE_SCHEMA='".$db."'",$result);

	while($row=$result->fetch_array())
	{
		if(!isset($db_struct[$db.'.'.$row['TABLE_NAME']]))
			$db_struct[$db.'.'.$row['TABLE_NAME']]=array();

		$db_struct[$db.'.'.$row['TABLE_NAME']][$row['COLUMN_NAME']]=array(
			'type'=>$row['COLUMN_KEY']!='',
			'nullable'=>($row['IS_NULLABLE']==='YES'),
			'default'=>$row['COLUMN_DEFAULT'],
			'column_type'=>$row['COLUMN_TYPE'],
			'autoincrement'=>($row['EXTRA']==='auto_increment'),
			'collation'=>$row['COLLATION_NAME'],
			'constraints'=>array()
		);
	}
	$result->free();

	$hDB->db_select("select TABLE_NAME, ENGINE, TABLE_COLLATION, ROW_FORMAT, TABLE_TYPE from information_schema.TABLES where TABLE_SCHEMA='".$db."'",$result);

	while($row=$result->fetch_array())
	{
		if(!isset($db_struct[$db.'.'.$row['TABLE_NAME']]))
			continue;

		if($row['TABLE_TYPE']=='VIEW')
		{
			unset($db_struct[$db.'.'.$row['TABLE_NAME']]);
			$views[]=$row['TABLE_NAME'];

			continue;
		}

		$db_struct[$db.'.'.$row['TABLE_NAME']]['__engine']=$row['ENGINE'];
		$db_struct[$db.'.'.$row['TABLE_NAME']]['__collation']=$row['TABLE_COLLATION'];
		$db_struct[$db.'.'.$row['TABLE_NAME']]['__row_format']=$row['ROW_FORMAT'];
	}
	$result->free();

	$hDB->db_select("select TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME from information_schema.KEY_COLUMN_USAGE where CONSTRAINT_SCHEMA='".$db."' and REFERENCED_TABLE_SCHEMA='".$db."'",$result);

	while($row=$result->fetch_array())
	{
		$db_struct[$db.'.'.$row['TABLE_NAME']][$row['COLUMN_NAME']]['constraints'][]=$row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME'];
	}
	$result->free();
}

function load_templates($dir)
{
	if(!is_dir($dir))
	{
		return;
	}

	global $templates;

	$hDir = opendir($dir);
	while (false !== ($file = readdir($hDir)))
	{
		if($file == '.' || $file == '..' || $file == '.svn')
		{
			continue;
		}

		if (is_dir($dir.$file))
		{
			load_templates($dir.$file.'/');
		}
		else if(substr($file,-4)=='.xml')
		{
			$templates[]=$dir.$file;
		}
	}
	closedir($hDir);
	return TRUE;
}

$db_struct=array();
$views=array();
$dbs=array();
$templates=array();

load_db_stats($dbconfig['dbname']);

load_templates('./template/');
load_templates('./workflow/template/');
load_templates(KILLI_DIR.'template/');

$missing_table = array();
$missing_index = array();
$missing_fkey  = array();
$missing_field = array();
$wrong_charset = array();
$wrong_field_charset = array();
$wrong_engine  = array();

// verification des objets
foreach(ORM::getDeclaredObjectsList() as $object)
{
	if(!ORM::$_objects[$object]['rights'])
	{
		continue;
	}
			
	// cache instance
    ${'instance_'.strtolower($object)} = ORM::getObjectInstance($object, FALSE);
}
foreach(ORM::getDeclaredObjectsList() as $object)
{
	if(!ORM::$_objects[$object]['rights'])
	{
		continue;
	}
	
	// test instanciation de l'objet
	$obj = ORM::getObjectInstance($object, FALSE);

    if($_GET['attr']==1)
    {
        if(isset($obj->object_domain))
        {
            dump_error(
                'L\'objet '.$object.' possède un object_domain en dehors de la méthode setDomain'
            );
        }

        if(isset($obj->domain_with_join))
        {
            dump_error(
                'L\'objet '.$object.' possède un domain_with_join en dehors de la méthode setDomain'
            );
        }

        // longueur du nom de l'objet
        if(strlen($object)>64)
        {
            dump_error(
                'L\'objet '.$object.' a un nom beaucoup trop long, 64c max ('.strlen($object).')'
            );
        }
    }

	// verification de la clé primaire
	$test_primary_key=false;
	if($_GET['prop']==1 && property_exists($object, 'table') && !property_exists($object, 'primary_key'))
	{
		dump_error('L\'objet '.$object.' n\'a pas la proprieté $primary_key');
	}

	if(property_exists($object, 'table') && property_exists($object, 'database') &&  !in_array(${'instance_'.strtolower($object)}->database, $dbs))
	{
		load_db_stats(${'instance_'.strtolower($object)}->database);
	}

	// existance de la table
	if($_GET['index']==1 && property_exists($object, 'table') && !isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && !in_array(${'instance_'.strtolower($object)}->table,$views))
	{
		dump_error('La table '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.' n\'existe pas pour l\'objet '.$object);
		$missing_table[$object][] = array('table' => ${'instance_'.strtolower($object)}->table);
		$score+=100; // Il manque des tables !!!
	}

	// test du moteur de stockage
	if($_GET['engine']==1 && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]['__engine']!='InnoDB')
	{
		dump_warning('La table '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.' n\'est pas au format InnoDB ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]['__engine'].')');
		$wrong_engine[$object][] = array('table' => ${'instance_'.strtolower($object)}->table);
	}

	// test de l'encodage par défaut de la table
	if($_GET['engine']==1 && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && substr($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]['__collation'],0,4)!='utf8')
	{
		dump_warning('La table '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.' n\'est pas au format UTF-8 ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]['__collation'].')');
		$wrong_charset[$object][] = array('table' => ${'instance_'.strtolower($object)}->table);
	}

  	/* Vérification que des attributs de l'objet n'ont pas été remplacé par des FieldDefinition. */
	$reserved_keyword_list = array('table', 'database', 'primary_key', 'order', 'reference', 'create', 'delete', 'view');
	foreach($reserved_keyword_list AS $keyword)
	{
		if($_GET['keywords']==1 && isset(${'instance_'.strtolower($object)}->$keyword) && ${'instance_'.strtolower($object)}->$keyword instanceof FieldDefinition)
		{
			if(
					($object == 'menurights' && $keyword == 'view') ||
					($object == 'object' && ($keyword == 'view' || $keyword == 'delete' || $keyword == 'create'))
			){
				continue;
			}
			dump_error('L\'attribut \''.$keyword.'\' de l\'objet \''.$object.'\' est un mot réservé.');
		}
	}

	// test de la reference
	$ref = isset($obj::$reference) ? $obj::$reference : (isset($obj->reference) ? $obj->reference : null);

	if($_GET['prop']==1 && $ref!=null && is_string($ref) && (!isset($obj->$ref) || !($obj->$ref instanceof FieldDefinition)))
	{
		dump_warning('L\'attribut de référence '.$object.'->'.$ref.' n\'existe pas');
	}

	// verification des attributs
	foreach(${'instance_'.strtolower($object)} as $name=>$definition)
	{
		if($definition instanceof FieldDefinition)
		{
			// longueur du nom de l'attribut
			if($_GET['attr']==1 && strlen($name)>64)
			{
				dump_error('L\'attribut '.$object. '->'.$name.' a un nom beaucoup trop long, 64c max ('.strlen($name).')');
			}

			// test des contraintes de longueur
			if($_GET['attr']==1 && $definition->editable == true && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && substr($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'],0,7)=='varchar' && $definition->type == 'text')
			{
				$var_len = substr($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'],8,-1);

				if(!(property_exists($definition, 'max_length') && $definition->max_length >= 1) && $definition->editable == TRUE)
				{
					dump_error('Aucune contrainte de longueur pour l\'attribut '.$object. '->'.$name.' de type varchar('.$var_len.'). Précisez la longeur maximale en parametre du TextFieldDefinition.');
				}
				else if(property_exists($definition, 'max_length') && $definition->max_length > $var_len)
				{
					dump_error('Mauvaise contrainte de longueur pour l\'attribut '.$object. '->'.$name.' de type varchar('.$var_len.'). La longeur maximale spécifiée ('.$definition->max_length.') est supérieur à la longeur du varchar');
				}
			}

			// test des contraintes sur les champs non éditables
			if($_GET['note']==1 && $definition->editable == false && !empty($definition->constraint_list))
			{
				dump_notice('Contrainte inutile pour l\'attribut '.$object. '->'.$name.' car le FieldDefinition n\'est pas éditable');
			}

			// test des textarea
			if($_GET['attr']==1 && $definition->type == 'textarea' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type']!='longtext')
			{
				dump_error('Type de colonne invalide ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'].') pour l\'attribut '.$object. '->'.$name.' de type textarea, la colonne doit être longtext');
			}

			// vérirfication des paramètres

			if($_GET['attr']==1)
			{
				if($definition->name!==NULL && !is_string($definition->name))
				{
					dump_error('Le paramètre #3 \'name\' de l\'attribut '.$object. '->'.$name.' doit être un String ('.gettype($definition->name).')');
				}

				if($definition->required!==FALSE && $definition->required!==TRUE)
				{
					dump_error('Le paramètre #5 \'required\' de l\'attribut '.$object. '->'.$name.' doit être un Booléen ('.gettype($definition->required).')');
				}

				if($definition->constraint_list!==NULL && !is_array($definition->constraint_list))
				{
					dump_error('Le paramètre #6 \'constraint_list\' de l\'attribut '.$object. '->'.$name.' doit être un Array, ou NULL ('.gettype($definition->constraint_list).')');
				}

				if($definition->domain!==NULL && !is_array($definition->domain))
				{
					dump_error('Le paramètre #7 \'domain\' de l\'attribut '.$object. '->'.$name.' doit être un Array, ou NULL ('.gettype($definition->domain).')');
				}

				if($definition->editable!==FALSE && $definition->editable!==TRUE)
				{
					dump_error('Le paramètre #8 \'editable\' de l\'attribut '.$object. '->'.$name.' doit être un Booléen ('.gettype($definition->editable).')');
				}

				if($definition->function!==FALSE && $definition->function!==NULL && !is_string($definition->function))
				{
					dump_error('Le paramètre #9 \'function\' de l\'attribut '.$object. '->'.$name.' doit être un String, FALSE, ou NULL ('.gettype($definition->function).')');
				}

				if($definition->description!==NULL && !is_string($definition->description))
				{
					dump_error('Le paramètre #10 \'description\' de l\'attribut '.$object. '->'.$name.' doit être un String, ou NULL ('.gettype($definition->description).')');
				}
			}

			if($_GET['attr']==1 && ($definition instanceof ExtendedFieldDefinition))
			{
				switch ($definition->type)
				{
					case 'extends' :
					case 'many2many' :
					case 'many2one' :
					case 'one2many' :
					case 'one2one' :
					case 'related' :
					case 'workflow_status' :

						if(empty($definition->object_relation))
						{
							dump_error('Paramètre object_relation non défini pour l\'attribut '.$object. '->'.$name);
						}
						break;
				}

				switch ($definition->type)
				{
					case 'one2many' :
					case 'one2one' :
					case 'related' :
					case 'workflow_status' :

						if(empty($definition->related_relation))
						{
							dump_error('Paramètre related_relation non défini pour l\'attribut '.$object. '->'.$name);
						}
						break;
				}
			}

			if(!property_exists($object,"primary_key"))
			{
				continue;
			}

			// verification de l'attribut de la clé primaire
			if(!$test_primary_key && $name==${'instance_'.strtolower($object)}->primary_key)
			{
				if($definition->type==='extends' && !class_exists($definition->object_relation))
				{
					dump_error('Extension d\'objet "'.$definition->object_relation.'" inconnue pour l`attribut '.$object. '->'.$name);
				}

				$test_primary_key=true;

				if($_GET['index']==1 && $test_primary_key && property_exists($object, 'table'))
				{
					if(isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && (!isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) || $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['type']!='PRI'))
					{
						dump_warning('Aucun index primaire sur la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' pour l\'objet '.$object);
					}
				}
			}

			// verification des booléens
			if($_GET['attr']==1 && $definition->type == 'checkbox' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type']!='tinyint(1) unsigned' && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type']!='tinyint(1)')
			{
				dump_error('Type de la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' invalide pour l\'attribut '.$object. '->'.$name.' de type booléen, doit être TINYINT(1) unsigned : ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'].')');
			}

			// verification des valeurs par défaut des booléens
			if($_GET['default']==1 && $definition->type == 'checkbox' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']!='0' && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']!='1')
			{
				dump_warning('Valeur par défaut de la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' invalide pour l\'attribut '.$object. '->'.$name.' de type booléen, doit être 0 ou 1 : ('.var_export($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default'],true).')');
			}

			// verification des booléens nullables
			if($_GET['note']==1 && $definition->type == 'checkbox' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['nullable'])
			{
				dump_notice('La colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' de l\'attribut '.$object. '->'.$name.' de type booléen est nullable, or il n\'y a que deux valeurs possible (0 et 1)');
			}

			// verification des valeurs par défaut des dates
			if($_GET['default']==1 && $definition->type=='date' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && ($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']=='0000-00-00' || $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']=='0000-00-00 00:00:00'))
			{
				dump_warning('La valeur par défaut du champ '.$object. '->'.$name. ' de type date est invalide ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default'].')');
			}

			// verification des datetime
			if($_GET['default']==1 && $definition->type=='datetime' && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && ($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type']!='datetime' && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type']!='timestamp'))
			{
				dump_warning('Type de colonne invalide pour le champ '.$object. '->'.$name. ' de type datetime, datetime ou timestamp attendu ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'].')');
			}

			// verification du type de relation
			if($_GET['relations']==1 && $definition->object_relation!==null && !in_array($definition->type, array('workflow_status','many2one','one2many','related','many2many','extends','parent','one2one')))
			{
				dump_error('Type de relation "'.$definition->type.'" inconnu pour l\'attribut '.$object. '->'.$name);
			}

			if($definition->object_relation!==null)
			{
				// verification du 2ieme argument des many2one, one2many et extends
				if($_GET['relations']==1 && ($definition->type=='many2one' || $definition->type=='one2many' || $definition->type=='many2many' || $definition->type=='extends') && !class_exists($definition->object_relation))
				{
					dump_error('Objet '.$definition->object_relation.' inconnu pour la relation de l`attribut '.$object. '->'.$name);
				}

				// verification des valeurs par défaut des relations
				if($_GET['default']==1 && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) && !(($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']-1)+1>0 || $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']==null))
				{
					dump_warning('La valeur par défaut du champ '.$object. '->'.$name. ' de type int est invalide ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default'].')');
				}

				if($definition->type=='related')
				{
					// verification du 2ieme argument des related
					if($_GET['relations']==1 && !property_exists(${'instance_'.strtolower($object)}, $definition->object_relation))
					{
						dump_error('Attribut '.$object.'->'.$definition->object_relation.' inconnu pour la relation '.$object. '->'.$name);
					}

					// verification du 3ieme argument des related
					if($_GET['relations']==1 && isset(${'instance_'.strtolower(${'instance_'.strtolower($object)}->{$definition->object_relation}->object_relation)}) && !property_exists(${'instance_'.strtolower(${'instance_'.strtolower($object)}->{$definition->object_relation}->object_relation)}, $definition->related_relation))
					{
						dump_error('Attribut '.${'instance_'.strtolower($object)}->{$definition->object_relation}->object_relation.'->'.$definition->related_relation.' inconnu pour la relation related de l\'attribut '.$object. '->'.$name);
					}
				}
				else if($definition->isDbColumn() && property_exists($object, 'table') && ($definition->type=='many2one' || $definition->type=='extends'))
				{
					// verification des index pour les many2one, one2many et extends
					if($_GET['index']==1 && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && (!isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]) || !$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['type']) && strtolower($definition->objectName) === strtolower($object))
					{
						dump_warning('Aucun index sur la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' pour l\'attribut '.$object. '->'.$name);
						$missing_index[$object][] = array('table' => ${'instance_'.strtolower($object)}->table, 'column' => $name);
					}

					// verification des clés étrangères pour les many2one, one2many et extends.
					if($_GET['fk']==1 && isset(${'instance_'.strtolower($definition->object_relation)})
									  && property_exists(${'instance_'.strtolower($definition->object_relation)}, 'table')
									  && property_exists(${'instance_'.strtolower($object)}, 'table')
									  && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table])
									  && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name])
									  && !in_array(${'instance_'.strtolower($definition->object_relation)}->table.'.'.${'instance_'.strtolower($definition->object_relation)}->primary_key,$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['constraints'])
					)
					{
						dump_fatal('Aucune contrainte d\'intégrité sur la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' pour l\'attribut '.$object. '->'.$name. ' vers la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($definition->object_relation)}->table.'.'.${'instance_'.strtolower($definition->object_relation)}->primary_key);
						$missing_fkey[$object][] = array('table' => ${'instance_'.strtolower($object)}->table, 'column' => $name, 'linked_table' => ${'instance_'.strtolower($definition->object_relation)}->table, 'linked_column' => ${'instance_'.strtolower($definition->object_relation)}->primary_key);
					}
				}
			}

			if($definition->type!=='related' && $definition->type!=='many2many' && $definition->type!=='one2many' && $definition->type!=='workflow_status')
			{
				// verification de la présence de la colonne en strict mode
				if($_GET['strict']==1 && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table]) && $definition->isDbColumn() && strtolower($definition->objectName) === strtolower($object) && !isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]))
				{
					dump_strict('La colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' n\'existe pas pour l\'attribut '.$object. '->'.$name.' (isDbColumn() renvoi TRUE)');
					$missing_field[$object][] = array('table' => ${'instance_'.strtolower($object)}->table, 'column' => $name, 'type' => $definition->type, 'nullable' => !$definition->required);
				}
			}

			if($_GET['default']==1 && $definition->required!=true && $definition->editable && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]))
			{
				// verification des colonnes d'attributs optionnels
				if($definition->type!='extends' && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['nullable']==false && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']==null && !$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['autoincrement'])
				{
					dump_warning('L\'attribut '.$object. '->'.$name.' est optionnel mais la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' n`est pas nullable et n\'a pas de valeur par défaut');
				}
			}

			if($_GET['note']==1 && $definition->required===true && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]))
			{
				// verification des colonnes d'attributs obligatoires
				if(($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['nullable']==true) && !$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['autoincrement'])
				{
					dump_notice('L\'attribut '.$object. '->'.$name.' est obligatoire mais la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' est nullable');
				}
			}

			if($_GET['attr']==1 && $definition->editable && property_exists($object, 'table') && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]))
			{
				// verification des valeurs par défaut
				if(!($definition->default_value==NULL && $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default']=='CURRENT_TIMESTAMP') && $definition->default_value != $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default'] && !$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['autoincrement'])
				{
					dump_error('La valeur par défaut de l\'attribut '.$object. '->'.$name.' ne correspond pas avec celle de la colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' ('.var_export($definition->default_value,true).' != '.var_export($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['default'],true).')');
				}
			}

			if($_GET['engine']==1 && property_exists($object, 'table') && !$definition->isVirtual() && isset($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]))
			{
				// test du format de stockage
				if($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['collation']!=null && substr($db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['collation'],0,4)!='utf8')
				{
					dump_warning('La colonne '.${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table.'.'.$name.' n\'est pas au format UTF-8 ('.$db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['collation'].')');
					$wrong_field_charset[$object][] = array('table' => ${'instance_'.strtolower($object)}->table, 'column' => $name, 'type' => $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['column_type'], 'nullable' => $db_struct[${'instance_'.strtolower($object)}->database.'.'.${'instance_'.strtolower($object)}->table][$name]['nullable']);
				}
			}
		}
	}

	if($_GET['prop']==1 && !$test_primary_key)
	{
		dump_error('L\'objet '.$object.' n\'a pas d\'attribut de type "primary key"');
	}
}

// verification des templates
if($_GET['tmpl']==1)
{
	foreach($templates as $template)
	{
		$pathinfo = pathinfo($template);

		libxml_use_internal_errors(true);
		if (($xml = simplexml_load_file($template, 'SimpleXMLElement',LIBXML_NOEMPTYTAG))===FALSE)
		{
			dump_template_error('Impossible d\'ouvrir le template '.$template.', erreur :<br/><br/>'.var_export(libxml_get_errors(),true));
			continue;
		}

		if(!isset($xml->attributes('xsi',true)->noNamespaceSchemaLocation))
		{
			dump_template_warning('Aucun XSD indiqué dans le template '.$template);
		}
		else
		{

			if(!file_exists($pathinfo['dirname'].'/'.$xml->attributes('xsi',true)->noNamespaceSchemaLocation))
			{
				dump_template_error('Impossible de lire le XSD '.$pathinfo['dirname'].'/'.$xml->attributes('xsi',true)->noNamespaceSchemaLocation.' du template '.$template);
			}
		}
	}
}
?>
<h3>Résolutions :</h3>
<pre class="info">
<?php
	foreach(ORM::getDeclaredObjectsList() as $object)
	{
		if(!isset($missing_table[$object]) &&
		   !isset($wrong_engine[$object]) &&
		   !isset($wrong_charset[$object]) &&
		   !isset($wrong_field_charset[$object]) &&
		   !isset($missing_index[$object]) &&
		   !isset($missing_fkey[$object]) &&
		   !isset($missing_field[$object]))
		   continue;

		echo '<br/><span class="sql-comment">-- ', $object, '</span><br/><br/>';

		if(isset($missing_table[$object]))
		foreach($missing_table[$object] AS $o)
		{
			$hORM = ORM::getORMInstance($object);
			echo $hORM->generateSQLcreateObject(), PHP_EOL;
		}

		if(isset($wrong_engine[$object]))
		foreach($wrong_engine[$object] AS $engine)
		{
			echo 'ALTER TABLE `', $engine['table'], '` ENGINE = INNODB;', PHP_EOL;
		}

		if(isset($wrong_charset[$object]))
		foreach($wrong_charset[$object] AS $charset)
		{
			echo 'ALTER TABLE `', $charset['table'], '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;', PHP_EOL;
		}

		if(isset($wrong_field_charset[$object]))
		foreach($wrong_field_charset[$object] AS $charset)
		{
			echo 'ALTER TABLE `', $charset['table'], '` CHANGE `', $charset['column'],'` `', $charset['column'],'` ', $charset['type'],' CHARACTER SET utf8 COLLATE utf8_general_ci ', $charset['nullable'] ? '': 'NOT NULL' ,';', PHP_EOL;
		}

		if(isset($missing_index[$object]))
		foreach($missing_index[$object] AS $index)
		{
			echo 'CREATE INDEX `', $index['column'], '_idx` ON `', $index['table'], '` (`', $index['column'],'`);', PHP_EOL;
		}

		if(isset($missing_fkey[$object]))
		foreach($missing_fkey[$object] AS $fk)
		{
			echo 'ALTER TABLE `', $fk['table'], '` ADD FOREIGN KEY (`', $fk['column'], '`) REFERENCES `', $fk['linked_table'], '` (`', $fk['linked_column'], '`) ON DELETE RESTRICT ON UPDATE RESTRICT;', PHP_EOL;
		}

		if(isset($missing_field[$object]))
		foreach($missing_field[$object] AS $field)
		{
			$not_proposed = false;
			switch($field['type'])
			{
				case 'many2one':
					$type = 'bigint(21) unsigned';
					$default_value = '0';
					break;
				case 'int':
					$type = 'int(11)';
					$default_value = '0';
					break;
				case 'date':
					$type = 'date';
					$default_value = '0000-00-00';
					break;
				case 'time':
					$type = 'time';
					$default_value = '00:00:00';
					break;
				case 'textarea':
					$type = 'text  CHARACTER SET utf8 COLLATE utf8_general_ci';
					$default_value = '';
					break;
				case 'text':
					$type = 'varchar(45)  CHARACTER SET utf8 COLLATE utf8_general_ci';
					$default_value = '';
					break;
				case 'checkbox':
					$type = 'tinyint(1) unsigned';
					$default_value = '0';
					break;
				default:
					$not_proposed = true;
			}
			if(!$not_proposed)
			{
				echo 'ALTER TABLE `', $field['table'], '` ADD `', $field['column'],'` ', $type, ' ', $field['nullable'] ? '': 'NOT NULL', ' DEFAULT \'', $default_value,'\'' ,';', PHP_EOL;
			}
		}
	}
?>
</pre>
<h1 class='score'>Fail score : <span style='color:<?= ($score==0?'green':'red'); ?>'><?= $score; ?></span></h1>
