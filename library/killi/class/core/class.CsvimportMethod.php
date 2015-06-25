<?php

/**
 *
 *  @class CsvimportMethod
 *  @Revision $Revision: 4139 $
 *
 */

class CsvimportMethod extends Common
{
	private $_filepath  = '/tmp';

	//.....................................................................
	public function edit( $view,&$data,&$total_object_list,&$template_name=NULL )
	{
		$template_name = "../library/killi/template/csvimport.xml";

		if( $view != 'form' )
		{
			header( 'HTTP/1.1 301 Moved Permanently' ) ;
			header( 'Location: ' . './index.php?action=csvimport.edit&view=form&mode=edition&token=' . $_SESSION['_TOKEN'] ) ;
			exit(0);
		}

		foreach( ORM::getDeclaredObjectsList() as $k)
		{
			$v = ORM::getObjectInstance($k);

			if( $v->create == 0 )
			{
				continue ;
			}

			unset( $crypt ) ;
			Security::crypt( $k, $crypt ) ;

			$data[ 'objectlist' ][] = array( 'id' =>$crypt, 'value'=>$k ) ;
		}

		return TRUE;
	}
	//.....................................................................
	public function write($data)
	{
		/* test du fichier */
		if(  !isset( $_FILES[ 'csvfile' ] ) && !is_array(  $_FILES[ 'csvfile' ] ) )
		{
			throw new Exception( '$_FILES[\'csvfile\'] not valid' );
		}

		$file = $_FILES[ 'csvfile' ] ;
		if( $file[ 'error' ] != 0 )
		{
			throw new Exception( 'error detected : ' . $file[ 'error' ] );
		}
		if( !isset( $file[ 'tmp_name' ] ) || trim( $file[ 'tmp_name' ] ) == '' )
		{
			$_SESSION['_ERROR_LIST'][] = 'Aucun fichier' ;
		}
		if( is_uploaded_file( $file[ 'tmp_name' ] ) === false )
		{
			throw new Exception( 'uploaded file failed' );
		}

		/* Renammage du fichier */
		$file_parts = pathinfo( $file[ 'tmp_name' ] ) ;
		if( !isset( $file_parts[ 'filename' ] ) || trim( $file_parts[ 'filename' ] ) == '' )
		{
			throw new Exception( 'get disk fiel name failed' );
		}
		if( !isset( $file_parts[ 'basename' ] ) || trim( $file_parts[ 'basename' ] ) == '' )
		{
			throw new Exception( 'get disk file name failed' );
		}
		if( !isset( $file_parts[ 'dirname' ] ) || trim( $file_parts[ 'dirname' ] ) == '' )
		{
			throw new Exception( 'get dirname failed' );
		}

		$filename = md5( $file_parts[ 'basename' ] ) ;
		if(  rename( $file_parts[ 'dirname' ] . '/' . $file_parts[ 'basename' ]  , $this->_filepath . '/' .  $filename ) === false )
		{
			throw new Exception( 'rename failed' );
		}

		header( 'HTTP/1.1 301 Moved Permanently' ) ;
		header( 'Location: ' . './index.php?action=csvimport.displayStructure&view=form&mode=edition&file=' . $filename . '&objectselected=' . $data[ 'objectselected' ] . '&token=' . $_SESSION['_TOKEN'] ) ;
		exit( 0 ) ;

		return TRUE;
	}

