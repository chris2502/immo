<?php

/**
 *  @class AlertXMLNode
 *  @Revision $Revision: 3870 $
 *
 */

class AlertXMLNode extends XMLNode
{
	public static function show_alerts($target, $close = FALSE)
	{
		$msg_type = array('error' => 'error',
						  'warning' => 'warn',
						  'info' => 'info',
						  'success' => 'success');

		$haveMessages = false;

		foreach($msg_type AS $type => $css)
		{
			if(isset($_SESSION['_ALERT'][$type][$target]))
			{
				foreach($_SESSION['_ALERT'][$type][$target] AS $title => $messages)
				{
					foreach($messages AS $index => $message)
					{
						$haveMessages = true;
						?>
						<div id="error_list_table" class="alert alert-<?= $css ?>">
							<?php
								if($close)
								{
									?>
									<a href="#" type="button" class="close" data-dismiss="alert">&times;</a>
									<?php
								}
							?>
							<h4><?= $title ?></h4> <?= $message ?>
						</div>
						<?php
						if (!($target == 'error' && isset($_GET['inside_iframe'])))
						{
							unset($_SESSION['_ALERT'][$type][$target][$title][$index]);
						}
					}

					if(empty($_SESSION['_ALERT'][$type][$target][$title]))
					{
						unset($_SESSION['_ALERT'][$type][$target][$title]);
					}
				}

				if(empty($_SESSION['_ALERT'][$type][$target]))
				{
					unset($_SESSION['_ALERT'][$type][$target]);
				}
			}
		}

		return $haveMessages;
	}

	public function open()
	{
		$target = $this->getNodeAttribute('target', 'global');
		$close = $this->getNodeAttribute('close', '1') == '1';

		$title = $this->getNodeAttribute('title', '');
		$message = $this->getNodeAttribute('string', '');
		$type = $this->getNodeAttribute('type', '');

		if(!empty($title) && !empty($message) && !empty($type))
		{
			$_SESSION['_ALERT'][$type][$target][$title][0] = $message;
		}
		self::show_alerts($target, $close);
	}
}
