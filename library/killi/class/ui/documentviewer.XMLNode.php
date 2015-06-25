<?php

/**
 *	@class DocumentViewerXMLNode
 *	@Revision $Revision: 4282 $
 *	
 */

class DocumentViewerXMLNode extends XMLNode
{
	private $_doctype = NULL;

	public function open()
	{
		$key		=  $this->getNodeAttribute('key');
		$object		= $this->getNodeAttribute('object');

		if (!isset($this->_current_data[$key]['value']))
		{
			throw new Exception("Impossible to find ".$key." key in current_data.", 1);
		}

		$object_id	=	$this->_current_data[$key]['value'];

		// TODO : Passer un data_src pour afficher un ensemble de documents.
		// $data_src = $this->getNodeAttribute('data_src');

		$document_list = array();
		ORM::getORMInstance('document')->browse(
			$document_list,
			$total,
			array('hr_name', 'document_id', 'mime_type', 'date_creation', 'size', 'users_id', 'etat_document_id')	,
			array( array('object', '=', $object), array('object_id', '=', $object_id) )		
		);

		if (count($document_list) == 0)
		{
			?><div id="error_list_table" class="alert alert-info">Aucun document à afficher</div><?php
			return TRUE;
		}

		// TODO : Faire en sorte de pouvoir activer ou désactiver le téléchargement et l'affichage des infos et/ou de sélectionner les champs à afficher
		foreach ($document_list as $doc)
		{
			$doc_id	= $doc['document_id']['value'];
			$type_mime	= $doc['mime_type']['value'];

			$crypted_doc_id		= '';
			Security::crypt($doc_id, $crypted_doc_id);

			$content = '';
			$type = '';
			switch ($type_mime)
			{
				case 'application/pdf':
					$this->_renderPDF($crypted_doc_id, $content);
					$type = 'PDF';
					break;
				
				default:
					?><div id="error_list_table" class="alert alert-info">Le type de document <strong><?php echo $type_mime; ?></strong> n'est pas encore pris en charge par le docuement viewer</div><?php
					break;
			}

			$size = $this->formatFileSize($doc['size']['value']);

			?>
			<div class="docviewer-item">

				<div class="docviewer-title cl">

					<div class="docviewer-infos l">
						<div class="docviewer-line cl">
							<div class="docviewer-label l">Nom : </div><div class="docviewer-txt l"><?php echo $doc['hr_name']['value']; ?></div>
						</div>
						<div class="docviewer-line cl">
							<div class="docviewer-label l">Créateur : </div><div class="docviewer-txt l"><?php echo $doc['users_id']['reference']; ?></div>
						</div>
						<div class="docviewer-line cl">
							<div class="docviewer-label l">Date de création : </div><div class="docviewer-txt l"><?php echo $doc['date_creation']['value']; ?></div>
						</div>	
						<div class="docviewer-line cl">
							<div class="docviewer-label l">Taille : </div><div class="docviewer-txt l"><?php echo $size; ?></div>
						</div>		
						<div class="docviewer-line cl">
							<div class="docviewer-label l">Type : </div><div class="docviewer-txt l"><?php echo $type; ?></div>
						</div>						
					</div>

					<div class="docviewer-dl r">
						<a href="./index.php?action=document.edit&token=<?php echo $_SESSION['_TOKEN']; ?>&view=form&crypt/primary_key=<?php echo $crypted_doc_id; ?>"><i class="fa fa-download fa-2x"></i></a>
					</div>

				</div>

				<div class="docviewer-content">
					<?php echo $content; ?>
				</div>

			</div><?php
		}

		return TRUE;
	}

	private function _renderPDF($crypted_doc_id, &$content)
	{
		$c_mime_type = '';
		Security::crypt('application/pdf', $c_mime_type);
		$token = $_SESSION['_TOKEN'];

		$content = '<embed src="./index.php?action=document.getDocContent&crypt/primary_key='.$crypted_doc_id.'&crypt/mime_type='.$c_mime_type.'&token='.$token.'" type="application/pdf" width="100%" height="600">';

		return TRUE;
	}

	private function formatFileSize($bytes, $dec = 2) 
	{
	    $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $factor = floor((strlen($bytes) - 1) / 3);

	    return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}
}