	public function displayStructure()
	{

		/* test des donnees */
		if( !isset( $_GET[ 'objectselected' ] ) || trim( $_GET[ 'objectselected' ] ) == '' )
		{
			throw new Exception( 'objectselected value not valid' ) ;
		}
		if( !isset( $_GET[ 'file' ] ) || trim( $_GET[ 'file' ] ) == '' )
		{
			throw new Exception( 'file value not valid' ) ;
		}
		if( file_exists(  $this->_filepath . '/' . $_GET[ 'file' ] ) === false )
		{
			throw new Exception( 'file not found : ' . $this->_filepath . '/' . $_GET[ 'file' ] ) ;
		}
		if( is_readable( $this->_filepath . '/' . $_GET[ 'file' ] ) === false  )
		{
			throw new Exception( 'file not readable : ' . $this->_filepath . '/' . $_GET[ 'file' ] ) ;
		}

		/* consitution du jeu de donnees */
		$data = array() ;

		/* Les attributs de l objet selectionne & les many2one */
		$data[ 'attribut' ] = array() ;
		$data[ 'many2one' ] = array() ;

		$hORM = ORM::getORMInstance($_GET[ 'objectselected' ]);

		$attribut = array() ;
		$hattribut = new AttributeMethod() ;

		$hattribut->getAttributeFromObject( $_GET[ 'objectselected' ], $_SESSION[ '_USER' ][ 'profil_id' ][ 'value' ], $attribut ) ;

		$attrlist = array() ;
		for( $i = 0 ; $i  < count( $attribut ) ; $i++ )
		{
			switch( $attribut[ $i ][ 'type' ][ 'value' ] )
			{
				case 'int':
				case 'text':
					unset( $crypt ) ;
					Security::crypt( $attribut[ $i ][ 'attr_name' ][ 'value' ], $crypt ) ;
					$data[ 'attribut' ][] = array( 'id'=>$crypt, 'value'=> $attribut[ $i ][ 'nom' ][ 'value' ] ) ;

					break ;

				case ( mb_substr( $attribut[ $i ][ 'type' ][ 'value' ], 0, 8 ) == 'many2one' ):
					$data[ 'many2one' ][] = array(
											'obj' => $_GET[ 'objectselected' ] ,
											'key' => $attribut[ $i ][ 'attr_name' ][ 'value' ] ,
											) ;


					break ;

			}
		}

		/* les données du fichier */
		$data[ 'csvimport' ] = array() ;
		$fh = fopen( $this->_filepath . '/' . $_GET[ 'file' ], 'r' ) ;
		if( $fh === false )
		{
			throw new Exception( 'read file failed' );
		}

		$i = 0 ;
		while ( ( $csvdata = fgetcsv( $fh , 1000, ";" ) ) !== false )
		{
			if( $i == 11 )
			{
				break ;
			}
			$data[ 'csvimport' ][] = $csvdata ;

			$i++ ;
		}

		fclose( $fh ) ;

		$data[ 'file' ][ 'filename'][ 'value' ] = $_GET[ 'file' ] ;
		$data[ 'obj' ][ 'objname'][ 'value' ] = $_GET[ 'objectselected' ] ;

		global $hUI;

		$hUI = new UI() ;
		$hUI->render( './library/killi/template/csvimportpre.xml', 0, $data, TRUE) ;

		return True ;
	}

