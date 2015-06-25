<?php

/**
 *  @class FileinputlocalXMLNode
 *  @Revision $Revision: 4605 $
 *
 */

function docsort($a, $b)
{
	$cmp = strcasecmp($a['name']['value'], $b['name']['value']);
	return (($cmp == 0)? 0 : ($cmp > 0));
}

class FileinputlocalXMLNode extends XMLNode
{
	public function open()
	{
		$attribute		  = $this->getNodeAttribute('attribute');
		$object			 = $this->getNodeAttribute('object', '');
		$label			  = $this->getNodeAttribute('string', '');
		$doc_type_id		= $this->getNodeAttribute('document_type_id', '');
		$force_type_display = $this->getNodeAttribute('force_type_display', '');
		$doc_type_filter	= $this->getNodeAttribute('document_type_filter', '');
		$read			   = TRUE;
		$write			  = TRUE;

		if(!empty($object))
		{
			$hInstance = ORM::getObjectInstance($object);
			if(property_exists($hInstance, $attribute))
			{
				Rights::getRightsByAttribute ( $hInstance->$attribute->objectName, $attribute, $read, $write );
			}
		}
		else
		{
			$action = explode('.', $_GET['action']);
			//$object = $action[0];
		}

		if( $write=== TRUE && (( $_GET['view'] == 'form' && isset($_GET['mode']) && $_GET['mode']=='edition' && isset($_GET['crypt/primary_key']) ) || $_GET['view'] == 'create'))
		{
			$action = explode('.', $_GET['action']);

			echo '<table class="field" cellspacing="2" cellpadding="1"><tr>';
			if(isset($_GET['crypt/primary_key']))
			{
				echo '<input type="hidden" id="__document_object_ref_id" name="crypt/document_object_ref_id" value="'.$_GET['crypt/primary_key'].'"/>';
			}
			$crypted_document_type_id=false;
			if(!empty($_GET['crypt/document_type_id']))
			{
				$clear_document_type_id=false;
				Security::decrypt($_GET['crypt/document_type_id'], $clear_document_type_id);
				if(substr($clear_document_type_id,0,5)=='eval:')
				{
					$eval_part = explode(':',$clear_document_type_id);
					eval('$clear_document_type_id ='.$eval_part[1].';');
					Security::crypt( $clear_document_type_id, $crypted_document_type_id);
				}
				else
				{
					$crypted_document_type_id=$_GET['crypt/document_type_id'];
				}
			}
			else if(!empty($doc_type_id))
			{
				$clear_document_type_id=false;
				if(substr($doc_type_id,0,5)=='eval:')
				{
					$eval_part = explode(':',$doc_type_id);
					eval('$clear_document_type_id ='.$eval_part[1].';');
				}
				else
				{
					$clear_document_type_id=$doc_type_id;
				}
				Security::crypt( $clear_document_type_id, $crypted_document_type_id);
			}

			if($crypted_document_type_id!==false && $force_type_display !="1")
			{
				echo '<input type="hidden" id="__document_type" name="crypt/document_type" value="'.$crypted_document_type_id.'"/>' ;
			}
			else
			{
				$doc_type_id_list = array();
				if(!empty($doc_type_filter))
				{
					$doc_type_id_raw = explode(',', $doc_type_filter);
					foreach($doc_type_id_raw AS $dt_id)
					{
						$type_txt = trim($dt_id);
						if(is_int($type_txt))
						{
							$doc_type_id = (int)$type_txt;
						}
						else
						{
							$doc_type_id = constant($type_txt);
						}
						$doc_type_id_list[] = $doc_type_id;
					}
				}

				$hORM = ORM::getORMInstance('documenttype');
				$doctype_list=array();
				$filters = array();
				$filters[] = array('name', '<>', 'inconnu');

				/* Limitation des types de documents à l'objet. */
				if (!empty($object))
				{
					$filters[] = array('object','=',$object);
				}

				/* Limitation des types de documents aux ID de documents. */
				if(count($doc_type_id_list) > 0)
				{
					$filters[] = array('document_type_id', 'in', $doc_type_id_list);
				}

				$hORM->browse($doctype_list, $total_doctype,array('document_type_id','name'), $filters);

				usort($doctype_list, 'docsort');
				echo '<table class="field" cellspacing="2" cellpadding="1"><tr><td class="field_label">Type de document : </td><td>';
				echo '<select id="__document_type" name="document_type" style="width: 300px;" class="required_field">';

				foreach($doctype_list as $doctype)
				{
					if(isset($_GET['crypt/document_type_id']))
					{
						Security::decrypt($_GET['crypt/document_type_id'], $decrypt_documents_type_id);
						if(substr($decrypt_documents_type_id,0,5)=='eval:')
						{
							$eval_part = explode(':',$decrypt_documents_type_id);
							eval('$decrypt_documents_type_id ='.$eval_part[1].';');
						}
						if($doctype['document_type_id']['value'] == $decrypt_documents_type_id)
						{
							echo sprintf('<option value="%s" selected="selected">%s</option>', $doctype['document_type_id']['value'], ($doctype['name']['value']));
						}
						else
						{
						  echo sprintf('<option value="%s">%s</option>', $doctype['document_type_id']['value'], ($doctype['name']['value']));
						}
					}
					else
						echo sprintf('<option value="%s">%s</option>', $doctype['document_type_id']['value'], ($doctype['name']['value']));
				}
				echo '</select></td></tr>';
			}
			echo '<tr><td class="field_label">'.$label.' : </td><td><input name="'.$attribute.'" id="__'.$attribute.'" type="file" /></td></table>';
		}
		return TRUE;
	}

	public function close()
	{

	}
}
