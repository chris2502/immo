<?php

/**
 *  @class ImgXMLNode
 *  @Revision $Revision: 4569 $
 *
 */

class ImgXMLNode extends XMLNode
{
	public function open()
	{
		$id	 = $this->id;
		$type   = $this->getNodeAttribute('type', 'digraph');
		$is_url = ($this->getNodeAttribute('url', '0') == '1');
		$src	= $this->getNodeAttribute('src');
		$center = ($this->getNodeAttribute('center', '1') == '1');
		$url	= ($is_url) // si "is_url"...
				? $src      // $url = $src
				: (         // sinon...
					array_key_exists($src, $this->_data_list) // $src existe dans data_list ?
					? $this->_data_list[$src]                 // Oui : $url = $this->_data_list[$src]
					: (                                       // Non : $src est un champ Killi de l'objet (attribut avec une clé "value") ?
						is_array($this->_current_data[$src]) && array_key_exists('value', $this->_current_data[$src])
						? $this->_current_data[$src]['value']         // Oui : $url = $this->_current_data[$src]['value']
						: $this->_current_data[$src]                  // Non : $url = $this->_current_data[$src]
						)
					);
		
		$width = $this->getNodeAttribute('width', '');
		$height = $this->getNodeAttribute('height', '');
		$border = $this->getNodeAttribute('border', '0');

		$notfound = $this->getNodeAttribute('notfound', '');
		$notfound_message = $this->getNodeAttribute('notfound_message', null);
		
		if (! file_exists($url) && $notfound=='gray')
		{
			?>
			<div <?php if ($center): ?>style='text-align:center'<?php endif; ?> class="empty_gray_image">
				<div style="background:#BBBBBB;<?= (!empty($width)) ? 'width:' . $width . ';height:' . $height : 'width:150px;height:150px;' ?><?php echo ($center)?'margin-left:auto;margin-right:auto;':'' ?>margin-top:0.5em;" >
					<strong style="color:#DD0000;margin-top:45%;display:inline-block;"><?=$notfound_message===null?'Image introuvable':$notfound_message?></strong>
				</div>
			</div>
			<?php	
		}
		elseif (! file_exists($url) && $notfound=='empty')
		{
			?>
			<div <?php if ($center): ?>style='text-align:center'<?php endif; ?> class="empty_image">
			<?=$notfound_message?>
			</div>
			<?php	
		}
		elseif ($type == 'digraph')
		{
			$height = "100%";
			if (isset($attributes['height']))
			{
				$height = $attributes['height'];
			}

			?>
				<div id='loader' style='text-align:center;width:100%;margin-top:155px'><img src='<?= KILLI_DIR ?>/images/loader.gif'/><br/><br/>Chargement en cours...</div>

				<div <?php if ($center): ?>style='text-align:center'<?php endif; ?>>
					<object height='0' data="<?= $url ?>" type="image/svg+xml" id='mysvg'></object>
				</div>

				<script>
				var svg = document.getElementById("mysvg");

				svg.addEventListener("load",function(){

					if(svg.contentDocument.documentElement.getAttribute('width')==null)
					{
						$('#loader').remove();

						svg.parentNode.style.paddingTop='10px';
						svg.parentNode.innerHTML=svg.contentDocument.documentElement.innerHTML;

						svg.remove();

						return;
					}
					var svgPtWidth = svg.contentDocument.documentElement.getAttribute('width');
					var svgPxWidth = svgPtWidth.replace(/([0-9]+)pt/g, function(match, group0)
					{
						return Math.round(parseInt(group0, 10) * 96 / 72);
					});

					var windowWidth=parseInt(($(window).width().toString()).replace(/px/g,''),10);

					if(svgPxWidth>windowWidth)
					{
						svg.contentDocument.documentElement.setAttribute('height','100%');
						svg.contentDocument.documentElement.setAttribute('width','100%');
					}

					svg.setAttribute('height','');

					$('#loader').remove();
				},false);

				</script>
			<?php
		}
		elseif ($type == 'image')
		{
			if (file_exists($url))
			{
				$url = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($url));
			}
			?>
				<div <?php if ($center): ?>style='text-align:center'<?php endif; ?>>
					<img src="<?php echo $url; ?>" border="<?= $border ?>" <?= (!empty($width)) ? 'style="width: ' . $width . ';height: ' . $height . '"' : '' ?>/>
				</div>
			<?php
		}
		elseif ($type == 'areamap')
		{
			if (file_exists($url))
			{
				$url = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($url));
			}
			?>
				<div <?php if ($center): ?>style='text-align:center'<?php endif; ?>>
					<img usemap="#imgmap_<?php echo $this->id; ?>" src="<?php echo $url; ?>" border="0" />
					<map name="imgmap_<?php echo $this->id; ?>">
			<?php

		}
	}

	public function close()
	{
		$type	= $this->getNodeAttribute('type', 'digraph');
		if ($type == 'areamap')
		{
?>
					</map>
				</div>
<?php
		}
	}
}