	public function save()
	{
		unset( $_SESSION['_ERROR_LIST'] ) ;

		/* tests des donnees */
		if( !isset( $_POST[ 'file/filename' ] ) && trim( $_POST[ 'file/filename' ] ) == '' )
		{
			throw new Exception( 'file/filename value not valid' ) ;
		}
		if( !isset( $_POST[ 'obj/objname' ] ) && trim( $_POST[ 'obj/objname' ] ) == '' )
		{
			throw new Exception( 'obj/objname value not valid' ) ;
		}

		/*  constitution des attributs requis */
		$attribut = array() ;
		$hattribut = new AttributeMethod() ;
		$hattribut->getAttributeFromObject(  $_POST[ 'obj/objname' ], $_SESSION[ '_USER' ][ 'profil_id' ][ 'value' ], $attribut ) ;

		$attrRequired = array() ;
		for( $i = 0 ; $i < count( $attribut ) ; $i++ )
		{
			if( $attribut[ $i ][ 'type' ][ 'value' ] == 'primary key' )
			{
				continue ;
			}

			if( $attribut[ $i ][ 'required' ][ 'value' ] == 1  )
			{
				$attrRequired[ $attribut[ $i ][ 'attr_name' ][ 'value' ] ] =  $attribut[ $i ][ 'attr_name' ][ 'value' ] = $attribut[ $i ][ 'nom' ][ 'value' ] ;
			}
		}

		$attrFile   = array() ;
		$attrStatic = array() ;

		foreach( $_POST as $k => $v )
		{
			if( preg_match( '/^attr\//', $k ) )
			{
				list( $a, $b ) = explode( '/', $k ) ;

				if( $_POST[ $k ]  == '0' )
				{
					continue ;
				}
				$attrFile [ $b ] = $_POST[ $k ] ;
			}

			if( preg_match( '/^' . $_POST[ 'obj/objname' ] . '\//', $k ) )
			{
				list( $a, $b ) = explode( '/', $k ) ;

				$attrStatic[ $b ] = $_POST[ $k ] ;
			}
		}

		$_SESSION['_ERROR_LIST'] = array() ;
		foreach( $attrRequired as $k1 => $v1 )
		{
			$found = 0 ;
			foreach( $attrStatic  as $k2 => $v2 )
			{
				if( $k1 == $k2 )
				{
					$found = 1 ;
					break ;
				}
			}

			if( $found == 1 )
			{
				continue ;
			}

			$found = 0 ;
			foreach( $attrFile  as $k2 => $v2 )
			{
				if(  $v2 == $k1 )
				{
					$found = 1 ;
					break ;
				}
			}

			if( $found == 0 )
			{
				$_SESSION['_ERROR_LIST'][] = $attrRequired[ $k1 ]  . ' Manquant';
			}
		}

		if( isset( $_SESSION['_ERROR_LIST'][ 0 ] ) )
		{
			header( 'HTTP/1.1 301 Moved Permanently' ) ;
			header( 'Location: ' . './index.php?action=csvimport.displayStructure&view=form&mode=edition&file=' . $_POST[ 'file/filename' ] . '&objectselected=' . $_POST[ 'obj/objname' ] . '&token=' . $_SESSION['_TOKEN'] ) ;
			exit( 0 ) ;
		}

		/* lecture du ficher */
		$line = array() ;

		if( file_exists(  $this->_filepath . '/' . $_POST[ 'file/filename' ] ) === false )
		{
			throw new Exception( 'file not found : ' . $this->_filepath . '/' . $_POST[ 'file/filename' ] ) ;
		}
		if( is_readable( $this->_filepath . '/' . $_POST[ 'file/filename' ] ) === false  )
		{
			throw new Exception( 'file not readable : ' . $this->_filepath . '/' . $_POST[ 'file/filename' ] ) ;
		}

		ini_set( 'max_execution_time' , 0 ) ;
		$fh = fopen( $this->_filepath . '/' . $_POST[ 'file/filename' ], 'r' ) ;
		if( $fh === false )
		{
			throw new Exception( 'read file failed' );
		}

		while ( ( $csvdata = fgetcsv( $fh , 1000, ";" ) ) !== false )
		{
			for( $i = 0 ; $i  < count( $csvdata ) ; $i++ )
			{
				if( strpos( $csvdata[ $i ], '#') !== false )
				{
					continue 2 ;
				}
			}

			$line[] = $csvdata ;
		}

		fclose( $fh ) ;

		/* Enregistrement */

		$hO  = new $_POST[ 'obj/objname' ] ;
		$OM  = $_POST[ 'obj/objname' ] . 'Method' ;
		$hOM = new $OM  ;
		for( $i = 0 ; $i < count( $line ) ; $i++ )
		{
			$obj_data = array() ;

			foreach( $attrFile as $k => $v )
			{
				$obj_data[ $v ] = $line[ $i ][ $k ] ;
			}

			foreach( $attrStatic as $k => $v )
			{
				$obj_data[ $k ] = $v ;
			}

			foreach( $obj_data  as $k => $v )
			{
				if( !isset( $attrRequired[ $k ] ) )
				{
					continue ;
				}

				if( preg_match( "/^(?:[-])?\d+(?:[,]\d+)?$/", $v ) )
				{
					$v = preg_replace( '/\,/m', '.', $v) ;
				}
				if( $hO->$k->secureSet( $v ) === false )
				{
					$_SESSION['_ERROR_LIST'][] = 'Erreur ligne ' . ($i+1) . ' ' . $attrRequired[ $k ] ;
					break ;
				}
			}

			if( isset( $_SESSION['_ERROR_LIST'][ 0 ] ) )
			{
				header( 'HTTP/1.1 301 Moved Permanently' ) ;
				header( 'Location: ' . './index.php?action=csvimport.displayStructure&view=form&mode=edition&file=' . $_POST[ 'file/filename' ] . '&objectselected=' . $_POST[ 'obj/objname' ] . '&token=' . $_SESSION['_TOKEN'] ) ;
				exit( 0 ) ;
			}

			$hOM->create( $obj_data, $id ) ;
		}

		unlink( $this->_filepath . '/' . $_POST[ 'file/filename' ] ) ;

		header( 'HTTP/1.1 301 Moved Permanently' ) ;
		header( 'Location: ' . './index.php?action=csvimport.edit&view=form&mode=edition&token=' . $_SESSION['_TOKEN'] ) ;

		return True ;
	}
}

