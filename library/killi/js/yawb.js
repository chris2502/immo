$(function() {
	$('input.yawb').click(function() {
		var button = $(this);
		var url = button.attr('data-url');
		
		if (button.hasClass('comment'))
		{
			var dialog = $('#Yawb_dialog');
			console.log(dialog);
			dialog.dialog({	modal:true,
							buttons: {	"Envoyer": function() { url = url + '&comment=' + dialog.find('textarea').val(); $(location).attr('href', url);},
										"Annuler": function() { $(this).dialog( "close" );}
							}
			});
		}
		else
		{
			$(location).attr('href', url);
		}
	});
});

