<?php

/**
 *  @class OpenerplinkXMLNode
 *  @Revision $Revision: 4555 $
 *
 */

class OpenerplinkXMLNode extends XMLNode
{
	public function open()
	{
		if(!in_array(ADMIN_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']) && !in_array(OPENERP_PROFIL_ID, $_SESSION['_USER']['profil_id']['value']))
			return TRUE;

		$id  = $this->getNodeAttribute('id', $this->_current_data['id']['value']);

		$function = ($this->getNodeAttribute('function', '0') == 1);  // default false

		$openerpobject = $this->getNodeAttribute('openerpobject');

		if( $function == TRUE )
		{
			$raw = explode('::', $function);
			$robj = ucfirst($raw[0]) . "Method";
			$obj = new $robj;
			$id = $obj->$raw[1]($id);

			if(is_null($id))
				return TRUE;
		}

		$action = "http://" . XMLRPC_SERVER_HTTP . ":8080/form/view?model=" . $openerpobject  . "&id=" . $id;

		?>
		<button type="button" onClick="return window.open('<?=$action; ?>', 'popup_buttonform_<?= $this->id ?>', config='height=600, width=800, toolbar=no, scrollbar=yes');" style="border: solid 2px #000000; width: 180px;">OPENERP</button>
		<?php

	}
}
