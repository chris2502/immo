<?php

namespace Killi\Core\ORM\Handler;

/**
 *  Interface pour les traitants de l'ORM
 *
 *  @package killi
 *  @interface HandlerInterface
 *  @Revision $Revision: 4445 $
 */

interface HandlerInterface
{
	/**
	 * @param array $original_id_list	tableau des id à lire
	 * @param array &$object_list		tableau des resultats
	 * @param array $original_fields	champs necessaires
	 */
	function read($u_original_id_list, &$object_list, array $original_fields=NULL);

	//-------------------------------------------------------------------------
	function create( array $object_data, &$object_id=null, $ignore_duplicate=false, $on_duplicate_key = FALSE );

	//-------------------------------------------------------------------------
	function write( $object_id, array $object, $ignore_duplicate=FALSE, &$affected=NULL);

	//-------------------------------------------------------------------------
	function browse(array &$object_list=NULL, &$total_record=0, array $fields=NULL, array $args=NULL, array $tri=NULL, $offset=0, $limit=NULL);

	//-------------------------------------------------------------------------
	function search(array &$object_id_list=NULL,&$total_record=0,array $args=NULL,array $order=NULL,$offset=0,$limit=NULL,array $extended_result=array());

	//-------------------------------------------------------------------------
	function count(&$total_record, $args=NULL);

	//-------------------------------------------------------------------------
	function unlink($object_id);
}
