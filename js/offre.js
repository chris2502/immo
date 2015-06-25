$(function(){
	  
	// Field de moyenne du marché
	 var montant_offre=$('#montant_offre'),
 	 montant='montant_offre';
	 montant_offre.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var montants=$(this).val()+' ';
			document.getElementById(montant).value=montants;
		}
 	});
	 
	// Field de prix demande
	 var prix_location=$('#prix_location'),
 	 location='prix_location';
	 prix_location.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var locations=$(this).val()+' ';
			document.getElementById(location).value=locations;
		}
 	});
	 
	 
	// Field de prix demande
	 var frai_fai=$('#frai_fai'),
 	 fai='frai_fai';
	 frai_fai.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var fais=$(this).val()+' ';
			document.getElementById(fai).value=fais;
		}
 	});
	 
	 /* suppression des espaces de séparation dans les chiffres **/
	 
	 var save=$('#bouton_save');
	 save.mouseup(function(){
	
		 var montant_tmp=montant_offre.val().replace(/\s/g, "");
	
		 var prix_tmp=prix_location.val().replace(/\s/g, "");

		 var fai_tmp=frai_fai.val().replace(/\s/g, "");
		 
		 document.getElementById(montant_offre).value=montant_tmp;
		 document.getElementById(prix_location).value=prix_tmp;
		 document.getElementById(frai_fai).value=fai_tmp;
	 });	 
	 
});