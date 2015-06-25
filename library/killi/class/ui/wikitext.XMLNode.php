<?php

class wikitextXMLNode extends XMLNode
{
	private $_parent;
	private $_text_html;
	private $_text_wiki;


	public function open()
	{
		$object			 = $this->getNodeAttribute('object');
		$attribute		  = $this->getNodeAttribute('attribute');
		$label			  = $this->getNodeAttribute('label','');

		$this->_text_wiki = $this->_current_data[$attribute]['value'];
		$this->_text_html = isset($this->_current_data[$attribute]['html'])?$this->_current_data[$attribute]['html']:$this->_text_wiki;


		if ($label == '' && $object)
		{
			$hObject = ORM::getObjectInstance($object);
			$this->_object_field = $hObject->$attribute;
			$label = $this->_object_field->name .' :';
		}

		$this->_addcss();
		
		?>
			<table class="field" cellspacing="2" cellpadding="1">
			<tbody>
				<tr>
					<td class="field_label"><?php echo $label; ?></td>
					<td></td>
				</tr>
		<?php

		if ($this->_edition)
		{
			?>
				   <tr>
					<td colspan="2" style="white-space:normal;">
					<textarea id="wiki-text" name="<?php echo $object,'/',$attribute; ?>"><?php echo $this->_text_wiki; ?></textarea>
					</td>
				</tr>
			</tbody>
			</table>
				
			<?php
		}
		else
		{
			$wikicode = new WikiCode($this->_text_html);
			// $wikicode->_replaceWikiImagesByTag();
			$wikicode->getHtml($this->_text_html);
			// $wikicode->_searchAndReplaceWikiImages();

		   ?>
			</tbody>
			</table>
			<div class="wikitextcontent">
			<?php echo $this->_text_html; ?>
			</div>
			<?php
		}

		return TRUE;
	} 


	private function _addcss()
	{
		?>
		<style type="text/css">
			.markItUpHeader {
				display:table;
			}
			.markItUpEditor {
				width:100%;
				height:120px;
			}
			.wikitextcontent
			{
				width: 100%;
				white-space:normal;
			}
			.wikitextcontent img
			{
				max-width: 100%;
			}
		</style>
		<?php   
	}

}
