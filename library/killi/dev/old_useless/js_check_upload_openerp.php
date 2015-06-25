<?php

require_once('./library/killi/include/config.php');
require_once('./library/killi/class/class.Security.php');
require_once('./library/killi/class/xmlrpc.php');
require_once('./library/killi/class/class.XMLRPC.php');


$files = array();
$x = new XMLRPC();
foreach($_POST as $key => $value) {
	$ids = array();
	$data_struct = array();
	if($key != 'folder') {
		$data_struct = array
		(
			array('datas_fname', 'ilike', $value)
		);

		$x->search('ir.attachment', $data_struct, $ids);

		if(count($ids)) {
			$files[$key] = $value;
		}
	}
}

echo json_encode($files);


