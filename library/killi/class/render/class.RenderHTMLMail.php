<?php

class RenderHTMLMail extends RenderHTML
{

	public function __construct()
	{
		$this->setDoctype(FALSE);
	}

	public function renderHeader()
	{
		?><head>
			<title><?php echo $this->_title; ?></title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<meta content="width=device-width">
			<style type="text/css">

			/*
			body.email_body, td { font-family: 'Helvetica Neue', Arial, Helvetica, Geneva, sans-serif; font-size:14px; }
			body.email_body { background-color: #EEE; margin: 0; padding: 0; -webkit-text-size-adjust:none; -ms-text-size-adjust:none; }
			h2.email_h2 { padding-top:12px; color:#0E7693; font-size:22px; }
			*/

			@media only screen and (max-width: 480px) {

			   table[class=w275], td[class=w275], img[class=w275] { width:135px !important; }
							  table[class=w30], td[class=w30], img[class=w30] { width:10px !important; }
							  table[class=w580], td[class=w580], img[class=w580] { width:280px !important; }
							  table[class=w640], td[class=w640], img[class=w640] { width:300px !important; }
							  img{ height:auto;}
							  table[class=w180], td[class=w180], img[class=w180] { width:280px !important; display:block; }
							  td[class=w20]{ display:none; }
		   }
					  </style>
		</head><?php
	}

	protected function _renderContent($content_array)
	{
		foreach ($content_array as $index => $data)
		{
			if (is_string($data))
			{?>
			<table class="w580"  width="580" cellpadding="0" cellspacing="0" border="0">
				<tbody>
					<tr>
						<td class="w580"  width="580">
							<div align="left" class="article-content">
								<p><?php echo $data; ?></p>
							</div>
						</td>
					</tr>
				</tbody>
			</table><?php
			}
			else if (is_array($data) && isset($data['label']) && array_key_exists('text', $data))
			{?>
			<table class="w580"  width="580" cellpadding="0" cellspacing="0" border="0">
				<tbody>
					<tr>
						<td class="w275"  width="150" valign="top">
							<div align="left" class="article-content">
								<p style="color:#666"><?php echo $data['label']; ?></p>
							</div>
						</td>
						<td class="w30"  width="30" class="w30"></td>
						<td class="w275"  width="400" valign="top">
							<div align="left" class="article-content">
								<p><?php echo $data['text']; ?></p>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			}
			else if (is_array($data) && isset($data['button']))
			{
				?><table class="w580" width="580" cellpadding="0" cellspacing="0" border="0">
					<tbody>
						<tr>
							<td class="w580" width="580">
								<div align="left" class="article-content">
									<p></p><p style="text-align:center"><a style="background-color:#C00;color:white;padding:10px;text-decoration:none;border-radius:3px" href="<?= $data['button']['url'] ?>" target="_blank"><?= $data['button']['label'] ?></a></p><p></p>
								</div>
							</td>
						</tr>
					</tbody>
				</table><?php
			}
			else
			{
				throw new Exception('Array format not allowed : ' . print_r($data, TRUE), 1);
			}
			?>
			<table class="w580"  width="580" cellpadding="0" cellspacing="0" border="0">
				<tbody>
					 <tr>
						<td class="w640"  width="640" height="15" bgcolor="#ffffff"></td>
					</tr>
				</tbody>
			</table>
			<?php
		}
	}

	public function renderBody()
	{
		?><body class="email_body" style="margin:0px; padding:0px; -webkit-text-size-adjust:none;">
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:rgb(42, 55, 78)" >
				<tbody>
					<tr>
						<td align="center" bgcolor="#DDD">
							<table  cellpadding="0" cellspacing="0" border="0">
								<tbody>
							<tr>
								<td class="w640" width="640" height="10"></td>
							</tr>

							<tr>
								<td align="center" class="w640"  width="640" height="20"><!-- <a style="color:#ffffff; font-size:12px;" href="#"><span style="color:#ffffff; font-size:12px;">Voir le contenu de ce mail en ligne</span></a> --></td>
							</tr>
							<tr>
								<td class="w640"  width="640" height="10"></td>
							</tr>


							<!-- entete -->
							<tr class="pagetoplogo">
								<td class="w640"  width="640">
									<table  class="w640"  width="640" cellpadding="0" cellspacing="0" border="0" bgcolor="white">
										<tbody>
											<tr>
												<td class="w30"  width="30"></td>
												<td  class="w580"  width="580" valign="middle" align="left">
													<div class="pagetoplogo-content">
														<img class="w580" style="text-decoration: none; display: block; color:#476688; font-size:30px;" src="<?php echo $this->_logo; ?>" alt="Free" />
													</div>
												</td>
												<td class="w30"  width="30"></td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>

							<!-- separateur horizontal -->
							<tr>
								<td  class="w640"  width="640" height="1" bgcolor="#d7d6d6"></td>
							</tr>

							 <!-- contenu -->
							<tr class="content">
								<td class="w640" class="w640"  width="640" bgcolor="#ffffff">
									<table class="w640"  width="640" cellpadding="0" cellspacing="0" border="0">
										<tbody>
											<tr>
												<td  class="w30"  width="30"></td>
												<td  class="w580"  width="580">

												<table class="w580"  width="580" cellpadding="0" cellspacing="0" border="0">
													<tbody>
														<tr>
															<td class="w580"  width="580">
																<h2 class="email_h2" style="color:#C00; font-weight:normal; font-size:22px; padding-top:12px;"><?php echo $this->_title; ?></h2>
																<?php
																$this->_renderContent($this->_content);
																?>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
												<td class="w30" class="w30"  width="30"></td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>

							<!--  separateur horizontal de 15px de haut -->
							<tr>
								<td class="w640"  width="640" height="15" bgcolor="#ffffff"></td>
							</tr>

							<!-- pied de page -->
							<tr class="pagebottom">
								<td class="w640"  width="640">
									<table class="w640"  width="640" cellpadding="0" cellspacing="0" border="0" bgcolor="#CCC">
										<tbody>
											<tr>
												<td colspan="5" height="10"></td>
											</tr>
											<tr>
												<td class="w30"  width="30"></td>
												<td class="w580"  width="580" valign="top">
													<p align="right" class="pagebottom-content-left">
													</p>
												</td>

												<td class="w30"  width="30"></td>
											</tr>
											<tr>
												<td colspan="5" height="10"></td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
							<tr>
								<td class="w640"  width="640" height="60"></td>
							</tr>
						</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</body><?php
	}
}
