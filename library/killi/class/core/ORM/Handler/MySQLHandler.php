<?php

namespace Killi\Core\ORM\Handler;

/**
 *  Traitant MySQL de l'ORM Killi
 *
 *  @package killi
 *  @class MySQLHandler
 *  @Revision $Revision: 4657 $
 */

use \FieldDefinition;
use \ExtendedFieldDefinition;

use Killi\Core\ORM\Debug\Performance;

use Killi\Core\ORM\ObjectManager;

use \TextFieldDefinition;
use \TextAreaFieldDefinition;
use \ReferenceFieldDefinition;

// Exceptions de l'ORM
use Killi\Core\ORM\Exception\InternalErrorException;
use Killi\Core\ORM\Exception\JoinConflictException;
use Killi\Core\ORM\Exception\ObjectDefinitionException;
use Killi\Core\ORM\Exception\UndeclaredObjectException;

// Exceptions externes
use \CantDeleteException;
use \InsertErrorException;
use \MismatchObjectException;

class MySQLHandler extends AbstractHandler
{
	use Performance;
	use ObjectManager;

	private $_hDB				= NULL;
	private $_extended_object	= NULL;
	private $_object_table	 	= NULL;
	private $_object_database	= NULL;
	private $_object_key_name	= NULL;
	private $_domain_with_join	= array();

	private static $desc_table		= array();
	private static $func_call_stack = array();

	public function boot()
	{
		global $hDB; //---On la recup depuis l'index ;-)
		$this->_hDB				= $hDB;

		if (!isset($this->_object->table))
		{
			throw new Exception("Object table is not defined in ".$this->_object_name);
		}

		if (!isset($this->_object->database))
		{
			throw new Exception("Object database is not defined in ".$this->_object_name);
		}

		$this->_object_table	= $this->_object->table;
		$this->_object_database = $this->_object->database;

		$primary_key = $this->_object->primary_key;
		$raw = explode(',', $primary_key);
		if(count($raw) == 1)
		{
			$this->_object_key_name	= $primary_key;
		}
		else
		{
			$this->_object_key_name = $raw;
		}
		$this->_extended_object	= ORM::getObjectInstance($this->_object_name, $this->_with_domain);
	}

	protected function read_process_outcast($fields_list, &$object_list)
	{
		foreach($fields_list as $attribute_name)
		{
			foreach($object_list as $object_id=>$object)
			{
				if(!isset($object_list[$object_id][$attribute_name]))
				{
					continue;
				}

				$casted_value = $object_list[$object_id][$attribute_name]['value'];

				$this->_object->$attribute_name->outCast($casted_value);

				// on ajoute/remplace les valeurs de l'outCast dans le tableau du field
				foreach($casted_value as $casted_value_name=>$casted_value_content)
				{
					$object_list[$object_id][$attribute_name][$casted_value_name] = $casted_value_content;
				}

				Rights::getRightsByAttribute ( $this->_object->$attribute_name->objectName, $attribute_name, $read, $write );

				$object_list[$object_id][$attribute_name]['editable'] = ((isset($object_list[$object_id][$attribute_name]['editable']) ? $object_list[$object_id][$attribute_name]['editable'] : TRUE) && $write);
			}
		}
		return TRUE;
	}

	protected function map_sql_alias($sql_alias, $prefix = NULL)
	{
		if($prefix != NULL)
		{
			$prefix .= '.';
		}

		$vars = array();
		preg_match_all('(%[a-zA-Z0-9_]+%)', $sql_alias, $vars);
		foreach($vars AS $var)
		{
			foreach($var AS $v)
			{
				$var_name = substr($v, 1, -1);
				$v2 = $prefix . '`'.$var_name.'`';
				$sql_alias = str_replace($v, $v2, $sql_alias);
			}
		}
		return $sql_alias;
	}

	protected function read_process_query(&$object_list, $id_list, $db_fields_select, $many2one_list)
	{
		//----------
		//
		//  Exécution de la récupération en base de données.
		//
		//  Champs nécessaire :
		//    - $db_fields_select		: Liste des champs en base de données à récupérer
		//    - $many2one_list			: Liste des champs many2one pour optimisation de récupération de reference
		//    - $id_list				: Liste des ids
		//    - $object_list			: Tableau d'objets contenant le résultat.
		//----------

		/* Traitement des références sur les many2one (Optimisation effectuée en base pour éviter un appel à getReferenceString). */
		$many2one_reference = array();
		$query_joins = '';
		$alias_id = 0;
		foreach($many2one_list AS $field_name)
		{
			$field_object = $this->_object->$field_name;

			/* S'il n'y a pas de relation sur le champs, impossible d'obtenir de références. */
			if(empty($field_object->object_relation))
			{
				throw new ObjectDefinitionException('Pas d\'object_relation sur le champ : ' . $field_object->name);
			}

			/* Pas de jointure possible sur un attribut calculé par fonction PHP. */
			if($field_object->isFunction())
			{
				continue;
			}

			$lnk_obj = ORM::getObjectInstance($field_object->object_relation);

			/* Si l'objet lié n'est pas un objet sur lequel il est possible de faire une jointure en base, on ignore le calcul de référence. */
			if(!isset($lnk_obj->database))
			{
				continue;
			}

			/* Si les bases de données sont différentes, on évite de faire la jointure. */
			if($lnk_obj->database != $this->_object->database)
			{
				continue;
			}

			/* Si l'objet lié n'a pas d'attribut reference, il est impossible de déterminer comment calculer la référence. */
			if(!isset($lnk_obj->reference))
			{
				continue;
			}

			$ref_field = $lnk_obj->reference;

			/* Si l'attribut reference de l'objet liée n'est pas une chaine de caractère, alors c'est que ce n'est pas la référence de l'objet. */
			if(!is_string($ref_field))
			{
				continue;
			}

			/* Si l'attribut reference de l'objet liée pointe sur un attribut qui n'est pas de type fieldDefinition, alors ce n'est pas une référence valide ! */
			if(!$lnk_obj->$ref_field instanceof FieldDefinition)
			{
				// FIXME: Peut être lever une exception dans ce cas ??
				continue;
			}

			/* Si l'attribut reference de l'objet liée utilise un champ appartenant à son parent, on évite de faire la jointure. */
			if($lnk_obj->$ref_field->objectName != get_class($lnk_obj))
			{
				continue;
			}

			/* Si l'attribut reference de l'objet liée est calculé par fonction PHP, on ne peut pas faire la jointure. */
			if($lnk_obj->$ref_field->isFunction())
			{
				continue;
			}

			$alias_id++;
			$m2o_table_alias = 'orm_m2o_' . $alias_id;
			$field_alias = 'orm_' . $ref_field . '_rf_' . $alias_id;
			$query_joins .= 'LEFT JOIN ' . $lnk_obj->database . '.' . $lnk_obj->table . ' ' . $m2o_table_alias . ' ON ' . $m2o_table_alias . '.' . $lnk_obj->primary_key . '=orm_main.' . $field_name . ' ';
			if($lnk_obj->$ref_field->isSQLAlias())
			{
				$calc = $this->map_sql_alias($lnk_obj->$ref_field->sql_alias, $m2o_table_alias);
				$many2one_reference[$field_alias] = array(	'table_alias' => '',
															'field_alias' => $field_alias,
															'field' => $calc,
															'field_name' => $field_name);
			}
			else
			{
				if($lnk_obj->$ref_field->type == 'related')
				{
					// TODO: Optim on related.
					continue;
				}
				$many2one_reference[$field_alias] = array(	'table_alias' => $m2o_table_alias,
															'field_alias' => $field_alias,
															'field' => $ref_field,
															'field_name' => $field_name);
			}
		}

		/* On ajoute l'attribut de référence s'il est présent et qu'il correspond à un champ de la table. */
		if(isset($this->_object->reference) && in_array( $this->_object->reference, $db_fields_select ))
		{
			$many2one_reference['orm_main_ref'] = array('table_alias' => 'orm_main',
														'field_alias' => 'orm_main_ref',
														'field' => $this->_object->reference,
														'field_name' => $this->_object->primary_key);
		}

		/**
		 * Traitement des id.
		 */

		$number_of_ids = count($id_list);

		if($number_of_ids == 0)
		{
			throw new Exception('Nombre d\'id incorrect à cet instant d\'éxécution !');
		}

		/* Traitement des id de clé primaires multiple. */
		$combination_id_list = $id_list;
		if(is_array($this->_object_key_name))
		{
			//$multiple_id_list = array();
			$combination_id_list = array();
			foreach($id_list AS $id)
			{
				$raw = explode(',', $id);
				$idx = 0;
				foreach($this->_object_key_name AS $k)
				{
					if(isset($raw[$idx]) && !empty($raw[$idx]))
					{
						//$multiple_id_list[$k][] = $raw[$idx];
						$combination_id_list[$id][$k] = $raw[$idx];
					}
					else
					{
						$combination_id_list[$id][$k] = NULL;
					}
					$idx++;
				}
			}
		}

		// ne devrait pas arriver !
		if(count($db_fields_select)==0)
		{
			$db_fields_select=array('*');
		}

		$query	= 'SELECT DISTINCT '.implode(',', $db_fields_select) . ' ';

		foreach($many2one_reference AS $field_alias => $ref)
		{
			if(!empty($ref['table_alias']))
			{
				$query .= ', ' . $ref['table_alias'] . '.' . $ref['field'] . ' AS ' . $ref['field_alias'] . ' ';
			}
			else
			{
				$query .= ', ' . $ref['field'] . ' AS ' . $ref['field_alias'] . ' ';
			}
		}

		$query .= 'FROM ' . $this->_object_database . '.' . $this->_object_table .  ' orm_main '
				. $query_joins
				. 'WHERE ';

		if(is_array($this->_object_key_name))
		{
			$or = array();
			$qs = array();
			foreach($combination_id_list AS $id)
			{
				$combination = array();
				foreach($this->_object_key_name AS $k)
				{
					if($id[$k] !== NULL)
					{
						$combination[] = 'orm_main.`'. $k .'`=\''.$this->_hDB->db_escape_string($id[$k]).'\'';
					}
					else
					{
						$combination[] = 'orm_main.`'. $k .'` IS NULL';
					}
				}

				$f = '(' . join(' AND ', $combination) . ')';
				$qs[] = $f;
			}
			foreach($this->_object_key_name AS $k)
			{
				$or[] = 'orm_main.' . $k;
			}

			$query .= '('.join(' OR ', $qs).')'
					.' ORDER BY ' . join(',', $or) . ' '
					. 'LIMIT ' . $number_of_ids;
		}
		else
		{
			$okn =$this->_object_key_name;
			$is_text_primary_key = FALSE;
			if($this->_object->$okn instanceof TextFieldDefinition ||
			   $this->_object->$okn instanceof TextAreaFieldDefinition ||
			   $this->_object->$okn instanceof ReferenceFieldDefinition)
			{
				$is_text_primary_key = TRUE;
			}

			$query .= 'orm_main.' . $this->_object_key_name . ' IN ('.($is_text_primary_key ? '\''.implode('\',\'', $combination_id_list).'\'' : implode(',', $combination_id_list)).') '
					.' ORDER BY orm_main.' . $this->_object_key_name . ' '
					. 'LIMIT ' . $number_of_ids;
		}

		//echo $query, PHP_EOL;

		//---Process query
		$result		= NULL;
		$numrows	= NULL;
		$this->_hDB->db_select($query, $result, $numrows);

		if((TOLERATE_MISMATCH == FALSE) && ($number_of_ids > $numrows))
		{
			throw new MismatchObjectException('Number result for "'.$this->_object_name.'" does not match number ID ! (Number=' . $number_of_ids . ', Result=' . $numrows . ')');
		}

		//---On deroule les resultats
		while(($row = $result->fetch_assoc()) != NULL)
		{
			foreach($row AS $key => $value)
			{
				if(is_array($this->_object_key_name))
				{
					$v = array();
					foreach($this->_object_key_name AS $k)
					{
						$v[] = $row[$k];
					}
					$object_id = join(',', $v);

					/**
						* Dans le cas où la primary key n'est pas présente dans le jeu de résultats (cas des clés primaires multiples), on ajoute l'object_id généré au tableau de résultats.
						*/
					$object_list[$object_id][$this->_object->primary_key]['value'] = $object_id;
				}
				else
				{
					$object_id = $row[$this->_object_key_name];
				}

				if(isset($many2one_reference[$key]) && !empty($many2one_reference[$key]))
				{
					$f = $many2one_reference[$key]['field_name'];
					$object_list[$object_id][$f]['reference'] = $value;
				}
				else
				if(property_exists($this->_object, $key))
				{
					$object_list[$object_id][$key]['value'] = $value;
				}
			}
		}

		if((TOLERATE_MISMATCH == FALSE) && ($number_of_ids != count($object_list)))
		{
			throw new MismatchObjectException('Number result for "'.$this->_object_name.'" does not match number ID ! (Number=' . $number_of_ids . ', Result=' . count($object_list) . ')');
		}

		if(!TOLERATE_MISMATCH)
		{
			foreach($object_list AS $o_id => $o_data)
			{
				if(count($o_data) == 0)
				{
					$message = 'L\'ORM n\'a pas su récupérer l\'élément ' . $o_id . ' avec la requête ! ';
					$message .= 'Requête : ' . $query;
					throw new InternalErrorException($message);
				}
			}
		}

		$result->free();
		unset($query);

		return TRUE;
	}

