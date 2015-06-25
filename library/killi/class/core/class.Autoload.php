<?php

/**
 *  @class Autoload
 *  @Revision $Revision: 4493 $
 *
 */

class Autoload
{
	protected static $_classes;
	protected static $_categories = array (
		'class',
		'workflow',
		'reporting'
	);

	protected static $_facades = array();

	private function __construct()
	{
	}

	public static function setFacade($classes)
	{
		if(!is_array($classes))
		{
			throw new Exception('Parameter "classes" is not an array !');
		}
		self::$_facades = $classes;
		return TRUE;
	}

	public static function declareClass($filename, $mapping = NULL, $force_object = FALSE, $rights = TRUE)
	{
		$file			= basename($filename);
		$dir			= dirname($filename);
		$categorie		= 'unknow';
		$classFileName	= substr($file, 6, - 4);

		/* On ne s'occupe pas des fichiers qui ne respectent pas la syntaxe class.MaClass.php */
		if (strncmp ( 'class.', $file, 6 ) || substr ( $file, - 4 ) != '.php')
		{
			return FALSE;
		}

		foreach ( self::$_categories as $cat )
		{
			if(strncmp('./' . $cat, $dir, strlen('./' . $cat)) == 0)
			{
				$categorie = $cat;
				break;
			}
		}

		switch ($categorie)
		{
			case 'class' :
				$namespacePath = substr ( $dir, strlen ( './class' ) );
			break;
			case 'unknow' :
				$namespacePath = '';
			break;
			default :
				$namespacePath = substr ( $dir, strlen ( './' . $categorie . '/class' ) );
		}

		$namespace = str_replace ( '/', '', $namespacePath );

		$className = ($mapping != NULL)?$mapping:($namespace . $classFileName);

		/* Si la classe n'est pas un controlleur, on l'enregistre dans l'orm. */
		if (($categorie != 'unknow' && substr ( $classFileName, - 6 ) != 'Method') || $force_object)
		{
			ORM::declareObject ( $className, $rights );
		}

		$dir = realpath($dir);
		self::$_classes [strtolower ( $className )] = array (
			'dir'		   => $dir,
			'categorie'	 => $categorie,
			'fileName'	  => $file,
			'namespace'	 => $namespace,
			'namespacePath' => $namespacePath,
			'className'	 => $className,
			'classFileName' => $classFileName
		);

		return true;
	}

	public static function declareContentClass($classname, $content_class)
	{
		$classInfo = array (
			'dir' => NULL,
			'categorie' => 'unknow',
			'fileName' => NULL,
			'namespace' => NULL,
			'namespacePath' => NULL,
			'className' => $classname,
			'classFileName' => NULL,
			'content' => $content_class
		);
		$classNameLower = strtolower ( $classname );

		self::$_classes [$classNameLower] = $classInfo;

		return TRUE;
	}

	public static function getClassCategorie($classname)
	{
		$classlower = strtolower ( $classname );
		if (! isset ( self::$_classes [$classlower] ))
		{
			return NULL;
		}
		return self::$_classes [$classlower] ['categorie'];
	}

