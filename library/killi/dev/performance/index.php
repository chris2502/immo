<?php

/**
 *
 * KILLI REQUIRED
 *
 */

@session_start();

define('INDEX', TRUE);
define('KILLI_DIR', './library/killi/');
define('ASSETS', './library/killi/dev/performance');

require_once(KILLI_DIR . './include/include.php');

ExceptionManager::enable();

Cache::get('HallOfShame', $bestQueries);

$scriptName = basename($_SERVER['PHP_SELF']);

if (isset($_GET['action']) && $_GET['action'] == 'flush')
{
	Cache::set('HallOfShame', NULL);
	Cache::set('lastFlushDate', time());
	header('Location: ./'.$scriptName);
	exit(0);
}

?>
<head>
	<title>Hall Of Shame</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width">
	<meta name="viewport" content="initial-scale=1.0">
	<link href="<?php echo ASSETS; ?>/img/favicon.ico" rel="icon" type="image/x-icon" />
	<script src="./library/killi/js/jquery/jquery.js"></script>
	<style type="text/css">
		@font-face { font-family: 'Open Sans'; font-style: normal; font-weight: 400; src: local('Open Sans'), local('OpenSans'), url(<?php echo ASSETS; ?>/fonts/opensans-regular.woff2) format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000; }
		@font-face { font-family: 'Open Sans'; font-style: normal; font-weight: 600; src: local('Open Sans Semibold'), local('OpenSans-Semibold'), url(<?php echo ASSETS; ?>/fonts/opensans-semibold.woff2) format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000; }
		@font-face { font-family: 'Open Sans Condensed'; font-style: normal; font-weight: 300; src: local('Open Sans Cond Light'), local('OpenSans-CondensedLight'), url(<?php echo ASSETS; ?>/fonts/opensans-condensed-light.woff2) format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000; }
		@font-face { font-family: 'Open Sans Condensed'; font-style: normal; font-weight: 700; src: local('Open Sans Condensed Bold'), local('OpenSans-CondensedBold'), url(<?php echo ASSETS; ?>/fonts/opensans-condensed-bold.woff2) format('woff2'); unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000; }

		.l { float: left; }
		.r { float: right; }
		.h { display: none; }
		.cl:after { content: "."; display: block; clear: both; visibility: hidden; line-height: 0; height: 0; }
		html[xmlns] .cl { display: block; }
		* html .cl { height: 1%; }

		body { background-color: #E9E9E9; font-family: 'Open Sans' sans-serif; font-size: 12px; }
		body, ul, li { margin: 0; padding: 0; list-style: none;/*background-image: url(<?php echo ASSETS; ?>/img/bg1.png);*/ }
		header { /*background-image: url(<?php echo ASSETS; ?>/img/red-curtain.jpg);*/ }

		a, .btn { margin-right: 10px; cursor: pointer; display: block; padding: 4px 8px; background: white; border: 1px solid #666; border-radius: 3px; text-decoration: none; color: #666; }
		a:hover, .btn:hover, a.active, .btn.active { background-color: rgb(145, 122, 8); border-color: rgb(94, 75, 0);  color: white; }

		#title { font-family: 'Open Sans Condensed'; text-align: center; /*width: 452px;*/ margin: 0 auto; padding: 30px 0; }
		#title h1 { margin: 0 15px; line-height: 60px; color: rgb(145, 122, 8); font-size: 60px; -webkit-text-stroke: 2px rgb(94, 75, 0); }
		#title img { margin-top: 18px; }

		#query-list { margin: 10px; }
		#query-list .query { margin: 10px; width: 400px; word-wrap: break-word; padding: 20px; border: 1px solid #CCC;border-radius: 6px; background-color: white; }
		#query-list .query h3 { text-align: center; margin-top: 0; text-transform: uppercase; }
		#query-list .query ul { height:600px; overflow-y: auto; }
		#query-list .query li { margin-top: 10px; padding-bottom: 10px; border-bottom: 1px solid #CCC; }
		#query-list .query li:last-child { padding-bottom: 0; border: 0; }
		#query-list .query .referer { color: #999; }

		#toolbar { list-style: none; padding: 0 20px; }
		#last-flush { padding: 4px 8px; }
		#last-flush .txt { color: #999; font-weight: 600; }
	</style>
	<script>
		$(document).ready(function()
		{
			var img_size = 45;
			var title_size = $('#title>h1').outerWidth();
			var width = (img_size*2)+title_size;
			$('#title').width(width);
			console.log('Title width > ('+img_size+'*2)+'+title_size+'='+width);
		});
	</script>
</head>
<body>
	<header>
		<div id="title" class="cl">
			<img class="l" src="<?php echo ASSETS; ?>/img/poop.png" alt="poop">
			<h1 class="l">Hall Of Shame</h1>
			<img class="l" src="<?php echo ASSETS; ?>/img/poop.png" alt="poop">
		</div>
	</header>

<?php

//$bestQueries = array('exec_time' => array(0 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),1 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),2 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),3 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),4 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),5 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),6 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),7 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),8 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs'),9 => array('value' => '3867.8248269558','request' => '/foinfo/?action=json.getOTsActifs')),'mem_usage' => array(0 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),1 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),2 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),3 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),4 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),5 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),6 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),7 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),8 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02'),9 => array('value' => '774.43075561523','request' => '/foinfo/index.php?action=json.read','referer' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02')),'mem_peak' => array(0 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),1 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),2 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),3 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),4 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),5 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),6 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),7 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),8 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F'),9 => array('value' => '987.5828704834','request' => '/gop/index.php?action=ticket.edit&view=create&hexacle=75106226M4&inside_popup=1&token=7a8c71cfe3df46ee51069e2340ec8d02','referer' => 'https://ftth.freebox.fr/cas/index.php?action=user.authentification&view=form&mode=edition&redirect=auth%3Doauth%26response_type%3Dcode%26scope%3D%26state%3DYWN0aW9uPXRpY2tldC5jcmVhdGU%3D%26client_id%3Dc1741dcc29ec623dcf43a4013d0b65bd%26redirect_uri%3Dhttps%3A%2F%2Fftth.freebox.fr%2Fgop%2F')),'sql_count' => array(0 => array('value' => '23812','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),1 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),2 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),3 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),4 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),5 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),6 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),7 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),8 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'),9 => array('value' => '987.5828704834','request' => '/foinfo/index.php?action=adresse.export_csv&token=c70cac41cd6db8d51d176555466afb17&crypt/workflow_node_id=UwRd','referer' => 'https://ftth.freebox.fr/foinfo/index.php?action=adresse.edit&crypt/workflow_node_id=UwRd&token=c70cac41cd6db8d51d176555466afb17'))); $bestQueries = json_encode($bestQueries);

echo '<div id="toolbar" class="cl">';
echo '	<a class="btn btn-flush r" href="./', $scriptName, '?action=flush">Flush</a>';
$lastFlushDate = NULL;
Cache::get('lastFlushDate', $lastFlushDate);

if (!empty($lastFlushDate))
{
	setlocale(LC_TIME, 'fr_FR.UTF8');
	$lastFlushDate = strftime('%A %d %B %H:%M:%S', $lastFlushDate);
	echo '	<div id="last-flush" class="r"><span class="txt">Dernier flush : </span><span class="date">'.$lastFlushDate.'</span></div>';
}
echo '</div>';

if($bestQueries === NULL)
{
	echo '</body></html>';
	exit(0);
}
$bestQueries = (array)json_decode($bestQueries, TRUE);


echo '<div id="query-list" class="cl">';
foreach($bestQueries AS $type => $queries)
{
	echo '<div class="query l">';
	echo '<img class="l" src="' . ASSETS . '/img/favicon.gif"/>';
	echo '<img class="r" src="' . ASSETS . '/img/favicon.gif"/>';
	echo '<h3>', $type, '</h3>';
	echo '<ul>';

	$count = 0;
	if(is_array($queries))
	{
		foreach($queries AS $query)
		{
			echo '<li>';
			echo '<div><strong>', $query['value'], '</strong></div>';
			echo '<div>', $query['request'], '</span></div>';
			if (isset($query['referer']))
			{
				echo '<div class="referer">',$query['referer'],'</div>';
			}
			echo '</li>';
		}
	}
	echo '</ul>';
	echo '</div>';
}

echo '</div>';
?>
</body></html>
