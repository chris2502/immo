/**
 *  @file killi.js
 *  @Revision $Revision: 2431 $
 *
 */

var main_calendar;

if ($.datepick)
{
	$.datepick.setDefaults({
		showOn: 'both',
		buttonImageOnly: true,
		buttonImage: './images/calendar.png',
		buttonText: 'Calendar',
		yearRange: '2008:2020'
	});
}

// FIX autocomplete + menubar

jQuery.fn.extend({
	 propAttr: $.fn.prop || $.fn.attr
});


//-----------------------------------------------------------------------------
function json2jqplotArray(json_data)
{
	var a2return = new Array();
	$.each(json_data, function(index, value){
		var a2push = new Array();
		$.each(value, function(idx, val){
			a2push.push([idx, val]);
		});
		a2return.push(a2push);
	});
	return a2return;
}
//-----------------------------------------------------------------------------
function reset_reference(name)
{
	document.getElementById(name+'reference').value='';
	document.getElementById(name).value='';
	document.getElementById(name+'reference').disabled = false;
	document.getElementById(name+'old').value='';

	return true;
}
//-----------------------------------------------------------------------------
function calendarSlotMouseDown(td)
{
	calendar_mouse_down = true;
	td.style.backgroundColor='#DDDDFF';
	calendar_selected_slot.push(this);

	return true;
}
//-----------------------------------------------------------------------------
function calendarSlotMouseUp(td)
{
	calendar_mouse_down = false;
	td.style.backgroundColor='#DDDDFF';

	return true;
}
//-----------------------------------------------------------------------------
function calendarSlotMouseOver(td)
{
	if (calendar_mouse_down===false)
	{
		td.style.backgroundColor='#DDDDDD';
	}
	else
	{
		td.style.backgroundColor='#DDDDFF';
		calendar_selected_slot.push(td);
	}

	return true;
}
//-----------------------------------------------------------------------------
function calendarSlotMouseOut(td)
{
	if (calendar_mouse_down===false)
	{
		td.style.backgroundColor='#FFFFFF';
	}

	return true;
}
//-----------------------------------------------------------------------------
function checkAll(checkbox)
{
	$('.multi_selector:enabled').each(function() { $(this).attr('checked', checkbox.checked); });

	return true;
}

function changeAll(attr)
{
	var value = $('#crypt_' + attr + '_all_value').val();
	$('.' + attr + '_class').each(function() { $(this).val(value); });

	return true;
}

//-----------------------------------------------------------------------------
function getDocument(crypt_document_id, target_container)
{
	$.ajax({
		url: './index.php?action=document.read' + add_token(),
		data: 'crypt/primary_key=' + crypt_document_id,
		dataType: 'json',
		success: function(response) {
			$('#' + target_container).empty();
			var html = '<table border="1" cellpadding="4" cellspacing="2">';
			for (i in response)
			{
				html += '<tr>';
				for (j in response[i])
				{
					html += '<td>' + response[i][j] + '</td>';
				}
				html += '</tr>';
			}
			var w = $('#' + target_container).parent().css('width');
			$('#' + target_container).html(html);
			$('#' + target_container).css('width', w);
		}
	});
}

$(document).ready(function () {
	$('#main_form').submit(function(event) {
		console.log('Submit event catched !');
		event.preventDefault();
		boutonEnregistrer();
		return false;
	});
});

//-----------------------------------------------------------------------------
function boutonEnregistrer()
{
    /*
    if ($('#__upload_target')) {
        _upload_get_progress();
    }
    */
	// $('textarea[ckedited]').each(function(idx){
	// 	$(this).val(CKEDITOR.instances[$(this).attr('id')].getData());
	// 	console.log($(this).val());
	// });

	var btn = $('#bouton_edition');
	if(btn.length)
	{
		btn.prop('disabled', true);
	}

	if ($('input[name^=xhrdocupload]').length > 0)
	{
		var fd = new FormData(document.main_form);
		var pathname = window.location.pathname;

		$('input[name^=xhrdocupload]').each(function(index){
			var id = $(this).attr('name');
			id     = id.substr(13, id.length);
			fd.append('docupload_' + id + '[]', global_filelist[parseInt($(this).val())]);
		});

		var jqXHR = $.ajax({
			// redirect = 0 : Désactivation de la redirection auto après écriture.
			url: document.main_form.action + '&redirect=0',
			type: 'POST',
			async: false,
			contentType:false,
			processData: false,
			cache: false,
			data: fd,
			dataType: 'json',
			success: function(data, textStatus, jqXHR){
				window.location.href = window.location.href.replace('&mode=edition', '');
			},
			error: function (xhr, ajaxOptions, thrownError) {
				alert('Une erreur est survenue, veuillez recharger la page et recommencer !');
				console.log(ajaxOptions);
				console.log(thrownError);
			}
		});
	}
	else
	{
		var main = $('#main_form');
		if(main.length)
		{
			main.unbind('submit').submit();
		}
	}

	//var url = window.opener.location.href;
	//window.opener.location.href = url.replace("#","&refresh=1");

	return true;
}
/*
function submitSearchForm(current_field)
{
	if(typeof current_field != undefined)
	{
		$('#search_form').append('<input type="hidden" name="'+current_field.attr('name')+'" value="'+current_field.val() + '"/>');
	}
	$('#search_form').submit();
}*/

var __trigger = false; // Variable pour section critique
function trigger_search(field_jquery)
{
	console.log('trigger_search...');
	if(!__trigger)
	{
		if(!(field_jquery instanceof jQuery))
		{
			field_jquery = $('#' + field_jquery);
		}
		console.log('trigger_search: ' + field_jquery.attr('id'));
		__trigger = true;
		field_jquery.focus();
		var e = jQuery.Event("keydown");
		e.keyCode = 13; // Enter
		//e.which = 13; // Enter
		field_jquery.trigger(e);
		__trigger = false;
	}
	return true;
}