	public static function loadClassWithNamespace($classname)
	{
		$class_name = ltrim($classname, '\\');
		$file_name  = '';
		$namespace = '';
		if($lastNsPos = strripos($class_name, '\\'))
		{
			$namespace = substr($class_name, 0, $lastNsPos);
			$class_name = substr($class_name, $lastNsPos + 1);
			$file_name  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$file_name .= str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

		if(strncmp($file_name, 'Killi/Core', 10) == 0)
		{
			$file_name = KILLI_DIR . '/class/core' . substr($file_name, 10);
		}

		if(!file_exists($file_name))
		{
			return FALSE;
		}

		require_once($file_name);

		return TRUE;
	}

	public static function loadClass($classname)
	{
		if(!empty(self::$_facades[$classname]))
		{
			$facade = $classname;
			$classname = self::$_facades[$classname];

			self::loadClassWithNamespace($classname);
			eval('class ' . $facade . ' extends ' . $classname . '{}');
			return TRUE;
		}

		if(strncmp($classname, 'Killi\Core', 10) == 0)
		{
			if(self::loadClassWithNamespace($classname))
			{
				return TRUE;
			}

			if($lastNsPos = strripos($classname, '\\'))
			{
				$namespace = substr($classname, 0, $lastNsPos);
				$class_name = substr($classname, $lastNsPos + 1);
			}
			//echo 'namespace ' . $namespace . '; class ' . $class_name . ' extends \\' . $class_name . '{}';
			eval('namespace ' . $namespace . '; class ' . $class_name . ' extends \\' . $class_name . '{}');
			return TRUE;
		}

		$classlower = strtolower ( $classname );

		// fichier virtuel
		if (isset ( self::$_classes [$classlower] ) && isset ( self::$_classes [$classlower] ['content'] ))
		{
			eval ( self::$_classes [$classlower] ['content'] );

			return TRUE;
		}

		// fichier classique
		if (isset ( self::$_classes [$classlower] ))
		{
			require_once (self::$_classes [$classlower] ['dir'] . '/' . self::$_classes [$classlower] ['fileName']);

			return TRUE;
		}

		// XMLNode
		if (substr ( $classname, - 7 ) == 'XMLNode')
		{
			$node_name = strtolower ( substr ( $classname, 0, - 7 ) );

			if (file_exists ( './class/ui/' . $node_name . '.XMLNode.php' ))
			{
				require_once ('./class/ui/' . $node_name . '.XMLNode.php');

				return TRUE;
			}

			if (file_exists ( KILLI_DIR . '/class/ui/' . $node_name . '.XMLNode.php' ))
			{
				require_once (KILLI_DIR . '/class/ui/' . $node_name . '.XMLNode.php');

				return TRUE;
			}
		}

		// FieldDefinition
		if (substr ( $classname, - 15 ) == 'FieldDefinition')
		{
			$field_name = strtolower ( substr ( $classname, 0, - 15 ) );

			if (file_exists ( './class/field/' . $field_name . '.FieldDefinition.php' ))
			{
				require_once ('./class/field/' . $field_name . '.FieldDefinition.php');

				return TRUE;
			}

			if (file_exists ( KILLI_DIR . '/class/field/' . $field_name . '.FieldDefinition.php' ))
			{
				require_once (KILLI_DIR . '/class/field/' . $field_name . '.FieldDefinition.php');

				return TRUE;
			}
		}

		// RenderFieldDefinition
		if (substr ( $classname, - 21 ) == 'RenderFieldDefinition')
		{
			$render_name = strtolower ( substr ( $classname, 0, - 21 ) );

			if (file_exists ( './class/field/render/' . $render_name . '.RenderFieldDefinition.php' ))
			{
				require_once ('./class/field/render/' . $render_name . '.RenderFieldDefinition.php');

				return TRUE;
			}

			if (file_exists ( KILLI_DIR . '/class/field/render/' . $render_name . '.RenderFieldDefinition.php' ))
			{
				require_once (KILLI_DIR . '/class/field/render/' . $render_name . '.RenderFieldDefinition.php');

				return TRUE;
			}
		}

		// Module de process
		if (substr ( $classname, - 6 ) == 'Module')
		{
			$module_name = substr ( $classname, 0, - 6 ) ;

			if (file_exists ( './process/class.' . $module_name . '.Module.php' ))
			{
				require_once ( './process/class.' . $module_name . '.Module.php');

				return TRUE;
			}

			if (file_exists ( KILLI_DIR . './class/process/class.' . $module_name . '.Module.php' ))
			{
				require_once (KILLI_DIR . './class/process/class.' . $module_name . '.Module.php');

				return TRUE;
			}
		}

		// librairies
		if (file_exists ( KILLI_DIR . '/library/' . $classname . '.php' ))
		{
			require_once (KILLI_DIR . '/library/' . $classname . '.php');

			return TRUE;
		}

		// controleurs de base
		if (substr ( $classname, - 6 ) == "Method" && class_exists ( substr ( $classname, 0, - 6 ) ))
		{
			eval ( 'class ' . $classname . ' extends Common {}' );

			return TRUE;
		}

		return FALSE;
	}

	public static function getClassPath($classname)
	{
		$classlower = strtolower ( $classname );
		if (! isset ( self::$_classes [$classlower] ))
		{
			return NULL;
		}
		return self::$_classes [$classlower] ['dir'];
	}

	public static function getClassNamespace($classname)
	{
		$classlower = strtolower ( $classname );
		if (! isset ( self::$_classes [$classlower] ))
		{
			return NULL;
		}
		return self::$_classes [$classlower] ['namespace'];
	}

	public static function getClassFileName($classname)
	{
		$classlower = strtolower ( $classname );
		if (! isset ( self::$_classes [$classlower] ))
		{
			return NULL;
		}
		return self::$_classes [$classlower] ['classFileName'];
	}

	public static function getClassNamespacePath($classname)
	{
		$classlower = strtolower ( $classname );
		if (! isset ( self::$_classes [$classlower] ))
		{
			return NULL;
		}
		return self::$_classes [$classlower] ['namespacePath'];
	}

	public static function loadAll()
	{
		foreach ( self::$_classes as $classname => $classdata )
		{
			self::loadClass ( $classname );
		}
	}
}

spl_autoload_register ( array (
	'Autoload',
	'loadClass'
) );

// Déclaration récursive des objets dans l'autoload
function include_classes($dir)
{
	// Lecture dossier.
	$hDir = opendir ( $dir );
	if(!$hDir)
	{
		throw new Exception('Unable to include classes in directory : ' . $dir);
	}
	while ( false !== ($file = readdir ( $hDir )) )
	{
		if ($file == '.' || $file == '..')
		{
			continue;
		}

		if (is_dir ( $dir . $file ))
		{
			include_classes ( $dir . $file . '/' );
		}
		else
		{
			Autoload::declareClass ( $dir . $file );
		}
	}
	closedir ( $hDir );
	return TRUE;
}

// Déclaration récursive des objets dans l'autoload
function include_killi_classes($dir)
{
	// Lecture dossier.
	$hDir = opendir ( $dir );
	if(!$hDir)
	{
		throw new Exception('Unable to include classes in directory : ' . $dir);
	}
	while ( false !== ($file = readdir ( $hDir )) )
	{
		if ($file == '.' || $file == '..' || substr($file,-4) != '.php')
		{
			continue;
		}

		if (is_dir ( $dir . $file ))
		{
			continue;
		}

		$classname = substr($file, 6, -4);
		$is_object = (substr($classname, -6) != 'Method');

		Autoload::declareClass($dir . $file, 'Killi'.$classname, FALSE, FALSE, FALSE);

		if (!file_exists('./class/class.'.$classname.'.php'))
		{
			Autoload::declareContentClass($classname, 'class '.$classname.' extends Killi'.$classname.' {}');

			if($is_object)
			{
				ORM::declareObject($classname);
			}
		}
	}
	closedir ( $hDir );
	return TRUE;
}
