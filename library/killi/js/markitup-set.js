// -------------------------------------------------------------------
// markItUp!
// -------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// -------------------------------------------------------------------
// Textile tags example
// http://en.wikipedia.org/wiki/Textile_(markup_language)
// http://www.textism.com/
// -------------------------------------------------------------------
// Feel free to add more tags
// -------------------------------------------------------------------
mySettings = {
    nameSpace:              "textile",
	onShiftEnter:		{keepDefault:false, replaceWith:'\n\n'},
	markupSet: [
		{name:'Heading 1', key:'1', openWith:'h1(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Heading 2', key:'2', openWith:'h2(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Heading 3', key:'3', openWith:'h3(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Heading 4', key:'4', openWith:'h4(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Heading 5', key:'5', openWith:'h5(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Heading 6', key:'6', openWith:'h6(!(([![Class]!]))!). ', placeHolder:'Votre titre ici...' },
		{name:'Paragraph', key:'P', openWith:'p(!(([![Class]!]))!). '},
		{separator:'---------------' },
		{name:'Bold', key:'B', closeWith:'*', openWith:'*'},
		{name:'Italic', key:'I', closeWith:'_', openWith:'_'},
		{name:'Stroke through', key:'S', closeWith:'-', openWith:'-'},
		{separator:'---------------' },
		{name:'Bulleted list', openWith:'(!(* |!|*)!)'},
		{name:'Numeric list', openWith:'(!(# |!|#)!)'}, 
		{separator:'---------------' },
		{name:'Picture', replaceWith:'![![Source:!:http://]!]([![Alternative text]!])!'}, 
		{name:'Upload Picture', key:'U', beforeInsert: function(markItUp) { InlineUpload.display(markItUp) },},
		{name:'Link', openWith:'"', closeWith:'([![Title]!])":[![Link:!:http://]!]', placeHolder:'Votre texte ici...' },
		{separator:'---------------' },
		{name:'Quotes', openWith:'bq(!(([![Class]!]))!). '},
		{name:'Code', openWith:'@', closeWith:'@'},
		{separator:'---------------' }
	]
}

var upload = false;
var InlineUpload = 
{
	dialog: null,
	block: '',
	offset: {},
	options: {
		container_class: 'markItUpInlineUpload',
		form_id: 'inline_upload_form',
		action: 'index.php?action=wikiuploaddocument.upload&token='+getUrlParameter('token')+'&exaction='+getUrlParameter('action')+'&crypt/exprimary_key='+getUrlParameter('crypt/primary_key'),
		inputs: {
			file: { label: 'File', id: 'inline_upload_file1', name: 'document' }
		},
		submit: { id: 'inline_upload_submit', value: 'upload' },
		close: 'inline_upload_close',
		iframe: 'inline_upload_iframe'
	},
	display: function(hash)
	{	
		if( ! $('.markItUpInlineUpload').length)
		{
			var self = this;
		
		/* Find position of toolbar. The dialog will inserted into the DOM elsewhere
		 * but has position: absolute. This is to avoid nesting the upload form inside
		 * the original. The dialog's offset from the toolbar position is adjusted in
		 * the stylesheet with the margin rule.
		 */
		this.offset = $(hash.textarea).prev('.markItUpHeader').offset();
		
		/* We want to build this fresh each time to avoid ID conflicts in case of
		 * multiple editors. This also means the form elements don't need to be
		 * cleared out.
		 */
		this.dialog = $([
			'<div class="',
			this.options.container_class,
			'"><div><div id="content_wikieditordialog"><form id="',
			this.options.form_id,
			'" action="',
			this.options.action,
			'" target="',
			this.options.iframe,
			'" method="post" enctype="multipart/form-data"><label for="',
			this.options.inputs.file.id,
			'">',
			this.options.inputs.file.label,
			'</label>',
			'<input type="hidden" name="document_type" value="image">',
			'<input type="hidden" name="crypt/document_object_ref_id" value="'+getUrlParameter('crypt/primary_key')+'">',
			'<input name="',
			this.options.inputs.file.name,
			'" id="',
			this.options.inputs.file.id,
			'" type="file" /><input id="',
			this.options.submit.id,
			'" type="button" value="',
			this.options.submit.value,
			'" /></form></div><div id="',
			this.options.close,
			'">X</div><iframe id="',
			this.options.iframe,
			'" name="',
			this.options.iframe,
			'"></iframe></div></div>',
		].join(''))
			.appendTo(document.body)
			.hide()
			.css('top', this.offset.top)
			.css('left', this.offset.left);
				
		
		//init submit button
		 
		$('#'+this.options.submit.id).click(function()
		{
			if($('#inline_upload_file1').val() == ''){
				alert('Please select a file to upload');
				return false;
			}
			upload = true;
			$('#'+self.options.form_id).submit().fadeTo('fast', 0.2);
		});
	
				
		// init cancel button
		 
		$('#'+this.options.close).click(this.cleanUp);
		
		
		// form response will be sent to the iframe
		 
		$('#'+this.options.iframe).bind('load', function()
		{
			if(upload){
				var response = document.getElementById(''+self.options.iframe).contentWindow.document.body.innerHTML;
				try {
					var json_response = $.parseJSON(response);

					this.block = [
						'!',
						response.src,
						'!'
					];
					$.markItUp({ replaceWith: this.block.join('') } );
					InlineUpload.dialog.fadeOut().remove();
					upload = false;
				}
				catch (e) {
					//Recuperation de l'erreur
					$('#content_wikieditordialog').html('Une erreur est survenue');
				}
					
			}
		});
		
		// Finally, display the dialog
		this.dialog.fadeIn('slow');
		}
	},
	cleanUp: function()
	{
		InlineUpload.dialog.fadeOut().remove();
	}
};


$(document).ready(function() {
	$("#wiki-text").markItUp(mySettings);
});