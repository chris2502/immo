$(function(){
	 
	// Field du montant de la taxe
	 var montant_taxe=$('#montant'),
	 montant='montant';
	 montant_taxe.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var montants=$(this).val()+' ';
			document.getElementById(montant).value=montants;
		}
	});
	 
	 /* suppression des espaces de séparation dans les chiffres **/
	 
	 var save=$('#bouton_save');
	 save.mouseup(function(){
	
		 var montant_tmp=montant_taxe.val().replace(/\s/g, "");
		 
		 document.getElementById(montant_taxe).value=montant_tmp;
	 });	 
	 
});