//----------------------------------------------------------------------------
function rememberLastNotebookTab(tab_name,event,ui)
{
	selected_tab = ui.index;
	document.cookie = "selected_tab_"+tab_name.replace('#','')+"="+selected_tab;

	return true;
}
//-----------------------------------------------------------------------------
function arguments_cookies(offset){
	  var endstr=document.cookie.indexOf (";", offset);
	  if (endstr===-1)
	  {
		  endstr=document.cookie.length;
	  }
	  return unescape(document.cookie.substring(offset, endstr));
}
//-----------------------------------------------------------------------------
function lire_cookie(nom) {
	  var arg=nom+"=";
	  var alen=arg.length;
	  var clen=document.cookie.length;
	  var i=0;
	  while (i<clen){
	    var j=i+alen;
	    if (document.cookie.substring(i, j)===arg)
	    {
	       return arguments_cookies(j);
	    }
	    i=document.cookie.indexOf(" ",i)+1;
	    if (i===0)
	    {
			break;
		}
	  }
	  return null;
}
//----------------------------------------------------------------------------
function export_csv_btn(listing_id, object_name, params)
{
	var token = $("#__token").val();
	var url = './index.php?action=' + object_name + '.export_csv&token=' + token + params;

	$('#export_columns_' + listing_id).dialog({
		resizable: false,
		modal: true,
		buttons: {
			"Export": function() {
				document.search_form.action = url;
				document.search_form.submit();
				document.search_form.action = '';
				$( this ).dialog( "close" );
			},
			'Annuler': function() {
				$( this ).dialog( "close" );
			}
		},
		create: function ()
		{
			$('#export_columns_' + listing_id).parent().appendTo($(document.search_form));
		}
	});



}
//----------------------------------------------------------------------------
function get_url_param( name )
{
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( window.location.href );
  if( results === null )
  {
    return "";
  }
  else
  {
    return results[1];
  }
}
//-----------------------------------------------------------------------------
function createXhrObject()
{
    if (window.XMLHttpRequest)
    {
        return new XMLHttpRequest();
    }

    if (window.ActiveXObject)
    {
        var names = [
            "Msxml2.XMLHTTP.6.0",
            "Msxml2.XMLHTTP.3.0",
            "Msxml2.XMLHTTP",
            "Microsoft.XMLHTTP"
        ];
        var i;
        for(i in names)
        {
            try{ return new ActiveXObject(names[i]); }
            catch(e){}
        }
    }
    window.alert("Votre navigateur ne prend pas en charge l'objet XMLHTTPRequest.");
    return null; // non supporté
}
//.............................................................................
function openInTarget(target_name, url)
{
	window.frames[target_name].location = url;

	return false;
}
//.............................................................................
function popupAutoCenter()
{


	return true;
}
//.............................................................................
function sendReferenceToParent(pk,reference,input_name)
{
	var val = input_name.replace(/\/|:|\./g, '_');
	var ref = val + 'reference';

	window.opener.document.getElementById(ref).value    = reference;
	window.opener.document.getElementById(val).value    = pk;
	window.opener.document.getElementById(ref).disabled = true;

	if (window.opener.document.getElementById(val+'hiddenkey'))
	{
		window.opener.document.getElementById(val+'hiddenkey').style='cursor:pointer;height:20px;position:relative;margin-top:-20px;-webkit-box-sizing: border-box;-moz-box-sizing:border-box;box-sizing:border-box;width: 100%;';
	}

	if (window.opener.document.getElementById(val+'old'))
	{
		window.opener.document.getElementById(val+'old').value= reference;
	}

	if(window.opener.document.getElementById(val).hasAttribute('onchange'))
	{
		window.opener.document.getElementById(val).onchange();
	}

	if(input_name.indexOf('r_')===0)
	{
		window.opener.trigger_search(ref);
	}

	window.close();

	return true;
}
//...........................................................................
getUrlParameter = function(key) {
	key = key.toLowerCase();
	var location = window.location.search;
	var data = location.slice(1).split('&');
	var i;
	for(i in data)
	{
		splt = data[i].split('=');
		if(key === splt[0])
		{
			return splt[1];
		}
	}

	return false;
};
//...........................................................................
popupReference2 = function(key, params) {
	var realkey = key.replace('/','_');
	var  keyword_reference = $('#'+params.keywordReference).val();
	var  keyword			 = $('#'+params.divid).val();
/*	var domains = $('.domain_' + params.field);*/
	var url = './index.php?action=' + params.object_class + '.edit&view=selection&input_name=' + params.input_name+'&keyword_reference='+params.keywordReference+'&keyword='+keyword;

	var err = 0;
	url += '&token=' + params.token;
	if(err)
	{
		return false;
	}

	window.open(url, 'popup_search_' + params.uniqid, 'height=' + params.height + ', width=' + params.width + ' , toolbar=no, scrollbars=yes');
};

popupReference = function(key, params) {
	var domain    = (params.domain) ? params.domain : '';
	var env       = (params.env) ? params.env : '';
	var url = './index.php?action=' + params.object_class + '.edit&view=selection&input_name=' + params.input_name+domain+env+'&create=' + params.create+'&token=' + params.token;

	window.lastpopup = window.open(url, 'popup_search_' + params.uniqid, 'height=' + params.height + ', width=' + params.width + ' , toolbar=no, scrollbars=yes');
};

setError = function(field, message, append) {
	if(message !== null)
	{
		$('#' + field + '_error').slideDown(500, function() {
			if(append===null)
			{
				$(this).html(message);
			}
			else
			{
				$(this).html($(this).html() + message + '<br>');
			}
			$('#' + field).focus();
		});
	}
	else
	{
		$('#' + field + '_error').slideUp(500, function() {
			$(this).html('');
		});
	}
};

submitMain = function() {
	var err = 0;
	$('.error_str').each(function() {
		$(this).html('');
	});

	$('#main_form').submit();
};

addOrder = function(field) {
	var elt = $('#order_' + field);

	if(elt !== null)
	{
		etats = ['', '^','v'];
		orderval = parseInt(elt.val());

		if(orderval ===2)
		{
			elt.val("0");
		}
		else
		{
			elt.val(++orderval);
		}

		$('#etat_order_' + field).html(etats[elt.val()]);
	}

	return false;
};

