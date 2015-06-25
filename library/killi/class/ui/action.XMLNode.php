<?php

/**
 *  @class ActionXMLNode
 *  @Revision $Revision: 4275 $
 *
 */

class ActionXMLNode extends XMLNode
{
	public function open()
	{
		$this->id = $this->id . '_' . uniqid();
		$string	= $this->getNodeAttribute('string');
		$js_method = $this->getNodeAttribute('js_method', NULL);

		$parent = $this->getParent();

		if ($js_method != NULL)
		{
			$key ='';

			if (isset($parent->name) && $parent->name == 'listing')
			{
				$pk_name = $parent->object_instance->primary_key;
				if (isset($this->_current_data[$pk_name]))
				{
					$pk = $this->_current_data[$pk_name]['value'];
					$crypted_pk = '';
					Security::crypt($pk, $crypted_pk);
					$key = ' data-key="'.$crypted_pk.'"';
				}
			}

			?>
			<button <?= $this->style() ?> type="button" onclick="<?php echo $js_method; ?>(this)" <?php echo $key; ?>><?php echo $string; ?></button>
			<?php
			return TRUE;
		}

		$object	= $this->getNodeAttribute('object');
		$method	= $this->getNodeAttribute('method');

		$refresh 					= $this->getNodeAttribute('refresh_parent', '0') == '1';
		$confirm_message			= $this->getNodeAttribute('confirm', '');
		$display_output				= $this->getNodeAttribute('display_output', '0') == '1';
		$dialog_url					= $this->getNodeAttribute('dialog_url', '');
		$ajax_mode					= $this->getNodeAttribute('ajax', '0') == '1';
		$dialog_width				= $this->getNodeAttribute('dialog_width', 800);
		$dialog_height				= $this->getNodeAttribute('dialog_height', 600);

		$primary_key_arg	= isset($_GET['crypt/primary_key']) ? 'crypt/primary_key='.$_GET['crypt/primary_key'] : '';
		
		if(!empty($parent))
		{
			if(!empty($parent->data_key))
			{
				$parent_key = $parent->getNodeAttribute('key', $parent->data_key);
				if(isset($this->_current_data[$parent_key]['value']))
				{
					$parent_id = $this->_current_data[$parent_key]['value'];
					$attribute = $this->getNodeAttribute('attribute', $parent_key);
					if(!empty($attribute))
					{
						$crypt_key = NULL;
						Security::crypt($parent_id, $crypt_key);
						$primary_key_arg .= '&crypt/' . $attribute . '=' . $crypt_key;
					}
				}
			}
		}
		
		$url = './index.php?action='.$object.'.'.$method.'&token='.$_SESSION['_TOKEN']. '&' . $primary_key_arg;

		if ($ajax_mode)
		{
			?><script type="text/javascript">
				function _activate_action_<?= $this->id ?>()
				{
					var confirmed = true;

					<?php
					if (!empty($confirm_message))
					{
						?>
						confirmed = confirm("<?= addslashes($confirm_message) ?>");
						<?php
					}
					?>

					if (confirmed)
					{
						$.ajax({
							async:	true,
							url:	  './index.php?action=<?php echo $object; ?>.<?php echo $method; ?>' + add_token(),
							data:	 '<?php echo $primary_key_arg ?>',
							dataType: 'text',
							success:  function(response) {
								$('#dialog<?= $this->id ?>').html(response);
								<?php if (!$refresh): ?>
								$('#dialog<?= $this->id ?>').dialog({bgiframe: true, modal: true});
								<?php else: ?>
								$('#dialog<?= $this->id ?>').dialog({
									closeOnEscape: false,
									bgiframe: true,
									modal: true,
									buttons: {
										'Ok': function() {
											$(this).dialog('close');
											location.reload();
										}
									}
								});
								// Faire dispaître le bouton de fermeture de la boîte de dialogue.
								$('a.ui-dialog-titlebar-close', $('#dialog<?= $this->id ?>').parent()).remove();
								<?php endif; ?>
							}
						});
					}
				}
			</script><?php
			?><div id="dialog<?= $this->id ?>" title="Information" style="display: none;"></div><?php
			?><button <?= $this->style() ?> <?= $this->css_class() ?> type="button" onClick="_activate_action_<?= $this->id ?>()"><?= $string ?></button><?php
		}
		else
		if ($display_output)
		{
			?>
			<script>
				function _activate_action_<?= $this->id ?>()
				{
					var confirmed = true;
					var url= '<?=$url?>';
					var serial = null;
					var dialog_width = '<?=$dialog_width?>';
					var dialog_height = '<?=$dialog_height?>';

					<?php
					if (!empty($confirm_message))
					{
						?>
						confirmed = confirm("<?= addslashes($confirm_message) ?>");
						<?php
					}
					?>

					if (confirmed)
					{
						<?php
						if(!empty($dialog_url))
						{
							?> $('body').append('<div id="dialog"></div>');
							var dialog_url = "<?= $dialog_url ?>";
							$('#dialog').load(dialog_url, null, function(a, b, c)
							{
								$(this).dialog(
								{
									autoOpen: true,
									buttons :
										{
											"Envoyer": function()
											{
												window.open(url+'&'+$.param($(':input')),'result_popup','menubar=no, status=no, scrollbars=yes, menubar=no, width='+dialog_width+', height='+dialog_height);
												$(this).dialog("destroy");														  $("#dialog").remove();
											}
										}
								});
							}); <?php
						}
						else
						{
							?> window.open('<?= $url ?>','result_popup','menubar=no, status=no, scrollbars=yes, menubar=no, width='+dialog_width+', height='+dialog_height) <?php
						}
						?>
					}
				}
			</script><?php

			$button				= new ButtonTag(); // WARNING: Deprecated
			$button->raw_style	= $this->style();
			$button->onclick	= '_activate_action_'.$this->id.'()';
			$button->content	= $string;
			$button->id			= $this->id;

			echo $button;
		}
		else
		{
			$message = "<center><b><font size=\"2\">Traitement en cours, merci de patienter</font></b><br /><br /><img src=\"". KILLI_DIR ."/images/wait.gif\"><br /><br /></center>";
			?><script>
				function _activate_action_<?= $this->id ?>()
				{
					var confirmed = true;

					<?php
					if (!empty($confirm_message))
					{
						?>
						confirmed = confirm("<?= addslashes($confirm_message) ?>");
						if(confirmed)
						{
							$('#dialog<?= $this->id ?>').dialog({bgiframe: true, modal: true});
						}
						<?php
					}
					?>

					if (confirmed)
					{
						document.location.href='<?= $url ?>'
					}
				}
			</script><?php
			?><div id="dialog<?= $this->id ?>" title="Information" style="display: none"><?= $message ?></div><?php
			?><button <?= $this->style() ?>  <?= $this->css_class() ?>type="button" onClick="_activate_action_<?= $this->id ?>()"><?= $string ?></button><?php
		}

		return TRUE;
	}
	//.....................................................................
	public function close()
	{
		return TRUE;
	}
}
