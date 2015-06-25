$(function(){
	//autocomplétion pour type de rue
	var liste= ['Rue', 'Avenue', 'Boulevard', 'Allée'];
	         $('#local_rue').autocomplete({
	             source : liste         
	         });
	
	 //remplissage automatique de département après remplissage de code postal
	 //département étant les 2 premiers chiffres du code postal
	 var cp= $('#local_cp'),
	 	 dep='local_dep';
	 cp.keyup(function(){
		 if($(this).val().length==5){
			 
		 	var dept=$(this).val().substring(0,2);
		 	document.getElementById(dep).value=dept;
	 	}
	 });
	 
	 /* Les espaces sont placés entre les chiffres après un nombre de chiffre%3=0 **/
	 
	 //field de surface brute
	 var surface_brute=$('#brut'),
	 	 brut='brut';
	 surface_brute.keyup(function(){
		if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var brute=$(this).val()+' ';
			document.getElementById(brut).value=brute;
		}
	 });
	 
	 // Field de surface en metre carre
	 var surface_carre=$('#carre'),
 	 carre='carre';
	 surface_carre.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var carres=$(this).val()+' ';
			document.getElementById(carre).value=carres;
		}
 	});
	 
	 // Field de estimation du prix
	 var estimation_prix=$('#estim'),
 	 estim='estim';
	 estimation_prix.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var estims=$(this).val()+' ';
			document.getElementById(estim).value=estims;
		}
 	});
	 
	 
	// Field de moyenne du marché
	 var moyen_marche=$('#moyen'),
 	 moyen='moyen';
	 moyen_marche.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var moyens=$(this).val()+' ';
			document.getElementById(moyen).value=moyens;
		}
 	});
	 
	// Field de prix demande
	 var prix_demande=$('#demande'),
 	 demande='demande';
	 prix_demande.keyup(function(){
	 	if($(this).val().length !=0 && ($(this).val().length==3 || $(this).val().length==7 ||  $(this).val().length==11)){
			var demandes=$(this).val()+' ';
			document.getElementById(demande).value=demandes;
		}
 	});
	 
	 /* suppression des espaces de séparation dans les chiffres **/
	 
	 var save=$('#bouton_save');
	 save.mouseup(function(){
		 //suppression des espaces pour surface brute
		 var brut_tmp=surface_brute.val().replace(/\s/g, "");
		 //suppression pour surface carre
		 var carre_tmp=surface_carre.val().replace(/\s/g, "");
		 //suppression pour estimation
		 var estim_tmp=estimation_prix.val().replace(/\s/g, "");
		//suppression pour moyenne marche
		 var moyen_tmp=moyen_marche.val().replace(/\s/g, "");
		//suppression pour prix demande
		 var demande_tmp=prix_demande.val().replace(/\s/g, "");
		 
		 document.getElementById(brut).value=brut_tmp;
		 document.getElementById(carre).value=carre_tmp;
		 document.getElementById(estim).value=estimation_tmp;
		 document.getElementById(moyen).value=moyen_tmp;
		 document.getElementById(demande).value=demande_tmp;
	 });	 
	 
});

