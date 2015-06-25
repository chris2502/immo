<?php

/**
 *  @class JQPlotXMLNode
 *  @Revision $Revision: 4618 $
 *
 */

/*
// TODO :
	- Séparer la legend en Axis 1 et Axis 2 lorsque l'option axis2 est activée
*/

define("HIGHLIGHTER_SIZE", 8);

class JQPlotXMLNode extends XMLNode
{
	public $debug = array(
		'data'		=> 0,
		'count'		=> 0,
		'lines'		=> 0,
		'params'	=> 0,
		'plugins'	=> 0,
		'legend'	=> 0
	);

	public $params = array();
	public $lines = array();
	public $line_visible = array();
	public $line_series = array();

	public $tooltip = array();
	public $is_tooltip = FALSE;

	public $legend = array();
	public $is_legend = FALSE;

	public static $is_first_plot = TRUE;

	public $count = array(
		'serie' => 0,
		'point' => 0,
		'color' => 0,
	);

	public static $jqPlotPlugins = array(
		'bar' =>				array( 'import' => FALSE,	'file' => 'jqplot.barRenderer.min.js' ),
	  //'bezierCurve' =>		array( 'import' => FALSE,	'file' => 'jqplot.BezierCurveRenderer.min.js', ),
	  //'block' =>				array( 'import' => FALSE,	'file' => 'jqplot.blockRenderer.min.js' ),
	  //'bubble' =>				array( 'import' => FALSE,	'file' => 'jqplot.bubbleRenderer.min.js' ),
		'canvasAxisLabel' =>	array( 'import' => TRUE,	'file' => 'jqplot.canvasAxisLabelRenderer.min.js', ),
		'canvasAxisTick' =>		array( 'import' => FALSE,	'file' => 'jqplot.canvasAxisTickRenderer.min.js', ),
	  //'canvasOverlay' =>		array( 'import' => FALSE,	'file' => 'jqplot.canvasOverlay.min.js' ),
		'canvasText' =>			array( 'import' => TRUE,	'file' => 'jqplot.canvasTextRenderer.min.js' ),
		'categoryAxis' =>		array( 'import' => FALSE,	'file' => 'jqplot.categoryAxisRenderer.min.js', ),
	  //'ciParser' =>			array( 'import' => FALSE,	'file' => 'jqplot.ciParser.min.js' ),
		'cursor' =>				array( 'import' => FALSE,	'file' => 'jqplot.cursor.min.js' ),
		'dateAxis' =>			array( 'import' => FALSE,	'file' => 'jqplot.dateAxisRenderer.min.js'),
		'donut' =>				array( 'import' => FALSE,	'file' => 'jqplot.donutRenderer.min.js' ),
	  //'dragable' =>			array( 'import' => FALSE,	'file' => 'jqplot.dragable.min.js' ),
	  //'enhancedLegend' =>		array( 'import' => FALSE,	'file' => 'jqplot.enhancedLegendRenderer.min.js', ),
	  //'funnel' =>				array( 'import' => FALSE,	'file' => 'jqplot.funnelRenderer.min.js' ),
	  //'highlighter' =>		array( 'import' => FALSE,	'file' => 'jqplot.highlighter.min.js' ),
	  //'json2' =>				array( 'import' => FALSE,	'file' => 'jqplot.json2.min.js' ),
		'logAxis' =>			array( 'import' => FALSE,	'file' => 'jqplot.logAxisRenderer.min.js'),
	  //'mekkoAxis' =>			array( 'import' => FALSE,	'file' => 'jqplot.mekkoAxisRenderer.min.js'),
	  //'mekko' =>				array( 'import' => FALSE,	'file' => 'jqplot.mekkoRenderer.min.js' ),
	  //'meterGauge' =>			array( 'import' => FALSE,	'file' => 'jqplot.meterGaugeRenderer.min.js'),
	  //'mobile' =>				array( 'import' => FALSE,	'file' => 'jqplot.mobile.min.js' ),
	  //'ohlc' =>				array( 'import' => FALSE,	'file' => 'jqplot.ohlcRenderer.min.js' ),
		'pie' =>				array( 'import' => FALSE,	'file' => 'jqplot.pieRenderer.modified.js' ),
		'pointLabels' =>		array( 'import' => FALSE,	'file' => 'jqplot.pointLabels.min.js' ),
	  //'pyramidAxis' =>		array( 'import' => FALSE,	'file' => 'jqplot.pyramidAxisRenderer.min.js', ),
	  //'pyramidGrid' =>		array( 'import' => FALSE,	'file' => 'jqplot.pyramidGridRenderer.min.js', ),
	  //'pyramid' =>			array( 'import' => FALSE,	'file' => 'jqplot.pyramidRenderer.min.js'),
		'trendline' =>			array( 'import' => FALSE,	'file' => 'jqplot.trendline.min.js' )
	);
	public static $jqPlotActivated = array();
/*
----------------------------------  ORIGINAL JQPLOT COLOR LIST
	public $color_list = array(
		'rgb(75, 178, 197)',
		'rgb(234, 162, 40)',
		'rgb(197, 180, 127)',
		'rgb(87, 149, 117)',
		'rgb(131, 149, 87)',
		'rgb(149, 140, 18)',
		'rgb(149, 53, 121)',
		'rgb(75, 93, 228)',
		'rgb(216, 184, 63)',
		'rgb(255, 88, 0)',
		'rgb(0, 133, 204)',
		'rgb(199, 71, 163)',
		'rgb(205, 223, 84)',
		'rgb(251, 209, 120)',
		'rgb(38, 180, 227)',
		'rgb(189, 112, 199)'
	);
*/
//----------------------------------  GOOGLE CHART COLOR LIST
	public $color_list = array(
		"#3366cc",
		"#dc3912",
		"#ff9900",
		"#109618",
		"#990099",
		"#cccccc",
		"#16d620",
		"#b77322",
		"#3b3eac",
		"#5574a6",
		"#329262",
		"#8b0707",
		"#e67300",
		"#6633cc",
		"#aaaa11",
		"#22aa99",
		"#994499",
		"#316395",
		"#b82e2e",
		"#66aa00",
		"#dd4477",
		"#0099c6"
	);

