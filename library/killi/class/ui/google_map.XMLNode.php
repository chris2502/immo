<?php

/**
 *  @class Google_mapXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class Google_mapXMLNode extends XMLNode
{
	public function open()
	{
		$this->_current_element_id++;
		$container_id = $this->_current_element_id;
		$zoom_level = 12;
		$center['lat'] =  48.881986;
		$center['lng'] =  2.420829;
		$type = 'G_NORMAL_MAP';
		$height = 600;


		$height		= $this->getNodeAttribute('height');
		$zoom_level	= $this->getNodeAttribute('zoom');
		$center 	= $this->getNodeAttribute('center');
		$type 		= $this->getNodeAttribute('type');

		if (isset($center) )
		{
			$center['lat'] = $this->_current_data[$this->attributes['center']]['lat'];
			$center['lng'] = $this->_current_data[$this->attributes['center']]['lng'];
		}

		?>
			<center>
				<div style="width: 98%; border: solid 1px #DDDDDD;">
					<br />
						<div id="map_canvas_<?= $container_id ?>" style="border: solid 2px #AAAAAA; width: 98%; height: <?= $height ?>px"></div>
							<table style="width: 98%;">
								<tr>
									<td>Latitude : <input style="width: 100px;" type="text" name="latitude_min" value=""> / <input style="width: 100px;" type="text" name="latitude_max" value=""></td>
									<td>Longitude :<input style="width: 100px;" type="text" name="longitude_min" value=""> / <input style="width: 100px;" type="text" name="longitude_max" value=""></td>
								</tr>
							</table>
						</div>
			</center>

			<script>
				map = new GMap2(document.getElementById("map_canvas_<?= $container_id ?>"));
				center = new GLatLng(<?= $center['lat'] ?>, <?= $center['lng'] ?>);
				map.setCenter(center, <?= $zoom_level ?>);
				map.addControl(new GSmallMapControl());
				map.addControl(new GMapTypeControl());
				map.setMapType(<?= $type ?>);

				//---Set coord
				GEvent.addListener(map, "move", function() {
				var bounds = map.getBounds();

				document.main_form.latitude_min.value 	= Math.round(bounds.getSouthWest().lat()*1000000)/1000000;
				document.main_form.latitude_max.value 	= Math.round(bounds.getNorthEast().lat()*1000000)/1000000;
				document.main_form.longitude_min.value 	= Math.round(bounds.getSouthWest().lng()*1000000)/1000000;
				document.main_form.longitude_max.value 	= Math.round(bounds.getNorthEast().lng()*1000000)/1000000;
				});

				GEvent.addListener(map, "zoomend", function() {
				<?php
				if (isset( $this->attributes['zoomend'] ))
				{
					echo $this->attributes['zoomend'].'()';
				}
				?>
				});

					GEvent.addListener(map, "moveend", function() {
						<?php
							if (isset( $this->attributes['moveend'] ))
							{
								echo $this->attributes['moveend'].'()';
							}
						?>
				});

				<?php
				if (isset( $this->attributes['marker_src'] ))
				{
					foreach( $this->_data_list[$this->attributes['marker_src']] as $marker )
						{
						?>
							var point = new GLatLng(<?= $marker['lat'] ?>,<?= $marker['lng'] ?>);
							var marker = new GMarker(point);
							centerMarker = marker;
							GEvent.addListener(marker, "click", function() {
								marker.openInfoWindowHtml("<?= $marker['label'] ?>");
							});

							map.addOverlay(marker);
						<?php
						}
				}
				?>

				</script>

				<?php

				return TRUE;
	}
}
