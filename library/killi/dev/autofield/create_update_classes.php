#!/usr/bin/php
<?php

$fkeys = $keys = array();
$clstype;

function get_results($query) {
	$result = mysql_query($query);
	$row = array();

	while(($r = mysql_fetch_array($result, MYSQL_ASSOC))) {
		$row[] = $r;
	}

	mysql_free_result($result);

	return $row;
}

function build_fielddefinition($column) {
	global $fkeys, $keys, $clstype;

	$clscontent = '
			$this->' . $column['COLUMN_NAME'] . ' = new FieldDefinition
			(
				$this,
				NULL,
				\'' . join(' ', array_map("ucfirst", explode('_', $column['COLUMN_NAME']))) . '\',
				\'';

		 switch($column['DATA_TYPE']) {
			case "float":
			case "real":
			case "decimal":
			case "double precision":
				$clscontent .= 'int';

				break;

			case "smallint":
			case "int":
			case "bigint":
			case "tinyint":
				if($column['COLUMN_KEY'] == 'PRI') {
					if($clstype == 'simple') {
						$clscontent .= 'primary key';
					} else {
						$clscontent .= 'extends:' . join('', array_map('ucfirst', explode('_', $fkeys[$column['COLUMN_NAME']]['REFERENCED_TABLE_NAME'])));
					}
				} elseif($column['COLUMN_TYPE'] == 'tinyint(1)') {
					$clscontent .= 'checkbox';
				} elseif(in_array($column['COLUMN_NAME'], $keys)) {
					$index = $fkeys[$column['COLUMN_NAME']];
					$clscontent .= 'many2one:' . join('', array_map('ucfirst', explode('_', $fkeys[$column['COLUMN_NAME']]['REFERENCED_TABLE_NAME'])));
				} else {
					$clscontent .= 'int';
				}

				break;

			case "timestamp":
				$clscontent .= 'date';

				break;

			case "varchar":
            case "char":
                $clscontent .= 'text';
                break;
            case "text":
                $clscontent .= 'textarea';
                break;
			default:
				$clscontent .= 'text';
                break;

		 }

		 $clscontent .= '\',
				' . ($column['IS_NULLABLE'] == 'YES' ? 'FALSE' : 'TRUE') . ',
				array()
			) ;
		 ';

	return $clscontent;
}

$conffile = (isset($argv[1]) ? $argv[1] : "conf.ini");

if(!is_readable($conffile)) {
	echo "$conffile does not exists";
	exit();
}

$conf = parse_ini_file($conffile, TRUE);
if(($conn = mysql_connect($conf['DB']['db_host'], $conf['DB']['db_user'], $conf['DB']['db_passwd'])) == FALSE) {
	echo mysql_error();
	exit();
}

$path = (isset($conf['SYS']['project_path']) ? $conf['SYS']['project_path']  : "../");

$read_dump = FALSE;
$dump_file = sprintf("dump_killischema_%s", $conf['DB']['db_schema']);

if(is_readable($dump_file)) {
	$time_diff = (isset($conf['SYS']) && isset($conf['SYS']['dump_lifetime']) ? intval($conf['SYS']['dump_lifetime']) : 86400);
	$time_file = filectime($dump_file);
	$time_now = time();

	if(($time_now - $time_file) < $time_diff) {
		$read_dump = TRUE;
	} else {
		@unlink($dump_file);
	}
}

if($read_dump == TRUE) {
	$tables_data = unserialize(file_get_contents($dump_file));
} else {
	if(mysql_select_db("information_schema") == FALSE) {
		echo mysql_error();
		exit();
	}

	$query = sprintf("SELECT TABLE_NAME FROM TABLES WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME NOT LIKE 'killi%%' AND TABLE_NAME NOT LIKE 'workflow%%' AND TABLE_NAME NOT LIKE 'xmlrpc%%' AND TABLE_NAME NOT LIKE '%%_log'", mysql_real_escape_string($conf['DB']['db_schema']));
	$tables = get_results($query);

	if(!count($tables)) {
		echo "No tables to proceed";
		exit();
	}

	$tables_data = array();

	foreach($tables as $table) {
		$table_name = $table['TABLE_NAME'];
		$tables_data[$table_name] = array();

		$query= sprintf("SELECT COLUMN_NAME, IS_NULLABLE, DATA_TYPE, COLUMN_TYPE, COLUMN_KEY FROM COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' ORDER BY ORDINAL_POSITION", mysql_real_escape_string($conf['DB']['db_schema']), mysql_real_escape_string($table_name));
		$columns = get_results($query);

		foreach($columns as $i => $v) {
			$columns[$i]['INDEX'] = array();

			if(preg_match("/int/", $columns[$i]['DATA_TYPE'])) {		
				$query = sprintf("SELECT CONSTRAINT_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' AND COLUMN_NAME = '%s' ORDER BY ORDINAL_POSITION", mysql_real_escape_string($conf['DB']['db_schema']), mysql_real_escape_string($table_name), mysql_real_escape_string($columns[$i]['COLUMN_NAME']));
				$columns[$i]['INDEX'] = get_results($query);
			}
		}

		$tables_data[$table_name]['COLUMNS'] = $columns;
	}

	mysql_close($conn);

	file_put_contents($dump_file, serialize($tables_data));
}