$(document).ready(function() {
	$('.search_input').keydown(function(event) {
		if(event.keyCode === 13)
		{
			$('#search_form').submit();
		}
	});

	// Raccourci Ctrl+Shift+S pour le bouton "Enregistrer".
	if ($('#bouton_save').length > 0) {
		$(document).keyup(function(event){
			if (event.shiftKey &&
				event.ctrlKey &&
				event.which == 83 &&
				!$('#bouton_save').prop('disabled'))
			{
				boutonEnregistrer();
			}
		});
	}

	$('.selected').click(function(event) {
		currval = $(this).val();
		sval = $.cookie('_selected');
		ischecked = $(this).is(':checked');

		if(sval === null && ischecked === true) {
			$.cookie('_selected', currval);
		} else {
			aval = sval.split(',');

			if(ischecked === true)
			{
				aval.push(currval);
			}

			hval = {};
			var a;
			for(a in aval)
			{
				if(aval[a] !== currval || ischecked === true)
				{
					hval[aval[a]] = true;
				}
			}

			aval = [];
			for(a in hval)
			{
				aval.push(a);
			}

			$.cookie('_selected', aval);
		}
	});
});

function print_r(obj)
{
	win_print_r = window.open('about:blank', 'win_print_r');
	win_print_r.document.write('<html><body>');
	r_print_r(obj, win_print_r);
	win_print_r.document.write('</body></html>');
}

function r_print_r(theObj, win_print_r)
{
	if(theObj.constructor == Array ||theObj.constructor == Object)
	{
		if (win_print_r === null)
		{
			win_print_r = window.open('about:blank', 'win_print_r');
		}
	}

	var p;
	for(p in theObj)
	{
		if(theObj[p].constructor == Array|| theObj[p].constructor == Object)
		{
			win_print_r.document.write("<li>["+p+"] =>"+typeof(theObj)+"</li>");
			win_print_r.document.write("<ul>");
			r_print_r(theObj[p], win_print_r);
			win_print_r.document.write("</ul>");
	    }
		else
		{
			win_print_r.document.write("<li>["+p+"] =>"+theObj[p]+"</li>");
	    }
	}
	win_print_r.document.write("</ul>");
}

addFormField = function(type, label, name, val, required, after) {
	required = (required == undefined ? false : required);
	var innerHTML = '<table class="field" cellspacing="2" cellpadding="1">';
	innerHTML += '<tr><td class="field_label">' + label + '</td><td>';
	innerHTML += '<table class="field_cell" cellspacing="0" cellpadding="0" style="border: none;">';
	innerHTML += '<tr><td style="width: 300px;">';

	switch(type) {
		case 'select':
			if(val instanceof Array) {
				innerHTML += '<select id="' + name + '" ' + (required === true ? ' class="required_field" ' : '') + ' name="' + name + '">';
				var key;
				for( key in val) {
					innerHTML += '<option value="' + key + '">' + val[key] + '</option>';
				}

				innerHTML += '</select>';
			}
		break;

		case 'text':
		default:
			innerHTML += '<input type="text" id="' + name + '" ' + (required === true ? ' class="required_field" ' : '') + ' name="' + name + '" value="' + val + '"/>';

		break;
	}

	innerHTML += '</td></tr></table>';
	innerHTML += '</td></tr></table>';

	if(!after)
	{
		$('#main_form').append(innerHTML);
	}
	else
	{
		$(after).after(innerHTML);
	}
};


this.imagePreview = function(){
	/* CONFIG */

		xOffset = 10;
		yOffset = 30;

		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result

	/* END CONFIG */
	$("a.preview").hover(function(e){
		this.t = this.title;
		this.title = "";
		var c = (this.t != "") ? "<br/>" + this.t : "";
		$("body").append("<p id='preview'><img src='"+ this.href +"' alt='Image preview' />"+ c +"</p>");
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");
    },
	function(){
		this.title = this.t;
		$("#preview").remove();
    });
	$("a.preview").mousemove(function(e){
		$("#preview")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});
};

// starting the script on page load
$(document).ready(function(){
	imagePreview();
});

//---------------------------------------------------------------------
// Grille de planification.
function isOverlapping(array, event)
{
		for(i in array)
   		{
  		if(array[i].title != event.title)
        	{
      		if(!(array[i].start >= event.end || array[i].end <= event.start))
            	{
          		return true;
      		}
  		}
		}
		return false;
}
//---------------------------------------------------------------------
// Restriction de placement dans une grille de planification.
function isAvailableArea(calendar, start, end, slot)
{
	var unavailable = false;

	// var new_start = clone(start);

	var new_start = new Date(start.getTime());

	while (new_start < end && !unavailable)
	{
		var d = new_start.getDay();
		var h = new_start.getHours();
		var m = new_start.getMinutes();
		console.log(d, h, m);
		if (calendar.find('.fc-cell_' + h + '_' + m + '_' + d).hasClass('unavailable-area-event'))
		{
			unavailable = true;
			break;
		}
		new_start.setTime(new_start.getTime() + (slot * 60 * 1000));
	}

	console.log(unavailable);

	return !unavailable;
}
//---------------------------------------------------------------------
// Retrait d'évenement dans une grille de planification.
function removeEvent(calid, eventid)
{
	var calendar = $("#"+calid);
	var allEvents = calendar.fullCalendar('clientEvents');
	var found = false;
	var title;
	var duration;
	var object;
	var objectid;
	var color;
	var deletable;
	var resizable;
	for(i in allEvents)
	{
		if(allEvents[i].id == eventid)
		{
			title = allEvents[i].title;
			duration = allEvents[i].duration;
			object = allEvents[i].object;
			objectid = allEvents[i].objectid;
			color = allEvents[i].color;
			deletable = (allEvents[i].deletable == true) ? '1' : '0';
			resizable = (allEvents[i].resizable == true) ? '1' : '0';
			found = true;
			break;
		}
	}
	if(!found)
	{
		alert('Evenement introuvable dans la grille de planification.');
		return;
	}
	if(object && object != 'undefined')
	{
		var oid = objectid;
		var postdata = {object: object,
						id: oid};
		$.post('./index.php?action='+object+'.unlink&redirect=0&token='+$("#__token").val()+'&crypt/primary_key='+oid,
			postdata,
			function(data) {
				if(data.success)
				{
					if($('#'+eventid).length == 0)
					{
						$('#planifier_events_'+calid).append('<div id="'+eventid+'" style="background-color: '+color+'; border-color: '+color+';" color="'+color+'" deletable="'+deletable+'" resizable="'+resizable+'" object="'+object+'" duration="'+duration+'" class="planifier_events_'+calid+' movable-event fc-event fc-event-skin fc-event-vert fc-corner-top fc-corner-bottom">'
															+'<div class="fc-event-head fc-event-skin" style="background-color: '+color+'; border-color: '+color+';">'
															+'<div class="fc-event-time">Durée : '+duration+' min</div>'
															+'</div>'
															+'<div class="fc-event-content">'
															+'<div class="fc-event-title">'+title+'</div>'
															+'</div>'
															+'<div class="fc-event-bg"></div>'
															+'</div>'
															+'<br/>');

						$('#'+eventid).each(function() {
							var eventObject = {
								id: $(this).attr('id'),
								object: $(this).attr('object'),
								title: $.trim($(this).find('div.fc-event-title').text()),
								duration: $(this).attr('duration'),
								color: $(this).attr('color'),
								editable: true,
								disableResizing: $(this).attr('resizable') == '0',
								deletable: $(this).attr('resizable') == '1',
							};
							$(this).data('eventObject', eventObject);
							$(this).draggable({
								zIndex: 999,
								revert: true,
								revertDuration: 0
							});
						});
					}
					else
					{
						$('#'+eventid).show();
					}
					calendar.fullCalendar('removeEvents', eventid);
					calendar.fullCalendar('refetchEvents');
				}
				else
				{
					if(data.error)
					{
						alert(data.error);
					}
					else
					{
						alert('Invalid server response : '+data);
					}
				}
			}, "json").error(function() { alert('Bad response from server !');});
	}
	else
	{
		calendar.fullCalendar('removeEvents', eventid);
	}
	return true;
}

