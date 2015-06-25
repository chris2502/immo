<?php

/**
 *  @class AreaXMLNode
 *  @Revision $Revision: 2316 $
 *
 */

class AreaXMLNode extends XMLNode
{
	public function open()
	{
		$shape  = $this->getNodeAttribute('shape', 'rect');
		$target = $this->getNodeAttribute('target', '_self');
		$data   = $this->_data_list[$this->getNodeAttribute('data_src')];
		foreach ($data as $area)
		{
			$alt = isset($area['alt'])? $area['alt'] : '';
			?><area shape="<?php echo $shape; ?>" title="<?php echo $alt; ?>" target="<?php echo $target; ?>" coords="<?php echo $area['coords']['x1'].','.$area['coords']['y1'].','.$area['coords']['x2'].','.$area['coords']['y2']; ?>" href="<?php echo $area['href']; ?>" /><?php
		}

	}
}
