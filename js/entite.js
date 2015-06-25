$(function(){
	var liste = [
	             {value: 'Proprietaire', desc:'Proprétaire du local'},
	             {value: 'Agence', desc: 'L\'agence qui a permit de trouver le local'},
	             {value: 'PCS', desc: 'Ce que c\'est? Aucune idée'},
	             {value: 'Syndicat', desc: '...En tout cas celà concerne le local'}
	         ];

	         $('#entite').autocomplete({
	             source : liste,
	             
	             select : function(event, ui){ // lors de la sélection d'une proposition
	                 $('#description').append( ui.item.desc ); // on ajoute la description de l'objet dans un bloc
	             }
	         });
});

