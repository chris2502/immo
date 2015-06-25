<?php

/**
 *  @class WizardXMLNode
 *  @Revision $Revision: 4099 $
 *
 */

class WizardXMLNode extends XMLNode
{
	public $uuid = NULL;
	public $step = 0;

	public function render($data_list, $view)
	{
		if($view != 'wizard')
		{
			return TRUE;
		}

		/* Parameters. */
		$this->_data_list = &$data_list;
		$this->_view = $view;

		if(!$this->check_render_condition())
		{
			return FALSE;
		}

		/* Recursive Rendering. */
		if(headers_sent() === TRUE)
		{
			echo '<!-- Open ', $this->name, ' -->', PHP_EOL;
		}

		$this->open();
		$child = $this->_childs[$this->step];
		$child->render($data_list, $view);

		$this->close();
		echo '<!-- Close ', $this->name, ' -->', PHP_EOL;
	}

	public function open()
	{
		$crypt_step = NULL;
		Security::crypt($this->step, $crypt_step);

		?><div class="wizard-container">
			<form name="main_form" id="main_form" method="post" action="./index.php?action=<?= $_GET['action'] ?>" enctype="multipart/form-data">
				<input type="hidden" name="form_data_checking" value="1"/>
				<input type="hidden" id="__token" name="token" value="<?= $_SESSION['_TOKEN']; ?>"/>
				<input type="hidden" name="wizard_uuid" value="<?= $this->uuid; ?>"/>
				<input type="hidden" name="crypt/step" value="<?= $crypt_step; ?>"/>

				<div class="wizard-crumblepath cl"><?php

				$count = 1;
				foreach ($this->_childs as $index => $child_data)
				{
					$title = $child_data->getNodeAttribute('string', 'Etape '.$count);
					$curr = '';
					if ($count == $this->step + 1)
					{
						$curr = ' current-step';
					}

					?><div class="wizard-crumblepath-step l<?php echo $curr; ?>"><?php echo '<span class="w-crumb-step-count">'.$count.'</span>'.$title; ?></div><?php

					$count++;
				}

				?></div><?php

	}

	public function close()
	{
			?><div class="wizard-btn-container">
					<?php
					if($this->step > 0 && $this->step < count($this->_childs)-1)
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-prev" type="submit" name="submitBtn" value="preceding">Précédent</button><?php
					}

					if($this->step < count($this->_childs)-1)
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-next" type="submit" name="submitBtn" value="following">Suivant</button><?php
					}
					else
					{
						?><button class="wizard-btn wizard-btn-validation wizard-btn-end" type="submit" name="submitBtn" value="terminate">Terminer</button><?php
					}
					?>
				</div>
			</form>
		</div>
		<script>
		$(document).ready(function()
		{
			$('.wizard-btn-validation').click(function(event)
			{
				var clone = $(this).clone();
				clone.html('En cours de chargement...');
				clone.attr("disabled", "disabled");
				$(this).after(clone);
				$(this).hide();
				$('#main_form').submit();
			});
		});
		</script>
		<?php
	}
}