	public function __construct($structure, $parent = NULL, $view = NULL)
	{
		if(!isset(self::$jqPlotActivated['main']))
		{
			self::$jqPlotActivated['main'] = FALSE;
			foreach(self::$jqPlotPlugins AS $plugin_name => $plugin)
			{
				self::$jqPlotActivated[$plugin['file']] = FALSE;
			}
		}
		parent::__construct($structure, $parent, $view);
	}

	public function close()
	{
		// --------------------------------
		// -		  PARAMETERS		  -
		// --------------------------------

		// global
		$title = '';
		$this->_getStringFromAttributes($title);
		$axis2			= $this->getNodeAttribute('axis2', array());

		// boolean
		$legend			= $this->getNodeAttribute('legend', '') == '1';
		$trend			= $this->getNodeAttribute('trend', '') == '1';
		$stack			= $this->getNodeAttribute('stack', '') == '1';
		$zoom			= $this->getNodeAttribute('zoom', '') == '1';

		// numeric
		$width			= $this->getNodeAttribute('width', '100%');
		$height			= $this->getNodeAttribute('height', '400');
		$legend_width	= $this->getNodeAttribute('legend_width', '120');

		$ticks			= $this->getNodeAttribute('ticks', '');
		$labelangle		= $this->getNodeAttribute('labelangle', '');
		$pad			= $this->getNodeAttribute('pad', '');
		$direction		= $this->getNodeAttribute('direction', '');
		$min 			= $this->getNodeAttribute('min', '');
		$max 			= $this->getNodeAttribute('max', '');

		// labels
		$xlabel 		= $this->getNodeAttribute('xlabel', '');
		$ylabel 		= $this->getNodeAttribute('ylabel', '');
		$x2label 		= $this->getNodeAttribute('x2label', '');
		$y2label 		= $this->getNodeAttribute('y2label', '');

		// labels
		$interval		= $this->getNodeAttribute('interval', '');
		$interval2		= $this->getNodeAttribute('interval2', '');

		$text_ok = TRUE;

		// Si la largeur n'est pas définie en pourcentage, pixels
		if (substr($width, -1) != '%')
		{
			$width .= 'px';
		}
		if (substr($height, -1) != '%')
		{
			$height .= 'px';
		}

		// test if data exists
		if (empty($this->lines))
		{
			?>
			<div class="jqplot-empty" style></div>
				<div class="jqplot-title" style="text-align:center"><?php echo $title; ?></div>
				<div class="jqplot-empty-container">
					Pas de données à afficher.
				</div>
			<?php
			return TRUE;
		}

		// ------------------------------------
		// -		  CONFIGURATION		   -
		// ------------------------------------

		// Global configuration
		if (!empty($title))
		{
			$this->params['title'] = $title;
		}
		if (!empty($legend))
		{
			$this->is_legend = TRUE;
		}

		// labels
		if ($xlabel != '')
		{
			JQPlotXMLNode::$jqPlotPlugins['canvasAxisLabel']['import'] = TRUE;
			$this->params['axesDefaults']['labelRenderer'] = '$.jqplot.CanvasAxisLabelRenderer';
			$this->params['axes']['xaxis']['label'] = $xlabel;
		}
		if ($ylabel != '')
		{
			JQPlotXMLNode::$jqPlotPlugins['canvasAxisLabel']['import'] = TRUE;
			$this->params['axesDefaults']['labelRenderer'] = '$.jqplot.CanvasAxisLabelRenderer';
			$this->params['axes']['yaxis']['label'] = $ylabel;
		}
		if ($x2label != '')
		{
			JQPlotXMLNode::$jqPlotPlugins['canvasAxisLabel']['import'] = TRUE;
			$this->params['axesDefaults']['labelRenderer'] = '$.jqplot.CanvasAxisLabelRenderer';
			$this->params['axes']['x2axis']['label'] = $x2label;
		}
		if ($y2label != '')
		{
			JQPlotXMLNode::$jqPlotPlugins['canvasAxisLabel']['import'] = TRUE;
			$this->params['axesDefaults']['labelRenderer'] = '$.jqplot.CanvasAxisLabelRenderer';
			$this->params['axes']['y2axis']['label'] = $y2label;
		}

		if ($interval != '')
		{
			$this->params['axes']['yaxis']['tickInterval'] = $interval;
		}
		if ($interval2 != '')
		{
			$this->params['axes']['y2axis']['tickInterval'] = $interval2;
		}

		if (!empty($ticks))
		{
			JQPlotXMLNode::$jqPlotPlugins['categoryAxis']['import'] = TRUE;
			$this->params['axes']['xaxis']['ticks'] = explode(',', $ticks);
			$this->params['axes']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
		}
		if (!empty($labelangle))
		{
			JQPlotXMLNode::$jqPlotPlugins['canvasAxisTick']['import'] = TRUE;
			$this->params['axesDefaults']['tickRenderer'] = '$.jqplot.CanvasAxisTickRenderer';
			$labelangle = explode(',', $labelangle);
			if (isset($labelangle[0]))
			{
				$this->params['axes']['xaxis']['tickOptions']['angle'] = (int)$labelangle[0];
			}
			if (isset($labelangle[1]))
			{
				$this->params['axes']['yaxis']['tickOptions']['angle'] = (int)$labelangle[1];
			}
			if (isset($labelangle[2]))
			{
				$this->params['axes']['x2axis']['tickOptions']['angle'] = (int)$labelangle[2];
			}
			if (isset($labelangle[3]))
			{
				$this->params['axes']['y2axis']['tickOptions']['angle'] = (int)$labelangle[3];
			}
		}
		if ($pad != '')
		{
			if (!preg_match('/:/', $pad))
			{
				$this->params['axesDefaults']['pad'] = $pad;
			}
			else
			{
				$pad = explode(":", $pad);
				$this->params['axes']['xaxis']['pad'] = $pad[0];
				$this->params['axes']['yaxis']['pad'] = $pad[1];
			}
		}
		if (!empty($direction))
		{
			$this->params['seriesDefaults']['rendererOptions']['barDirection'] = $direction;
			switch ($direction) {
				case 'horizontal':
					# code...
					break;

				default:
					# code...
					break;
			}
		}
		if ($min != '')
		{
			$min = explode(':', $min);
			if ($min[0] != '')
			{
				$this->params['axes']['xaxis']['min'] = $min[0];
			}
			if ($min[1] != '')
			{
				$this->params['axes']['yaxis']['min'] = $min[1];
			}
		}
		if ($max != '')
		{
			$max = explode(':', $max);
			if ($max[0] != '')
			{
				$this->params['axes']['xaxis']['max'] = $max[0];

}			if ($max[1] != '')
			{
				$this->params['axes']['yaxis']['max'] = $max[1];
			}
		}

		// boolean
		if ($trend)
		{
			JQPlotXMLNode::$jqPlotPlugins['trendline']['import'] = TRUE;
			$this->params['axesDefaults']['renderer'] = '$.jqplot.LineRenderer()';
		}
		if ($stack)
		{
			$this->params['stackSeries'] = TRUE;
			$this->params['seriesDefaults']['rendererOptions']['barMargin'] = 30;
			$this->params['axes']['yaxis']['padMin'] = 0;
		}
		if ($zoom)
		{
			JQPlotXMLNode::$jqPlotPlugins['cursor']['import'] = TRUE;
			$this->params['cursor']['show'] = TRUE;
			$this->params['cursor']['zoom'] = TRUE;
		}
		//JQPlotXMLNode::$jqPlotPlugins['logAxis']['import'] = TRUE;
		//$this->params['axes']['yaxis']['renderer'] = '$.jqplot.LogAxisRenderer';
		// ---------------------------------------
		// -		 IMPORT SCRIPTS / CSS		-
		// ---------------------------------------

		if (self::$is_first_plot)
		{
			?><style>

				/* global */
				.jqplot-clearfix { clear:both; }

				/* container */
				.jqplot-container { position: relative; -webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }

				/* chart */
				.jqplot-chart { float: left; }
				.jqplot-axis { font-size: 1em !important; }
				.jqplot-highlighter-tooltip, .jqplot-canvasOverlay-tooltip { font-size: 1em !important; }
				.jqplot-title { font-size: 1.5em !important; }

				.jqplot-empty-container { background-color:#fffdf6; border: 2px solid gray; padding: 15px; margin-bottom:20px; font-size: 14px; }

				/* piechart */
				.jqplot-data-label, .jqplot-point-label { background-color: #FFF; padding: 2px 4px; border-radius: 3px; margin: -2px -4px; box-shadow: 0 1px 2px rgba(0,0,0,0.3); }

				/* legend */
				.jqplot-legend { float: left; border:1px solid #DDD; box-shadow: 2px 2px 6px rgba(0,0,0,0.2); moz-box-shadow: 2px 2px 6px rgba(0,0,0,0.2); }
				.jqplot-legend-wrapper { padding: 10px; margin: auto; background-color: #FFF; }
				.jqplot-legend-line { padding: 2px 0; }
				.jqplot-legend-text { text-align:right; color: #666; padding: 3px 5px; }
				.jqplot-legend-text-link:visited { color: #222; }
				.jqplot-legend-color-outline { width: 16px; text-align: right; padding: 2px; border: 1px solid #CCC; }
				.jqplot-legend-color { width: 16px; height: 13px; cursor: pointer; }
				.jqplot-legend-infos { color: #666; padding: 3px 5px; }
				.jqplot-legend-hidden .jqplot-legend-color { margin-left: 1px; border-radius: 2px; width: 20px; height: 16px; background: url('./images/jqplot/legend-hide.png') no-repeat center center #333 !important; }
				.jqplot-legend-hidden .jqplot-legend-color-outline { border: none; padding: 0; }
				.jqplot-legend-actions td { padding-bottom: 10px; }

				/* tooltip */
				.jqplot-tooltip { padding: 4px 8px !important; box-shadow: 2px 2px 6px rgba(0,0,0,0.2); }
				.jqplot-tooltip-list { list-style: none; margin: 0; }
				.jqplot-tooltip-list li { padding-bottom: 4px; margin-bottom: 4px; border-bottom: 1px solid #DDD; }
				.jqplot-tooltip-list li:last-child { padding-bottom: 0; margin-bottom: 0; border-bottom: none; }
				.jqplot-tooltip-list span { font-weight: bold; }

				/* zoom */
				.jqplot-zoom-reset { width: 38px; position: absolute; top: 0; left: 0; z-index: 999; }
			</style><?php
		}

		if (!self::$jqPlotActivated['main'])
		{
			self::$jqPlotActivated['main'] = TRUE;
			?><link rel='stylesheet' type='text/css' href='./library/killi/js/jqplot/jquery.jqplot.css' /><?php
			?><script language='javascript' type='text/javascript' src='./library/killi/js/jqplot/jquery.jqplot.min.js'></script><?php
			?><script language='javascript' type='text/javascript' src='./library/killi/js/jqplot.js'></script><?php
		}

		foreach (JQPlotXMLNode::$jqPlotPlugins as $plugin)
		{
			if($plugin['import'] && !self::$jqPlotActivated[$plugin['file']])
			{
				self::$jqPlotActivated[$plugin['file']] = TRUE;
				?><script language='javascript' type='text/javascript' src='./library/killi/js/jqplot/plugins/<?php echo $plugin['file']; ?>'></script><?php
			}
		}

		// ------------------------------
		// -			DEBUG		   -
		// ------------------------------

		if ($this->debug['lines'])
		{
			echo '<div class="title">Debug : Lignes de données</div>';
			echo '<div class="json">' . json_encode($this->lines) . '</div>';
			echo '<div class="array">' . display_array($this->lines) . '</div>';
		}
		if ($this->debug['params'])
		{
			echo '<div class="title">Debug : Paramètres</div>';
			echo '<div class="json">' . json_encode($this->params) . '</div>';
			echo '<div class="array">' . display_array($this->params) . '</div>';
		}
		if ($this->debug['legend'])
		{
			echo '<div class="title">Debug : Légende</div>';
			echo display_array($this->legend);
		}
		if ($this->debug['plugins'])
		{
			echo '<div class="title">Debug : Plugins chargés</div>';
			echo '<pre style="border: solid 2px #000066; padding:10px; font-size: 0.7em; display:inline-block;">';
			foreach (JQPlotXMLNode::$jqPlotPlugins as $plugin)
			{
				if($plugin['import'])
				{
					echo $plugin['file'] . '<br>';
				}
			}
			echo '</pre>';
		}

		// ---------------------------------------
		// -	 PREPARE DATAS FOR JQPLOT.JS	 -
		// ---------------------------------------

		// prepare datas to be sent to jqplot.js
		$datas['lines']			= $this->lines;
		$datas['params']		= $this->params;
		$datas['lines_series']	= $this->line_series;
		$datas['lines_visible']	= $this->line_visible;

		if ($trend)
		{
			$datas['trend'] = 1;
		}

		if ($zoom)
		{
			$datas['zoom'] = 1;
		}

		if ($this->is_legend)
		{
			$datas['legend'] = 1;
		}

		$datas = preg_replace('/:"(\$\..*?)"/', ':$1', json_encode($datas));

		// ---------------------------------------
		// -		 CREATE THE PLOTCHART		-
		// ---------------------------------------

		?><div id="<?php echo $this->id; ?>_container" class="jqplot-container" style="width:<?php echo $width; ?>; height:<?php echo $height; ?>">
			<?php
			if ($zoom)
			{
				?><button class="jqplot-zoom-reset">1:1</button><?php
			}
			?><script>
				var datas_<?php echo $this->id; ?> = <?php echo $datas; ?>;
			</script><div id="<?php echo $this->id; ?>_plot" class="jqplot-chart" data-jqplot="datas_<?php echo $this->id; ?>"></div><?php

		/* LEGEND  */
		if ($this->is_legend)
		{
			?><div id="<?php echo $this->id; ?>_legend"  class="jqplot-legend" style="width:<?php echo $legend_width; ?>">
				<table class="jqplot-legend-wrapper">
					<tr class="jqplot-legend-actions">
						<td colspan="3">
							<button class="jqplot-legend-hide-all">Masquer tout</button>
						</td>
					</tr><?php

			$count = 0;
			$color_count = 0;
			foreach ($this->legend['series'] as $data)
			{
				?><tr class="jqplot-legend-line" id="jqplot-legend-line-<?php echo $count; ?>"><?php

					// set text
					?><td class="jqplot-legend-text"><?php
					if (isset($data['action']))
					{
						?><a class="jqplot-legend-text-link" href="index.php?action=<?php echo $data['action'] . '&token=' . $_SESSION['_TOKEN']; ?>"><?php
					}

					if (is_integer($data['value']))
					{
						echo 'Serie ' . $data['value'];
					}
					else
					{
						echo $data['value'];
					}

					if (isset($data['action']))
					{
						?></a><?php
					}
					?></td><?php

					// set color
					if (isset($data['color']))
					{
						?><td>
						<div class="jqplot-legend-color-outline">
							<div class="jqplot-legend-color" style="background-color:<?php echo $data['color']; ?>"></div>
						</div>
						</td><?php
					}
					else
					{
						?><td>
						<div class="jqplot-legend-color-outline">
							<div class="jqplot-legend-color" style="background-color:<?php echo $this->color_list[$color_count % count($this->color_list)]; ?>"></div>
						</div>
						</td><?php
						$color_count++;
					}
					// set infos
					if (isset($data['infos']))
					{
						?><td class="jqplot-legend-infos"><?php
							echo $data['infos'];
						?></td><?php
					}
					?>

				</tr><?php
				$count++;
			}
			?>	</table>
			</div>
			<div class="jqplot-clearfix"></div>
			<?php

		}
		?></div><?php

		self::$is_first_plot = FALSE;

		return TRUE;
	}

}
