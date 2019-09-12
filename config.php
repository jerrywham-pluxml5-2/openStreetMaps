<?php if(!defined('PLX_ROOT')) exit;
$pluginName = get_class($plxPlugin);
$oldtype = $plxPlugin->getParam('type');

#if previous TYPE MODE is no CP, but tools displayed by change selector & use 1 GET TOOL : save new type param : this use case is Fixed
if($oldtype != 1 AND (isset($_GET['db2csv']) OR isset($_GET['testAdresses']))){
	$plxPlugin->setParam('type', 1, 'numeric');
	$plxPlugin->saveParams();
}
if($plxPlugin->getParam('type')==1){#since v1.2.1 #CP MODE TOOLS
	if(isset($_GET['db2csv'])){#Export 2 CSV
		$table = ($_GET['db2csv']>1?'all_':'').'towns';#'(all_)towns'
		$plxPlugin->db2csv($table);# launch download & EXIT inside funk or false
		$_SESSION['error'] = $plxPlugin->getLang('L_CSV_KO') . ' #db2csv #towns.csv';
		header('Location: parametres_plugin.php?p='.$pluginName);#parametres_plugin.php?p=openStreetMaps
		exit;
	}
	if(isset($_GET['testAdresses'])){#Found unvalid Postal Codes & Towns (local db & Nominatim
		$time = time();
		$_SESSION['info'] = $log = '<br />';#
		$all = 0;
		$tot = array('red'=>0,'orange'=>0,'green'=>0,'blue'=>0);#counters
		$plxPlugin->getAdresses();#load adrss

		$GPS = array();
		unset($plxPlugin->list['valides']);
		foreach ($plxPlugin->list as $ville => $marker) {
			if (!empty($ville)) {
				$all++;
				if (!isset($GPS[$ville])) {
					$GPS[$ville] = $plxPlugin->search(trim(str_replace(array('CEDEX','0','1','2','3','4','5','6','7','8','9'),'',$marker[0]['VILLE'])),$marker[0]['CP']);
					$log .= $GPS[$ville]['log'];
					$tot[ $GPS[$ville]['logtype'] ]++;
				}
				#Fix : Notice: Undefined index lon
				if(!isset($GPS[$ville]['lon'])) {
					$_SESSION['error'] .= $marker[0]['CP'].' '.$plxPlugin->getLang('L_GEO_KO').': '.$ville.' '.@$GPS[$ville]['lat'].','.@$GPS[$ville]['lon'].'<br />';
				}
				#else{
				#	$_SESSION['info'] .= $marker[0]['CP'].' '.$plxPlugin->getLang('L_GEO_OK').': '.$ville.' '.$GPS[$ville]['lat'].','.$GPS[$ville]['lon'].'<br />';
				#}
			}
		}
		$see = '';
		$time = time() - $time;
		$time = ' ('.$time.'s)';
		if($tot['red'] OR $tot['orange'] OR $tot['green'] OR $tot['blue']){#display links 4 show / hide (red, orange, green & blue) lines
			$see = $plxPlugin->getLang('L_VIEW_HIDE').':&nbsp;'.
				(!$tot['red']?'':'<br /><a class="text-red" href="javascript:void(0);" onclick="see(\'text-red\')">'.$tot['red'] . ' ' . $plxPlugin->getLang('L_POG_NO').' '.$plxPlugin->getLang('L_POG_ADDIN_DB') .'</a>').
				(!$tot['orange']?'':'<br /><a class="text-orange" href="javascript:void(0);" onclick="see(\'text-orange\')">'.$tot['orange'] . ' ' . $plxPlugin->getLang('L_POG_BAD').' ' .$plxPlugin->getLang('L_POG_NO').' '.$plxPlugin->getLang('L_POG_ADDIN_DB') .'</a>').
				(!$tot['green']?'':'<br /><a class="text-green" href="javascript:void(0);" onclick="see(\'text-green\')">'.$tot['green'] . ' ' . $plxPlugin->getLang('L_POG_ADDIN_DB') .'</a>').
				(!$tot['blue']?'':'<br /><a class="text-blue" href="javascript:void(0);" onclick="see(\'text-blue\')">'.$tot['blue'] . ' ' . $plxPlugin->getLang('L_POG_IN_DB') .'</a>;').
				'<br />';
		}
		$_SESSION[$pluginName.'_LOG'] = '<br /><b>'.$plxPlugin->getLang('L_LOG').' '.$plxPlugin->getLang('L_TEST_ADRESSES_BTN').' :</b>&nbsp;'.$plxPlugin->getLang('L_POG_TOT').' : '.$all.' '.$plxPlugin->getLang('L_POG_LNS').$time.'<br />'.$see.$log;
		header('Location: parametres_plugin.php?p='.$pluginName.'#panneau_logs');#parametres_plugin.php?p=openStreetMaps
		exit;
	}
}#FI CP MODE

