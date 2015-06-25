function setPlotSize(chart)
{
	var contW = chart.parent().outerWidth();
	var contH = chart.parent().height();
	var legendW = chart.parent().children('.jqplot-legend').children('.jqplot-legend-wrapper').outerWidth();
	var legendH = chart.parent().children('.jqplot-legend').height();

	var legend_margin_top = 27;
	
	if (legendW == null)
	{					
		var plotW = contW - 10;
	}
	else
	{
		chart.parent().children('.jqplot-legend').css('margin-top', legend_margin_top);
		var plotW = contW - legendW - 40;
	}

	chart.outerWidth(plotW - 20);
	chart.height(contH);

	// FIX legend height issue
	var chartH = chart.height() - legend_margin_top
	if (legendH > chartH)
	{
		chart.parent().children('.jqplot-legend').outerWidth(legendW);
		chart.parent().children('.jqplot-legend').height(chart.height() - legend_margin_top);
		chart.parent().children('.jqplot-legend').css('overflow-y', 'scroll');
	}
	else
	{
		chart.parent().children('.jqplot-legend').outerWidth(legendW + 20);
	}

}

function setVisible(chart, serie_id, bool)
{	
	// if chart is a pie
	if (chart.data('jqplot').series[0]._diameter > 0)
	{
		if (!bool)
		{
			chart.data('jqplot')._stackData[1][serie_id][1] = 0;
		}
		else
		{
			chart.data('jqplot')._stackData[1][serie_id][1] = chart.data('jqplot').data[0][serie_id][1];
		}
	}
	// else if is a stacked barchart
	/*else if (chart.data('jqplot').options.stackSeries == true)
	{
		$.each(chart.data('jqplot')._stackData[serie_id], function(id, value) {
			//console.log( id + ' - ' + value );
			console.log( chart.data('jqplot')._stackData[serie_id][id][1] );
			console.log( chart.data('jqplot')._plotData[serie_id][id][1] );
			chart.data('jqplot')._stackData[serie_id][id][1] = 0;
			chart.data('jqplot')._plotData[serie_id][id][1] = 0;
			console.log( chart.data('jqplot')._stackData[serie_id][id][1] );
			console.log( chart.data('jqplot')._plotData[serie_id][id][1] );
		});
		//chart.data('jqplot').series[serie_id]._stackData[0][1] = 0;

		//console.log(chart.data('jqplot'));
	}*/
	// else
	else if (chart.data('jqplot').series[serie_id])
	{
		chart.data('jqplot').series[serie_id].show = bool;
	}

	// hide/show legend
	if (bool)
	{
		chart.parent().find('#jqplot-legend-line-'+serie_id).removeClass('jqplot-legend-hidden');
	}
	else
	{
		chart.parent().find('#jqplot-legend-line-'+serie_id).addClass('jqplot-legend-hidden');
	}
	
}