// Extension utilisée pour gérée des évenements sur la visibilité des onglets.
(function($){
	$.fn.extend({
		onShow: function(callback, unbind)
		{
			return this.each(function(){
				var obj = this;
				var bindopt = (unbind==undefined)?true:unbind;
				if($.isFunction(callback)){
					var checkVis = function()
					{
						if($(obj).is(':visible'))
						{
							callback.call();
							if(bindopt)
							{
								$('body').unbind('click keyup keydown', checkVis);
								var checkHid = function()
								{
									if($(obj).is(':hidden'))
									{
										$('body').unbind('click keyup keydown', checkHid);
										$('body').bind('click keyup keydown', checkVis);
									}
								}
								$('body').bind('click keyup keydown', checkHid);
							}
						}
					}
					if($(this).is(':hidden'))
					{
						$('body').bind('click keyup keydown', checkVis);
					}
					else
					{
						callback.call();
						var checkHid = function()
						{
							if($(obj).is(':hidden'))
							{
								$('body').unbind('click keyup keydown', checkHid);
								$('body').bind('click keyup keydown', checkVis);
							}
						}
						$('body').bind('click keyup keydown', checkHid);
					}
				}
			});
		}
	});
})(jQuery);

// Plugin jQuery utilisé pour la mise à jours des éléments.
(function($){
	$.setCallback = function(t, p) {
		t.p = p;
		t.callback = p.callback;
		return t;
	};
	$.fn.Updatable = function(options){
		var defaults = {
				callback: null,
		};
		var p = $.extend(defaults, options);
		return this.each(function () {
			$.setCallback(this, p);
		});
	};
	$.fn.updateAttribute = function (attribute, new_value) {
		return this.each(function () {
			if (this.callback)
				this.callback($(this), attribute, new_value);
			else
				alert('Update callback is not defined !');
		});
	};
})(jQuery);

// Mise à jours du parametre d'une flexigrid.
function updateFlexiAttribute(obj, attribute, new_value)
{
	if(!obj[0])
	{
		alert('Erreur de mise à jours de flexigrid...');
		return;
	}
	if(obj[0].p.params)
	{
		var parameters = obj[0].p.params;
		var found = false;
		for(var i in parameters)
		{
			if(parameters[i].name == attribute)
			{
				found = true;
				parameters[i].value = new_value;
			}
		}
		if(!found)
		{
			parameters.push({ name:attribute, value:new_value});
		}

		obj.flexOptions({ params: parameters });
	}
	else
	{
		obj.flexOptions({ params: [{ name:attribute, value: new_value }]});
	}
	if(obj.parent().is(':visible'))
		obj.flexReload();
}


// -------------------------------------------------------------------------
// GESTION DES ANNOTATIONS D'IMAGE
//-------------------------------------------------------------------------

var ImageAnnotation = {};

/**
 * Edition d'une annotation
 */
ImageAnnotation.edit = function(area_id)
{
	
	// Masquer tous les input et rendre visible les spans
	$("input.area_annotation_caption").hide();
	$("span.area_annotation_caption").show();
	$("input.btn_save_annotation").css('visibility', 'hidden');
	$("input.btn_cancel_annotation").css('visibility', 'hidden');

	// Placer le contenu du span dans le input
	var content = $("#"+area_id).find('span').text();
	//$("#"+area_id).find('input').val(content);
	$('#input_area_annotation_caption_'+area_id).val(content);
	
	// Cacher le span, faire apparaitre l'input
	//$("#"+area_id).find('span').hide();
	//$("#"+area_id).find('input').fadeIn(500);
	$('#span_area_annotation_caption_'+area_id).hide();
	$('#input_area_annotation_caption_'+area_id).fadeIn(500);

	// Rendre le bouton visible
	$('#btn_save_annotation_'+area_id).css('visibility', 'visible');
	$('#btn_cancel_annotation_'+area_id).css('visibility', 'visible');

	return 0;
}

/**
 * Annuler la modification
 */
ImageAnnotation.cancel = function(area_id)
{
	
	// Cacher le span, faire apparaitre l'input
	$("#"+area_id).find('input').fadeOut(250, function(){$("#"+area_id).find('span').show()});

	// Rendre le bouton invisible
	$("#"+'btn_save_annotation_'+area_id).css('visibility', 'hidden');
	$("#"+'btn_cancel_annotation_'+area_id).css('visibility', 'hidden');

	return 0;
}

/**
 * Suppression d'une annotation
 * @param area_id
 * @param session
 * @returns {Number}
 */
