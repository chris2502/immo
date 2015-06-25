<?php

/**
 *  @class FormXMLNode
 *  @Revision $Revision: 3523 $
 *
 */

class FormXMLNode extends XMLNode
{
	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if($view != 'form')
		{
			return false;
		}
		parent::__construct($structure, $parent, $view);
	}

	public function render($data_list, $view)
	{
		if($view != 'form')
		{
			return false;
		}
		parent::render($data_list, $view);
	}

	public function open()
	{
		$object = $this->getNodeAttribute('object');
		$action = $this->getNodeAttribute('action', $object.'.write');
		$login = $this->getNodeAttribute('login', false);
		$css_class = $this->getNodeAttribute('css_class', NULL);
		$crypted_login = '';
		if ($login == 1 && isset($_SESSION['_USER']))
		{
			 Security::crypt($_SESSION['_USER']['login']['value'], $crypted_login);
			 $crypted_login = '&crypt/login='.$crypted_login;
		}

		$upload_tgt = $this->getNodeAttribute('upload_target', '');

		if (!empty($upload_tgt)):
?>
		<!-- Progress bar -->
		<div id="progressMeter" style="display: none;">
			<h2>Traitement en cours...</h2>
			<div id="progressBar"><div id="meter" style="width: 0;"></div></div>
		</div>
		<!-- Cible d'upload -->
		<iframe id="<?php echo $upload_tgt; ?>" name="<?php echo $upload_tgt; ?>" width="800" height="200" frameborder="0" style="display:none;" onload="_reloadPageNoEdition();"></iframe>
		<input id="__upload_started" type="hidden" value="0" />
		<input id="__upload_target" type="hidden" value="<?php echo $upload_tgt; ?>" />
		<?php
		endif;

		?><form name="main_form" id="main_form" method="post" <?php if($css_class != NULL){ echo 'class="'.$css_class.'"'; } ?> action="./index.php?action=<?php echo $action; echo $crypted_login; if (!empty($upload_tgt)): ?>&inside_iframe=1<?php endif; ?>" enctype="multipart/form-data"<?php if (!empty($upload_tgt)): ?> target="<?php echo $upload_tgt; ?>"<?php endif; ?>><?php

			if (!empty($upload_tgt))
			{
				?><input type="hidden" id="keyFile" name="APC_UPLOAD_PROGRESS" value="<?php echo uniqid(); ?>" /><?php
			}

			?><input type="hidden" name="form_data_checking" value="1"/><?php
			?><input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN'] ?>"/><?php

			if (isset($_GET['inside_popup']))
			{
				?><input type="hidden" name="refresh_parent" value="1"/><?php
			}

		if (isset($_GET['crypt/primary_key']))
		{
			$hInstance = ORM::getObjectInstance($object);
			?><input type="hidden" id="crypt/<?= $hInstance->primary_key ?>" name="crypt/<?= $hInstance->primary_key ?>" value="<?= $_GET['crypt/primary_key'] ?>"/><?php
		}
		return TRUE;
	}
	//.....................................................................
	public function close()
	{
		?></form><?php

		return TRUE;
	}
}
