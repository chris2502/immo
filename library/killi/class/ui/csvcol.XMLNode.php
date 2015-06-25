<?php

/**
 *  @class CsvcolXMLNode
 *  @Revision $Revision: 2756 $
 *
 */

class CsvcolXMLNode extends XMLNode
{
	public function open()
	{
		/* Preparation du menu deroulant */
		for( $i = 0 ; $i < count( $this->_data_list[ 'many2one' ] ) ; $i++ )
		{
			$this->_current_data = array(  $this->_data_list[ 'many2one' ][ $i ][ 'key' ] => array( 'value' => '', 'editable' => true ) ) ;

			$this->getNodeAttribute('object', $this->_data_list[ 'many2one' ][ $i ][ 'obj' ] );

			$this->getNodeAttribute('object', $this->_data_list[ 'many2one' ][ $i ][ 'keyj' ] );

			$this->_render_start_field() ;
		}

		unset( $crypt ) ;
		Security::crypt( '0', $crypt ) ;

		$combo  = '<select name="crypt/attr/%s">' . "\n";
		$combo  .= '<option value="' . $crypt . '">...</option>' . "\n";
		for( $i = 0 ; $i  < count( $this->_data_list[ 'attribut' ] ) ; $i++ )
		{
			$combo .= '<option value="' . $this->_data_list[ 'attribut' ][ $i ][ 'id' ] . '">' . $this->_data_list[ 'attribut' ][ $i ][ 'value' ] . '</option>' . "\n";
		}
		$combo .= '</select>' . "\n" ;

		?>
		<table
		><?php

			for( $i = 0 ; $i < count( $this->_data_list[ 'csvimport' ] ) ; $i++ )
			{
				if( $i == 0 )
				{
					?>
					<tr>
					<?php

					for( $j = 0 ; $j < count( $this->_data_list[ 'csvimport' ][ 0 ] ) ; $j++ )
					{
						$tmp = sprintf( $combo, $j ) ;
						?>
						<td>
						<?= $tmp ?>
						</td>
						<?php
					}
					?>
					</tr>
					<?php
				}

			?>
			<tr>
			<?php

			for( $j = 0 ; $j < count( $this->_data_list[ 'csvimport' ][ $i ] ) ; $j++ )
				{
				?>
				<td>
				<?= $this->_data_list[ 'csvimport' ][ $i ][ $j ] ?>
				</td>
				<?php
				}

				?>
				</tr>
				<?php
				}

				?>
				<tr>
				<?php

				for( $j = 0 ; $j < count( $this->_data_list[ 'csvimport' ][ 0 ] ) ; $j++ )
				{
					?>
					<td>......</td>
					<?php
				}

				?>
				</tr>
				<?php
		?>
		</table
		><?php
	}
}
