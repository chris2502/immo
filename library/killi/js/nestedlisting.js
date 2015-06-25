/**
 * Le script javascript associés aux composants nestedlistings
 * 
 * Utilise les evenements suivants :
 * 
 * - doImport
 * - updateCount
 * 
 * @author Brice Boutillon
 */
(function( $ ){
	$.fn.nestedlisting = function() {
		/**
		 * La fonction d'entrée.
		 * 
		 * Mets en place le script sur les composants selectionnés.
		 */
		return this.each(function() {
			/**
			 * La racine du script, généralement le <div class="nestedList">
			 */
			var root = $(this);
			
			/**
			 * Evite la propagation d'un event click lors d'une selection sur une sous-liste.
			 */
			function stopEvent(event)
			{
				event.stopPropagation();
				return true;
			}
			
			/**
			 * Selectionne toutes lignes d'une table. 
			 */	
			function generalSelect(event) {
				var checkbox = $(this).closest('table').find('> tbody > tr.row input').filter(':checkbox');
				if ($(this).attr('checked')) {
					checkbox.attr('checked', 'checked');
				}
				else {
					checkbox.removeAttr('checked');
				}
				checkbox.change();
				return true;
			}
		 
			/**
			 * Ouvre une popup à partir d'un element clickable ayant les attributs :
			 * data-rul et data-size.
			 * 
			 * L'attribut data-size permettant de définir la taille de la popup étant
			 * optionnel. 
			 */		
			function openInPopup(event)
			{
				var size = $(this).attr('data-size');
				if (size)
				{
					size = size.split('x');
					size = 'height=' + size[1] + ', width=' + size[0] + ', ';
				}
				else
				{
					size = 'height=600, width=800, ';
				}
				return window.open($(this).attr('data-url'), 'popup_buttonform_' + $(this).attr('data-id'), size + 'toolbar=no, scrollbars=yes');
			}
					
			/**
			 * Effectue le tri des listes en fonctions des colonnes selectionnées.
			 */
			function sortTable() {				
				/**
				 * Permet la comparaison de deux éléments des tableaux générés 
				 * lors de l'execution de sortTable.
				 * 
				 * Array[0]:			La valeur numérique ou string sur laquel se basser pour la comparaison.
				 * Array[1]:			La ligne de la liste à laquel correspond la valeur.
				 */
				function sortArray(a, b) {		
					if (isNumber(a[0]) && isNumber(b[0])) {
							a[0] = parseFloat(a[0]);
							b[0] = parseFloat(b[0]);
					}		
					if (a[0] < b[0]) return -1;
					if (a[0] > b[0]) return 1;
					return 0;
				}

				/**
				 * Détermine si l'argument est une valeur numérique.
				 */
				function isNumber(n) {
					return !isNaN(parseFloat(n)) && isFinite(n);
				}			
				
				var column = $(this).index();
				var tbody = $(this).closest('table').children('tbody');
				var sortedTable = new Array();
				var value;
				
				tbody.children('tr.row').each(function () {
					var cell = $(this).children().eq(column);
					if (!(value = cell.attr('data-value')))
						value = cell.text();
					value = value.replace(',', '.');
					sortedTable.push(new Array(value, this));
				});
					
				/**
				 * Tri du tableau et affichage des flêches signifiant le tri et
				 * le sens de celui-ci.
				 */
				sortedTable.sort(sortArray);
				
				var c = $(this).closest('table').children('thead').find('th').eq(column);
				var asc = c.hasClass('sortAsc');
				c.end().removeClass('sortDesc').removeClass('sortAsc');	
				if (asc) {
					c.removeClass('sortAsc').addClass('sortDesc');
					sortedTable.reverse();
				}
				else {
					c.addClass('sortAsc').removeClass('sortDesc');
				}
					
				/**
				 * On réorganise les lignes de la liste afin d'afficher le nouvel ordre.
				 */
				for (var i = (sortedTable.length -1); i >= 0; i--) {
					tbody.prepend(sortedTable[i][1]);
					nest = sortedTable[i][1].nests;
					for (var j = (nest.length - 1); j >= 0; j--) {
						$(sortedTable[i][1]).after(nest[j]);
					}
				}
			}
			
			function toggleTable()
			{
				this.nests.fadeToggle(100);
				$(this).find('td.open_marker').toggleClass('expanded');
				return true;
			}
			
			function hideTable()
			{
				this.nests.fadeOut(100);
				$(this).find('td.open_marker').removeClass('expanded');
				return true;
			}
			
			function showTable()
			{
				this.nests.fadeIn(100);
				$(this).find('td.open_marker').addClass('expanded');
				return true;
			}
			
			function toggleTableOnSelect()
			{
				if ($(this).attr('checked'))
				{
					$(this).closest('tr.row').each(showTable);
				}
				else
				{
					$(this).closest('tr.row').each(hideTable);
				}
				return true;
			}
			
			function checkboxSelect(event) {
				var tr = $(this).closest('tr');

				if (tr[0].nests)
				{
					if (event.currentTarget.checked == false && tr.hasClass('selected')) {
						tr.removeClass('selected')[0].nests.removeClass('selected');
					}
					else if (event.currentTarget.checked == true && tr.hasClass('selected') == false) {
						tr.addClass('selected')[0].nests.addClass('selected');
					}
				}
				return true;
			}

			/**
			 * On commence par cacher les sous-listes, lier leur affichage à l'evenement 'click',
			 * et finalement, on génére les tableaux liants les sous-listes
			 * à des lignes de leurs parents.
			 */
			root.find("tr.nest").hide();
			root.find("tr.row").each(function() {
				this.nests = $(this).nextUntil('tr.row', 'tr.nest');
				for (var i = 0; i < this.nests.length; i++)
					this.nests[i].linkedRow = this;
			}).click(toggleTable).filter("tr.row.expanded").each(toggleTable);
			
			root.find('th input').filter(':checkbox').change(generalSelect);
			root.find('td input').change(checkboxSelect).filter('.expand:checkbox').change(toggleTableOnSelect);	
			
			/**		
			 * Si le header des listes n'a pas d'attribut "data-sort", on
			 * sélectionne le tri initiale sur la première colonne du header avec
			 * la classe '.sortable'.
			 * 
			 * Si on a pas de header, on sélectionne la 1° colonne.
			 * 
			 * Puis on applique ce tri initiale.
			 * 
			 * TODO: Limiter le tri uniquement à certaines colonnes.
			 */		
			root.find('table').each(function() {
				var dataSort = $(this).find('th');
				dataSort = dataSort.filter('.sortable[data-sort]').eq(0);
				var target = null;
				if (dataSort) {
					target = dataSort;
					if ($(dataSort).attr('data-sort') == 'desc')
						$(dataSort).addClass('sortAsc');
				}
				else {
					target = $(this).find('th.sortable').eq(0);
				}	
				target.each(sortTable);
			}).find("thead th.sortable").click(sortTable);
			
			root.find('button, input, a, .clickable').click(stopEvent);
			root.find('[data-url]').click(openInPopup);
			root.css('visibility', 'visible');
		});
	};
	$.fn.nestedselect = function(action) {
		/**
		 * La racine du script, généralement le <div class="nestedList">
		 */
		var root = false;
		var globalCountTimeout;

		/**
		 * Mets à jour la selection des enfants et du parent en fonction 
		 * de la selection de l'élément.
		 */
		function updateSelect(event)
		{
			var value = ($(this).attr('checked')) ? 'checked' : null;
			var current_row = $(this).closest('tr.row');
			if (current_row.length)
			{
				var nest_list = current_row[0].nests;
				
				if (nest_list)
				{
					nest_list.find('input:checkbox').attr('checked', value).end().find('table').trigger('recount');
				}	
				
				var parent = current_row.closest('tr.nest');
				if (parent.length)
				{
					parent = $(parent[0].linkedRow);			
					if (value)
					{
						parent.find('td input:checkbox').attr('checked', value);
					}
				}
				window.clearTimeout(globalCountTimeout);
				globalCountTimeout = setTimeout(function() { root.nestedselect('globalCount');}, 1000);
			}
			countSubTotal();
			return true;
		}

		
		function updateGlobalCount()
		{
			root.find('label span[data-object]').each(function () {
				var count = 0;
				var span = $(this);	
				var table_list = root.find('table[data-object='+span.attr('data-object')+']');
				table_list.each(function() {
					var table_row_list = $(this).find('> tbody > tr.row');
					if (table_row_list.has('input:checkbox').length)
					{
						count = count + table_row_list.has('input:checked').length;
					}
					else
					{
						if ($(this).closest('tr.nest').length > 0)
						{
							var parent_row = $(this).closest('tr.nest')[0].linkedRow;
							if ($(parent_row).has('input:checked').length)
							{
								count = count + table_row_list.length;
							}
						}
					}
				});				
				span.find('strong').text(count);	
			});
		}
		
		/**
		 * Récupère les lignes sélectionnés, mets en forme les données et les envoi.
		 */
		function submitIt(event) {
			var start_table = root.find('> table');
			var json = {};
			getArraysValues(json, start_table);
			root.append('<input type="hidden" name="'+root.attr('id')+'" value=\''+JSON.stringify(json)+'\' />');
			root.closest('form').attr('action', 'index.php?action='+event.data).submit();
			return true;
		}
		
		function getArraysValues(parent, current_table)
			{
				var key = null;	
				current_table.find('> tbody > tr.row').filter(':has(input:checked), :not(:has(input))').each(function() {
					key = $(this).attr('data-key');
					var child = {};
					var data = $(this).attr('data-extra');
					if (typeof (data) == 'string')
					{
						child.data = JSON.parse(data);
					}
					else if (typeof (data) == 'object')
					{
						child.data = data;
					}
										
					if (typeof (this.nests) != 'undefined' && this.nests.length)
					{
						for (var j = 0; j < this.nests.length; j++ )
						{
							childTable = $(this.nests[j]).find('> td > table');
							getArraysValues(child, childTable);
						}
					}
					parent[key] = child;
				});
			}

		function countSubTotal()
		{
			if (root.find('td.nestedtotal').length == 0)
				return;
			root.find('tr.nest').each(function (e) {
				var tr = this;
				var nbSelTotal = 0;
				var nb = 0;
				var ele_total = $(tr.linkedRow).children('td.nestedtotal');
				if (ele_total.attr('data-total') == undefined)
				{
					ele_total.attr('data-total', ele_total.text());
				}
				var total_class = ele_total.attr('class').replace(/\s/g,'').replace('nestedtotal', '').replace('euro', '');
				$(tr).find('td.'+total_class).each(function () {
					 if ($(this).text().length > 0)
					 {
						nb = formatPriceToNB($(this).text());
						if ($(this).parent().find('.checkbox').find('input').attr('checked') == 'checked')
						{
							nbSelTotal = nbSelTotal + nb;
						}
					 }
				});
				seltotal = formatNbToPrice(nbSelTotal);
				ele_total.attr('data-selected-total',seltotal);
				ele_total.text(seltotal+' / '+ ele_total.attr('data-total'));
			});
			checkTotal();
		}

		function checkTotal(){
			var nbTd = 0;
			var sum = 0;
			var sum_selected = 0;
			if (root.find('td.nestedtotal').length == 0)
				return;

			root.find('tr').each(function ()
			{
				if (!$(this).hasClass('lastTotal'))
				{
					if ($(this).find('td.nestedtotal').length == 0)
						return;
					nbTd = $(this).find('td.nestedtotal').prevUntil('tr').length;
				}
				if ($(this).find('td.nestedtotal').attr('data-total') == undefined)
						return;
				sum += formatPriceToNB($(this).find('td.nestedtotal').attr('data-total'));
				sum_selected += formatPriceToNB($(this).find('td.nestedtotal').attr('data-selected-total'));
			});
			if (root.find('tr.lastTotal').length == 0)
			{
				var str = "<tr class=\"lastTotal\"><td>TOTAL</td>";
				for (var i=0;i<nbTd-1; i++)
				{
					str+="<td></td>";
				}
				str+= "<td class=\"tdLastTotal euro\"></td></tr>";
				root.children('table').append(str);
			}
			root.find('.tdLastTotal').text(formatNbToPrice(sum_selected)+' / '+formatNbToPrice(sum));
			return true;
		}
		
		function formatNbToPrice(nb)
		{
			var result = addThousandSeparator(Math.round(nb*100)/100).toString().replace(/\./,',')+' € ';
			return result;
		}

		function formatPriceToNB(nb_str)
		{
			var result = parseFloat(nb_str.replace(/\s€/g,'').replace(/,/g,'.').replace(/\s/g,''));
			return result;
		}

		/**
		 * La fonction d'entrée.
		 * 
		 * Mets en place le script sur les composants selectionnés.
		 */
		return this.each(function() {
			root = $(this);
			if (action)
			{
				updateGlobalCount();
			}
			else
			{
				/**		
				 * Initialization de la selection
				 */
				root.find('td input').filter(':checkbox').change(updateSelect);			
				countSubTotal();
				/**
				 * Lie le bouton Selectionner à la fonction de validation du formulaire.
				 */
				var buttons = root.attr('data-send').split('::');
				for (b in buttons)
				{
					$('#'+buttons[b].split(':')[0]).click(buttons[b].split(':')[1], submitIt);
				}
				/**
				 * S'ils existent ( Mode formulaire uniquement ), on initilize les compteurs
				 * globaux d'éléments. ( à coté du bouton de selection )
				 */
				root.find('label span[data-object]').each(function () {
					var span = $(this);
					var obj = span.attr('data-object');
					span.find('em').text(root.find('table[data-object='+obj+'] > tbody > tr.row').length);
				});
				
				root.find('table.nestedselect span[data-object]').each(function () {
					var span = $(this);
					var obj = span.attr('data-object');
					var nests = span.closest('tr.row')[0].nests;
					span.find('em').text(nests.find('table[data-object='+obj+'] > tbody > tr.row').length);
				});
			}
		});		
	};
	
	$.fn.nestedimport = function() {
		/**
		 * La fonction d'entrée, initialize le plugin.
		 * 
		 */
		return this.each(function() {
			var root = $(this);
			var id = root.attr('id');
			var quantity_index = root.find('th.quantity').eq(0).index();

			function doSubmit(event) {
				var start_table = root.find('> table');
				var json = {};
				getArraysValues(json, start_table);
				root.append('<input type="hidden" name="'+root.attr('id')+'" value=\''+JSON.stringify(json)+'\' />');
				return true;
			}

			function getArraysValues(parent, current_table)
			{
				var key = null;	
				current_table.find('> tbody > tr.row').each(function() {
					key = $(this).attr('data-key')
					var child = {};
					var data = $(this).attr('data-extra');
					if (typeof (data) == 'string')
					{
						child.data = JSON.parse(data);
					}
					else if (typeof (data) == 'object')
					{
						child.data = data;
					}
					
					var qty = $(this).find('td').eq(quantity_index).find('input').val();

					if (typeof (qty) != 'undefined')
					{
						if (typeof (data) == 'undefined')
						{
							child.data = {};
						}						
						child.data.quantity = qty;
					}

					var selected = $(this).find('td.nested-select option:selected').attr('data-select');

					if (typeof (selected) != 'undefined')
					{
						if (typeof (child.data) == 'undefined')
						{
							child.data = {};
						}
						if (typeof (selected) == 'string')
						{
							selected = JSON.parse(selected);
						}
						if (typeof (selected) == 'object')
						{
							$.each(selected, function(key, value)
							{
								child.data[key] = value;
							});
						}
					}

					if (typeof (this.nests) != 'undefined' && this.nests.length)
					{
						for (var j = 0; j < this.nests.length; j++ )
						{
							childTable = $(this.nests[j]).find('> td > table');
							getArraysValues(child, childTable);
						}
					}
					if (typeof (key) == 'undefined')
					{
						if(typeof (parent.unkeyed) == 'undefined')
						{
							parent.unkeyed = new Array();
						}
						parent.unkeyed.push(child);
					}
					else
					{
						parent[key] = child;
					}				
				});			
			}
			
			function doRemove() {
				var table = $(this).closest('table');						
				$(this).closest('tr').remove();
				if (table.find('tr.row').length == 0)
				{
					table.find('tbody').append($('<td colspan="'+ table.find('th').length +'" class="emptyList"> - PAS DE DONNEES - </td>'));
				}
				table.trigger('updateCount');						
			}
			
			function updateCount() {
				$(this.linkedRow).find('td.count').text($(this).find('> td > table > tbody > tr.row').length);
			}
			
			function doImport(event, element, object, quantity)
			{
				event.stopPropagation();
				var tables = null;
				var pk = element.attr('data-key');
				var clone = element.clone();
				var column_count = element.eq(0).children().length;
				
				if (root.find('tr.nest').length)
				{
					tables = root.find('tbody tr.nest.selected table');
				}
				else
				{
					tables = root.find('table');
				}
				tables = tables.filter('table[data-object=' + object + ']');				
				
				if (column_count < quantity_index)
				{
					for (var x = 0; x < (quantity_index - column_count); x++)
					{
						clone.append($('<td></td>'));								
					}
				}
				
				tables.each(function () {
					var tr = $(this);
					var tbody = $(this).find('tbody');
					tbody.find('td.emptyList').remove();
					
					var existing = $(tbody).find('tr[data-import-key="'+pk+'"]');
					if(existing.length === 0)
					{				
						tbody.append(
							clone.clone().attr('data-import-key', pk)
							.append($('<td><input type="text" value="' + quantity + '" size="4"></td><td><div class="ui-icon ui-icon-trash"></div></td>'))
						).find('div.ui-icon-trash').click(doRemove);
					}
					else
					{
						var inText = existing.find('input[type="text"]');
						var qty = parseInt(inText.attr('value'));
						if (isNaN(qty))
						{
							qty = 0;
						}
						inText.attr('value', qty + quantity);	
					}
				});
				return true;
			}

			root.find('tbody tr.nest').bind('updateCount', updateCount);
			root.find('table.nestedimport > thead > tr').append('<th></th>');
			root.find('table.nestedimport > tbody > tr.row').each(function() {
				if ($(this).attr('data-editable') == undefined || $(this).attr('data-editable') == 1)
				{
					var qty = $(this).find('td').eq(quantity_index);
					qty.html('<input type="text" value="'+qty.text()+'" size="4" />');
					$(this).append($('<td><div class="ui-icon ui-icon-trash"></div></td>').click(doRemove));
				}
			});

			$('#bouton_save').bind('click',doSubmit);
			root.bind('doImport', doImport);
		});
	};
	
	$.fn.nestedexport = function() {
		 return this.each(function() {
			var root = $(this);
			var target = root.attr('data-export-to');
			var object = root.attr('data-export-as');
			
			root.find('thead tr').prepend('<th colspan="2" class="export"></th>');
			root.find('tbody tr.row').prepend('<td class="export"><div class="ui-icon ui-icon-circle-arrow-w clickable"></div></td><td class="quantity"><input type="text"  value="1" size="4" /></td>');
			root.find('td.export div.ui-icon').click(function() {
				var block = $(this).closest('tr').clone();
				var quantity = parseInt(block.find('td.quantity input').val());
				block.find('td.quantity, td.export').remove();
				$('#' + target).trigger('doImport', [block, object, quantity]);
			});	
		});
	 };
}( jQuery ));


$(function() {
	//Recuperation onclick bouton_save
	var btn_save_click = $('#bouton_save').attr('onclick');
	$('#bouton_save').attr('onclick',null);

	$("div.nestedlisting").nestedlisting().has("table.nestedimport").nestedimport();
	$("div.nestedselect").each(function () {
		$(this).nestedselect();
	});
	$("table.nestedexport").nestedexport();

	$('#bouton_save').attr('onclick', btn_save_click);
	$(document).trigger( "nested-ready" );
});



