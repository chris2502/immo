<?php

/**
 *  @class Json_dataXMLNode
 *  @Revision $Revision: 4466 $
 *
 */

class Json_dataXMLNode extends XMLNode
{
	public function open()
	{
		if ($this->_view =='form')
		{
			$object	= $this->getNodeAttribute('object');
			$attribute = $this->getNodeAttribute('attribute');
			$title	 = $this->getNodeAttribute('string');
			$editable  = ($this->getNodeAttribute('editable', '0') == '1');

			if (!$this->_edition || !$editable)
			{
				$data = NULL;
				if (isset($this->_current_data[$attribute]['value']) && !empty($this->_current_data[$attribute]['value']))
				{
					$data = json_decode($this->_current_data[$attribute]['value'],TRUE);
				}
				?>
				<table cellspacing="0" style="width: 100%; border: solid 1px #BBBBBB;">

					<tr class="ui-widget-header ui-state-hover">
						<th style="background-color: #AAAAFF; width: 4px;"></th>
						<th style="background-color: #EEEEEE; width: 1px;"></th>
						<th style="background-color: #AAAAFF; width: 2px;"></th>
						<th style="background-color: #EEEEEE; width: 1px;"></th>
						<th style="background-color: #AAAAFF; width: 1px;"></th>

						<th style="border-bottom: solid 1px #BBBBBB; background-color: #EEEEEE;"><?= $title ?></th>
						<th style="text-align: right; width: 60px; border-bottom: solid 1px #BBBBBB; background-color: #EEEEEE;"/>
					</tr>

					<tr>
						<td style="height: 2px;" colspan="7"></td>
					</tr>

					<tr>
						<td colspan="7">
							<?php $this->_generate_table($data); ?>
						</td>
					</tr>

				</table>
				<?php
			}
			else
			{
				if (!defined('JSON_EDITOR_INCLUDED'))
				{
				?>
				<script src="./library/killi/js/jquery.jsoneditor.min.js"></script>
				<link rel="stylesheet" type="text/css" href="./library/killi/css/jsoneditor.css"/>
				<?php
					define('JSON_EDITOR_INCLUDED', 1);
				}
				?>

				<table cellspacing="0" style="width: 100%; border: solid 1px #BBBBBB;">

					<tr class="ui-widget-header ui-state-hover">
						<th style="background-color: #AAAAFF; width: 4px;"></th>
						<th style="background-color: #EEEEEE; width: 1px;"></th>
						<th style="background-color: #AAAAFF; width: 2px;"></th>
						<th style="background-color: #EEEEEE; width: 1px;"></th>
						<th style="background-color: #AAAAFF; width: 1px;"></th>

						<th style="border-bottom: solid 1px #BBBBBB; background-color: #EEEEEE;"><?= $title ?></th>
						<th style="text-align: right; width: 60px; border-bottom: solid 1px #BBBBBB; background-color: #EEEEEE;"/>
					</tr>

					<tr>
						<td style="height: 2px;" colspan="7"></td>
					</tr>

					<tr>
						<td colspan="7">
							<div id="editor<?=$this->id?>" class="json-editor"></div>
						</td>
					</tr>
				</table>
				<textarea name="<?=$object?>/<?=$attribute?>" id="<?=$this->id?>" style="display:none;"><?=$this->_current_data[$attribute]['value']?></textarea>
				<script type="text/javascript">
				$(document).ready(function(){
					if ($('#<?=$this->id?>').val().trim() == '')
					{
						var json_data = {};
					}
					else
					{
						var json_data = JSON.parse($('#<?=$this->id?>').val());
					}
					$('#editor<?=$this->id?>').jsonEditor(
						json_data, 
						{
							change: function(data)
							{
								$('#<?=$this->id?>').val(JSON.stringify(data));
							}
						}
					);
				});
				</script>
				<?php
			}
		}
		else // Vue liste
		{
			echo substr($this->_current_data[$attribute]['value'],0,16).'...';
		}
	}

	private function _generate_table($data)
	{
	?>
		<table class="table_list" cellspacing="0">
			<tr>
				<th>Clé</th>
				<th>Valeur</th>
			</tr>

			<?php
			if(!is_null($data))
			{
				foreach($data as $key=>$value)
				{
				?>
				<tr>
					<td style="width:50%;"><?= $key ?></td>
					<td>
					<?php
					if (is_array($value))
					{
						$this->_generate_table($value);
					}
					else
					{
						echo $value;
					}
					?>
					</td>
				</tr>
				<?php
				}
			}
			?>
		</table>
	<?php
	}
}
