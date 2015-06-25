<?php

require_once('./library/killi/class/class.DbLayer.php');
require_once('./library/killi/class/class.ObjectDefinition.php');
require_once('./library/killi/include/config.php');
require_once('./library/killi/class/xmlrpc.php');
require_once('./library/killi/class/class.XMLRPC.php');
require_once('./include/config.php');

function __autoload($name) {
	$filename = "./class/class.$name.php";
	if(file_exists($filename)) {
		require_once($filename);
	} else {
		$filename = "./library/killi/class/class.$name.php";
		if(file_exists($filename)) {
			require_once($filename);
		}
	}
}

if(count($_FILES)) {
	$file = $_FILES['__document__'];
	$tempFile = $file['tmp_name'];
	$targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/';
	$targetFile =  str_replace('//','/',$targetPath) . $file['name'];
	
	$ret = move_uploaded_file($tempFile,$targetFile);

	if($ret !== FALSE) {
		$mode = FILE_TEXT;
		$gzmode = 'w';

		if($file['type'] != 'text/plain') {
			$mode = FILE_BINARY;
			$gzmode .= 'b9';
		}

		if(($filecontent = file_get_contents($targetFile, $mode)) !== FALSE) {
			$targetFileGz = $targetFile . '.gz';
			$f = gzopen($targetFileGz, $gzmode);
			gzwrite($f, $filecontent);
			gzclose($f);

			$sub = str_replace(' ', '', $file['name']);
			$realtargetFile = $targetFile;
			$targetFile = $targetFileGz;
			$filecontent = file_get_contents($targetFile, FILE_BINARY);
			$subname = substr($sub, 0, strrpos($sub, '.'));
			$document_name = sprintf('%s-%s-%s', date('Y-m-d'), $subname, $_POST['openerp_id']);
			$document_object = $_POST['object'];
			$document_reference = $_POST['object_ref'];
			$openerp_model = $_POST['openerp_model'];
			$openerp_id = $_POST['openerp_id'];

			$x = new XMLRPC();

			if($openerp_model == 'ftth.address.address') {
				$adr_list = array();
				$data_struct = array
				(
					array('si_address_id', '=', $openerp_id)
				);

				$x->search('ftth.address.address', $data_struct, $adr_list);

				if(empty($adr_list)) {
					$data_struct = array
					(
						array('si_address_id', '=', $openerp_id),
						array('active', '=', False)
					);

					$x->search('ftth.address.address', $data_struct, $adr_list);
				}

				$openerp_id = $adr_list[0];
			}

			$id_list = array();

			$data_struct = array
			(
				array('name', 'ilike', $document_name),
				array('datas_fname', 'ilike', $file['name']),
				array('res_model', '=', $openerp_model),
				array('res_id', '=', $openerp_id)
			);

			$x->search('ir.attachment', $data_struct, $id_list);

			$data_struct = array
			(
				array
				(
					'name' => $document_name,
					'datas_fname' => $file['name'],
					'file_size' => $file['size'],
					'file_type' => $file['type'],
					'res_model' => $openerp_model,
					'res_id' => $openerp_id,
					'datas' => base64_encode($filecontent)
				)
			);

			$obj = new $document_object;

			if(!count($id_list)) {
				$x->create('ir.attachment', $data_struct, $id_list);

				if(count($id_list))
				{
					$query = 'insert into `' . DBSI_DATABASE.'`.`' . $obj->table . '` (`' . $document_reference . '`, `document_id`) values  (\'' . $openerp_id . '\', \''.$id_list[0].'\')';
					$hDB->db_execute($query);
				}
			} else {
				$x->write('ir.attachment', $data_struct[0], $id_list[0]);
			}
			

			unlink($realtargetFile);
			unlink($targetFile);
		}

		echo "1";
	} else {
		echo "0";
	}
} else {
	echo "0";
}