$tables_name = array_keys($tables_data);

foreach($tables_data as $table_name => $data) {
	$raw = explode('_', $table_name);
	$main = $raw[0];
	$last = $raw[count($raw)-1];
	$keys = array();
	$fkeys = array();
	$columns_name = array();
	$pkey = NULL;
	$tplname = join('', $raw);
	$clsname = join('', array_map('ucfirst', $raw));
	$clsfile = "class.$clsname.php";
	$clsdescr = join(' ', array_map('ucfirst', $raw));
	$clstype = 'simple';
	$nbcolumns = count($data['COLUMNS']);
	$nbfkeys = 0;

	foreach($data['COLUMNS'] as $column) {
		if($column['INDEX'] != FALSE) {
			foreach($column['INDEX'] as $index) {
				if($index['REFERENCED_TABLE_NAME']) {
					$nbfkeys ++;
					$fkeys[$column['COLUMN_NAME']] = $index;
				}
			}
		}

		if($column['COLUMN_KEY'] == 'PRI' && is_null($pkey)) {
			$pkey = $column['COLUMN_NAME'];
		}

		$columns_name[] = $column['COLUMN_NAME'];
	}

	if(count($raw) > 1) {

		$keys = array_keys($fkeys);

		if(in_array($main, $tables_name) && in_array($main . '_id', $keys) && !in_array($last . '_id', $keys)) {
			$clstype = 'extended';
		}
	} 

	$fullpath = $path . 'class/' . $clsfile;

	if(!is_dir($path . 'class/'))
		mkdir($path . 'class/', 0755, TRUE);

	if(!file_exists($fullpath)) {
		$clscontent = '<?php

	class ' . $clsname . '
	{
		public $description			= \'' . $clsdescr . '\';
		public $database			= ' . $conf['DB']['db_const'] . ';
		public $table				= \'' . $table_name . '\';
		public $primary_key			= \'' . $data['COLUMNS'][0]['COLUMN_NAME'] . '\';
		public $log					= FALSE;

		//----------------------------------------------------------------
		function setDomain()
		{
			return TRUE;
		}
		//----------------------------------------------------------------
		function __construct()
		{
			$this->object_domain[] = array();

			';

			foreach($data['COLUMNS'] as $column) {
				$clscontent .= build_fielddefinition($column);
			}

			$clscontent .= '
		}
	}

	$object_list[] = new ' . $clsname . '();';

		file_put_contents($fullpath, $clscontent);
	} else {
		$clscontent = file_get_contents($fullpath);

		preg_match_all("/this->([a-z0-9\_]+)\s?=\s?new FieldDefinition\n\s+\(\n[^\;]+\;/m", $clscontent, $fields, PREG_OFFSET_CAPTURE);

		$last_pos = 0;
		$token_fields = array();

		foreach($fields[0] as $key => $value) {
			$last_pos = max(($value[1] + strlen($value[0])), $last_pos);
		}

		foreach($fields[1] as $field) {
			$token_fields[] = $field[0];
		}

		$fields_diff = array_diff($columns_name, $token_fields);

		if(count($fields_diff)) {
			foreach($fields_diff as $field) {
				$c = NULL;

				foreach($data['COLUMNS'] as $column) {
					if($column['COLUMN_NAME'] == $field) {
						$c = $column;
					}
				}

				$first = substr($clscontent, 0, $last_pos);
				$content = build_fielddefinition($c);
				$last = substr($clscontent, $last_pos + 1);

				$clscontent = $first . "\n" . $content . $last;
			}

			file_put_contents($fullpath, $clscontent);
		}
	}

	$clsnamemth = $clsname . 'Method';
	$clsfile = 'class.' . $clsnamemth . '.php';

	if(!is_dir($path . 'class/'))
		mkdir($path . 'class/', 0755, TRUE);

	$fullpath = $path . 'class/' . $clsfile;

	if(!file_exists($fullpath)) {
		$clscontent = '<?php

	class ' . $clsnamemth . ' extends Common
	{
		//..................................................
		public function getReferenceString(array $id_list, array &$reference_list)
		{
			$hORM = ORM::getORMInstance(\'' . strtolower($clsname)  . '\');
			$hORM->read($id_list, $object_list);

			foreach($object_list as $key=>$value)
			{
				';
				
				if(in_array('nom', $columns_name)) {
					$clscontent .= '$reference_list[$key] = $value[\'nom\'][\'value\'];';
				} else {
					$clscontent .= '//$reference_list[$key] = $value[\'<must define the reference key>\'][\'value\']';
				}

				$clscontent .= '
			}

			return TRUE;
		}
	}

?>';

		file_put_contents($fullpath, $clscontent);
	}

	if(!is_dir($path . 'template/'))
		mkdir($path . 'template/', 0755, TRUE);

	$fullpath = $path . 'template/' . $tplname . '.xml';

	if(!file_exists($fullpath)) {
		$tplxml = new SimpleXMLElement("<data></data>");
		$tplxml->addChild("header");
		$tplxml->addChild("menu");
		$title = $tplxml->addChild("title");
		$title->addAttribute("string", "Edition " . $clsdescr);
		$navigator = $tplxml->addChild("navigator");
		$navigator->addAttribute("object", $tplname);

		$list = $tplxml->addChild("list");
		$list->addAttribute("object", $tplname);
		$list->addAttribute("key", $pkey);

		foreach($data['COLUMNS'] as $column) {
			if($column['COLUMN_KEY'] != 'PRI') {
				$field = $list->addChild("field");
				$field->addAttribute("object", $tplname);
				$field->addAttribute("attribute", $column['COLUMN_NAME']);
				$field->addAttribute("search", "1");
			}
		}

		$create = $tplxml->addChild("create");
		$create->addAttribute("object", $tplname);
		$create->addAttribute("action", sprintf("%s.create", $tplname));

		foreach($data['COLUMNS'] as $column) {
			if($column['COLUMN_KEY'] != 'PRI') {
				$field = $create->addChild("field");
				$field->addAttribute("object", $tplname);
				$field->addAttribute("attribute", $column['COLUMN_NAME']);
			}
		}

		$form = $tplxml->addChild("form");
		$form->addAttribute("object", $tplname);
		$notebook = $form->addChild("notebook");
		$page = $notebook->addchild("page");
		$page->addAttribute("string", "Informations de base");

		foreach($data['COLUMNS'] as $column) {
			if($column['COLUMN_KEY'] != 'PRI') {
				$field = $notebook->addChild("field");
				$field->addAttribute("object", $tplname);
				$field->addAttribute("attribute", $column['COLUMN_NAME']);
			}
		}

		$tplxml->asXML($fullpath);
		$finalxml = dom_import_simplexml($tplxml)->ownerDocument;
		$finalxml->formatOutput = true;
		$finalxml->save($fullpath);
	} else {
		$tplxml = simplexml_load_file($fullpath);
		$childs = $tplxml->children();
		$diff = FALSE;

		if(isset($childs->list)) {
			$token_fields = array();
			$fields = $childs->list->field;

			foreach($fields as $field) {
				$token_fields[] = $field["attribute"][0];
			}

			$fields_diff = array_diff($columns_name, $token_fields);
			$diff = (count($fields_diff) > 0);

			foreach($data['COLUMNS'] as $column) {
				if(in_array($column['COLUMN_NAME'], $fields_diff) && $column['COLUMN_KEY'] != 'PRI') {
					$field = $childs->list->addChild("field");
					$field->addAttribute("object", $tplname);
					$field->addAttribute("attribute", $column['COLUMN_NAME']);
					$field->addAttribute("search", "1");
				}
			}
		}

		if(isset($childs->create)) {
			$token_fields = array();
			$fields = $childs->create->field;

			foreach($fields as $field) {
				$attributes = $field->attributes();
				$token_fields[] = $attributes["attribute"];
			}

			$fields_diff = array_diff($columns_name, $token_fields);

			foreach($data['COLUMNS'] as $column) {
				if(in_array($column['COLUMN_NAME'], $fields_diff) && $column['COLUMN_KEY'] != 'PRI') {
					$field = $childs->create->addChild("field");
					$field->addAttribute("object", $tplname);
					$field->addAttribute("attribute", $column['COLUMN_NAME']);
				}
			}
		}

		if(isset($childs->form)) {
			$token_fields = array();
			$fields = $childs->form->field;

			foreach($fields as $field) {
				$attributes = $field->attributes();
				$token_fields[] = $attributes["attribute"];
			}

			$fields_diff = array_diff($columns_name, $token_fields);

			foreach($data['COLUMNS'] as $column) {
				if(in_array($column['COLUMN_NAME'], $fields_diff) && $column['COLUMN_KEY'] != 'PRI') {
					$field = $childs->form->addChild("field");
					$field->addAttribute("object", $tplname);
					$field->addAttribute("attribute", $column['COLUMN_NAME']);
				}
			}
		}

		if($diff == TRUE) {
			$tplxml->asXML($fullpath);
			$finalxml = dom_import_simplexml($tplxml)->ownerDocument;
			$finalxml->formatOutput = true;
			$finalxml->save($fullpath);
		}
	}
}

?>