if(!empty($_POST)) {
	plxToken::validateFormToken($_POST);# Controle du token du formulaire : si posté
	if ($_POST['type'] == 1) {#CP MODE
		$plxPlugin->setParam('drawMode', $_POST['drawMode'], 'numeric');#since v1.2.1
		$plxPlugin->setParam('item_principal', $_POST['item_principal'], 'string');
		$plxPlugin->setParam('itemville', $_POST['itemville'], 'string');
		$plxPlugin->setParam('itemcp', $_POST['itemcp'], 'string');
	} else {#COORDS MODE
		$plxPlugin->setParam('item_principal_coord', $_POST['item_principal_coord'], 'string');
		$plxPlugin->setParam('itemlat', $_POST['itemlat'], 'string');
		$plxPlugin->setParam('itemlong', $_POST['itemlong'], 'string');
	}#FI CP OR COORDS MODE
	#CP MODE TOOLS #since v1.3 #before save all param
	if($oldtype == 1 OR $_POST['type'] == 1){#since v1.2.1
		#if previous MODE is no CP, but tools displayed by change selector & use 1 POST TOOL : save new type param : this use case is Fixed
		if($oldtype != 1){
			$plxPlugin->setParam('type', $_POST['type'], 'numeric');
			$plxPlugin->saveParams();
		}
		# purge des listings
		if (isset($_POST['purge'],$_POST['purge_what'])) {#since v1.2.1 #Purge listing folder (osm json) & table of db
			$_SESSION['info'] = '';
			$lng = '_LIST';
			switch ($_POST['purge_what']) {
				case 'listing':
					$file = PLX_PLUGINS.get_class($plxPlugin).'/listing/*.txt';
					$files = glob($file);# get all file names
					$ok = false;
					foreach($files as $file){# iterate files
						if(is_file($file)){
							unlink($file);# delete file
							$file = basename($file);
							$ok .= sprintf($plxPlugin->getLang('L_DELETED'),$file).'<br />';#L_DELETE_SUCCESSFUL
						}
					}

					$_SESSION['info'] .= $plxPlugin->getLang('L_PURGE_BTN').'<br />'.$plxPlugin->getLang('L_PURGE_LIST').' &laquo;listing&raquo;<br />'.$ok;#L_DELETE_SUCCESSFUL
					$_SESSION[$pluginName.'_LOG'] .= ' <i class"text-'.($ok?'green':'red').'"> '.$_SESSION['info'].' '.$plxPlugin->getLang('L_PURGED').'</i><br />'.PHP_EOL;
					break;
				default:#case 'db':
					$lng = '_DB';
					$plxPlugin->csv2db('purge'.$_POST['purge_what']);# clear db
					break;
			}
			$_SESSION['info'] .= $plxPlugin->getLang('L_PURGE_BTN').' '.$plxPlugin->getLang('L_PURGE'.$lng).' &laquo;'.$_POST['purge_what'].'&raquo. '.$plxPlugin->getLang('L_PURGED');
			header('Location: '.$plxAdmin->racine.$plxAdmin->path_url);#parametres_plugin.php?p=openStreetMaps
			exit;
		}

		else if(isset($_POST['csv2db'])){#since v1.2.1 #Import in db from csv file
			if(isset($_POST['csv'])){
				$plxPlugin->setParam('csv', $_POST['csv'], 'string');
				$plxPlugin->saveParams();
				$medias = isset($plxAdmin->aConf['images'])?$plxAdmin->aConf['images']:$plxAdmin->aConf['medias'];#old or new pluxml 5.x
				$csv = str_replace(array(PLX_ROOT.$medias,$medias),array($medias,PLX_ROOT.$medias),$_POST['csv']);#ADD ../../ TO DATA/MEDIAS
				if($plxPlugin->csv2db($csv)){#load csv file in db all_towns
					$_SESSION['info'] = $plxPlugin->getLang('L_CSV_OK') . ' all_towns #csv2db';
				}else{
					$_SESSION['error'] = $plxPlugin->getLang('L_CSV_KO') . ' all_towns #csv2db';
				}
				header('Location: '.$plxAdmin->racine.$plxAdmin->path_url);#parametres_plugin.php?p=openStreetMaps
				exit;
			}
		}
		else if(isset($_POST['coords2db'])){#Import in db from fields
			if(isset($_POST['cp'],$_POST['nom'],$_POST['lat'],$_POST['lon'])){
				$csv = 'coords2db'.$_POST['cp'].';'.$_POST['nom'].';'.$_POST['lat'].';'.$_POST['lon'];
				if($plxPlugin->csv2db($csv)){#load csv file in db all_towns
					$_SESSION['info'] = $plxPlugin->getLang('L_CSV_OK') . ' all_towns #csv2db';
				}else{
					$_SESSION['error'] = $plxPlugin->getLang('L_CSV_KO') . ' all_towns #csv2db';
				}
				header('Location: '.$plxAdmin->racine.$plxAdmin->path_url);#parametres_plugin.php?p=openStreetMaps
				exit;
			}
		}
	}#FI CP MODE TOOLS

	$plxPlugin->setParam('pageName', $_POST['pageName'], 'string');
	$plxPlugin->setParam('mnuDisplay', $_POST['mnuDisplay'], 'numeric');
	$plxPlugin->setParam('mnuName', $_POST['mnuName'], 'string');
	$plxPlugin->setParam('mnuPos', $_POST['mnuPos'], 'numeric');
	$plxPlugin->setParam('template', $_POST['template'], 'string');
	$plxPlugin->setParam('source', $_POST['source'], 'string');
	$plxPlugin->setParam('type', $_POST['type'], 'numeric');

	$plxPlugin->setParam('width', $_POST['width'], 'numeric');
	$plxPlugin->setParam('unit', $_POST['unit'], 'string');
	$plxPlugin->setParam('height', $_POST['height'], 'numeric');
	$plxPlugin->setParam('zindex', $_POST['zindex'], 'numeric');#since v1.2.1

	$plxPlugin->setParam('latitude', $_POST['latitude'], 'string');
	$plxPlugin->setParam('longitude', $_POST['longitude'], 'string');
	$plxPlugin->setParam('zoom', $_POST['zoom'], 'numeric');

	$plxPlugin->setParam('showpopup', $_POST['showpopup'], 'string');
	$plxPlugin->setParam('popupLatitude', $_POST['popupLatitude'], 'string');
	$plxPlugin->setParam('popupLongitude', $_POST['popupLongitude'], 'string');
	$plxPlugin->setParam('popupTexte', strip_tags($_POST['popupTexte'],'<b><a><em><strong><u>'), 'string');

	$plxPlugin->setParam('itemnom', $_POST['itemnom'], 'string');
	$plxPlugin->setParam('itemval', $_POST['itemval'], 'string');
	$plxPlugin->setParam('dataval', $_POST['dataval'], 'string');
	$plxPlugin->setParam('itemcoord', $_POST['itemcoord'], 'string');
	$plxPlugin->setParam('datacoord', $_POST['datacoord'], 'string');
	$plxPlugin->saveParams();
	header('Location: '.$plxAdmin->racine.$plxAdmin->path_url);#parametres_plugin.php?p=openStreetMaps
	exit;
}

