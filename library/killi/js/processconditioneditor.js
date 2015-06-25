/**
 * ====================================
 * moduleConditionEditor
 * ====================================
 *
 * Éditeur de condition user friendly pour la gestion des process.
 *
 * Author: Timothé Mermet-Buffet
 * Date: Janvier 2015
 */

(function($){
	$.fn.moduleConditionEditor = function(options){
		var defaults = {
				editable: true,
				variable: {},
				output: [],
				default_output: 'other',
		};
		var p = $.extend(defaults, options);
		return this.each(function () {
			$.setOptions(this, p);

			var $this = $(this);
			$.render($this);
		});
	};

	$.setOptions = function(t, p) {
		t.options = p;
		return t;
	};

	$.updateField = function(element) {
		var output = $.generateJSONOutput(element);
		element.val(JSON.stringify(output));
	}

	$.render = function(t) {

		var editable			= t[0].options.editable;
		var output				= t[0].options.output;
		var isOutputEditable	= (output.length == 0) && editable;

		var container = $('<div class="moduleConditionEditor" tabindex="1"></div>');
		t[0].main_container = container;

		if(editable)
		{
			container.keydown(function(event) {
				switch(event.keyCode)
				{
					case 13: // Enter
						$.updateField(t);
						break;
					case 46: // Delete
						var toDelete = container.find('li.selected');
						if(toDelete.hasClass('operator'))
						{
							toDelete = toDelete.parent();
							toDelete.detach();
							break;
						}
						toDelete.empty();
						toDelete.attr('class', '');
						var ce = toDelete.data('contenteditable');
						if(ce == '1')
						{
							toDelete.attr('contenteditable', 'true');
						}
						break;
				}
			});
		}

		var content = t.val();
		var obj = jQuery.parseJSON(content);

		var list = $('<ul></ul>');

		if(!isOutputEditable)
		{
			for(var o in output)
			{
				var data = {};
				if(obj && obj.hasOwnProperty(output[o]))
				{
					data = obj[output[o]];
				}
				var subcontainer = $.renderConditionBlock(t, output[o], data);
				list.append(subcontainer);
			}
		}
		else
		{
			for(var response_index in obj)
			{
				if (obj.hasOwnProperty(response_index))
				{
					var subcontainer = $.renderConditionBlock(t, response_index, obj[response_index]);
					list.append(subcontainer);
				}
			}
		}

		/**
			* Generating footer (add new output to the condition)
			*/
		var footer = $('<div class="add_output"></div>');
		if(isOutputEditable)
		{
			var new_output_field = $('<input type="text" value=""/>');
			var new_output_button = $('<button type="button">+</button>');

			new_output_button.click(function() {
				var value = new_output_field.val();
				var subcontainer = $.renderConditionBlock(t, value, '');
				list.append(subcontainer);
				new_output_field.val('');
			});

			footer.append(new_output_field);
			footer.append(new_output_button);
		}

		container.append(list);
		container.append(footer);

		t.parent().append(container);

		return t;
	};

	$.assocArraySize = function(obj) {
		var size = 0, key;
		for (key in obj)
		{
			if (obj.hasOwnProperty(key))
			{
				size++;
			}
		}
		return size;
	};

	$.renderConditionBlock = function(element, output, condition) {
		var subcontainer = $('<li></li>');

		var editable = element[0].options.editable;
		var isOutputEditable = element[0].options.output.length == 0 && editable;

		/**
		 * Define name of the output.
		 */
		if(editable)
		{
			var output_name_field = $('<input type="text" value="'+output+'"/>');
			if(!isOutputEditable)
			{
				var output_name_field = $('<input type="hidden" value="'+output+'"/>');
			}
			subcontainer.append(output_name_field);
		}

		if(!isOutputEditable)
		{
			subcontainer.append('<h3>Sortie : ' + output + '</h3>');
		}

		/**
		 * Toolbar
		 */
		var toolbar = $('<div class="toolbar"></div>');
		var button_list = $('<ul></ul>');

		/* Opérateurs */
		var button_op = $('<a>Operateurs</a>');
		var button_op_container = $('<li></li>');
		button_op_container.append(button_op);

		var menu_callback = function(c)
		{
			var form_container = subcontainer.find('.formula');
			var f = form_container.find('.selected');
			var editor = $.renderSubCondition(element, c);
			if(f.length)
			{
				f.removeClass('selected');
				f.children().empty();
				f.append(editor);
			}
			else
			if(form_container.children().length == 0)
			{
				form_container.append(editor);
			}
			$.updateField(element);
		}

		var button_op_list = $('<ul></ul>');
		var operators_list = {
			'Et': function() {
				menu_callback({'operateur': 'and', 'operande1': ['', '']});
			},
			'Ou': function() {
				menu_callback({'operateur': 'or', 'operande1': ['', '']});
			},
			'=': function() {
				menu_callback({'operateur': '=', 'operande1': '', 'operande2': ''});
			},
			'!=': function() {
				menu_callback({'operateur': '!=', 'operande1': '', 'operande2': ''});
			},
			'>': function() {
				menu_callback({'operateur': '>', 'operande1': '', 'operande2': ''});
			},
			'<': function() {
				menu_callback({'operateur': '<', 'operande1': '', 'operande2': ''});
			},
			'>=': function() {
				menu_callback({'operateur': '>=', 'operande1': '', 'operande2': ''});
			},
			'<=': function() {
				menu_callback({'operateur': '<=', 'operande1': '', 'operande2': ''});
			},
		};

		for(var op in operators_list)
		{
			var var_ctn = $('<li></li>');
			var var_btn = $('<a>'+op+'</a>');
			var_btn.data('callback', operators_list[op]);
			var_btn.click(function() {
				var container = subcontainer.find('.formula').find('.selected');

				var type = container.parent().data('type');
				if(typeof type == 'undefined' || type == 'boolean')
				{
					if(!container.length || !container.hasClass('operator'))
					{
						var func = $(this).data('callback');
						func();
					}
				}
				return true;
			});
			var_ctn.append(var_btn);
			button_op_list.append(var_ctn);
		}
		button_op_container.append(button_op_list);

		/* Variables */
		var variables_list = element[0].options.variable;

		if($.assocArraySize(variables_list) > 0)
		{
			var button_var = $('<a>Variables</a>');
			var button_var_container = $('<li></li>');
			button_var_container.append(button_var);

			var button_var_list = $('<ul></ul>');

			for(var v in variables_list)
			{
				var var_ctn = $('<li></li>');
				var var_btn = $('<a>'+variables_list[v]+'</a>');

				var value = $('<span attribute="'+v+'">' + variables_list[v] + '</span>');

				var_btn.data('value', value);

				var_btn.click(function() {
					var form_container = subcontainer.find('.formula');
					var f = form_container.find('.selected');

					if(!f.length)
					{
						return false;
					}

					f.removeClass('selected');
					f.addClass('variable');

					var editor = $.renderSubCondition(element, $(this).data('value').clone());
					f.empty();
					f.attr('contenteditable', 'false');
					f.append(editor);

					$.updateField(element);
				});
				var_ctn.append(var_btn);
				button_var_list.append(var_ctn);
			}
			button_var_container.append(button_var_list);
		}

		/**
		 * Build toolbox
		 */
		button_list.append(button_op_container);
		button_list.append(button_var_container);

		toolbar.append(button_list);

		if(editable)
		{
			subcontainer.append(toolbar);
		}

		/**
		 *  Build condition editor.
		 */
		var editor = $.renderConditionEditor(element, condition);
		subcontainer.append(editor);

		return subcontainer;
	};

	$.renderConditionEditor = function(element, condition) {
		var container = $('<div class="formula cl"></div>');

		var str = $.renderSubCondition(element, condition);

		container.append(str);
		return container;
	};

	$.renderSubCondition = function(element, condition) {

		if(!condition.operateur)
		{
			if(condition.attribute)
			{
				var var_name = element[0].options.variable[condition.attribute];
				if(typeof var_name == 'undefined')
				{
					var_name = condition.attribute;
				}
				var o = $('<span attribute="' + condition.attribute + '">'+var_name+'</span>');
				return o;
			}
			return condition;
		}

		var op = condition.operateur;

		var callback = function(e) {

			if(!element[0].options.editable)
			{
				return false;
			}

			if($(e).find('ul').length > 0)
			{
				return false;
			}

			$(".selected").removeClass("selected");

			$(e).addClass('selected');
		};

		switch(op)
		{
			case 'and':
			case 'or':
				var op1 = condition.operande1;
				var operator = $('<ul></ul>');
				operator.data('operator', op);
				operator.data('type', 'boolean');
				for(var v in op1)
				{
					if(v != 0)
					{
						var op_li = $('<li class="operator">ET</li>');

						if(op == 'or')
						{
							op_li = $('<li class="operator">OU</li>');
						}

						op_li.click(function() { callback(this); });
						operator.append(op_li);
					}
					var li = $('<li></li>');
					li.click(function() { callback(this); });
					var o = $.renderSubCondition(element, op1[v]);
					li.append(o);
					if(li.find('>span[attribute]').length)
					{
						li.addClass('variable');
					}
					operator.append(li);
				}
				return operator;
				break;
			case '=':
			case '!=':
			case '>':
			case '<':
			case '>=':
			case '<=':
				var operator = $('<ul></ul>');
				operator.data('operator', op);
				operator.data('type', 'binary');

				var o = $.renderSubCondition(element, condition.operande1);
				var li_op1 = $('<li></li>');
				li_op1.append(o);
				if(li_op1.find('>span[attribute]').length)
				{
					li_op1.addClass('variable');
				}
				operator.append(li_op1);

				var li_op = $('<li></li>');
				li_op.addClass('operator');
				li_op.append(op);
				operator.append(li_op);

				var o = $.renderSubCondition(element, condition.operande2);
				var li_op2 = $('<li></li>');
				li_op2.append(o);
				if(li_op2.find('>span[attribute]').length)
				{
					li_op2.addClass('variable');
				}
				operator.append(li_op2);

				if(element[0].options.editable)
				{
					li_op.click(function() { callback(this); });

					li_op1.attr('contenteditable', 'true');
					li_op1.data('contenteditable', '1');
					li_op1.click(function() { callback(this); });
					li_op1.keyup(function() { $.updateField(element); });

					li_op2.attr('contenteditable', 'true');
					li_op2.data('contenteditable', '1');
					li_op2.click(function() { callback(this); });
					li_op2.keyup(function() { $.updateField(element); });
				}

				return operator;
				break;
			default:
				console.log('Operand not recognized ! ' + op);
		}

		console.log('ERROR !');

		return null;
	};

	$.generateJSONOutput = function(element)
	{
		var container = element[0].main_container;

		var read_formula = function(element) {
			var output = {};
			var operator = element.data('operator');

			/**
			 * Text or variable
			 */
			if(typeof operator == 'undefined')
			{
				if(element.hasClass('variable'))
				{
					var value = element.find('span').attr('attribute');
					return {'attribute': value};
				}
				return element.text();
			}

			/**
			 * Operator
			 */
			output['operateur'] = operator;

			switch(element.data('type'))
			{
				case 'boolean':
					var operande1 = [];

					element.find('>li:not(.operator)').each(function() {
						var child = $(this).find('>ul');
						if(child.length)
						{
							operande1.push(read_formula(child));
						}
						else
						{
							operande1.push(read_formula($(this)));
						}
					});

					output['operande1'] = operande1;
					break;
				case 'binary':
					var operande1 = [];

					var binary = element.find('>li:not(.operator)');

					output['operande1'] = read_formula($(binary[0]));
					output['operande2'] = read_formula($(binary[1]));
					break;
				default:
					console.log('Unrecognized operator ! ' + operator);
			}

			return output;
		};

		var output = {};
		container.find('>ul').children().each(function() {
			var $this = $(this);
			var output_name = $this.find('>input').val();
			var o = read_formula($this.find('.formula > ul'));
			output[output_name] =  o;
		});
		return output;
	};
})(jQuery);
