<?php

/**
 *  @class CodeGeneratorXMLNode
 *  @Revision $Revision: 372 $
 *
 */

class JetonXMLNode extends XMLNode
{
	public function open()
	{
		$typejeton_list = $this->_data_list['typejeton_list'];
		?>
		<div>
			<table id="SeparatorXMLNode_550c37201946d6_27091865" class="separator">
				<tbody>
					<tr>
						<td>Générer un jeton</td>
					</tr>
				</tbody>
			</table>
			<table class="group">
				<tbody>
					<tr>
						<td style="width: 40% !important;">
							<table class="field" cellspacing="2" cellpadding="1">
								<tbody>
									<tr>
										<td class="field_label">Type de code</td>
										<td>
											<table class="reference_table">
												<tbody>
													<tr>
														<td>
															<select name="crypt/type_jeton_id" style="width:100%">
																<option style="color:#999" value="">Choisir un type de jeton</option>
																<?php
																$html = NULL;
																foreach ($typejeton_list as $ktj => $vtj)
																{
																	$typejeton_id = NULL;
																	Security::crypt($ktj, $typejeton_id);
																	$html .= '<option value="'.$typejeton_id.'">'.$vtj['name']['value'].'</option>';
																}
																echo $html;
																?>
															</select>
														</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
						<td style="width: 1% !important;"></td>
						<td style="width: 40% !important;">
							<table class="field" cellspacing="2" cellpadding="1">
								<tbody>
									<tr>
										<td class="field_label">Destinataire</td>
										<td>
											<table class="reference_table">
												<tbody>
													<tr>
														<td>
															<input id="add-destinataire" style="width:100%" type="text" value=""/>
															<input type="hidden" id="destinataire-id" name="crypt/destinataire_id" value=""/>
														</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
						<td style="width: 1% !important;"></td>
						<td class="killi-button-container" style="width: 18% !important;">
							<button type="submit">
								<div>Générer</div>
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<script type="text/javascript">
		$(function(){
			$('#add-destinataire').keyup(function(e)
			{
				if (e.keyCode == 8)
				{
					$('#add-destinataire').val('').text('');
					$('#destinataire-id').val('').text('');
				}

			});

			$('#add-destinataire').autocomplete(
			{
				source: './index.php?action=jeton.ajaxContactSearch'+add_token(),
				minLength: 3,
				select: function( event, ui )
				{
					$('#add-destinataire').val(ui.item.label).text(ui.item.label);
					$('#destinataire-id').val(ui.item.crypted_id).text(ui.item.crypted_id);
				}
			});
		});
		</script>
		<?php
		
		return TRUE;
	}
}