ImageAnnotation.unlink = function(area_id, session)
{

	//var area_annotation_caption = $("#input_area_annotation_caption_"+area_id).val();
	
	$.ajax({
		url: 'index.php?action=imageannotation.unlink&token=' + session + '&redirect=0',
		data:{
			'crypt/primary_key': area_id,
		},
		success: function(data) {
			if(typeof data.success != 'undefined')
			{
				//window.reload();
				$('#annotation_table').find('.table_list').find("#delete_"+area_id).closest("tr").remove();
				$('#mappingArea-'+area_id).remove();
				
			}
		},
		error: function(data){
			alert("Impossible de supprimer l'annotation");
		},
		dataType: "json"
	});
	
	return 0;
}

/**
 * Sauvegarde d'une annotation
 * @param area_id
 * @param session
 * @returns {Number}
 */
ImageAnnotation.write = function(area_id, session)
{
	var area_annotation_caption = $("#input_area_annotation_caption_"+area_id).val();
	area_annotation_caption = area_annotation_caption.replace(/\\/g,"\/" ); ;
	$("#input_area_annotation_caption_"+area_id).val(area_annotation_caption);

	$.post('index.php?action=imageannotation.write&token=' + session + '&redirect=0',
		{
			'crypt/primary_key': area_id,
			'imageannotation/annotation_texte': area_annotation_caption,
		},
	 	function(data) {
			if(typeof data.success != 'undefined')
			{

				var content = $("#"+area_id).find('input').val();
				$("#"+area_id).find('span').text(content);

				$("#"+area_id).find('input').hide();
				$("#"+area_id).find('span').fadeIn(500);

			}
		}
		, 'json'
	);

	return 0;
}

/**
 * Création d'une annotation
 * @param session
 * @param primary_key
 * @returns
 */
ImageAnnotation.create = function(session, primary_key)
{

	var area_id = 0;

	var area_annotation_caption = $.trim( $("#annotation").val() );
	area_annotation_caption = area_annotation_caption.replace(/\\/g,"\/" ); ;

	var area_Ax =  $('#coord_Ax').val();
	var area_Ay =  $('#coord_Ay').val();
	var area_Bx =  $('#coord_Bx').val();
	var area_By =  $('#coord_By').val();
	var initW   =  $('#mappingImage').width();
	var initH   =  $('#mappingImage').height();

	if( area_Ax === 'NULL' || area_Ax === '' || area_Ax === null
		|| area_Ay === 'NULL' || area_Ay === '' || area_Ay === null
		|| area_Bx === 'NULL' || area_Bx === '' || area_Bx === null
		|| area_By === 'NULL' || area_By === '' || area_By === null)
	{
		alert('delimitez une zone sur l\'image.');
		return false;
	}

	if( area_annotation_caption == '' )
	{
		alert('indiquez un texte pour cette annotation');
		return false;
	}

	$.ajax({
		url: 'index.php?action=imageannotation.create&token=' + session + '&redirect=0',
		data: {
			'object': 'imageannotation',
			'crypt/imageannotation/image_id': primary_key,
			'imageannotation/annotation_texte': area_annotation_caption,
			'imageannotation/coord_Ax': (area_Ax/initW),
			'imageannotation/coord_Ay': (area_Ay/initH),
			'imageannotation/coord_Bx': (area_Bx/initW),
			'imageannotation/coord_By': (area_By/initH)
		},
	 	success : function(data) {
			if(typeof data.success != 'undefined')
			{
				var string_area = '{"id":"'+data.id+'","caption":"'+area_annotation_caption+'","A":{"x":'+(area_Ax/initW)+',"y":'+(area_Ay/initH)+'},"B":{"x":'+(area_Bx/initW)+',"y":'+(area_By/initH)+'}}';
				var row = '<tr class="step ui-widget" data-mapping-area=\''+ string_area +'\'>';
				row += '	<td style="width:60px;text-align:center">';
				row += '		<input id="delete_'+data.id+'" type="image" src="./library/killi/images/gtk-delete.png" style="width:15px;height:15px;" onclick="if(confirm(\'Supprimer cette annotation ?\')) {ImageAnnotation.unlink(\''+data.id+'\', \''+session+'\'); return false;}"/>';
				row += '		<input id="edit_'+data.id+'" type="image" src="./library/killi/images/edit.png" style="width:15px;height:15px;"  onclick="ImageAnnotation.edit(\''+ data.id + '\'); return false;"/>';
				row += '	</td>';
				row += '	<td style="text-align:left;vertical-align:middle">';
				row += '		<span id="'+data.id+'">';
				row += '			<span class="area_annotation_caption" id="span_area_annotation_caption_' + data.id + '">'+ area_annotation_caption +'</span>';
				row += '			<input type="text" id="input_area_annotation_caption_' + data.id + '" class="area_annotation_caption" style="display:none;"/>';
				row += '		</span>';
				row += '	</td>';
				row += '	<td width="20px">';
				row += '		<input id="btn_save_annotation_' + data.id + '" class="btn_save_annotation" type="image" src="./library/killi/images/true.png" style="width:15px;height:15px;" onclick="ImageAnnotation.write(\''+data.id+'\', \''+session+'\'); return false;"/>';
				row += '		<input id="btn_cancel_annotation_' + data.id + '" class="btn_cancel_annotation" type="image" src="./library/killi/images/false.png" style="width:15px;height:15px;" onclick="ImageAnnotation.cancel(\''+data.id+'\', \''+session+'\'); return false;"/>';
				row += '	</td>';
				row += '</tr>';
				$('#annotation_table').find('.table_list').find('tbody').append(row);
				$("#annotation").val('');
				$('#coord_Ax').val('');
				$('#coord_Ay').val('');
				$('#coord_Bx').val('');
				$('#coord_By').val('');
				$("#currentToHide").detach();
				ImageAnnotation.step(data.id);
				}
			else
			{
				alert("Impossible d'ajouter l'annotation.");
			}
		},
		error: function(data) {
			alert("Impossible d'ajouter l'annotation.");
		},
		dataType: "json"
	});


	return false;
}

/**
 * Annuler l'ajout d'annotation
 */
ImageAnnotation.reset = function()
{
	$("#annotation").val('');
	$('#coord_Ax').val('');
	$('#coord_Ay').val('');
	$('#coord_Bx').val('');
	$('#coord_By').val('');
	$("#currentToHide").detach();
}

// --- Annotations : 
// --- Affichage des zones sur l'image