$(document).ready(function(){

	$('body').append('<div class="jqplot-tooltip" style="position:absolute;background:#fff;border:1px solid #ccc;display:none;padding:2px;z-index:9999"></div>');

	$('.jqplot-chart').each(function(){

		setPlotSize($(this));

		// get datas and params
		var datas	 = eval($(this).attr('data-jqplot'));

		// arrays
		var lines		  = datas.lines;
		var params		  = datas.params;
		var lines_series  = datas.lines_series;
		var lines_visible = datas.lines_visible;

		//booleans
		var trend		  = datas.trend;
		var zoom		  = datas.zoom;
		var legend		  = datas.legend;

		// test if trend is active
		if (!trend)
		{
			trend = 0;
		}
		$.jqplot.config.enablePlugins = trend;

		// create jqplot chart
		var jqplot_div = $('#'+$(this).attr('id'));
		jqplot_div.jqplot(
			lines,
			params
		);

		//jqplot_div.data('jqplot').replot();

		// [FIX] replot when barchart is stack
		if(jqplot_div.data('jqplot').options.stackSeries)
		{
			jqplot_div.data('jqplot').replot();
		}

		/* ----------------------------- */
		/*         LEGEND HIDING         */
		/* ----------------------------- */
		if (legend)
		{			
			// hide invisible series
			if ($.inArray(false, lines_visible) > -1 || $.inArray("0", lines_visible) > -1)
			{
				$.each(lines_visible, function(serie_id, bool) {
					if (bool == "0")
					{
						bool = false;
					}
					if (!bool)
					{
						setVisible(jqplot_div, serie_id, bool);
					}
				});
				jqplot_div.data('jqplot').replot();
			}
			//set legend-color hidding on click
			jqplot_div.parent().find('.jqplot-legend-color-outline').each(function(){
				$(this).click(function(){
					var serie_id = $(this).parents('.jqplot-legend-line').attr('id').replace('jqplot-legend-line-','');
					if ($(this).parents('.jqplot-legend-line').hasClass('jqplot-legend-hidden'))
					{
						bool = true;
					}
					else
					{					
						bool = false;
					};
					setVisible(jqplot_div, serie_id, bool);
					if (jqplot_div.data('jqplot').stackSeries == true)
					{
						jqplot_div.data('jqplot').redraw();
					}
					else
					{
						jqplot_div.data('jqplot').replot();
					}
				});
			});
			//set legend-color hidding on click
			jqplot_div.parent().find('.jqplot-legend-hide-all').each(function(){
				$(this).button();
				$(this).click(function(){
					if ($(this).hasClass('show'))
					{
						$(this).removeClass('show');
						$(this).children('span').html('Masquer tout');
						bool = true;
					}
					else
					{
						$(this).addClass('show');
						$(this).children('span').html('Afficher tout');
						bool = false;
					}				
					$.each(lines_visible, function(serie_id) {
						setVisible(jqplot_div, serie_id, bool);
					});
					jqplot_div.data('jqplot').replot();
					return false;
				});
			});			
		}


		/* ----------------------------- */
		/*         DATA HIGHLIGHT        */
		/* ----------------------------- */

		// show highlighting on Linecharts
		$.each(lines_series, function(index, serie_id) {
			jqplot_div.data('jqplot').series[serie_id].highlightMouseOver = true;
		});
		// Mouse over
		$(this).bind('jqplotDataHighlight', function (event, seriesIndex, pointIndex, datas) {	

			$('.jqplot-tooltip').css('display', 'block');

			if ( datas[2] != '' )
			{
				message = datas[2];
			}
			else
			{
				message = datas[0] + ' (' + datas[1] + ')';
			}
			$('.jqplot-tooltip').html(message);
			
		});
		// Mouse out
		$(this).bind('jqplotDataUnhighlight', function (event) {
			$('.jqplot-tooltip').css('display', 'none');
		});
		// Mouse move + caption
		$(document).bind('mousemove', function(e){
			$('.jqplot-tooltip').css({
				 left:	e.pageX + 12,
				 top:	 e.pageY 
			});
		});

		// Zoom buttons
		if (zoom)
		{
			zoom_button = $(this).parent().children('.jqplot-zoom-reset');
			zoom_button.button();
			zoomT = $(this).children('.jqplot-zoom-canvas').offset().top - $(this).children('.jqplot-grid-canvas').offset().top;
			zoomL = $(this).children('.jqplot-zoom-canvas').offset().left - $(this).children('.jqplot-grid-canvas').offset().left;
			zoom_button.css('top', zoomT + 10);
			zoom_button.css('left', zoomL + 10);
			zoom_button.click(function() {
				jqplot_div.data('jqplot').resetZoom();
				return false;
			});
		}

	});

	// resizing
	$(window).resize(function() {
		$('.jqplot-chart').each(function(){
			setPlotSize($(this));
			$(this).data('jqplot').replot();
		});
	});

});

$(window).load(function(){
	
	$('.ui-tabs').each(function(){
		$(this).bind('tabsshow', function(event, ui) {
			$(this).find('.jqplot-chart:visible').each(function()
			{
				var identifier = $(this).attr('id');
				setPlotSize($(this));
				$(this).data('jqplot').replot();
			});
		});
	});

});