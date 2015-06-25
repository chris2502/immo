function addImageToWysiwyg(id)
{
	// Click sur btn "Ajouter image" = création form avec input file.
	var formStr =	'<fieldset id="sendImage_' + id + '" style="width:300px;padding:4px;">' +
						'<legend>Nouvelle image</legend>' + 
						'<input id="file_' + id + '" type="file" name="image" />' + 
						'<button type="button" id="btn_cancel_' + id + '" style="width:140px;">Annuler</button>' + 
					'</fieldset>';

	$('#btn_addimage_' + id).fadeOut(300, function(){
		$(formStr).fadeIn().insertAfter($('#btn_addimage_' + id));

		// Annuler upload (suppression form)
		$('#btn_cancel_' + id).click(function(){
			$('#sendImage_' + id).fadeOut(300, function(){
				$(this).remove();
				$('#btn_addimage_' + id).fadeIn();
			});
		});

		// Confirmer upload.
		// Appel méthode "wysiwyg_upload_image" du contrôleur de l'objet (Common).
		$('#file_' + id).on('change', function(event){
			var object   = $('#' + id).attr('object');
			var formData = new FormData();
			var files    = event.target.files;
			$.each(files, function(key, value){
				formData.append(key, value);
			});
			$.ajax({
				url         : './index.php?action=' + object + '.wysiwyg_upload_image&token=' + $("#__token").val(),
				type        : 'POST',
				data        : formData,
				cache       : false,
				dataType    : 'json',
				processData : false,
				contentType : false,
				success     : function(data, textStatus, jqXHR){
					// $('#' + id).jqteVal($('#' + id).val() + '<br /><br /><img src="data:' + data.type + ';base64,' + data.base64 + '" />');

					CKEDITOR.instances[id].insertHtml('<img src="data:' + data.type + ';base64,' + data.base64 + '" />');

					$('#sendImage_' + id).fadeOut(300, function(){
						$(this).remove();
						$('#btn_addimage_' + id).fadeIn();
					});
					return true;
				},
				error       : function(jqXHR, textStatus, errorThrown){
					$('#sendImage_' + id).fadeOut(300, function(){
						$(this).remove();
						$('#btn_addimage_' + id).fadeIn();
					});
					return true;
				}
			});
		});
	});
}

$(document).ready(function(){
	for (var i in CKEDITOR.instances) {
		CKEDITOR.instances[i].on('change', function() { 
			CKEDITOR.instances[i].updateElement();
		});
	}
});
