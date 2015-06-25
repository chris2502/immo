<?php

/**
 *
 *  @class LockProcess
 *  @Revision $Revision: 4198 $
 *
 */

class LockProcess
{
	private $_process_name	   = null ;
	private $_unlock_on_destruct = false ;

	public function __construct( $config = null )
	{
		if( is_array( $config ) )
		{
			if( isset( $config[ 'TIMEOUT' ] ) && is_numeric( $config[ 'TIMEOUT' ] ) )
			{
				define( 'LOCK_TIMEOUT', $config[ 'TIMEOUT' ] ) ;
			}

			if( isset( $config[ 'AUTOUNLOCK' ] ) &&  $config[ 'AUTOUNLOCK' ] === true )
			{
				$this->_unlock_on_destruct = true ;
			}
		}
	}

	public function __destruct()
	{
		if( $this->_unlock_on_destruct === true )
		{
			$this->unlock( $this->_process_name ) ;
		}
	}

	public function lock( $process_name )
	{
		if( !isset( $process_name ) || trim( $process_name ) == '' )
		{
			return false ;
		}
		$this->_process_name = $process_name ;

		if( $this->isrunning() === False )
		{
			return false ;
		}

		if( file_put_contents( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX, date( 'U' ) ) === false )
		{
			return false;
		}

		return true ;
	}

	public function unlock( $process_name )
	{

		if( !isset( $process_name ) || trim( $process_name ) == '' )
		{
			return false ;
		}
		$this->_process_name = $process_name ;

		if( file_exists( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX ) === true )
		{
			unlink( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX ) ;
		}

		return true ;
	}

	public function isrunning( $process_name = null )
	{
		if( is_null( $process_name ) === false && trim( $process_name ) != '' )
		{
			$this->_process_name = $process_name ;
		}

		if( ( is_null( $this->_process_name ) === true ) || ( trim( $this->_process_name ) == '' ) )
		{
			return false ;
		}

		if( file_exists( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX ) === true )
		{
			$locktime = file_get_contents( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX ) ;
			if( $locktime !== false )
			{
				if( ( $locktime + LOCK_TIMEOUT ) < date( 'U' ) )
				{
					if( file_put_contents( LOCK_DIR . '/' . $this->_process_name . LOCK_SUFFIX, date( 'U' ), LOCK_EX ) !== false )
					{
						return true ;
					}
				}
			}

			return false ;
		}

		return true ;
	}
}
