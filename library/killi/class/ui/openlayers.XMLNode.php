<?php

/**
 *  @class OpenLayersXMLNode
 *  @Revision $Revision: 4078 $
 *
 */

class OpenLayersXMLNode extends XMLNode
{
	public static $js_included = false;
	public function open()
	{
		$id = $this->getNodeAttribute('id', 'openlayers_map_' . $this->id);
		$markerMethod = $this->getNodeAttribute('marker', '');
		$vectorMethod = $this->getNodeAttribute('vector', '');
		$width = $this->getNodeAttribute('width', '800px');
		$height = $this->getNodeAttribute('height', '400px');

		$marker = empty($markerMethod) ? false : true;
		$vector = empty($vectorMethod) ? false : true;

		$crypt_pk = isset($_GET['crypt/primary_key']) ? '&crypt/primary_key='.$_GET['crypt/primary_key'] : '';

		$hParentNode = NULL;
		$hParentNode = $this->getParent('notebook');
		$tab_id = NULL;
		if (!is_null($hParentNode))
		{
			$tab_id = $hParentNode->id;
		}

		$hPage = NULL;
		$hPage = $this->getParent('page');
		if(!self::$js_included)
		{
			self::$js_included = true;
			?>
		<script src="./library/killi/bundle/openlayers/OpenLayers.js"></script>
		<script src="./library/killi/bundle/openlayers/OpenLayers.debug.js"></script>
		<script src="./library/killi/bundle/openlayers/B_MarkerGrid.js"></script>
		<script src="./library/killi/bundle/openlayers/C_MarkerTile.js"></script>
		<script src="./library/killi/bundle/openlayers/D_Bounds.js"></script>
		<link type="text/css" rel="stylesheet" href="./library/killi/bundle/openlayers/map.css" />
			
		<script src="https://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
 		<style type="text/css">
		#controlToggle li {
			list-style: none;
		}
		div.olControlMousePosition {
			color: white;
			background-color:black;
		}
		.olLayerGooglePoweredBy {
			visibility:hidden;
		}
		</style>
		<?php } ?>
		<script type='text/javascript'>
			var lon = 2.331848;
			var lat = 48.857826;
			var zoom = 12;
			var features = "2:3;1:1;1:2;2:4;5:17;5:18;3:8;3:9;3:10";

			var PI = 3.14159265358979323846;
			lon_map = lon * 20037508.34 / 180;
			lat_map = Math.log(Math.tan( (90 + lat) * PI / 360)) / (PI / 180);
			lat_map = lat_map * 20037508.34 / 180;
			var <?= $id ?>_var = {
				attributes: {},
				updateAttribute: function(attribute, new_value) {
					this.attributes[attribute] = new_value;
					<?php if($marker) { ?>
						this.POI.Refresh();
					<?php } ?>
					<?php if($vector) { ?>
						this.Vector.Refresh();
					<?php } ?>
				},
				map: null,
				gmap: null,
				POI: null,
				get_osm_url: function (bounds) {
						var res = this.map.getResolution();
						var x = Math.round ((bounds.left - this.maxExtent.left) / (res * this.tileSize.w));
						var y = Math.round ((this.maxExtent.top - bounds.top) / (res * this.tileSize.h));
						var z = this.map.getZoom();
						var path =  z + "/" + x + "/" + y ;
						var url = this.url;
						if (url instanceof Array) {
							url = this.selectUrl(path, url);
						}
						return url + path;
					},
				<?php if($marker) { ?>
					get_poi_url: function (bounds) {
						  var url = "index.php?action=<?= $markerMethod ?>&token=<?= $_SESSION['_TOKEN'] . $crypt_pk ?>";
						  return url;
						},
				<?php } ?>
				<?php if($vector) { ?>
					get_vector_url: function (bounds) {
						  var url = "index.php?action=<?= $vectorMethod ?>&token=<?= $_SESSION['_TOKEN'] . $crypt_pk ?>";
						  return url;
						},
				<?php } ?>
				get_params: function (bounds) {
					if(typeof this.attributes == 'undefined')
					{
						this.attributes = {};
					}
					var res = this.map.getResolution();
					this.attributes['z'] = this.map.getZoom();
					this.attributes['l'] = getLeft(bounds);
					this.attributes['t'] = getTop(bounds);
					this.attributes['r'] = getRight(bounds);
					this.attributes['b'] = getBottom(bounds);
					this.attributes['f'] = features;
					return this.attributes;
				   },
				init: function() {
					OpenLayers.Util.onImageLoadErrorColor= "#E7FFC2";
					OpenLayers.Util.onImageLoadError = function()
													{
														this.style.backgroundColor= "#E7FFC2";
														//this.src = "img/my404t.png";
													}
					this.map = new OpenLayers.Map( "<?= $id ?>", {
														   maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
													   numZoomLevels: 10,
													   maxResolution: 156543,
															   units: 'm',
														  projection: "EPSG:41001"
															  }
					);
					this.gmap = new OpenLayers.Layer.Google("Streets", { numZoomLevels : 25 });

					<?php if($marker) { ?>
						this.POI = new OpenLayers.Layer.MarkerGrid("POI",  { type: 'txt',
																		   getURL: this.get_poi_url,
																		getParams: this.get_params,
																	  attribution: "GoogleMap France",
																		   buffer: 0,
																	   singleTile: true,
																		   }
						);
						this.POI.setIsBaseLayer(false);
						this.POI.setVisibility(true);
					<?php } ?>

					<?php if($vector) { ?>
						this.Vector = new OpenLayers.Layer.Vector("Vector", {});

						var point = new OpenLayers.Geometry.Point(lon_map, lat_map);
						var point2 = new OpenLayers.Geometry.Point(lon_map+200, lat_map+200);

						var line = new OpenLayers.Geometry.LineString([point, point2]);

						var path = new OpenLayers.Feature.Vector(line, {}, {
							'strokeWidth': 5,
							'strokeColor': '#0000FF'
						});

						this.Vector.addFeatures([path]);

						this.Vector.setIsBaseLayer(false);
						this.Vector.setVisibility(true);
					<?php } ?>
					this.map.addLayers([this.gmap <?= ($vector) ? ', this.Vector' : '' ?><?= ($marker) ? ', this.POI' : '' ?>]);
					this.map.setCenter(new OpenLayers.LonLat(lon_map, lat_map), zoom);
				},
				refresh: function()
				{
					$('#<?=$id?>').empty();
					this.init();
				}
			};
		</script>
		<div id="<?= $id ?>" style="border: 1px solid #000; width: <?= $width ?>; height: <?= $height ?>"></div>
 		<script type="text/javascript">
			$("#<?= $id ?>").Updatable({callback: function(obj, attribute, new_value) {
				<?= $id ?>_var.updateAttribute(attribute, new_value);
			}});

			$(document).ready(function(){
				<?= $id ?>_var.init();
			});

			$('#<?php echo $tab_id ?>').bind('tabsselect', function(evt, ui){
				// Reload map if its tab is selected.
				var cur_tab = $(ui.tab).attr('href');
				if (cur_tab == '#<?php echo $hPage->id; ?>') {
					<?= $id ?>_var.refresh();
				}
			});
 		</script>
		<?php

		return TRUE;
	}
}