ImageAnnotation.currentScrollTop = 0;
ImageAnnotation.currentScrollTopOld = 0;

ImageAnnotation.step = function(last_only)
{
	$('tr.step').each(function(){
		var $this = $(this);
		var mappingArea = $.parseJSON($this.attr('data-mapping-area'));
		var borderColor = (mappingArea.borderColor != undefined && mappingArea.borderColor != '')? mappingArea.borderColor : "#333";
		
		var Ax   = mappingArea.A.x;
		var Ay   = mappingArea.A.y;
		var Bx   = mappingArea.B.x;
		var By   = mappingArea.B.y;
		
		var imgW   = $('#mappingImage').width();
		var imgH   = $('#mappingImage').height();
		var offset = $('#mappingImage').position();
		
		var left   = Math.min(Ax, Bx)*imgW + offset.left;
		var top    = Math.min(Ay, By)*imgH + offset.top;
		var right  = Math.max(Ax, Bx)*imgW + offset.left;
		var bottom = Math.max(Ay, By)*imgH + offset.top;
		var width  = right - left;
		var height = bottom - top;

		if (width==0)  width  = imgW; // Si dimension nulle : 100%
		if (height==0) height = imgH; // Si dimension nulle : 100%

		if(last_only == null || last_only == mappingArea.id)
		{
			$('#mapping')
				.append('<div id="mappingArea-'+mappingArea.id+'" class="mappingArea"><div class="caption">'+mappingArea.caption.substring(0,35)+'</div></div>')
				.find('#mappingArea-'+mappingArea.id)
				.css({
					'border-color':borderColor,
					'top'   : top+"px",
					'left'  : left+"px",
					'height': height+"px",
					'width' : width+"px"
				})
				.addClass('mappingArea-'+mappingArea.id)
				.hide();
			$this.hover(
				function(){
					$('.mappingArea-'+mappingArea.id).fadeIn(25);
				},
				function(){
					$('.mappingArea-'+mappingArea.id).fadeOut(25);
			});
		}

	}); // END OF $('tr.step').each()
}
$(document).ready(function(){ImageAnnotation.step();});

ImageAnnotation.showOnMove = function(last_only)
{
	$('#sb-body').each(function(){

		var $this = $(this);
		var mappingAreas_list = $.parseJSON($this.attr('data-mapping-areas'));
		
		for(var key in mappingAreas_list)
		{
			var mappingArea = mappingAreas_list[key];
			var borderColor = (mappingArea.borderColor != undefined && mappingArea.borderColor != '')? mappingArea.borderColor : "#333";
	
			if(last_only == null || last_only == mappingArea.id)
			{
				$('#sb-body')
					.append('<div id="mappingArea-'+mappingArea.id+'" class="mappingArea"><div class="caption">'+mappingArea.caption.substring(0,35)+'</div></div>')
					.find('#mappingArea-'+mappingArea.id)
					.css({
						'border-color': borderColor,
						'top'   : "0px",
						'left'  : "0px",
						'height': "1px",
						'width' : "1px"
					})
					.addClass('mappingArea-'+mappingArea.id)
					.hide()
					;
			}
			
		} // for(mappingArea)

	
		$this.mousemove(function(event){
			var x = event.pageX - $this.offset().left
			var y = event.pageY - $this.offset().top
			var mappingAreas_list = $.parseJSON($this.attr('data-mapping-areas'))
			for(var key in mappingAreas_list)
			{
				var mappingArea = mappingAreas_list[key];
				var Ax   = mappingArea.A.x;
				var Ay   = mappingArea.A.y;
				var Bx   = mappingArea.B.x;
				var By   = mappingArea.B.y;
				
				var imgW = $('#sb-body').width();
				var imgH = $('#sb-body').height();
				
				if (Ax-Bx == 0 || Ax-By == 0) continue;
				
				var left   = Math.min(Ax, Bx)*imgW;
				var top    = Math.min(Ay, By)*imgH;
				var right  = Math.max(Ax, Bx)*imgW;
				var bottom = Math.max(Ay, By)*imgH;
				var width  = right - left;
				var height = bottom - top;
	
				if (width == 0) continue;
				if (height == 0) continue;
	
				$('.mappingArea-'+mappingArea.id).css('left',   left+'px');
				$('.mappingArea-'+mappingArea.id).css('top',    top+'px');
				$('.mappingArea-'+mappingArea.id).css('width',  width+'px');
				$('.mappingArea-'+mappingArea.id).css('height', height+'px');
				var right  = left + width;
				var bottom = top + height;
	
				if (
				   x >= left && x <= right
				&& y >= top && y <= bottom
				)
				{
					$('.mappingArea-'+mappingArea.id).fadeIn(25);
				}
				else
				{
					$('.mappingArea-'+mappingArea.id).fadeOut(25);
				}
			}
	
		});	// mousemouve
		
	}); // $.each()
	
};

//--- Annotations : 
//--- Tracé zone de sélection rectangulaire

ImageAnnotation.x1 = 0,
ImageAnnotation.x2 = 0,
ImageAnnotation.y1 = 0,
ImageAnnotation.y2 = 0,
ImageAnnotation.isSelecting = false;

ImageAnnotation.selectArea_onMouseMove = function(e)
{
	if(ImageAnnotation.isSelecting)
	{
		var parentOffset = $('#mappingImage').parent().offset();
		var x = e.pageX - parentOffset.left;
		var y = e.pageY - parentOffset.top;
		if(x-ImageAnnotation.x1 > 0 && y-ImageAnnotation.y1 > 0)
		{
			$("#current").css({
				width:Math.abs(x - ImageAnnotation.x1),
				height:Math.abs(y - ImageAnnotation.y1)
			}).fadeIn();
			$("#coord_Bx").val( parseInt(Math.abs(x),10) );
			$("#coord_By").val( parseInt(Math.abs(y),10) );
		}
	}
	return 0;
}


//-------------------------------------------------------------------------
// 
//-------------------------------------------------------------------------

$(document).ready(function(){
	// Evry input with the "colorpicker" attribute.
	$('input[colorpicker]').ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
			$(el).val('#' + hex.toUpperCase());
			$(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$(this).ColorPickerSetColor(this.value.substr(1));
		}
	})
	.bind('keyup', function(){
		$(this).ColorPickerSetColor(this.value.substr(1));
	});
});