	/**
	 * @param array $original_id_list	tableau des id à lire
	 * @param array &$object_list		tableau des resultats
	 * @param array $fields				champs necessaires
	 */
	function read($id_list, &$object_list, array $fields = array())
	{
		$db_fields = array();
		$sql_alias_list			= array();
		$many2one_list			= array();

		/**
		 * Constitution des tableaux de champs par types.
		 */

		foreach($fields AS $field_name)
		{
			$field_object = $this->_object->$field_name;

			/* Les champs virtuels ne sont pas traités. */
			if($field_object->isVirtual())
			{
				continue;
			}

			/* Champs récupéré sur l'objet parent. */
			if($field_object->objectName != $this->_object_name)
			{
				continue;
			}

			/* Champs calculés SQL */
			if($field_object->isSQLAlias())
			{
				$sql_alias_list[$field_name] = $field_name;
				continue;
			}

			if($field_object->isDbColumn())
			{
				$db_fields[$field_name] = $field_name;
			}

			/* Dispatch */
			switch($field_object->type)
			{
				case 'many2one':
					$many2one_list[] = $field_name;
					break;
				case 'related':
					$join_attr = $field_object->object_relation;
					/* Ajout de l'attribut de jointure s'il n'est pas présent dans la liste. */
					if(!isset($fields[$join_attr]))
					{
						/* L'attribut de jointure se trouve sur le parent. */
						if($this->_object->$join_attr->objectName !== $this->_object_name)
						{

						}
						else
						/* L'attribut de jointure est calculé par fonction PHP. */
						if($this->_object->$join_attr->isFunction())
						{

						}
						else
						/* L'attribut de jointure est un alias SQL. */
						if($this->_object->$join_attr->isSQLAlias())
						{
							$sql_alias_list[$join_attr] = $join_attr;
						}
						/* L'attribut de jointure est présent dans la table de l'objet en base de données. */
						else
						if($field_object->isDbColumn())
						{
							$db_fields[$join_attr] = $join_attr;
						}
					}
					break;
				default:
					break;
			}
		}

		/**
		 * ACHTUNG: Pas bô mais nécessaire...
		 */
		foreach($this->_object AS $field_name => $field)
		{
			if($field instanceof FieldDefinition)
			{
				if($field->objectName == $this->_object_name)
				{
					if($field->type == 'many2one' && $field->isDbColumn())
					{
						$db_fields[$field_name] = $field_name;
					}
				}
			}
		}

		/**
		 * Préparation des champs pour la requête de lecture.
		 */
		$db_fields_select = array();

		/* On selectionne les colonnes de la table. */
		foreach($db_fields AS $field_name)
		{
			$db_fields_select[$field_name] = 'orm_main.' . '`'.$field_name.'`'; // on select
		}

		/* On selectionne les SQL Alias. */
		foreach($sql_alias_list AS $field_name)
		{
			$calc = $this->map_sql_alias($this->_object->$field_name->sql_alias, 'orm_main');
			$db_fields_select[$field_name] = $calc . ' AS ' . $field_name;
		}
		unset($sql_alias_list);

		//----------
		//
		// Récupération via BDD
		//
		//----------
		$this->read_process_query($object_list, $id_list, $db_fields_select, $many2one_list);
		$this->read_process_outcast(array_keys($db_fields_select), $object_list);
		unset($many2one_list);
		unset($db_fields_select);

		//----------
		//
		//  Fin de la récupération en base de données.
		//
		//----------

		if(!is_array($object_list))
		{
			$object_list=array();
		}

		/* On élimine les objets vide : Dans le cas de tolerate_mismatch les objets peuvent ne pas exister ! */
		foreach($id_list AS $id)
		{
			if(isset($object_list[$id]) && count($object_list[$id]) == 0)
			{
				unset($object_list[$id]);
			}
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	private function _findExtendedObject( $object_name, &$object_list )
	{
		$object_list[] = ORM::getObjectInstance($object_name);
		$i = count( $object_list ) ;
		$i-- ;

		foreach( $object_list[ $i ] as $k => $v )
		{
			if (is_object($object_list[ $i ]->$k) && is_object($v) && $v instanceof FieldDefinition
					&& !is_array($object_list[ $i ]->$k->type) && $object_list[ $i ]->$k->type === 'extends')
			{
				$tmp = $object_list[ $i ]->$k->object_relation;
				if(isset( $tmp ) && $tmp != '')
				{
					$this->_findExtendedObject($tmp, $object_list);
				}
			}
		}
	}

	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE )
	{
		$OL = array() ;
		if( is_object( $this->_object ) === true )
		{
			$this->_findExtendedObject( $this->_object_name, $OL ) ;
			$OL = array_reverse( $OL ) ;
		}

		$query  = NULL ;
		$total = count($OL);

		for( $i = 0 ; $i < $total; $i++ )
		{
			//---Call Pre-read
			$objectName = get_class($OL[$i]);
			$method = $objectName . 'Method';
			if(class_exists($method))
			{
				$hInstance = ORM::getControllerInstance($objectName);
				//$hInstance = new $method();
				$hInstance->preCreate($object_data,$object_data);
			}

			$emptySet = true;
			foreach( $OL[ $i ] as $field_name => $field )
			{
				/* On ne prend que les FieldDefinition déclarés sur l'objet. */
				if(!($field instanceof FieldDefinition))
				{
					continue;
				}

				/* On évite les champs qui ne sont pas sur l'objet courant (càd récupéré du parent). */
				if($field->objectName != $objectName)
				{
					continue;
				}

				/* On retire les champs non présent en base */
				if(!$field->isDbColumn())
				{
					continue;
				}

				/* Si le champ n'a pas de valeur définis. */
				if(!isset($object_data[$field_name]))
				{
					continue;
				}

				/* On évite les many2one non renseigné. */
				if($field->type == 'many2one' && $object_data[$field_name] == 0)
				{
					continue;
				}

				/* On n'enregistre pas les champs vide. */
				if($object_data[$field_name] === '' && $object_data[$field_name] !== FALSE)
				{
					continue;
				}

				if(!is_array($field->type) && preg_match( '/^primary/i', $field->type) && $total > 1)
				{
					continue;
				}

				$field->inCast($object_data[ $field_name ]);

				$emptySet = false;

				if($query == '' )
				{
					if( $ignore_duplicate === false )
					{
						$query = 'INSERT INTO ' . $OL[ $i ]->{'database'} . '.' . $OL[ $i ]->{'table'} . ' SET ' ;
					}
					else
					{
						$query = 'INSERT IGNORE INTO ' . $OL[ $i ]->{'database'} . '.' . $OL[ $i ]->{'table'} . ' SET ' ;
					}
					$query .= '`'.$field_name.'`' . '=\'' . $this->_hDB->db_escape_string( $object_data[ $field_name ] ) . '\'';
				}
				else
				{
					$query .= ', ' . '`'.$field_name.'`' . '=\'' . $this->_hDB->db_escape_string( $object_data[ $field_name ] ) . '\'' ;
				}
			}

			/* Cas de la création d'un objet parent d'un extended sans définition des attributs du père. */
			if($emptySet)
			{
				if(!isset($object_data[$OL[$i]->primary_key]))
				{
					$query = 'INSERT INTO ' . $OL[ $i ]->database . '.' . $OL[ $i ]->table . ' (' . $OL[$i]->primary_key . ') VALUES (NULL);';
				}
			}

			if( $query != '' )
			{
				$nbrow = 0 ;
				$id = 0;
				if(is_array($on_duplicate_key))
				{
					$query .= " ON DUPLICATE KEY ".$this->get_write_sql(NULL, $on_duplicate_key, FALSE, FALSE, FALSE, FALSE);
				}
				$this->_hDB->db_execute( $query, $nbrow ) ;
				$this->_hDB->db_insert_id($id);

				if(empty($object_data[  $OL[ $i ]->primary_key ]))
				{
					if($id == 0 && $on_duplicate_key === FALSE)
					{
						/* Si l'objet existe déjà en base, on ignore la suite de la création. */
						if($ignore_duplicate === true)
						{
							return TRUE;
						}

						throw new InsertErrorException('i 1 Unable to insert element : ' . $this->_object_name);
					}
					$object_data[  $OL[ $i ]->primary_key ] = $id;
				}

				$query = '' ;
			}
		}

		$object_id = $object_data[$this->_object->primary_key];

		if($object_id == 0 and !$on_duplicate_key)
		{
			throw new InsertErrorException('Unable to insert element : ' . $this->_object_name);
		}

		//---Call Post Create
		$method = $this->_object_name . 'Method';
		if(class_exists($method))
		{
			$hInstance = ORM::getControllerInstance($this->_object_name);
			$hInstance->postCreate($object_id,$object_id);
		}

		return true;
	}
	//-------------------------------------------------------------------------
	private function get_write_sql(
		$object_id			  ,
		array $object		   ,
		$ignore_duplicate=FALSE ,
		$with_where_clause=TRUE ,
		$with_set = TRUE		,
		$with_table_name=TRUE )
	{
		$OL = array() ;
		$this->_findExtendedObject( $this->_object_name, $OL ) ;

		//---On cree le tableau des objets
		$object_list = array();

		foreach( $this->_extended_object as $key=>$value)
		{
			if( $this->_extended_object->$key instanceof FieldDefinition )
			{
				$object_list[] = $this->_extended_object->$key->objectName;
			}
		}

		//---On dedoublonne
		$object_list = array_unique($object_list);

		//---On cree le bloc data pour chaque object
		$object_data = array();
		foreach( $object_list as $object_name)
		{
			$method	= $object_name . 'Method' ;
			if(class_exists($method))
			{
				$hInstance = ORM::getControllerInstance($object_name);
				$hInstance->preWrite( $object_id, $object, $object ) ;
			}

			foreach($object as $key=>$value)
			{
				if (isset($this->_extended_object->$key) && $this->_extended_object->$key instanceof FieldDefinition)
				{
					if ($this->_extended_object->$key->objectName===$object_name)
					{
						$object_data[$object_name][$key] = $value;
					}
				}
			}
		}

		//---Si plusieurs tables dans $object_data ---> on traite tout ce qui n'est pas object courant
		$extends_data		 = array();
		$current_object_data = array();

		if (count($OL)>1)
		{
			foreach($object_data as $key=>$value)
			{
				if ($key!=$this->_object_name)
				{
					foreach($value as $k=>$v)
					{
						$extends_data[$k] = $v;
					}
				}
				else
				{
					foreach($value as $k=>$v)
					{
						$current_object_data[$k] = $v;
					}
				}
			}

			//---On recherche les extends de l'object courant
			$extends_list = array();
			foreach($this->_object as $key=>$value)
			{
				if (  $this->_object->$key instanceof FieldDefinition )
				{
					if ($this->_object->$key->type === 'extends')
					{
						$extends_list[$this->_object->$key->object_relation] = $key;
					}
				}
			}

			//---On fait les write des extends
			foreach(array_keys($extends_list) as $extends_object_name)
			{
				//--- !!! Recursivité !!!
				$hORM = ORM::getORMInstance($extends_object_name);
				$hORM->write($object_id,$extends_data);
			}
		}
		else
		{
			$current_object_data = $object;
		}

		if($with_table_name)
		{
			$table_reference = $this->_object_database.'.'.$this->_object_table;
		}
		else
		{
			$table_reference = '';
		}


		if( $ignore_duplicate === false )
		{
			$query = 'update '.$table_reference;
		}
		else
		{
			$query = 'update IGNORE '.$table_reference;
		}

		$query = 'update '.$table_reference;

		//---On deroule les champs
		foreach($current_object_data as $key=>$value)
		{
			if (!isset($this->_extended_object->$key))
			{
				continue;
			}

			// traitement du cryptage des mots de passe
			if($this->_extended_object->$key instanceof PasswordFieldDefinition && !empty($this->_extended_object->$key->crypt_method) && function_exists($this->_extended_object->$key->crypt_method))
			{// si c'est un champ de type mot de passe dont la propriété crypt_method correspond a une fonction qui existe
				if(empty($value))
				{// si il est vide on ne met rien a jour
					continue;
				}
				else
				{// sinon on crypt avec la méthode parametré via setCryptMethod(md5|sah1|...)
					$value=call_user_func($this->_extended_object->$key->crypt_method,$value);
				}
			}

			$this->_extended_object->$key->inCast($value);

			//---Si PK on ignore
			if ($this->_extended_object->$key->type==='primary key')
			{
				continue;
			}

			//---Si fct ou virtuel on ignore
			if (!$this->_extended_object->$key->isDbColumn())
			{
				continue;
			}

			//---Gestion du NULL
			if(  $value  !== NULL )
			{
				$value = '"'.$this->_hDB->db_escape_string($value).'"' ;
			}
			else
			{
				$value = 'NULL' ;
			}

			if( !isset( $fields))
			{
				if($with_set)
				{
					$fields =' SET `'.$key.'`='.$value ;
					continue ;
				}
				else
				{
					$fields =' `'.$key.'`='.$value ;
					continue;
				}
			}

			$fields .= ', `'.$key.'`='.$value ;
		}

		if (!isset($fields))
		{
			return NULL;
		}

		$query .= $fields;

		if($with_where_clause)
		{
		//---Add condition
			$query.=' where `'.$this->_object_key_name.'`="'.$object_id.'"';
		}

		return $query;
	}

	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL)
	{
		//---On soumet la requete
		$query = $this->get_write_sql($object_id, $object, $ignore_duplicate);
		if($query == NULL)
		{
			return TRUE;
		}

		$this->_hDB->db_execute($query, $affected);

		if ($affected>1)
		{
			throw new Exception( 'More than 1 records affected by update. Transaction canceled !!!' ) ;
		}
		
		$method	= $this->_object_name . 'Method' ;
		if(class_exists($method))
		{
			$hInstance = new $method() ;
			$hInstance->postWrite( $object_id, $affected );
		}

		return TRUE;
	}
	//-------------------------------------------------------------------------
	function browse(array &$object_list=NULL,
			&$total_record = 0,
			array $fields=NULL,
			array $args=NULL,
			array $tri=NULL,
			$offset=0,
			$limit=NULL)
	{
		if($object_list == NULL)
		{
			$object_list = array();
		}
		$id_list = array();
		$this->_orm_handler->search($id_list,$total_record,$args,$tri,$offset,$limit);

		//---Si pas de resultat ---> pas de read
		if (count($id_list)===0)
		{
			return TRUE;
		}
		else
		{
			//---Process NULL id
			$no_null_list = array();

			foreach ($id_list as $id => $value)
			{
				if ($value==NULL)
				{
					$object_list[$id] = NULL;
				}
				else
				{
					$no_null_list[$id] = $value;
				}
			}

			unset($id_list);
			$this->_orm_handler->read($no_null_list,$object_list,$fields);

			return TRUE;
		}
	}
	//-------------------------------------------------------------------------
	function search(array &$object_id_list = NULL,&$total_record = 0,array $args=NULL,array $order=NULL,$offset=0,$limit=NULL,array $extended_result=array())
	{
		if($object_id_list == NULL)
		{
			$object_id_list = array();
		}

		//$hQB = new QueryBuilder($this->_object, $this->_count_total);

		//---Toujours Utiliser la version extended
		//$this->_object = ORM::getObjectInstance($this->_object_name);

		//---Order
		if (($order==NULL) && (isset($this->_object->order)))
		{
			$order = $this->_object->order;
		}

		//---Add domain rules to args
		if(isset($this->_object->object_domain) && count($this->_object->object_domain) > 0)
		{
			if($args == NULL)
			{
				$args = array();
			}

			foreach($this->_object->object_domain AS $domain_rule)
			{
				if (count($domain_rule)>0)
				{
					$args[] = $domain_rule;
				}
			}
		}

		//---On cree la jointure extends
		$object_list = array();
		$table_list  = array();
		$join_list   = array();

		$this->_findExtendedObject($this->_object_name, $object_list);

		foreach($object_list as $object)
		{
			$table = $object->database.'.'.$object->table;

			if(!in_array($table, $table_list))
			{
				$table_list[] = $table;
			}

			/**
			 * On parcours les champs à le recherche des workflow_status --> creation des jointures
			 */
			$count_workflow_status = 0;
			$workflow_status_where_list = array();

			if($args != NULL)
			{
				/* Parcours des champs. */
				foreach($object as $key=>$value)
				{
					if(!$object->$key instanceof FieldDefinition || is_array($object->$key->type))
					{
						continue;
					}

					/* Si le champ est related, on recherche s'il correspond à un workflow_status. */
					$obj = $object;
					$mapping = array();		// gestion du mapping de champs lorsqu'un champ à un nom différent en related.
					$mapping[$key] = $key;	// Cas par défaut.
					if($obj->$key->type === 'related')
					{
						$loc_attribute = $obj->$key->object_relation;
						$rel_attribute = $obj->$key->related_relation;

						if($obj->$loc_attribute->type !== 'one2one' && $obj->$loc_attribute->type !== 'many2one' && $obj->$loc_attribute->type !== 'related' && $obj->$loc_attribute->type !== 'extends')
						{
							throw new ObjectDefinitionException('Attribut related utilisant un attribut non many2one ! (' . $obj->$loc_attribute->type . ')');
						}

						if($obj->$loc_attribute->type === 'related')
						{
							continue;
						}

						$object_rel = $obj->$loc_attribute->object_relation;

						$hI = ORM::getObjectInstance($object_rel, FALSE); // Trouver une autre solution...
						if(!isset($hI->$rel_attribute) || !($hI->$rel_attribute instanceof FieldDefinition))
						{
							throw new ObjectDefinitionException('L\'attribut "' . $rel_attribute . '" n\'existe pas dans l\'objet "' . $object_rel . '" appelé lors d\'un search depuis l\'objet "' . $this->_object_name . '" !');
						}

						if($hI->$rel_attribute->type !== 'workflow_status')
						{
							continue;
						}

						$obj = $hI;
						$mapping[$key] = $rel_attribute;
						$key = $rel_attribute;
					}

					if ($obj->$key->type !== 'workflow_status')
					{
						continue;
					}

					/* Parcours des arguments de recherche. */
					foreach($args as $ka=>$arg)
					{
						if(!isset($mapping[$arg[0]]) || $mapping[$arg[0]] != $key)
						{
							continue;
						}

						$hORM = ORM::getORMInstance('node');
						$node_list = array();

						$count_workflow_status++;

						$op = $arg[1];
						$node_id = NULL;
						if(is_array($arg[2]))
						{
							$first = reset($arg[2]);
							if(is_numeric($first))
							{
								$node_id = $first;
							}
						}
						else
						if(is_numeric($arg[2]))
						{
							$node_id=$arg[2];
						}

						if($node_id != NULL)
						{
							//---On recup le workflow node
							$hORM->read(array($node_id),$node_list,array('object'));
						}

						$keywt = $obj->$key->related_relation;

						$keyname = $obj->primary_key;
						if($keywt === NULL || $keywt == 'id')
						{
							if(is_array($this->_object_key_name))
							{
								throw new ObjectDefinitionException('Le champ \''.$key.'\' de l\'objet \''.get_class($obj).'\' doit prendre 3 paramètres : workflow_status:workflow_name:key_name !');
							}
							$keywt = 'id';
							$keyname = $obj->primary_key;
						}

						if(isset($obj->$key->pk_relation) && $obj->$key->pk_relation !== NULL)
						{
							$keyname = $obj->$key->pk_relation;
						}
						$attr_src = $table . '.' . $keyname;

						$node_name = NULL;
						if(!is_array($arg[2]))
						{
							if(is_numeric($arg[2]))
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.workflow_node_id'.$op.$arg[2];
							}
							else
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.node_name'.$op.'\''.$arg[2].'\'';
								$node_name = $arg[2];
							}
						}
						else
						{
							$first = reset($arg[2]);
							if(is_numeric($first))
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.workflow_node_id '.$op.' ('.implode(', ',$arg[2]).')';
							}
							else
							{
								$workflow_status_where_list[] = 'kwn'.$count_workflow_status.'.node_name '.$op.' (\''.implode('\', \'',$arg[2]).'\')';
								$node_name = $arg[2];
							}
						}

						//---Si object != ordre_travail
						// TODO: Support du filtrage sur d'autres types d'objets. */
						if ($node_name != NULL || ($node_id != NULL && $node_list[$node_id]['object']['value'] != 'ordretravail'))
						{
							$table_list[] = 'killi_workflow_token as kwt'.$count_workflow_status;
							$join_list[]  = $attr_src.'=kwt'.$count_workflow_status.'.'.$keywt;

							$table_list[] = 'killi_workflow_node as kwn'.$count_workflow_status;
							$join_list[]  = 'kwn'.$count_workflow_status.'.workflow_node_id=kwt'.$count_workflow_status.'.node_id';
						}
						else //--- Si ODT
						{
							$table_list[] = 'killi_ordre_travail as kodt'.$count_workflow_status;
							$join_list[]  = $attr_src.'=kodt'.$count_workflow_status.'.id';

							$table_list[] = 'killi_workflow_token as kwt'.$count_workflow_status;
							$join_list[]  = 'kwt'.$count_workflow_status.'.id=kodt'.$count_workflow_status.'.ordre_travail_id';

							$table_list[] = 'killi_workflow_node as kwn'.$count_workflow_status;
							$join_list[]  = 'kwn'.$count_workflow_status.'.workflow_node_id=kwt'.$count_workflow_status.'.node_id';
						}
						//array_splice($args,$ka,1);
						unset($args[$ka]);
					}
				}
			}

			/**
			 *  On parcours les champs à le recherche des extends/related/many2many/one2one --> creation des jointures
			 */
			$related_field_list = array();
			$one2one_field_list = array();
			$many2many_field_list = array();
			foreach($object as $key=>$value)
			{
				/* On vérifie que l'attribut est bien un field de l'objet. */
				if (!($object->$key instanceof FieldDefinition) || is_array($object->$key->type))
				{
					continue;
				}

				/* On retire les champs virtuels */
				if($object->$key->isVirtual())
				{
					continue;
				}

				/* On effectue la jointure de l'objet avec son parent via l'attribut en extends. */
				switch($object->$key->type)
				{
					case 'extends':
						//---On instancie l'object de ref
						$hInstance = ORM::getObjectInstance($object->$key->object_relation);

						if(is_null($object->$key->related_relation))
						{
							$related_relation = $key;
						}
						else
						{
							$related_relation = $object->$key->related_relation;
						}

						if(is_null($object->$key->pk_relation))
						{
							$pk_relation = $key;
						}
						else
						{
							$pk_relation = $object->$key->pk_relation;
						}

						$join_list[] = $hInstance->table.'.'.$related_relation.'='.$object->table.'.'.$pk_relation;
						$table = $hInstance->database.'.'.$hInstance->table;

						// $hQB->addLeftJoin($hInstance->database, $hInstance->table, $hInstance->primary_key, $key);

						if(!in_array($table, $table_list))
						{
							$table_list[] = $table;
						}
						break;
					case 'related':
						$related_field_list[$key] = $key;
						break;
					case 'one2one':
						$one2one_field_list[$key] = $key;
						break;
					case 'many2many':
						$many2many_field_list[$key] = $key;
						break;
				}
			}

			if($args !== NULL)
			{
				foreach($related_field_list AS $key)
				{
					//---related
					foreach($args as $ka=>$arg)
					{
						if($arg[0] != $key)
						{
							continue;
						}

						/* On construit la jointure de related sur l'objet qui contient cet attribut. */
						$fieldOwner = ORM::getObjectInstance($object->$key->objectName);

						/* TODO: Remplacer cette construction par un do..while qui sera plus approprié. */
						/* Contient l'attribut cible de l'objet qui pointe en many2one. */
						$many2one_attribute = $object->$key->object_relation;

						// $hQB->join($many2one_attribute);
						/* On retire les champs calculés PHP. */
						if($fieldOwner->$many2one_attribute->isFunction())
						{
							continue;
						}

						/* On vérifie que l'attribut cible est bien de type many2one/one2one. */
						if($fieldOwner->$many2one_attribute->type == 'many2one' ||
						   $fieldOwner->$many2one_attribute->type == 'one2one')
						{
							$many2one_object = $fieldOwner->$many2one_attribute->object_relation;
							$object_attribute = $fieldOwner->$key->related_relation;

							/* On récupère l'objet qui pointe en many2one. */
							$hInstance = ORM::getObjectInstance($many2one_object);

							/**
							 * Si l'élément de jointure est un objet jsonBrowse, on ne peut pas créer de jointure.
							 */
							if(!isset($hInstance->database))
							{
								throw new Exception('Impossible de créer une jointure de l\'objet ' . $object->$key->objectName . ' avec l\'objet jsonBrowse : ' . $many2one_object . ' !');
							}

							/* On construit la jointure pour la récupération des champs. */
							$table = $hInstance->database.'.'.$hInstance->table;
							$alias = $table;
							if($fieldOwner->database.'.'.$fieldOwner->table == $table)
							{
								$alias = 'orm_' . $fieldOwner->$key->object_relation;
								$table = $table . ' ' . $alias;
							}

							$first = $alias.'.'.$hInstance->primary_key;

							if($fieldOwner->$many2one_attribute->type == 'one2one')
							{
								if($fieldOwner->$many2one_attribute->related_relation != NULL)
								{
									$first = $alias.'.'.$fieldOwner->$many2one_attribute->related_relation;
								}
							}

							if($fieldOwner->$many2one_attribute->isSQLAlias())
							{
								$second = $this->map_sql_alias($fieldOwner->$many2one_attribute->sql_alias, $fieldOwner->database.'.'.$fieldOwner->table);
							}
							else
							{
								if($fieldOwner->$many2one_attribute->type == 'one2one')
								{
									$rem_field = $fieldOwner->primary_key;
								}
								else
								{
									$rem_field = $fieldOwner->$key->object_relation;
								}
								$second = $fieldOwner->database.'.'.$fieldOwner->table.'.'.$rem_field;
							}
							$join = $first . '=' . $second;

							if($first == $second)
							{
								throw new JoinConflictException('Erreur de jointure sur le champ related \''. $key .'\' : ' . $join);
							}

							if(!in_array($join, $join_list))
							{
								$join_list[] = $join;
							}

							if(!in_array($table, $table_list))
							{
								$table_list[] = $table;
							}

							if($hInstance->$object_attribute->type == 'many2many')
							{
								$rel_object = ORM::getM2MObject($hInstance->$object_attribute->object_relation, $hInstance->$key->objectName);

								if($rel_object === NULL)
								{
									throw new Exception('Impossible de determiner l\'objet de laison '.$object->$key->object_relation.'/'.$object->$key->objectName);
								}

								$hRel = ORM::getObjectInstance($rel_object);


								$table_hRel		= $hRel->database.'.'.$hRel->table;
								$first			= $hInstance->database.'.'.$hInstance->table.'.'.$hInstance->primary_key;
								$second			= $table_hRel.'.'.$hInstance->primary_key;
								$join_object	= $first . ' = ' . $second;

								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_object);
								}

								$hInstance2 = ORM::getObjectInstance($hInstance->$key->object_relation);
								$table_hInstance	= $hInstance2->database.'.'.$hInstance2->table;
								$first				= $table_hInstance.'.'.$hInstance2->primary_key;
								$second				= $table_hRel.'.'.$hInstance2->primary_key;
								$join_hInstance		= $first . ' = ' . $second;

								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_hInstance);
								}

								if(!in_array($join_object, $join_list))
								{
									$join_list[] = $join_object;
								}

								if(!in_array($join_hInstance, $join_list))
								{
									$join_list[] = $join_hInstance;
								}

								if(!in_array($table_hRel, $table_list))
								{
									$table_list[] = $table_hRel;
								}

								if(!in_array($table_hInstance, $table_list))
								{
									$table_list[] = $table_hInstance;
								}
							}

							/* Si l'attribut de destination est un related, on ajoute la jointure et on reboucle. */
							while(!empty($object_attribute)
								&& $hInstance->$object_attribute->type == 'related')
							{
								$obj = $hInstance;
								$m2o_attr = $obj->$object_attribute->object_relation;
								$lnk_obj = $obj->$object_attribute->related_relation;

								if($obj->$m2o_attr->type != 'many2one' && $obj->$m2o_attr->type != 'one2one' && $obj->$m2o_attr->type != 'related')
								{
									throw new Exception('Erreur de related avec le champ : \'' . $object_attribute . '\' dans l\'objet ' . get_class($obj) . '. Le champ \'' . $m2o_attr . '\' n\'est pas un champ many2one/one2one !');
								}

								if($obj->$m2o_attr->function !== NULL)
								{
									break; // Pas de jointure sur un champ calculé.
								}

								$m2o_object = $obj->$m2o_attr->object_relation;

								$hInstance = ORM::getObjectInstance($m2o_object);

								$table = $hInstance->database.'.'.$hInstance->table;

								$first = $table.'.'.$hInstance->primary_key;
								$second = $obj->database.'.'.$obj->table.'.'.$obj->$object_attribute->object_relation;
								if($obj->$m2o_attr->type == 'one2one')
								{
									if($obj->$m2o_attr->related_relation != NULL)
									{
										$first = $table.'.'.$obj->$m2o_attr->related_relation;
										$second = $obj->database.'.'.$obj->table.'.'.$obj->primary_key;
									}
								}
								$join = $first . '=' . $second;
								if($first == $second)
								{
									throw new JoinConflictException('Erreur de jointure sur le champ related \''. $key .'\' : ' . $join);
								}

								if(!in_array($join, $join_list))
								{
									$join_list[] = $join;
								}

								if(!in_array($table, $table_list))
								{
									$table_list[] = $table;
								}
								$object_attribute = $lnk_obj;
							}
						}
					}
				}

				//---Many2many
				foreach($many2many_field_list AS $key)
				{
					foreach($args as $ka=>$arg)
					{
						if($arg[0] != $key)
						{
							continue;
						}

						$rel_object = ORM::getM2MObject($object->$key->object_relation, $object->$key->objectName);

						if($rel_object === NULL)
						{
							throw new Exception('Impossible de determiner l\'objet de laison '.$object->$key->object_relation.'/'.$object->$key->objectName);
						}

						$hRel = ORM::getObjectInstance($rel_object);
						$hInstance = ORM::getObjectInstance($object->$key->object_relation);

						$table_hRel		= $hRel->database.'.'.$hRel->table;
						$first			= $object->database.'.'.$object->table.'.'.$object->primary_key;
						$second			= $table_hRel.'.'.$object->primary_key;
						$join_object	= $first . ' = ' . $second;

						if($first == $second)
						{
							throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_object);
						}

						$table_hInstance	= $hInstance->database.'.'.$hInstance->table;
						$first				= $table_hInstance.'.'.$hInstance->primary_key;
						$second				= $table_hRel.'.'.$hInstance->primary_key;
						$join_hInstance		= $first . ' = ' . $second;

						if($first == $second)
						{
							throw new JoinConflictException('Erreur de jointure sur le champ many2many \''. $key .'\' : ' . $join_hInstance);
						}

						if(!in_array($join_object, $join_list))
						{
							$join_list[] = $join_object;
						}

						if(!in_array($join_hInstance, $join_list))
						{
							$join_list[] = $join_hInstance;
						}

						if(!in_array($table_hRel, $table_list))
						{
							$table_list[] = $table_hRel;
						}

						if(!in_array($table_hInstance, $table_list))
						{
							// TODO: Tenter de se passer de ça !
							//$table_list[] = $table_hInstance;
						}
					}
				}
			}

			unset($related_field_list);
			unset($one2one_field_list);
			unset($many2many_field_list);

			$sql_computed_field = array();

			/**
			 *  Build order
			 */
			$order_by = ' ';
			if ($order != NULL)
			{
				/* Génération de l'order by. */
				$ordering = array();
				foreach($order as $o)
				{
					/* Définition explicite. */
					$raw = explode('.', $o);
					if(count($raw) > 1)
					{
						// $hQB->addOrderBy($o);
						$ordering[] = $o;
						continue;
					}

					/* Définition implicite. */
					$t = explode(' ', $o);
					$field = $t[0];
					$order = isset($t[1]) ? $t[1] : 'ASC';
					unset($t);

					if(!(isset($this->_object->$field) && $this->_object->$field instanceof FieldDefinition))
					{
						continue;
					}
					$table = $this->_object->table;

					if($this->_object->$field->isSQLAlias())
					{
						$calc = $this->map_sql_alias($this->_object->$field->sql_alias, $this->_object->database.'.'.$table);
						$sql_computed_field['computed_alias_'.$table.'_'.$field] = $calc;
						$ordering[] = 'computed_alias_'.$table.'_' . $field . ' ' . $order;
						$computed = true;
						continue;
					}

					if($this->_object->$field->type == 'workflow_status')
					{
						$obj = ORM::getObjectInstance($this->_object->$field->objectName); // Gestion pour l'extends

						$table_list[] = 'killi_workflow_token AS kwto';
						$join_list[]  = $obj->table.'.'.$obj->primary_key.'=kwto.id';

						$table_list[] = 'killi_workflow_node AS kwno';
						$join_list[]  = 'kwno.workflow_node_id=kwto.node_id';
						$args[] = array('kwno.object', '=\''.strtolower($this->_object->$field->objectName).'\'');

						$ordering[] = 'kwto.date ' . $order;
						continue;
					}

					if($this->_object->$field->type == 'related')
					{
						$relation_name = $this->_object->$field->object_relation;
						$hInstance = ORM::getObjectInstance($this->_object->$relation_name->object_relation);
						$table = $hInstance->table;
						$key = $hInstance->primary_key;
						// $hQB->join($relation_name);

						$fieldN = $this->_object->$field->related_relation;
						$hField = $hInstance->$fieldN;
						$o = $fieldN;
						$hInstance = ORM::getObjectInstance($hField->objectName);
						$table = $hInstance->table;

						$table_list[] = $hInstance->database . '.' . $table;
						$join_list[] = $table . '.' . $key . '=' . $this->_object->table . '.' . $relation_name;

						if($hField->isSQLAlias())
						{
							$calc = $this->map_sql_alias($hField->sql_alias, $hInstance->database.'.'.$table);
							$sql_computed_field['computed_alias_'.$table.'_'.$field] = $calc;
							$ordering[] = 'computed_alias_'.$table.'_' . $field . ' ' . $order;
							$computed = true;
							continue;
						}
					}

					// $hQB->addOrderBy($table, $field, $order);
					$ordering[] = $table . '.' . $o;
				}

				if(count($ordering) > 0)
				{
					$order_by = 'ORDER BY ' . join(', ', $ordering);
				}
			}
			else
			{
				/* Order by par défaut. */
				if(is_array($this->_object_key_name))
				{
					$order = array();
					foreach($this->_object_key_name AS $pk)
					{
						$order[] = $this->_object->table.'.'.$pk;
					}
					if(count($order) > 0)
					{
						$order_by = 'ORDER BY ' . join(',', $order);
					}
				}
				else
				{
					// $hQB->addOrderBy($this->_object->table, $this->_object_key_name, 'ASC');
					$order_by = 'ORDER BY '.$this->_object->table.'.'.$this->_object_key_name;
				}
			}

			/**
			 * Construction des jointures
			 */
			$table_list =  array_unique( $table_list ) ;

			//---If join domain is set
			if (isset($this->_object->domain_with_join))
			{
				foreach($this->_object->domain_with_join['table'] as $table)
				{
					$table_list[] = $table;
				}

				foreach($this->_object->domain_with_join['join'] as $join)
				{
					$join_list[] = $join;
				}

				foreach($this->_object->domain_with_join['filter'] as $filter)
				{
					$args[] = isset($filter[2]) ? array($filter[0], $filter[1], $filter[2]) : array($filter[0], $filter[1]);
				}
			}

			//---On dedoublonne les jointures
			for ($i=0;$i<count($table_list);$i++)
			{
				for ($j=$i+1; $j<count($table_list);$j++)
				{
					if ($table_list[$i]==$table_list[$j])
					{
						array_splice($table_list,$j,1);
						$merge_on = $join_list[$i-1].' and '.$join_list[$j-1];
						array_splice($join_list,$j-1,1);
						$join_list[$i-1] = $merge_on;
					}
				}
			}

			/**
			 *  Build where list
			 */
			$where = ' ';
			$having = ' ';
			if (($args!=NULL) || (count($workflow_status_where_list)>0))
			{
				$where = ' WHERE ';
				$having = ' HAVING ';
				foreach($args as $arg)
				{
					//---Need 3 key
					if (count($arg)<2)
					{
						throw new Exception('search conditions need 2 or 3 attributes !');
					}

					$computed	= false;
					$field		= $arg[0];
					$is_as		= false;
					$operator	= strtolower($arg[1]);
					$fullqual	= (strpos($field, '.') !== False);
					$field_object = NULL;
					if(!$fullqual)
					{
						$as_explode=explode(' as ',$field);
						if(count($as_explode) == 2)
						{
							$field = $as_explode[1];
							$is_as = true;
						}

						if(property_exists($this->_object, $field))
						{
							$field_object = $this->_object->$field;
							if($field_object->isFunction() || $field_object->isVirtual())
							{
								continue; // pas de where sur les champs calculés PHP ou Virtuel.
							}

							if($field_object->search_disabled === TRUE)
							{
								continue; // filtrage interdit
							}

							if(is_array($field_object->type))
							{
								$hInstance = ORM::getObjectInstance($this->_object->$field->objectName);
								$field = $hInstance->database.'.'.$hInstance->table.'.'.$field;
							}
							else
							if($field_object->isSQLAlias())
							{
								$field_objectName = $field_object->objectName;
								$hInstance = ORM::getObjectInstance($field_objectName);
								$field_db = $hInstance->database;
								$field_table = $hInstance->table;

								$calc = $this->map_sql_alias($field_object->sql_alias, $field_db.'.'.$field_table);

								$sql_computed_field[$field] = $calc;
								$computed = true;
							}
							else
							if($field_object->type == 'related')
							{
								/* TODO: Remplacer cette construction par un do..while qui sera plus approprié. */
								/* Attribut many2one ou one2one de liaison */
								$attribute_relation = $field_object->object_relation;
								if($this->_object->$attribute_relation->isFunction() || $this->_object->$attribute_relation->isVirtual())
								{
									continue;
								}

								if($this->_object->$attribute_relation->type == 'many2one')
								{
									/* On récupère l'attribut de l'objet que l'on veut récupérer. */
									$attr	 = $field_object->related_relation;

									/* On récupère l'objet sur lequel on fait le lien. */
									$hInstance = ORM::getObjectInstance($this->_object->$attribute_relation->object_relation);

									if($hInstance->$attr->isFunction() || $hInstance->$attr->isVirtual())
									{
										continue;
									}

									$is_SQLComputed = FALSE;
									if($hInstance->$attr->isSQLAlias())
									{
										$is_SQLComputed = TRUE;
									}

									$computedField = FALSE;

									/* Si l'attribut que l'on veut récupérer est un related, on remonte à son parent. */
									while($hInstance->$attr->type == 'related')
									{
										/* Attribut many2one de liaison */
										$attribute_relation = $hInstance->$attr->object_relation;

										/* On récupère l'attribut de l'objet que l'on veut récupérer. */
										$attr = $hInstance->$attr->related_relation;

										/* On récupère l'objet sur lequel on fait le lien. */
										$hInstance = ORM::getObjectInstance($hInstance->$attribute_relation->object_relation);

										if($hInstance->$attr->isSQLAlias())
										{
											$is_SQLComputed = TRUE;
											break;
										}

										if($hInstance->$attr->isFunction() || $hInstance->$attr->isVirtual())
										{
											$computedField = TRUE;
											break;
										}
									}

									if($computedField)
									{
										continue;
									}

									if($is_SQLComputed)
									{
										$field = $this->map_sql_alias($hInstance->$attr->sql_alias, $hInstance->database.'.'.$hInstance->table);
									}
									else
									if($hInstance->$attr->type=='many2many')
									{
										$M2MObject = ORM::getM2MObject($hInstance->$attr->object_relation, $hInstance->$attr->objectName);
										$RObject = ORM::getObjectInstance($hInstance->$attr->object_relation);
										$hM2MInstance = ORM::getObjectInstance($M2MObject);
										$field = $hM2MInstance->database.'.'.$hM2MInstance->table.'.'.$RObject->primary_key;
									}
									else
									{
										$field = $hInstance->database.'.'.$hInstance->table.'.'.$attr;
									}
								}
								else
								if($this->_object->$attribute_relation->type == 'one2one')
								{
									$hInstance = ORM::getObjectInstance($this->_object->$attribute_relation->object_relation);
									$attr = $field_object->related_relation;
									$field = $hInstance->database.'.'.$hInstance->table.'.'.$attr;
								}
							}
							else
							if($field_object->type=='many2many')
							{
								$M2MObject = ORM::getM2MObject($field_object->object_relation, $field_object->objectName);
								$RObject = ORM::getObjectInstance($field_object->object_relation);
								$hM2MInstance = ORM::getObjectInstance($M2MObject);
								$field = $hM2MInstance->database.'.'.$hM2MInstance->table.'.'.$RObject->primary_key;
							}
							else
							{
								if($this->_object_name != $field_object->objectName)
								{
									$hInstance = ORM::getObjectInstance($this->_object->$field->objectName);
									if(!$hInstance->$field->isDbColumn())
									{
										continue;
									}
									$field = $hInstance->database.'.'.$hInstance->table.'.'.$field;
								}
								else
								{
									if($is_as)
									{
										$field = preg_replace('/%field%/', $this->_object->database.'.'.$this->_object->table.'.'.$field, $as_explode[0]);
									}
									else
									{
										$field = $this->_object->database.'.'.$this->_object->table.'.'.$field;
									}
								}
							}
						}
						else
						{
							Debug::log('la propriete \'' . $field . '\' n\'existe pas dans l\'objet de type ' . $this->_object_name);
						}
					}

					//---Si relation in
					if ($operator === 'in' || $operator === 'not in')
					{
						//---arg 2 doit etre un array
						if (!is_array($arg[2]))
						{
							throw new Exception('3 args of domain using IN relation must be array !');
						}

						//---Si array vide
						if (count($arg[2])==0)
						{
							$object_id_list = array();
							return TRUE;
						}

						$formated = '(';
						$pos=0;
						foreach($arg[2] as $value)
						{
							if(is_array($value))
							{
								$f = array();
								foreach($value AS $v)
								{
									$f[] = '\''.$this->_hDB->db_escape_string($v[$arg[0]]).'\'';
								}
								$formated .= join(',', $f);
							}
							else
							{
								$formated.='\''.$this->_hDB->db_escape_string($value).'\'';
							}

							if (($pos+1)<count($arg[2]))
							{
								$formated.=',';
							}

							$pos++;
						}

						$arg[2] = $formated.')';
					}
					else
					if ($operator === 'between')
					{
						if (count($arg) < 4)
						{
							throw new Exception('4 args are expected using BETWEEN condition');
						}

						// Timestamp cases
						for ($idx = 2 ; $idx <=3 ; $idx++)
						{
							if (is_numeric($arg[$idx]))
							{
								$arg[$idx] = date('Y-m-d H:i:s', $arg[$idx]);
							}
						}

						$arg[2] = '"'.$arg[2].'" AND "'.$arg[3].'"';
					}
					else
					if (isset($arg[2]) && $field_object)
					{
						// cast
						$field_object->inCast($arg[2]);

						$arg[2] = '\'' .$this->_hDB->db_escape_string($arg[2]) . '\'';
					}

					if($field_object)
					{
						// cast de la colonne en date
						if($field_object instanceof DateFieldDefinition || $field_object->type === 'date')
						{
							$field = 'DATE('.$field.')';
						}

						// cast de la colonne en date si recherche une date
						if($field_object instanceof DatetimeFieldDefinition && (!isset($arg[2]) || preg_match('/^\'[0-9]{4}-[0-9]{2}-[0-9]{2}\'/', $arg[2])))
						{
							$field = 'DATE('.$field.')';
						}

						// cast de la colonne en heure si recherche une heure
						if(($field_object instanceof TimeFieldDefinition || $field_object instanceof DatetimeFieldDefinition || $field_object->type === 'time') && (!isset($arg[2]) || preg_match('/^\'[0-9]{2}:[0-9]{2}:[0-9]{2}\'$/', $arg[2])))
						{
							$field = 'TIME('.$field.')';
						}
					}

					if($computed)
					{
						$having .= (isset($arg[2]))?'('.$field.' '.$arg[1].' '.$arg[2].') and ':'('.$field.' '.$arg[1].') and ';
					}
					else
					{
						//$hQB->addWhere($arg[0], $arg[1], $arg[2]);
						$where .= (isset($arg[2]))?'('.$field.' '.$arg[1].' '.$arg[2].') and ':'('.$field.' '.$arg[1].') and ';
					}
				} // End foreach..

				//---Process workflow_status
				foreach($workflow_status_where_list as $k=>$workflow_status_where)
				{
					$where .=   '('.$workflow_status_where.') and ';
				}

				//---Remove last and
				if($where==' WHERE ')
				{
					$where='';
				}
				else
				{
					$where = substr($where,0,-5);
				}

				if($having == ' HAVING ')
				{
					$having = '';
				}
				else
				{
					$having = substr($having,0,-5);
				}

			}

			/**
			 *  Construction de la requête.
			 */
			if (count($table_list)===1)
			{
				if (count($extended_result)==0)
				{
					$query = 'SELECT ';

					if($this->_count_total===TRUE)
					{
						$query .= 'SQL_CALC_FOUND_ROWS ';
					}

					if(is_array($this->_object_key_name))
					{
						$pks = array();
						foreach($this->_object_key_name AS $k)
						{
							$pks[] = $this->_object_table.'.'.$k;
						}

						$query .= 'DISTINCT '.join(',', $pks);
					}
					else
					{
						$query .= 'DISTINCT('.$this->_object_key_name.')';
					}
				}
				else
				{
					$as_extended_result = array();

					foreach($extended_result as $k=>$v)
					{
						$as_extended_result[$k] = $v.' AS k'.$k;
					}

					$query = 'SELECT '.join(',',$as_extended_result).','.$this->_object_key_name;
				}
				foreach($sql_computed_field AS $field => $field_calc)
				{
					$query .= ', ' . $field_calc . ' AS ' . $field;
				}
				$query .= ' FROM '.$this->_object_database.'.'.$this->_object_table.' '.$where.' '.$having.' '.$order_by;
			}
			else
			{
				$extends_from	= $table_list[0];
				$nt				= count($table_list);

				for( $ti = 1; $ti < $nt; $ti ++)
				{
					$extends_from .= ' LEFT JOIN '.$table_list[$ti].' ON ('.$join_list[$ti-1].') ';
				}
				$extends_from .= $where.' '.$having;

				if (count($extended_result)==0)
				{
					$query = 'SELECT ';

					if($this->_count_total===TRUE)
					{
						$query .= 'SQL_CALC_FOUND_ROWS ';
					}
					if(is_array($this->_object_key_name))
					{
						$pks = array();
						foreach($this->_object_key_name AS $k)
						{
							$pks[] = $this->_object_table.'.'.$k;
						}

						$query .= 'DISTINCT '.join(',', $pks);
					}
					else
					{
						$query .= 'DISTINCT('.$this->_object_table.'.'.$this->_object_key_name.')';
					}
				}
				else
				{
					$as_extended_result = array();
					foreach($extended_result as $k=>$v)
					{
						$as_extended_result[$k] = $v.' AS k'.$k;
					}

					$query = 'SELECT '.join(',',$as_extended_result).','.$this->_object_table.'.'.$this->_object_key_name;
				}
				foreach($sql_computed_field AS $field => $field_calc)
				{
					$query .= ', ' . $field_calc . ' AS ' . $field;
				}
				$query .= ' FROM '.$extends_from.' '.$order_by;
			}

			/**
			 *  Process limit
			 */
			if ($limit!=NULL)
			{
				//$hQB->setOffset($offset*$limit)->setLimit($limit);
				$query.=' LIMIT '.$offset*$limit.','.$limit;
			}

			/**
			 *  Exécution de la requête.
			 */
			//$q2 = $hQB->build();
			//echo 'Query Builder :', PHP_EOL, $q2, PHP_EOL;
			//echo 'Search:', PHP_EOL;
			//echo $query, PHP_EOL;
			//Debug::printAtEnd($query);
			//---Process query
			$this->_hDB->db_select($query, $result, $numrows);

			if($this->_count_total === TRUE)
			{
				$query = 'SELECT FOUND_ROWS() AS total_record;';
				$this->_hDB->db_select($query, $counter, $num);
				$row = $counter->fetch_assoc();
				$total_record = $row['total_record'];
				$counter->free();
			}
			else
			{
				$total_record = NULL;
			}

			/**
			 *  Récupération du résultat.
			 */
			while ($row = $result->fetch_assoc())
			{
				if (count($extended_result)==0)
				{
					if(is_array($this->_object_key_name))
					{
						$r = array();
						foreach($this->_object_key_name AS $k)
						{
							if(!isset($row[$k]))
							{
								$r[] = NULL;
							}
							else
							{
								$r[] = $row[$k];
							}
						}
						$object_id_list[] = join(',', $r);
					}
					else
					{
						$object_id_list[] = $row[$this->_object_key_name];
					}
				}
				else
				{
					$resultat							= array();
					$resultat[$this->_object_key_name]	= $row[$this->_object_key_name];

					foreach($extended_result as $k=>$v)
					{
						$resultat[$v] =  $row['k'.$k];
					}

					$object_id_list[] = $resultat;
				}
			}

			$result->free();

			return TRUE;
		}
	}
	//-------------------------------------------------------------------------
	function count(&$total_record, $args=NULL )
	{
		$old_count = $this->_count_total;
		$this->_count_total = true;

		$object_list = array();
		$state = $this->_orm_handler->search($object_list ,$total_record, $args, null, 0, 1);

		$this->_count_total = $old_count;

		return $state;
	}
	//-------------------------------------------------------------------------
	function unlink($object_id)
	{
		$OL = array();
		$this->_findExtendedObject( $this->_object_name, $OL);

		$nbOL = count($OL);
		$hInstance_list = array();
		for($i = 0; $i < $nbOL; $i ++)
		{
			//---Call Pre-unlink
			$hInstance = null;
			$object_name = get_class( $OL[ $i ] );
			$method = $object_name.'Method';
			if(class_exists($method))
			{
				$hInstance = ORM::getControllerInstance($object_name);

				if ($hInstance->preUnlink($object_id,$object_id)==FALSE)
				{
					throw new CantDeleteException('Cannot preUnlink object '.get_class($OL[$i]). ' with object_id '.$object_id);
				}
			}
			$hInstance_list[$i] = $hInstance;
		}

		//---On supprime les token lies a cet objet sauf si c'est un ordre de travail
		if(strtolower($this->_object_name) != 'ordretravail')
		{
			$query = sprintf(
					'DELETE FROM '.RIGHTS_DATABASE.'.killi_workflow_token
					USING '.RIGHTS_DATABASE.'.killi_workflow_token,'.RIGHTS_DATABASE.'.killi_workflow_node
					WHERE (killi_workflow_token.node_id=killi_workflow_node.workflow_node_id)
					AND (killi_workflow_node.object="%s")
					AND (killi_workflow_token.id="%s")', strtolower($this->_object_name), $object_id);

			$this->_hDB->db_execute($query, $rows);
		}
		for($i = 0; $i < $nbOL; $i ++)
		{
			$rows = 0;

			$query = sprintf('DELETE FROM `%s`.`%s`  WHERE  `%s`= "%s";'
					, $OL[$i]->database
					, $OL[$i]->table
					, $OL[$i]->primary_key
					, $object_id);
			$tmp = $this->_hDB->db_execute($query, $rows);

			if($rows == 0 && $tmp === TRUE)
			{
				throw new CantDeleteException('Cannot delete object '.get_class($OL[$i]). ' with object_id '.$object_id. '(row not found)' );
			}

			if(isset($hInstance_list[$i]) && $hInstance_list[$i]->postUnlink($object_id)==FALSE)
			{
				throw new CantDeleteException('Cannot postUnlink object '.get_class($OL[$i]). ' with object_id '.$object_id);
			}
		}
		return TRUE;
	}

	//-------------------------------------------------------------------------
	function generateSQLcreateObject($is_temporary = FALSE)
	{
		$temporary = '';
		if($is_temporary)
		{
			$temporary = 'TEMPORARY';
		}
		$query  = 'CREATE ' . $temporary . ' TABLE IF NOT EXISTS ' . $this->_object->database . '.' . $this->_object->table . ' ( ' . PHP_EOL;
		$query .= "\t" . '`' . $this->_object->primary_key . '` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,' . PHP_EOL;

		foreach($this->_object AS $fieldname=>$field)
		{

			if(!($this->_object->$fieldname instanceof FieldDefinition))
			{
				continue;
			}

			if($field->type == 'primary key')
			{
				continue;
			}

			if($field->objectName != get_class($this->_object))
			{
				continue;
			}

			if(!$field->isDbColumn())
			{
				continue;
			}

			if(($field->type != 'extends'))
			{
				$default_value = $field->default_value;
				$skip = false;
				switch($field->type)
				{
					case 'text':
						$length = empty($field->max_length) ? 255 : $field->max_length;
						$fieldtype = 'varchar('.$length.') CHARACTER SET utf8 COLLATE utf8_general_ci';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT \''.$default_value.'\'';
						break;
					case 'csscolor':
						$fieldtype = 'varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT \''.$default_value.'\'';
						break;
					case 'int':
						$fieldtype = 'INT(10)';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'time':
						$fieldtype = 'time';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'date':
						$fieldtype = 'date';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'datetime':
						$fieldtype = 'datetime';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . $default_value . '\'');
						break;
					case 'checkbox':
					case 'bool':
						$fieldtype = 'tinyint(1) unsigned';
						$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT '.(empty($default_value) ? 'NULL' : '\'' . ($default_value === FALSE ? '0' : '1') . '\'');
						break;
					case 'textarea' :
					case 'serialized':
					case 'json':
						$fieldtype = 'LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL';
						break;
					default:
						switch($field->type)
						{
							case 'many2many':
								$fieldtype = 'INT(10) UNSIGNED';
								$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT NULL';
								break;
							case 'many2one':
								$fieldtype = 'INT(10) UNSIGNED';
								$fieldtype .= $field->required ? ' NOT NULL' : ' DEFAULT NULL';
								break;
							default:
								$skip = true;
						}
				}
				if(!$skip)
				{
					$query .=  "\t" . '`' . $fieldname . '` ' . $fieldtype . ',' . PHP_EOL;
				}
			}
		}

		$query .= 'PRIMARY KEY (`' . $this->_object->primary_key . '`)' . PHP_EOL;
		$query .= ') ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		return $query;
	}
	//-------------------------------------------------------------------------
	function createObjectInDatabase()
	{
		if(!isset($this->_hDB))
		{
			throw new Exception('Database instance not found !');
		}

		$this->_hDB->db_rollback();

		$query = $this->generateSQLcreateObject($this->_object_database != TESTS_DATABASE);

		$this->_hDB->db_execute($query, $rows);
	}
	//-------------------------------------------------------------------------
	function deleteObjectInDatabase()
	{
		if(!isset($this->_hDB))
		{
			throw new Exception('Database instance not found !');
		}

		$this->_hDB->db_rollback();
		$this->_hDB->db_execute(
			'DROP TABLE '.TESTS_DATABASE.'.' . $this->_object->table . ';',
			$rows
		);
	}
}
