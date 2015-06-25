<?php

/**
 *  @class HTMLXMLNode
 *  @Revision $Revision: 4563 $
 *
 */

class HTMLXMLNode extends XMLNode
{
	public function open()
	{
		if(isset($_GET['render_node']))
		{
			return FALSE;
		}

		$ui_theme = UI::getTheme();

		if(!headers_sent())
		{
			header('Content-type: text/html; charset=UTF-8');
		}

		$nocache='';

		if(DISPLAY_ERRORS===TRUE || DISABLE_CACHE===true)
		{
			$nocache='?'.uniqid();
		}

		$title = HEADER_MESSAGE;

		$titleXMLNode = $this->search('title');
		if($titleXMLNode != NULL)
		{
			$string = $titleXMLNode->getNodeAttribute('string', '');
			$title = self::parseString($string, $this->_data_list, NULL) . ' -- ' . $title;
		}

		?><!DOCTYPE html><?php
		?><html><?php
			?><head><?php
				?><title><?php echo(strip_tags($title)); ?></title><?php
				?><meta http-equiv="content-type" content="text/html; charset=UTF-8" /><?php
				?><meta name="viewport" content="width=device-width" /><?php
				?><meta name="viewport" content="initial-scale=1.0" /><?php
				if (file_exists('images/favicon.png'))
				{
					?><link rel="icon" type="image/png" href="images/favicon.png" /><?php
				}

			$javascripts=array(

				// JQUERY

				'jquery/jquery.js',
				'jquery/jquery.cookie.js',
				'jquery/jquery.colorpicker.js',
				'jquery/jquery.qrcode.js',
				'jquery/jquery.tablesorter.js',
				'jquery/jquery.markitup.js',
				'jquery/jquery.jsoneditor.js',
				'jquery/dtree.js',
				'jquery/flexigrid.js',

				// JQUERY UI

				'jquery/ui/jquery.ui.core.js',
				'jquery/ui/jquery.ui.widget.js',
				'jquery/ui/jquery.ui.mouse.js',
				'jquery/ui/jquery.ui.draggable.js',
				'jquery/ui/jquery.ui.droppable.js',
				'jquery/ui/jquery.ui.position.js',
				'jquery/ui/jquery.ui.resizable.js',
				'jquery/ui/jquery.ui.dialog.js',
				'jquery/ui/jquery.ui.button.js',
				'jquery/ui/jquery.ui.datepicker.js',
				'jquery/ui/jquery.ui.tabs.js',
				'jquery/ui/jquery.ui.menu.js',
				'jquery/ui/jquery.ui.autocomplete.js',
				'jquery/ui/jquery.ui.timepicker-addon.js',
				'jquery/ui/jquery.ui.fullcalendar.js',
				'jquery/ui/jquery.ui.qtip.js',
				'jquery/ui/jquery.ui.multiselect.js',
				'jquery/ui/jquery.ui.slider.js',
				'jquery/ui/jquery.ui.toggles.js',
				'jquery/ui/jquery.ui.accordion.js',
				'jquery/ui/jquery.ui.tooltip.js',

				// BOOTSTRAP
				'bootstrap/bootstrap-alert.js',

				// Process condition editor
				'processconditioneditor.js',

				// KILLI
				'killi.js',

				// Extension de l'objet Date Javascript pour le support de la fonction format.
				'date-format-fr.extends.js',

				//markitup
				'markitup-set.js',

				// Handsontable
				'jquery.handsontable.full.js',

				// Special Events
				'jquery/ui/jquery.effects.core.js',
			);

			foreach($javascripts as $javascript)
			{
				?><script src="./library/killi/js/<?= $javascript; ?><?= $nocache; ?>"></script><?php
			}

			?><link type="text/css" rel="stylesheet" href="./library/killi/css/base/jquery.ui.all.css<?= $nocache; ?>"/><?php

			?><link type="text/css" rel="stylesheet" href="./library/killi/css/markitup-skin.css<?= $nocache; ?>"/><?php
			?><link type="text/css" rel="stylesheet" href="./library/killi/css/markitup-set.css<?= $nocache; ?>"/><?php

			?><link type="text/css" rel="stylesheet" href="./library/killi/css/processconditioneditor.css<?= $nocache; ?>"/><?php

			?><!-- Handsontable --><?php
			?><link type="text/css" rel="stylesheet" href="./library/killi/css/jquery.handsontable.full.css<?= $nocache; ?>" /><?php

			?><!-- KILLI --><?php

 			?><link type="text/css" rel="stylesheet" href="./library/killi/css/UI.css<?= $nocache; ?>" /><?php

			?><link type="text/css" rel="stylesheet" href="./library/killi/css/font-awesome.min.css" /><?php

			if(file_exists('./library/killi/css/UI.less'))
			{
				?><link rel="stylesheet/less" type="text/css" rel="stylesheet" href="./library/killi/css/UI.less<?= $nocache; ?>"/><?php
			}

			if($ui_theme !== NULL)
			{
				?><!-- Thème --><?php
				?><link type="text/css" rel="stylesheet" href="./library/killi/css/<?php echo $ui_theme; ?>/jquery.ui.<?php echo $ui_theme; ?>.all.css<?= $nocache; ?>" /><?php

				if(file_exists('./library/killi/css/'.$ui_theme.'/jquery.ui.'.$ui_theme.'.all.less'))
				{
					?><link rel="stylesheet/less" type="text/css" rel="stylesheet" href="./library/killi/css/<?php echo $ui_theme; ?>/jquery.ui.<?php echo $ui_theme; ?>.all.less<?= $nocache; ?>"/><?php
				}

				if(file_exists(KILLI_DIR . 'css/'.$ui_theme.'/js/script.js'))
				{

					?><script src="./library/killi/css/<?php echo $ui_theme; ?>/js/script.js"></script><?php
				}
			}

			if(file_exists('css/print.css'))
			{
				?><link type="text/css" rel="stylesheet" href="./css/print.css<?= $nocache; ?>" media="print"/><?php
			}

			if (isset($_SESSION['refresh_parent']))
			{

				?><script>
					window.opener.location.reload();
				</script><?php

			}
			unset($_SESSION['refresh_parent']);

			?><!-- Application --><?php

			//----Import other js
			if(is_dir('./js'))
			{
				$dir = scandir("./js");
				{
					foreach($dir AS $file)
					{
						if (substr($file,-3)===".js")
						{
							?><script src="./js/<?= $file ?><?= $nocache; ?>"></script><?php
						}
					}
				}
				//closedir($dir);
			}

			//----Import other css
			if(is_dir('./css'))
			{
				$dir = opendir("./css");
				{
					while (false !== ($file = readdir($dir)))
					{
						if (substr($file,-4)===".css")
						{
							?><link type="text/css" rel="stylesheet" href="./css/<?= $file ?><?= $nocache; ?>"/><?php
						}
						else if (substr($file,-5)===".less")
						{
							?><link rel="stylesheet/less" type="text/css" rel="stylesheet" href="./css/<?= $file ?><?= $nocache; ?>"/><?php
						}
					}
				}
				closedir($dir);
			}

			if (isset($_SESSION['_USER_PREFERENCES']['unlocked_header']['value']) && $_SESSION['_USER_PREFERENCES']['unlocked_header']['value'] == '0')
			{
				?><script>$(document).ready(lock_header);</script><?php
			}

			if(file_exists('./library/killi/js/lesscss.js'))
			{
				?><script src="./library/killi/js/lesscss.js<?= $nocache; ?>"></script><?php
			}

			if(DISPLAY_ERRORS===TRUE)
			{
				?><script>
				$(document).ready(function()
				{
					if(!$('#__token').val())
					{
						document.body.innerHTML+='<br/><h3>Erreur de token !</h3>#__token introuvable !';
					}
				});
				</script><?php
			}

			?></head><body><?php

		AlertXMLNode::show_alerts('global');

		if (isset($_SESSION['_ERROR_LIST']))
		{
			?><div id="error_list_table" class="alert alert-error"><?php

			foreach($_SESSION['_ERROR_LIST'] as $key=>$error)
			{
				?><div><?= $key ?> : <?= $error ?></div><?php
			}

			?></div><?php

			if (!isset($_GET['inside_iframe'])) {
				unset($_SESSION['_ERROR_LIST']);
			}
		}

		if (isset($_SESSION['_WARNING_LIST']))
		{
			?><div id="warning_list_table" class="alert alert-warn"><?php

			foreach($_SESSION['_WARNING_LIST'] as $key=>$error)
			{
				?><div><?= $key ?> : <?= $error ?></div><?php
			}

			?></div><?php

			unset($_SESSION['_WARNING_LIST']);
		}

		if (isset($_SESSION['_MESSAGE_LIST']))
		{
			?><div id="message_list_table" class="alert alert-info"><?php

			foreach($_SESSION['_MESSAGE_LIST'] as $key=>$error)
			{
				?><div><?= $key ?> : <?= $error ?></div><?php
			}

			?></div><?php

			unset($_SESSION['_MESSAGE_LIST']);
		}

		if (isset($_SESSION['_POST_TRAITEMENT']))
		{

			foreach($_SESSION['_POST_TRAITEMENT'] as $key=>$value)
			{
				echo "<div id=\"".$key."\">".$value."</div>";
			}
			unset($_SESSION['_POST_TRAITEMENT']);
		}

		return TRUE;
	}
	//.....................................................................
	public function close()
	{
		if(isset($_GET['render_node']))
		{
			return FALSE;
		}

		if (isset($_SESSION['_USER']) || DISPLAY_ERRORS)
		{
			global $start_time,
				   $start_memory,
				   $object_list,
				   $hDB;

			$mem_usage		= (memory_get_usage()-$start_memory)/(1024*1024);
			$mem_peak		= memory_get_peak_usage()/(1024*1024);
			$ellapsed_time	= microtime(true)-$start_time;
			$sql_time		= $hDB->_cumulateProcessTime*1000;
			$sql_count		= $hDB->_numberQuery++;

			$span_error=' style="color:red;font-weight:bold"';


			$cpu1=sys_getloadavg();
			if(!defined('BENCHMARK_INFO') || BENCHMARK_INFO!==false)
			{
				?><center style='margin-top:40px;margin-bottom:10px' id="benchmark_infos">Cpu : <span><?php printf("%1.2f",($cpu1[0]/(count(explode("\n",`cat /proc/cpuinfo | grep processor`))-1))*100) ?> %</span><?php
				?> / Memory : <span><?php printf("%1.2f",self::get_memory()) ?> %</span><?php
				?> / Mem Usage : <span<?= DISPLAY_ERRORS && $mem_usage>30 		? $span_error : null ?>><?php printf("%1.2f",$mem_usage) ?> Mo</span><?php
				?> / Peak : <span<?= DISPLAY_ERRORS && $mem_peak>30 		? $span_error : null ?>><?php printf("%1.2f",$mem_peak) ?> Mo</span><?php
				?> / Page generated in <span<?= DISPLAY_ERRORS && $ellapsed_time>1.5 ? $span_error : null ?>><?php printf("%1.3f",$ellapsed_time) ?> sec</span><?php
				?> / MySQL : <span<?= DISPLAY_ERRORS && $sql_count>200 	? $span_error : null ?>><?= $sql_count ?> queries</span> in <span<?= DISPLAY_ERRORS && $sql_time>500 		? $span_error : null ?>><?php printf("%1.3f",$sql_time) ?> msec</span> (<?php printf("%1.2f",$sql_time!=0?100/($ellapsed_time/($sql_time/1000)):0) ?> %)<?php

				if (class_exists('KilliCurl')  && KilliCurl::$queries_number > 0)
				{
					?> / Curl : <span<?= DISPLAY_ERRORS && KilliCurl::$queries_number>20 	? $span_error : null ?>><?= KilliCurl::$queries_number ?> queries</span> in <span<?= DISPLAY_ERRORS && KilliCurl::$time>60 		? $span_error : null ?>><?php printf("%1.3f",KilliCurl::$time) ?> sec</span><?php
				}

				echo (DbLayer::$lock == TRUE ? ' / <span style="color:red;font-weight:bold">LOCK</span>':'');

				?></center><?php

				if(DISPLAY_ERRORS)
				{
					$rows_count_alert = $hDB->_numberRows>1000 ? $span_error : null;


					?><center style='margin-bottom:10px' id="benchmark_infos">
						Selected rows : <span<?= $rows_count_alert ?>><?php printf('%s',$hDB->_numberRows) ?></span>
					 	/ APC/OPcache : <?= (function_exists('apc_fetch') || function_exists('opcache_reset'))?'<span style="color:green;font-weight:bold">ON</span>':'<span style="color:red;font-weight:bold">OFF</span>'?>
					  	/ ORM read : <span><?php printf('%1.3f', ORM::$_cumulate_process_time*1000) ?> msec</span>
					  	/ UI->render() : <span><?php printf('%1.3f', UI::$_start_time_render*1000) ?> msec</span>
						/ Crypt : <span><?php printf('%1.3f', Security::$_cumulateProcessTime*1000) ?> msec</span>
					  </center>
					  <center>
					  	SQL Stat : <span><?= isset($hDB) ? $hDB->db_stat() : null ?> msec</span>
					  </center>
					  <br/><?php
				}
			}
		}

		?>
		<script>
			$(document).ready(function()
			{
				if(run_stack)
				{
					run_stack();
				}
			});
		</script>
		<?php

		//---Affichage des messages d'erreurs
		if (isset($_SESSION['_ERROR_LIST']))
		{
			?>
			<script>
			<?php
			foreach($_SESSION['_ERROR_LIST'] as $key => $error)
			{
				$key = str_replace('/','_',$key);
				?>
				setError('<?=$key;?>', '<?=$error;?>');
				<?php
			}
			?>
			</script>
			<?php
			if (!isset($_GET['inside_iframe'])) {
				unset($_SESSION['_ERROR_LIST']);
			}
		}

		if(!empty(UI::$_refresh_data)) { ?>
			<script>
				var refreshFields = new Array();
					<?php
						foreach(UI::$_refresh_data AS $second => $field)
						{
						  echo 'refreshFields[' , $second , '] = [';
						  foreach($field AS $key => $data)
						  {
						  	echo '{';
						  	echo 'key: \'' , $key , '\', ';
							foreach($data AS $f => $v)
						  	{
								echo '\''.$f.'\': \'' , $v , '\', ';
						  	}
						  	echo '},';
						  }
						  echo '];', "\n";
						}
					?>
				if (typeof(String.prototype.localeCompare) === 'undefined') {
					String.prototype.localeCompare = function(str, locale, options) {
						return ((this == str) ? 0 : ((this > str) ? 1 : -1));
					};
				}
				var autoRefresh = true;
				//------------------------------------------------------------------------
				$(document).ready(function()
				{
					<?php
					$raw = explode('.', $_GET['action']);
					$url = 'index.php?action=' . $raw[0] . '.refresh&token=' . $_SESSION['_TOKEN'];
					foreach(UI::$_refresh_data AS $second => $field)
					{
						?>
					window.setInterval(function() {
						if(autoRefresh)
						{
							$.ajax({
								type:	 'POST',
								async:	true,
								url:	  '<?=$url?>',
								dataType: 'json',
								data:	 {fields: refreshFields[<?=$second?>]},
								success:  function(response) {
									$.each(response, function(key, value) {
										$('.refresh[key='+key+']').each(function() {
											var oldText = $(this).html();
											$(this).html(value);
											if(value != oldText)
											{
												$(this).css('background-color', '#77FF77').delay(400).queue(function() { $(this).css('background-color', 'transparent'); $(this).dequeue(); });
											}

											if(typeof $(this).attr('onchange') != 'undefined')
											{
												eval($(this).attr('onchange'));
											}
										});
									});
								}
							});
						}
					}, <?= $second*1000 ?>);
						<?php
					}
					?>
				});

			</script>
			<?php }

		?></body></html><?php

		return TRUE;
	}
	//.........................................................................
	private static function get_memory()
	{
		foreach(file('/proc/meminfo') as $ri)
			$m[strtok($ri, ':')] = strtok('');
		return 100 - (($m['MemFree'] + $m['Buffers'] + $m['Cached']) / $m['MemTotal'] * 100);
	}
}