add_token = function() {
	return '&token=' + $('#__token').val();
}

// toggle input

$(document).ready(function()
{
	$('div.checkbox_input').click(function()
	{
		var onchange = $(this).attr('onchange');

		if(!onchange)
		{
			onchange = '';
		}

		var funcs = onchange.split('|');

		if($(this).hasClass('checkbox_oui'))
		{
			$(this).addClass('checkbox_non');
			$(this).removeClass('checkbox_oui');

			$('#'+$(this).attr('id')+'_yes').attr('checked',false);
			$('#'+$(this).attr('id')+'_no').attr('checked','checked');
			for(f in funcs)
			{
				if(funcs[f] != '')
				{
					var func_name = funcs[f]+'($("#'+$(this).attr('id')+'_no"))';
					eval(func_name);
				}
			}
		}
		else
		{
			$(this).addClass('checkbox_oui');
			$(this).removeClass('checkbox_non');

			$('#'+$(this).attr('id')+'_yes').attr('checked','checked');
			$('#'+$(this).attr('id')+'_no').attr('checked',false);
			for(f in funcs)
			{
				if(funcs[f] != '')
				{
					var func_name = funcs[f]+'($("#'+$(this).attr('id')+'_yes"))';
					eval(func_name);
				}
			}
		}

	});
});

// Menu flottant
// todo cleanup + factorisation @vp

$(document).ready(function()
{
	if($('#warning_list_table').length==0 && $('#error_list_table').length==0 && $('#message_list_table').length==0 && $('table.header:first').length==1 && $('#main_menu').length==1 && $('table.title:first').length==1 && $('table.navigator:first').length==1)
	{
		$('table.header:first').css({
			position:'fixed',
			top:0,
			'background-color':'white',
			'z-index':1004
		});

		$('#main_menu').css({
			position:'fixed',
			top:'28px',
			'z-index':1005
		});

		$('table.title:first').css({
			position:'fixed',
			top:'54px',
			'background-color':'white',
			'z-index':1003
		});

		$('table.title:first').after('<div id="hidden_white_bg_header" style="background-color: white;position: fixed;top:0;height: 86px;width: 100%;z-index:1001"></div>');

		$('table.navigator:first').css({
			position:'fixed',
			top:'78px',
			'margin-top':0,
			'z-index':1001
		});

		$('table.navigator:first').after('<div id="hidden_white_bg" style="margin-top:106px;z-index:1000"></div>');

	}
});

/*
 * Returns total header elements heights sum
 */
function header_height()
{
	var total_h = 0;
	// Message list
	total_h += parseInt($('#message_list_table').css('height') || '0px');
	// Error list
	total_h += parseInt($('#error_list_table').css('height') || '0px');
	// warning list
	total_h += parseInt($('#warning_list_table').css('height') || '0px');
	// Header message
	total_h += parseInt($('table.header:first').css('height') || '0px');
	// Main menu
	total_h += parseInt($('#main_menu').css('height') || '0px');
	// Title bar
	total_h += parseInt($('table.title:first').css('height') || '0px');
	// Navigator
	total_h += parseInt($('table.navigator:first').css('height') || '0px');
	// Total heights
	return total_h;
}

/*
 * Returns real footer height occupation (with margins).
 */
function footer_height()
{
	var total_h = 0;
	// Height of the line
	total_h += parseInt($('center[id=benchmark_infos]').css('height') || '0px');
	// Top margin
	total_h += parseInt($('center[id=benchmark_infos]').css('margin-top') || '0px');
	// Bottom margin
	total_h += parseInt($('center[id=benchmark_infos]').css('margin-bottom') || '0px');
	// Block height
	return total_h;
}

/*
 * Gets the header to stop behaving "fixed", just like good'ol IW.
 */
function lock_header()
{
	$('table.header:first').css({
		position:'initial',
		top:0,
		'background-color':'initial',
		'z-index':1004
	});

	$('#main_menu').css({
		position:'initial',
		top:'28px',
		'z-index':1005
	});

	$('table.title:first').css({
		position:'initial',
		top:'54px',
		'background-color':'initial',
		'z-index':1003
	});

	$('table.navigator:first').css({
		position:'initial',
		top:'78px',
		'margin-top':'2px',
		'z-index':1001
	});

	$('#hidden_white_bg_header').remove();
	$('#hidden_white_bg').remove();
}

// popup de confirmation de suppression