#liste les fichiers du dossier csv
$csvDir = PLX_PLUGINS.$pluginName.DIRECTORY_SEPARATOR.'csv'.DIRECTORY_SEPARATOR;# BASE url in bigData() #js
$file = $csvDir.'*.*';#PLX_PLUGINS.get_class($plxPlugin).'/csv/*.csv';
$files = glob($file);# get all file names
$csvFiles = $csvFile = '';
foreach($files as $file){# iterate files
	if(is_file($file)){
		if(empty($csvFile)) $csvFile = $file;
		$fileName = basename($file);
		$csvFiles .= '<br /><a class="osmcsvlnk" title="'.$plxPlugin->getLang('L_CSV_PRE_SEL_PH').'" href="javascript:void(0);" onclick="bigData(this.innerHTML);">'.$fileName.'</a>'.PHP_EOL;
	}
}
if(!empty($csvFiles)) $csvFiles = '<br />'.$plxPlugin->getLang('L_CSV_PRE_SEL').':' . $csvFiles;
# Type de fichier source
$type = $plxPlugin->getParam('type')=='' ? 1 : $plxPlugin->getParam('type');
$show = $plxPlugin->getParam('type')==1 ? 'on' : '';#CP MODE
$logs = (isset($_SESSION[$pluginName.'_LOG']));#CP MODE TOOLS

$pageName = $plxPlugin->getParam('pageName')=='' ? 'Localisation' : $plxPlugin->getParam('pageName');
$mnuDisplay = $plxPlugin->getParam('mnuDisplay')=='' ? 1 : $plxPlugin->getParam('mnuDisplay');
$mnuName = $plxPlugin->getParam('mnuName')=='' ? $plxPlugin->getLang('L_DEFAULT_MENU_NAME') : $plxPlugin->getParam('mnuName');
$mnuPos = $plxPlugin->getParam('mnuPos')=='' ? 2 : $plxPlugin->getParam('mnuPos');
$template = $plxPlugin->getParam('template')=='' ? 'static.php' : $plxPlugin->getParam('template');
$source = $plxPlugin->getParam('source')=='' ? 'plugins/'.$pluginName.'/source.exemple.xml' : $plxPlugin->getParam('source');

$item_principal = $plxPlugin->getParam('item_principal')=='' ? 'adherent' : $plxPlugin->getParam('item_principal');
$itemville = $plxPlugin->getParam('itemville')=='' ? 'ville' : $plxPlugin->getParam('itemville');
$itemcp = $plxPlugin->getParam('itemcp')=='' ? 'cp' : $plxPlugin->getParam('itemcp');
$csv = !empty($csvFile)?$csvFile:'';#PLX_PLUGINS.$pluginName.DIRECTORY_SEPARATOR.'csv'.DIRECTORY_SEPARATOR.'villes_FR.csv';#laposte_hexasmal.csv ...
$csv = $plxPlugin->getParam('csv')=='' ? $csv : $plxPlugin->getParam('csv');
$drawMode = $plxPlugin->getParam('drawMode')=='' ? 1 : $plxPlugin->getParam('drawMode');#since v1.2.1

