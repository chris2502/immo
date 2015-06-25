<?php

/**
 *  @class Json_plotchartXMLNode
 *  @Revision $Revision: 4198 $
 *
 */

class Json_plotchartXMLNode extends XMLNode
{
	public function open()
	{
		$chart_id  = $this->getNodeAttribute('id', $this->id);
		$object	= $this->getNodeAttribute('object');
		$method	= $this->getNodeAttribute('method');
		$title	 = $this->getNodeAttribute('string', '');
		$refresh   = $this->getNodeAttribute('refresh', false);
		$postdata  = $this->_data_list[$this->getNodeAttribute('post_data')];
		$resizable = ($this->getNodeAttribute('resizable', '0') == '1');
		$height	= $this->getNodeAttribute('height', '600');
		$width	 = $this->getNodeAttribute('width', '100%');

		// Si la largeur n'est pas définie en pourcentage, pixels
		if (substr($width, -1) != '%')
		{
			$width .= 'px';
		}

		$hParentNode = NULL;

		$hParentNode = $this->getParent('page');

		$tab_id = NULL;
		if (!is_null($hParentNode))
		{
			$tab_id = $hParentNode->id;
		}

		if (!defined('JQPLOT_INCLUDED')):
			define('JQPLOT_INCLUDED', TRUE);
		?>
		<script language='javascript' type='text/javascript' src='<?= KILLI_DIR ?>/js/jqplot/jquery.jqplot.min.js'></script>
		<link rel='stylesheet' type='text/css' href='<?= KILLI_DIR ?>/js/jqplot/jquery.jqplot.css' />
		<?php
		endif;
		if (!defined('JQPLOT_DATEAXISRENDERER_INCLUDED')):
			define('JQPLOT_DATEAXISRENDERER_INCLUDED', TRUE);
		?>
		<script type='text/javascript' src='<?= KILLI_DIR ?>/js/jqplot/plugins/jqplot.dateAxisRenderer.min.js'></script>
		<?php
		endif;
		if (!defined('JQPLOT_POINTLABELS_INCLUDED')):
			define('JQPLOT_POINTLABELS_INCLUDED', TRUE);
		?>
		<script type='text/javascript' src='<?= KILLI_DIR ?>/js/jqplot/plugins/jqplot.pointLabels.min.js'></script>
		<?php
		endif;
		if (!defined('JQPLOT_HIGHLIGHTER_INCLUDED')):
			define('JQPLOT_HIGHLIGHTER_INCLUDED', TRUE);
		?>
		<script type='text/javascript' src='<?= KILLI_DIR ?>/js/jqplot/plugins/jqplot.highlighter.min.js'></script>
		<?php
		endif;
		?>

		<script type="text/javascript">
		var plot<?php echo $chart_id; ?> = null;
		var buildChart<?php echo $chart_id; ?> = function(){
			if (plot<?php echo $chart_id; ?> == null)
			{
				$('#ajaxplot_<?php echo $chart_id; ?>').html('<center><img src="<?= KILLI_DIR ?>/images/loader.gif" border="0"/></center>');
			}
			else
			{
				$('#ajaxplot_<?php echo $chart_id; ?>').append('<div style="float:right;" id="ajaxplot_loader_<?php echo $chart_id; ?>"><img src="<?= KILLI_DIR ?>/images/loading.gif" border="0"/></div>');
			}
			$.ajax({
				async:	true,
				url:	  './index.php?action=<?php echo $object; ?>.<?php echo $method; ?>' + add_token(),
				data:	 <?php echo json_encode($postdata); ?>,
				dataType: 'json',
				success:  function(response) {
					<?php /*console.log('-- RESPONSE --', response);*/ ?>
					if (!(response.data instanceof Array) || typeof(response.data) != 'object' || response.data.length == 0)
					{
						plot<?php echo $chart_id; ?> = null;
						var html_str =	'<center>' +
											'<h3>- PAS DE DONNÉES -</h3>' +
											<?php if ($refresh === false): ?>
											'<br /> ' +
											'<button style="width:100px;" '+
													'type="button" ' +
													'onclick="buildChart<?php echo $chart_id; ?>();">Actualiser</button>' +
											<?php endif; ?>
										'</center>';
						$('#ajaxplot_<?php echo $chart_id; ?>').html(html_str);
						return;
					}

					// Compléments d'options
					response.options.highlighter = {
						show: true,
						sizeAdjust: 7.5
					}
					<?php if (!empty($title)): ?>
					response.options.title = '<?php echo $title ?>';
					<?php endif; ?>
					// Données de tooltip supplémentaires
					if (response.tooltipdata)
					{
						response.options.highlighter.tooltipContentEditor = function (str, seriesIndex, pointIndex, plot){
							return response.tooltipdata[seriesIndex][pointIndex];
						};
					}
					else if (response.tooltipcurve)
					{
						// Données de tooltip auto.
						response.options.highlighter.tooltipContentEditor = function (str, seriesIndex, pointIndex, plot){
							return response.options.series[seriesIndex].label + ': ' + str;
						};
					}
					// Effacement du chart.
					if (plot<?php echo $chart_id; ?> != null)
					{
						$('#ajaxplot_<?php echo $chart_id; ?>').append();
						plot<?php echo $chart_id; ?>.init('ajaxplot_<?php echo $chart_id; ?>', response.data, response.options);
						plot<?php echo $chart_id; ?>.redraw();
					}
					else
					{
						$('#ajaxplot_<?php echo $chart_id; ?>').html('');
						<?php if ($resizable): ?>
						$('#ajaxplot_<?php echo $chart_id; ?>_resizer').resizable({
							handles: 's'
						});
						$('#ajaxplot_<?php echo $chart_id; ?>_resizer').bind('resize', function(event, ui) {
							plot<?php echo $chart_id; ?>.replot({resetAxes: false});
						});
						<?php endif; ?>
						plot<?php echo $chart_id; ?> = jQuery.jqplot('ajaxplot_<?php echo $chart_id; ?>', response.data, response.options);
						$(window).resize(function(){
							plot<?php echo $chart_id; ?>.replot({resetAxes: false});
						});
						<?php if (!is_null($tab_id)): ?>
						$('#<?php echo $tab_id ?>').onShow(function(){
							plot<?php echo $chart_id; ?>.replot({resetAxes: false});
						});
						<?php endif; ?>
					}
					$('#ajaxplot_loader_<?php echo $chart_id; ?>').remove();
				}
			});
		};
		$(document).ready(function(){
			buildChart<?php echo $chart_id; ?>();

			<?php if ($refresh !== false): ?>
			setInterval('buildChart<?php echo $chart_id; ?>()', <?php echo intval($refresh) * 1000; ?>);
			<?php endif; ?>
		});

	</script>
	<div id="ajaxplot_<?php echo $chart_id; ?>_resizer" style="width:<?php echo $width; ?>;height:<?php echo $height; ?>px;<?php if ($resizable): ?>border-bottom: 3px double #ccc;<?php endif; ?>">
		<div id="ajaxplot_<?php echo $chart_id; ?>" style="width:100%;height:100%;font-size:13px;"></div>
<?php
		return TRUE;
	}
	//.....................................................................
	public function close()
	{
?>
	</div>
<?php
		return TRUE;
	}
}