function dial_delete_confirm(pk, object){
	$( "<div style='display:none' title='Suppression'>"
			+"<p><span class='ui-icon ui-icon-alert' style='float: left; margin: 0 7px 20px 0;'></span><b>Attention !</b> Vous êtes sur le point de supprimer définitivement cet élément ("+object+").<br/>Êtes-vous sûr de vouloir continuer ?</p>"
			+"</div>")

	.dialog({
		resizable: false,
		modal: true,
		buttons: {
			"Supprimer": function() {
				window.location.href='./index.php?action='+object+'.unlink&crypt/primary_key='+pk+add_token();
			},
			'Annuler': function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

//-----------------------------------------------------------------------------
function retrieveFieldValue(field, name)
{
	var result = '';
	if($(field).length == 0)
	{
		result = $('.field_value[name="'+name+'"]').text();
	}
	else
	{
		$(field).each(function() {
			if($(this).attr('type') == 'radio' && $(this).is(':checked') == true)
			{
				result = $(this).val();
			}
			else
			if($(this).attr('type') == 'text' || $(this).attr('type') == 'hidden')
			{
				result = $(this).val();
			}
			else
			if($(this).prop("tagName") == 'SELECT')
			{
				result = $(this).children('option:selected').text();
			}
		});
	}
	return result;
}
//-----------------------------------------------------------------------------
function updatePeriodSelector(oFrom, planifier_id)
{
	var theDate = $(oFrom).val();
	var date_list = theDate.split('-');
	$('#' + planifier_id).fullCalendar('gotoDate', parseInt(date_list[0], 10), parseInt(date_list[1], 10)-1, parseInt(date_list[2], 10));
}

function head_zoom(delta)
{
	$('html').css('zoom',$('html').css('zoom')-(-delta/10));
	$.cookie('zoom',$('html').css('zoom'),{expires:365});
}

$(document).ready(function()
{
	var zoom = $.cookie('zoom');
	if(zoom)
	{
		$('html').css('zoom',zoom);
	}
});

function show_reset(field){
	if(field.val()!='')
	{
		$('#'+field.attr('id')+'_reset').show();
	}
	else
	{
		$('#'+field.attr('id')+'_reset').hide();
	}
};

$(document).ready(function()
{
	$('.blink').each(function() {
	    var elem = $(this);
	    setInterval(function() {
	        if (elem.css('textDecoration') == 'underline') {
	            elem.css('textDecoration', 'none');
	        } else {
	            elem.css('textDecoration', 'underline');
	        }
	    }, 750);
	});

	$('.table_list tr td > div > div').each(function(){
		if (this.style.backgroundColor != '')
		{
			$(this).parent().css('background-color', $(this).css('background-color'));
			$(this).css('background-color','');
		}
	});
});


function Clone() { }
function clone(obj) {
    Clone.prototype = obj;
    return new Clone();
}

$(document).ready(function()
{
	$.ui.dialog.prototype.options.zIndex = 1100;

	$.ui.dialog.prototype.options.open=function(){
		$('.ui-dialog').parent().children(':not(.ui-dialog, script)').not(':empty').css({'-webkit-filter':'grayscale(1)','filter':'grayscale(1)'});
	}

	$.ui.dialog.prototype.options.close=function(){
		$('.ui-dialog').parent().children(':not(.ui-dialog, script)').not(':empty').css({'-webkit-filter':'none','filter':'none'});
	}
});


$.datepicker.regional['fr'] = {
	closeText: 'Ok',
	prevText: '<Prec.',
	nextText: 'Suiv.>',
	currentText: 'Aujourd\'hui',
	monthNames: ['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aôut','Septembre','Octobre','Novembre','Decembre'],
	monthNamesShort: ['Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aou','Sept','Oct','Nov','Dec'],
	dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
	dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
	dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
	weekHeader: 'Sem',
	dateFormat: 'dd/mm/yy',
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: ''
};
$.datepicker.setDefaults($.datepicker.regional['fr']);

$.timepicker.regional['fr'] = {
	timeOnlyTitle: 'Heures',
	timeText: 'Selection',
	hourText: 'Heures',
	minuteText: 'Minutes',
	secondText: 'Secondes',
	millisecText: 'MilliSecondes',
	currentText: 'Maintenant',
	closeText: 'Ok',
	ampm: false,
	showSecond: true,
	minuteGrid: 10,
	secondGrid: 10,
	timeFormat: 'hh:mm:ss',
	hourGrid: 3
};
$.timepicker.setDefaults($.timepicker.regional['fr']);

$(function(){
	$( ".tooltip_link" ).tooltip({
	      track: true
    });
});

function ajax_listing_pagination_wait(id)
{
	var loader = $('<div style="position: absolute; top:0;bottom:0;right:0;left:0;background-color:rgba(255,255,255,.25);text-align: center;"><img src="./library/killi/images/loader.gif"></div>');
	var content = $('#' + id).find('.listing_content');
	content.prepend(loader);

}

function ajax_listing_pagination(id, page, show)
{
	var filters = $('#' + id).find('tr.listing_filters').find('input,textarea,select').serialize();
	$.ajax({
		async: true,
		url: document.location.href.replace('#', '')+'&render_node='+id+'&render_listing_page='+page,
		type: 'POST',
		data: filters,
		dataType: 'html',
		success: function(response)
		{
			/* Check if the response contain HTML data with the good id */
			if($(response).filter('#' + id).length)
			{
				/* Get filters status */
				var filter_active = $('#' + id).find('tr.listing_filters').data('filter_enabled');

				/* Update data */
				$('#' + id).replaceWith(response);

				/* Set filters status */
				if(filter_active)
				{
					$('#' + id).find('tr.listing_filters').toggle();
				}

				/* Save page loaded */
				$.cookie('rlp_'+id, page);
			}
			else
			{
				$('#' + id).replaceWith('<h3>Une erreur est survenu lors du chargement, veuillez recharger la page et réessayer !</h3>');
			}
		}
	});
}

function addThousandSeparator(n){
    var rx=  /(\d+)(\d{3})/;
    return String(n).replace(/^\d+/, function(w){
        while(rx.test(w)){
            w= w.replace(rx, '$1 $2');
        }
        return w;
    });
}

// Menu height fix
$(document).ready(function()
{
	headerH = $("table.header").outerHeight() + $("#main_menu").outerHeight();
	$('table.title').css('top', headerH, 'important');
	headerH = headerH + $('table.title').outerHeight();
	$('#hidden_white_bg_header').css('height', headerH);
	$('.navigator').css('top', headerH);
	headerH = headerH + $('.navigator').outerHeight();
	$('#hidden_white_bg').css('margin-top', headerH);
});

var stackedLoading = [];

function push_loading(func)
{
	stackedLoading.push(func);
}

function run_stack()
{
	for(var i in stackedLoading)
	{
		stackedLoading[i]();
	}
}

function displayPDF(button)
{
	$obj = $(button);

	if ($('#documents-display').length == 0)
	{
		var $listing = $obj.parents('#listing_document_list');

		$listing.wrap('<div id="display-pdf-container"/>');
		$listing.after('<div id="documents-display" style="margin-top:15px;height:600px;width:100%;background-color:#CCC;"/>');
	}

	var $loader = $('<div id="display-pdf-loader-container" style="padding-top:30px;"><div id="display-pdf-loader" style="background-color: #FFF;margin: 20px auto;text-align: center;width: 200px;padding: 20px;border-radius: 4px;"><img src="./library/killi/images/loader.gif"/><br/><br/><span>Chargement du document en cours...</span></div></div>');

	var pk = $obj.data('key');
	var doc_url = './index.php?action=document.getDocContent&crypt/primary_key='+pk+add_token();

	$('#documents-display').append($loader);

	$.ajax(
	{
	    url: doc_url,
	    cache: true,
	    mimeType: 'application/pdf',
	    success: function ()
	    {
	        $("#documents-display").append('<embed style="width:100%;height:600px;" type="application/pdf" src="' + doc_url + '"/>');
	       	$loader.remove();
	    }
	});
}