$item_principal_coord = $plxPlugin->getParam('item_principal_coord')=='' ? 'coordonnees' : $plxPlugin->getParam('item_principal_coord');
$itemlat = $plxPlugin->getParam('itemlat')=='' ? 'latitude' : $plxPlugin->getParam('itemlat');
$itemlong = $plxPlugin->getParam('itemlong')=='' ? 'longitude' : $plxPlugin->getParam('itemlong');
# Taille
$width = $plxPlugin->getParam('width')=='' ? 90 : $plxPlugin->getParam('width');
$unit = $plxPlugin->getParam('unit')=='' ? '%' : $plxPlugin->getParam('unit');
$height = $plxPlugin->getParam('height')=='' ? 840 : $plxPlugin->getParam('height');
$zindex = $plxPlugin->getParam('zindex')=='' ? 0 : $plxPlugin->getParam('zindex');#since v1.2.1
# Localisation de la carte
$latitude = $plxPlugin->getParam('latitude')=='' ? 45.8 : $plxPlugin->getParam('latitude');
$longitude = $plxPlugin->getParam('longitude')=='' ? 2 : $plxPlugin->getParam('longitude');
$zoom = $plxPlugin->getParam('zoom')=='' ? 3 : $plxPlugin->getParam('zoom');
# Pop-up d'accueil
$showpopup = $plxPlugin->getParam('showpopup')=='' ? '' : $plxPlugin->getParam('showpopup');
$popupLatitude = $plxPlugin->getParam('popupLatitude')=='' ? 48.8566 : $plxPlugin->getParam('popupLatitude');
$popupLongitude = $plxPlugin->getParam('popupLongitude')=='' ? 2.3538 : $plxPlugin->getParam('popupLongitude');
$popupTexte = $plxPlugin->getParam('popupTexte')=='' ? '<b>Paris</b>' : $plxPlugin->getParam('popupTexte');
# Items facultatifs
$itemnom = $plxPlugin->getParam('itemnom')=='' ? '' : $plxPlugin->getParam('itemnom');
$itemval = $plxPlugin->getParam('itemval')=='' ? '' : $plxPlugin->getParam('itemval');
$dataval = $plxPlugin->getParam('dataval')=='' ? '' : $plxPlugin->getParam('dataval');
$itemcoord = $plxPlugin->getParam('itemcoord')=='' ? '' : $plxPlugin->getParam('itemcoord');
$datacoord = $plxPlugin->getParam('datacoord')=='' ? '' : $plxPlugin->getParam('datacoord');

