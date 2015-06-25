$(function(){
	//autocomplétion pour type de rue
	var liste= ['Charge annuelle', 'Taxe Foncière', 'Autres taxes'];
	         $('#typetaxe').autocomplete({
	             source : liste         
	         });
	
});