# On récupère les templates des pages statiques
$files = plxGlob::getInstance(PLX_ROOT.'themes/'.$plxAdmin->aConf['style']);
if ($array = $files->query('/^static(-[a-z0-9-_]+)?.php$/')) {
	foreach($array as $k=>$v)
		$aTemplates[$v] = $v;
}
for($i=1; $i < 19 ; $i++) {
	$aZoom[$i] = $i;
}
$baseImgUri = PLX_PLUGINS.$pluginName.'/images/';
?>
<style>
	fieldset select{margin-top: 0;}
	h3{margin-left:-10px;font-weight:bolder;}
	.toogleImg:hover{text-decoration:none;}
	.osmcsvlnk:before{font-weight:bold;content:" + ";}
	#panneau_exemple{max-width:100%;float:left;background:#FFF48D;padding:10px;border:1px solid #A1A1A1;}
	#panneau_tools,#panneau_config{float:left;}
	#waiting{z-index:1000000;position:fixed;width:100%;height:100%;background-color:#1a1a1a;top:0px;left:0px;text-align:center;font-size:xx-large;line-height:1.618;color:#FFF;padding-top:3.9%;}
	#optionsPopUp,#divCp,#divCoord{border-left:double #A1A1A1;padding:10px;margin-left:50px;}
	.bleu{color:blue;}
	.vert{color:green;}
	.rouge{color:red;}
	#panneau_tools{background-color:#ddd;line-height:1;padding:1rem 0;}
	#id_csv{max-width:95%;}
	form .col {margin-bottom: .16rem;}
	@media (max-width: 1080px) {#panneau_exemple {max-width:100%;}}
</style>
<div id="waiting" style="display:none"><?php $plxPlugin->lang('L_WAIT') ?>...<sup><sub id="waitingmore"></sub></sup><br /><img src="<?php echo $baseImgUri ?>loading.gif"></div>
<?php if (!defined('PLX_VERSION')) {#avant 5.5 ?>
<h2><?php echo $plxPlugin->getInfo('title') ?></h2>
<?php } #action="parametres_plugin.php?p=openStreetMaps" ?>
	<form method="post">
		<div class="col sml-12 med-12 lrg-6">
			<div id="divTools" style="display:<?php echo $show?'block':'none' ?>;">
				<a class="toogleImg" onclick="toogle('panneau_tools','tools');" style="float:left;cursor:help;">&nbsp;<img style="width:16px;" id="img_panneau_tools" src="<?php echo $baseImgUri.'tools-'.($logs?'out':'in').'.png'; ?>" title="Bascule des outils" />&nbsp;</a>
				<div id="panneau_tools" style="display:<?php echo $logs?'block':'none' ?>;">
					<fieldset class="tools">
						<p class="field col sml-12">
							<a id="testAdress" onclick="wait(this.id);" class="green button" href="parametres_plugin.php?p=<?php echo $pluginName ?>&amp;testAdresses=1" title="<?php $plxPlugin->lang('L_TEST_ADRESSES_TITLE') ?>">&laquo;<?php $plxPlugin->lang('L_TEST_ADRESSES_BTN') ?>&raquo;</a>
						</p>
						<p class="field col sml-12">
							<input type="submit" name="purge" value="<?php $plxPlugin->lang('L_PURGE_BTN') ?>" onclick="return confirm('<?php $plxPlugin->lang('L_PURGE_BTN') ?> '+document.getElementById('id_purge_what').value+'?');"/>
							<?php plxUtils::printSelect('purge_what',array('listing'=>$plxPlugin->getLang('L_PURGE_LIST').' &laquo;listing&raquo;','all_towns'=>$plxPlugin->getLang('L_PURGE_DB').' &laquo;all_towns&raquo;','towns'=>$plxPlugin->getLang('L_PURGE_DB').' &laquo;towns&raquo;'),''); ?>
						</p>
						<p class="field col sml-12">
							<?php $plxPlugin->lang('L_EXPORT_BTN') ?> :
							<a class="blue button" href="parametres_plugin.php?p=<?php echo $pluginName ?>&amp;db2csv=1" onclick="reload(1)" target="_blank" title="<?php $plxPlugin->lang('L_EXPORT_BTN') ?>">&laquo;towns&raquo;</a>&nbsp;<a class="blue button" href="parametres_plugin.php?p=<?php echo $pluginName ?>&amp;db2csv=2" onclick="reload(2)" target="_blank" title="<?php $plxPlugin->lang('L_EXPORT_BTN') ?>">&laquo;all_towns&raquo;</a>
						</p>
						<p class="field col sml-12"><label for="id_csv"><?php $plxPlugin->lang('L_CSV_TITLE') ?>&nbsp;:</label></p>
						<div class="col sml-12">
							<a title="<?php echo $plxPlugin->lang('L_CSV_SELECTION') ?>" id="toggler_csv" href="javascript:void(0)" onclick="mediasManager.openPopup('id_csv', true)" style="outline:none; text-decoration: none"><b>+&nbsp;</b></a>
							<?php plxUtils::printInput('csv',$csv,'text','33-255',false,'" placeholder="'.$plxPlugin->getLang('L_CSV_FILE')) ?>
							<?php plxUtils::printInput('csv2db',$plxPlugin->getLang('L_IMPORT_BTN'),'submit','',false,'" onclick="wait(this.id);" placeholder="'.$plxPlugin->getLang('L_CSV_MAIN')) ?>
							<?php echo $csvFiles ?>
						</div>
						<p class="field col sml-12">
							<img class="float-right" src="<?php echo $baseImgUri ?>SQLite.png" title="SqLite 3" alt="SqLite 3 logo">
							<i><sup><sub><?php $plxPlugin->lang('L_CSV_FILE_TIPS') ?></sub></sup></i>
						</p>

						<p class="field col sml-12"><label for="id_csv"><?php $plxPlugin->lang('L_COORDS_TITLE') ?>:</label></p>
						<div class="col sml-12">
							<div class="col sml-6"><?php $plxPlugin->lang('L_CP')?>:<br /><?php plxUtils::printInput('cp','','text','5-10',false,'" placeholder="'.$plxPlugin->getLang('L_CP')) ?></div>
							<div class="col sml-6"><?php $plxPlugin->lang('L_NOM')?>:<br /><?php plxUtils::printInput('nom','','text','15-255',false,'" placeholder="'.$plxPlugin->getLang('L_NOM')) ?></div>
						</div>
						<div class="col sml-12">
							<div class="col sml-6"><?php $plxPlugin->lang('L_LAT')?>:<br /><?php plxUtils::printInput('lat','','text','15-255',false,'" placeholder="'.$plxPlugin->getLang('L_LAT')) ?></div>
							<div class="col sml-6"><?php $plxPlugin->lang('L_LON')?>:<br /><?php plxUtils::printInput('lon','','text','15-255',false,'" placeholder="'.$plxPlugin->getLang('L_LON')) ?></div>
						</div>
						<p class="field col sml-12"><?php plxUtils::printInput('coords2db',$plxPlugin->getLang('L_COORDS_BTN'),'submit','',false,'" onclick="wait(this.id);" placeholder="'.$plxPlugin->getLang('L_CSV_MAIN')) ?>
						<br /><i><sup><sub><?php $plxPlugin->lang('L_COORDS_TIPS') ?></sub></sup></i></p>

<?php if($logs): ?>
						<a class="toogleImg" onclick="toogle('panneau_logs','logs');" style="float:left;cursor:help;"><img style="width:16px;" id="img_panneau_logs" src="<?php echo $baseImgUri.'logs-'.($logs?'out':'in').'.png'; ?>" title="Bascule des logs" /></a>
						<p id="panneau_logs" style="display:<?php echo $logs?'block':'none' ?>;"><?php echo $_SESSION[$pluginName.'_LOG'];?></p>
<?php unset($_SESSION[$pluginName.'_LOG']);endif;#logs?>

					</fieldset>
				</div>
				<br />
			</div>

			<div id="panneau_config">
				<fieldset>
					<p class="field"><label for="id_pageName"><?php $plxPlugin->lang('L_STATIC_PAGE_TITLE') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('pageName',$pageName,'text','20-60') ?>
					<p class="field"><label for="id_mnuDisplay"><?php echo $plxPlugin->lang('L_MENU_DISPLAY') ?>&nbsp;:</label></p>
					<?php plxUtils::printSelect('mnuDisplay',array('1'=>L_YES,'0'=>L_NO),$mnuDisplay); ?>
					<p class="field"><label for="id_mnuName"><?php $plxPlugin->lang('L_MENU_TITLE') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('mnuName',$mnuName,'text','20-20') ?>
					<p class="field"><label for="id_mnuPos"><?php $plxPlugin->lang('L_MENU_POS') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('mnuPos',$mnuPos,'text','2-5') ?>
					<p class="field"><label for="id_template"><?php $plxPlugin->lang('L_MENU_TEMPLATE') ?>&nbsp;:</label></p>
					<?php plxUtils::printSelect('template', $aTemplates, $template) ?>
					<p class="field"><label for="id_source"><?php $plxPlugin->lang('L_SOURCE_FILE') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('source',$source,'text','20-120') ?>

					<div id="divDraw" <?php echo $show == 'on' ? '': 'style="display:none;"';?>>
						<p class="field"><label for="id_drawMode"><?php echo $plxPlugin->lang('L_DRAW_MODE') ?>&nbsp;:</label></p>
						<?php plxUtils::printSelect('drawMode',array('0'=>$plxPlugin->getLang('L_DRAW_MODE_0'),'1'=>$plxPlugin->getLang('L_DRAW_MODE_1'),2=>$plxPlugin->getLang('L_DRAW_MODE_2')),$drawMode); ?>
						<br /><i><?php echo $plxPlugin->lang('L_DRAW_MODE_TIPS') ?></i>
					</div>

					<p class="field"><label for="id_type"><?php $plxPlugin->lang('L_FILE_TYPE') ?>&nbsp;:</label></p>
					<select id="id_type" name="type" class="type" onchange="toogleType();">
						<option <?php echo $show == 'on' ? 'selected="selected"' : '';?> value="1"><?php $plxPlugin->lang('L_PC') ?></option>
						<option <?php echo $show == 'on' ? '': 'selected="selected"';?> value="2"><?php $plxPlugin->lang('L_COORDS') ?></option>
					</select>

					<div id="divCp" <?php echo $show == 'on' ? '': 'style="display:none;"';?>>
						<p class="field"><label for="id_item_principal"><?php $plxPlugin->lang('L_SOURCE_MAIN_ITEM') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('item_principal',$item_principal,'text','20-120') ?>
						<p class="field"><label for="id_itemville"><?php $plxPlugin->lang('L_SOURCE_ITEM_VILLE') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('itemville',$itemville,'text','20-120') ?>
						<p class="field"><label for="id_itemcp"><?php $plxPlugin->lang('L_SOURCE_ITEM_CP') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('itemcp',$itemcp,'text','20-120') ?>
					</div>
					<div id="divCoord" <?php echo $show == 'on' ? 'style="display:none;"' : '';?>>
						<p class="field"><label for="id_item_principal_coord"><?php $plxPlugin->lang('L_SOURCE_MAIN_ITEM') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('item_principal_coord',$item_principal_coord,'text','20-120') ?>
						<p class="field"><label for="id_itemlat"><?php $plxPlugin->lang('L_SOURCE_ITEM_LATITUDE') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('itemlat',$itemlat,'text','20-120') ?>
						<p class="field"><label for="id_itemlong"><?php $plxPlugin->lang('L_SOURCE_ITEM_LONGITUDE') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('itemlong',$itemlong,'text','20-120') ?>
					</div>

					<h3><?php $plxPlugin->lang('L_SIZE') ?></h3>
					<p class="field"><label for="id_width"><?php $plxPlugin->lang('L_FRAME_WIDTH') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('width',$width,'text','20-4') ?>
					<p class="field"><label for="id_unit"><?php $plxPlugin->lang('L_WIDTH_UNIT') ?>&nbsp;:</label></p>
					<?php plxUtils::printSelect('unit',array('px'=>'px','%'=>'%'),$unit); ?>
					<p class="field"><label for="id_height"><?php $plxPlugin->lang('L_FRAME_HEIGHT') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('height',$height,'text','20-4') ?>
					<p class="field"><label for="id_zindex"><?php $plxPlugin->lang('L_FRAME_ZINDEX') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('zindex',$zindex,'text','20-4') ?>


					<h3><?php $plxPlugin->lang('L_POS') ?></h3>
					<p class="field"><label for="id_latitude"><?php $plxPlugin->lang('L_LATITUDE') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('latitude',$latitude,'text','20-30') ?>
					<p class="field"><label for="id_longitude"><?php $plxPlugin->lang('L_LONGITUDE') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('longitude',$longitude,'text','20-30') ?>
					<p class="field"><label for="id_zoom"><?php $plxPlugin->lang('L_FRAME_ZOOM') ?>&nbsp;:</label></p>
					<?php plxUtils::printSelect('zoom',$aZoom,$zoom); ?>

					<h3><?php $plxPlugin->lang('L_POP_UP') ?></h3>
					<p><label for="id_showpopup"><?php $plxPlugin->lang('L_SHOW_POP_UP') ?>&nbsp;:</label><input type="checkbox" id="id_showpopup" name="showpopup" onclick="toogle('optionsPopUp');" <?php echo $showpopup == 'on' ? 'checked="checked"' : ''; ?>></p>
					<div id="optionsPopUp" <?php echo $showpopup == 'on' ? '': 'style="display:none;"';?>>
						<p class="field"><label for="id_popupLatitude"><?php $plxPlugin->lang('L_POP_UP_LATITUDE') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('popupLatitude',$popupLatitude,'text','20-30') ?>
						<p class="field"><label for="id_popupLongitude"><?php $plxPlugin->lang('L_POP_UP_LONGITUDE') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('popupLongitude',$popupLongitude,'text','20-30') ?>
						<p class="field"><label for="id_popupTexte"><?php $plxPlugin->lang('L_POP_UP_TXT') ?>&nbsp;:</label></p>
						<?php plxUtils::printInput('popupTexte',$popupTexte,'text','20-120') ?>
					</div>

					<h3><?php $plxPlugin->lang('L_OPTIONAL') ?></h3>
					<p class="field"><label for="id_itemnom"><?php $plxPlugin->lang('L_SOURCE_ITEM_NOM') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('itemnom',$itemnom,'text','20-120') ?>
					<p class="field"><label for="id_itemval"><?php $plxPlugin->lang('L_SOURCE_ITEM_VAL') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('itemval',$itemval,'text','20-120') ?>
					<p class="field"><label for="id_dataval"><?php $plxPlugin->lang('L_DATA_ITEM_VAL') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('dataval',$dataval,'text','20-120') ?>
					<p class="field"><label for="id_itemcoord"><?php $plxPlugin->lang('L_SOURCE_ITEM_COORD') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('itemcoord',$itemcoord,'text','20-120') ?>
					<p class="field"><label for="id_datacoord"><?php $plxPlugin->lang('L_DATA_ITEM_COORD') ?>&nbsp;:</label></p>
					<?php plxUtils::printInput('datacoord',$datacoord,'text','20-120') ?>

				</fieldset>
			</div>
		</div>
		<p class="in-action-bar">
			<?php echo plxToken::getTokenPostMethod() ?>
			<input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" />
		</p>
	</form>
<div class="col sml-12 med-12 lrg-6">
	<a class="toogleImg" onclick="toogle('panneau_exemple');" style="float:left;cursor:help;">&nbsp;<img style="width:16px;" id="img_panneau_exemple" src="<?php echo $baseImgUri.'zoom-in.png'; ?>" title="Bascule des exemples" />&nbsp;</a>
	<div id="panneau_exemple" style="display:none;">
		<h2><?php $plxPlugin->lang('L_SMPL_SRC') ?></h2>
		<p>data/configuration/plugin.adhesion.adherents.xml</p>
		<h2><?php $plxPlugin->lang('L_SMPL_FILE') ?></h2>
		<h3><?php $plxPlugin->lang('L_SMPL_TYPE_CP') ?></h3>
		<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
	<span class="bleu">&lt;document&gt;</span>
		<span class="rouge">&lt;adherent number="00001"&gt;</span>
			<span class="bleu">&lt;nom&gt;</span><span class="vert">&lt;![CDATA[Tryphon]]&gt;</span><span class="bleu">&lt;/nom&gt;</span>
			<span class="bleu">&lt;prenom&gt;</span><span class="vert">&lt;![CDATA[Tournesol]]&gt;</span><span class="bleu">&lt;/prenom&gt;</span>
			<span class="bleu">&lt;adresse1&gt;</span><span class="vert">&lt;![CDATA[Château de Moulinsart]]&gt;</span><span class="bleu">&lt;/adresse1&gt;</span>
			<span class="bleu">&lt;adresse2&gt;</span><span class="vert">&lt;![CDATA[]]&gt;</span><span class="bleu">&lt;/adresse2&gt;</span>
			<span class="rouge">&lt;cp&gt;</span><span class="vert">&lt;![CDATA[75000]]&gt;</span><span class="rouge">&lt;/cp&gt;</span>
			<span class="rouge">&lt;ville&gt;</span><span class="vert">&lt;![CDATA[Paris]]&gt;</span><span class="rouge">&lt;/ville&gt;</span>
		<span class="rouge">&lt;/adherent&gt;</span>
	<span class="bleu">&lt;document&gt;</span>
		</pre>
		<h3><?php $plxPlugin->lang('L_SMPL_TYPE_COORDS') ?></h3>
		<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
	<span class="bleu">&lt;document&gt;</span>
		<span class="rouge">&lt;coordonnees number="00001"&gt;</span>
			<span class="bleu">&lt;nom&gt;</span><span class="vert">&lt;![CDATA[Paris]]&gt;</span><span class="bleu">&lt;/nom&gt;</span>
			<span class="rouge">&lt;latitude&gt;</span><span class="vert">&lt;![CDATA[48.8566]]&gt;</span><span class="rouge">&lt;/latitude&gt;</span>
			<span class="rouge">&lt;longitude&gt;</span><span class="vert">&lt;![CDATA[2.3538]]&gt;</span><span class="rouge">&lt;/longitude&gt;</span>
		<span class="rouge">&lt;/coordonnees&gt;</span>
	<span class="bleu">&lt;document&gt;</span>
		</pre>
		<p><?php $plxPlugin->lang('L_SMPL_TXT1') ?></p>
		<p>&nbsp;</p>
		<p><?php $plxPlugin->lang('L_SMPL_TXT2') ?> &laquo;adherent&raquo;.</p>
		<p><?php $plxPlugin->lang('L_SMPL_TXT3') ?> &laquo;cp&raquo; &amp; &laquo;ville&raquo;.</p>
		<p>&nbsp;</p>
		<p><?php $plxPlugin->lang('L_SMPL_TXT4') ?></p>
	</div>
</div>

<script type="text/javascript">
	var wait = function(i) {
		document.getElementById(i).style.display = 'none';
		document.getElementById('waiting').style.display = 'block';
		window.setTimeout(function(){addHtml("<?php $plxPlugin->lang('L_WAIT1') ?>")},6180);
		window.setTimeout(function(){addHtml("<?php $plxPlugin->lang('L_WAIT2') ?>")},16180);
		window.setTimeout(function(){addHtml("<?php $plxPlugin->lang('L_WAIT3') ?>")},26180);
		window.setTimeout(function(){addHtml("<?php $plxPlugin->lang('L_WAIT4') ?>")},33333);
	}
	var addHtml = function(t){
		var e = document.getElementById('waitingmore');
		e.innerHTML = e.innerHTML + '<br />' + t + '...';
	}
	var see = function(c) {
		var e = document.querySelectorAll('.text-log.' + c);
		console.log(e,e.length);
		for(var i = 0; i<e.length; i++){
			console.log(i,e[i]);
			e[i].classList.toggle('sml-hide');//
		}
/*
		var a = e[0].classList.contains('sml-hide');//search
		for(var i =0; i > e.lenght(); i++){
			if(a)
				e[i].classList.remove('sml-hide');//
			else
				e[i].classList.add('sml-hide');//
		}
*/
	}
	var reload = function(a) {
		window.setTimeout(function(){window.location.reload();},3333 * a);
	}
	var bigData = function(f) {
		f = '<?php echo $csvDir?>' + f;
		document.getElementById('id_csv').value = f;
	}
	var toogle = function(qui,icn) {
		icn = icn?icn:'zoom';
		var myDiv = document.getElementById(qui);
		var exemp = qui.search('exemple')?true:false;
		var toggl = exemp?document.getElementById('img_'+qui):'';
		if (myDiv.style.display == 'none') {
			myDiv.style.display = 'block'; if(exemp) toggl.src = '<?php echo $baseImgUri ?>'+icn+'-out.png';
		} else {
			myDiv.style.display = 'none'; if(exemp) toggl.src = '<?php echo $baseImgUri ?>'+icn+'-in.png';
		}
	}
	var toogleType = function() {
		var Cp = document.getElementById('divCp');
		var Draw = document.getElementById('divDraw');
		var Coord = document.getElementById('divCoord');
		var Tools = document.getElementById('divTools');

		if (Cp.style.display == 'none') {
			Cp.style.display = 'block';
			Draw.style.display = 'block';
			Tools.style.display = 'block';
			Coord.style.display = 'none';
		} else {
			Cp.style.display = 'none';
			Draw.style.display = 'none';
			Tools.style.display = 'none';
			Coord.style.display = 'block';
		}
	}
</script>
