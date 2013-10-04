<?php if(!defined('PLX_ROOT')) exit;


# Control du token du formulaire
plxToken::validateFormToken($_POST);

if(!empty($_POST)) {
	$plxPlugin->setParam('pageName', $_POST['pageName'], 'string');
	$plxPlugin->setParam('mnuDisplay', $_POST['mnuDisplay'], 'numeric');
	$plxPlugin->setParam('mnuName', $_POST['mnuName'], 'string');
	$plxPlugin->setParam('mnuPos', $_POST['mnuPos'], 'numeric');
	$plxPlugin->setParam('template', $_POST['template'], 'string');
	$plxPlugin->setParam('source', $_POST['source'], 'string');

	$plxPlugin->setParam('type', $_POST['type'], 'string');
if ($_POST['type'] == 1) {
	$plxPlugin->setParam('item_principal', $_POST['item_principal'], 'string');
	$plxPlugin->setParam('itemville', $_POST['itemville'], 'string');
	$plxPlugin->setParam('itemcp', $_POST['itemcp'], 'string');
} else {
	$plxPlugin->setParam('item_principal_coord', $_POST['item_principal_coord'], 'string');
	$plxPlugin->setParam('itemlat', $_POST['itemlat'], 'string');
	$plxPlugin->setParam('itemlong', $_POST['itemlong'], 'string');
}
	$plxPlugin->setParam('width', $_POST['width'], 'numeric');
	$plxPlugin->setParam('unit', $_POST['unit'], 'string');
	$plxPlugin->setParam('height', $_POST['height'], 'numeric');

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
	header('Location: parametres_plugin.php?p=openStreetMaps');
	exit;
}
$pageName =  $plxPlugin->getParam('pageName')=='' ? 'Localisation' : $plxPlugin->getParam('pageName');
$mnuDisplay =  $plxPlugin->getParam('mnuDisplay')=='' ? 1 : $plxPlugin->getParam('mnuDisplay');
$mnuName =  $plxPlugin->getParam('mnuName')=='' ? $plxPlugin->getLang('L_DEFAULT_MENU_NAME') : $plxPlugin->getParam('mnuName');
$mnuPos =  $plxPlugin->getParam('mnuPos')=='' ? 2 : $plxPlugin->getParam('mnuPos');
$template = $plxPlugin->getParam('template')=='' ? 'static.php' : $plxPlugin->getParam('template');
$source = $plxPlugin->getParam('source')=='' ? 'plugins/openStreetMaps/source.exemple.xml' : $plxPlugin->getParam('source');
# Type de fichier source
$type = $plxPlugin->getParam('type')=='' ? 1 : $plxPlugin->getParam('type');
$show = $plxPlugin->getParam('type')==1 ? 'on' : '';

$item_principal = $plxPlugin->getParam('item_principal')=='' ? 'adherent' : $plxPlugin->getParam('item_principal');
$itemville = $plxPlugin->getParam('itemville')=='' ? 'ville' : $plxPlugin->getParam('itemville');
$itemcp = $plxPlugin->getParam('itemcp')=='' ? 'cp' : $plxPlugin->getParam('itemcp');

$item_principal_coord = $plxPlugin->getParam('item_principal_coord')=='' ? 'coordonnees' : $plxPlugin->getParam('item_principal_coord');
$itemlat = $plxPlugin->getParam('itemlat')=='' ? 'latitude' : $plxPlugin->getParam('itemlat');
$itemlong = $plxPlugin->getParam('itemlong')=='' ? 'longitude' : $plxPlugin->getParam('itemlong');
# Taille
$width = $plxPlugin->getParam('width')=='' ? 90 : $plxPlugin->getParam('width');
$unit = $plxPlugin->getParam('unit')=='' ? '%' : $plxPlugin->getParam('unit');
$height = $plxPlugin->getParam('height')=='' ? 840 : $plxPlugin->getParam('height');
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

?>
<style>
	h3 { margin:10px;font-weight: bolder; }
	#panneau_exemple { width:550px;float: right;margin-left:5%;background: #FFF48D;padding:10px;border: 1px solid #A1A1A1; }
	#panneau_config { min-width:500px;float: left; }
	#optionsPopUp, #divCp, #divCoord { border-left:double #A1A1A1;padding:10px;margin-left:50px; }
	.bleu { color:blue; }
	.vert { color:green; }
	.rouge { color:red; }
</style>

<div id="panneau_exemple">
	<h2>Exemples de chemin vers le fichier xml source</h2>
	<p>data/configuration/plugin.adhesion.adherents.xml</p>
	<h2>Exemples de fichier xml source</h2>
	<h3>Type Code postal</h3>
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
	<h3>Type Coordonnées</h3>
	<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
<span class="bleu">&lt;document&gt;</span>
	<span class="rouge">&lt;coordonnees number="00001"&gt;</span>
		<span class="bleu">&lt;nom&gt;</span><span class="vert">&lt;![CDATA[Paris]]&gt;</span><span class="bleu">&lt;/nom&gt;</span>
		<span class="rouge">&lt;latitude&gt;</span><span class="vert">&lt;![CDATA[48.8566]]&gt;</span><span class="rouge">&lt;/latitude&gt;</span>
		<span class="rouge">&lt;longitude&gt;</span><span class="vert">&lt;![CDATA[2.3538]]&gt;</span><span class="rouge">&lt;/longitude&gt;</span>
	<span class="rouge">&lt;/coordonnees&gt;</span>
<span class="bleu">&lt;document&gt;</span>
	</pre>
	<p>Les trois items les plus importants sont ceux surlignés en rouge.</p>
	<p>&nbsp;</p>
	<p>Dans le premier exemple ci-dessus, l'item principal est "adherent".</p>
	<p>Les items secondaires sont "cp" et "ville".</p>
	<p>&nbsp;</p>
	<p>Vous pouvez avoir des noms différents mais vous devez conserver la structure de base (constituée par les 3 items rouges).</p>
</div>
<div id="panneau_config">
<h2><?php echo $plxPlugin->getInfo('title') ?></h2>

<form action="parametres_plugin.php?p=openStreetMaps" method="post">
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
		<p class="field"><label for="id_type"><?php $plxPlugin->lang('L_FILE_TYPE') ?>&nbsp;:</label></p>
		<select id="id_type" name="type" class="type" onchange="toogleType();">
			<option <?php echo $show == 'on' ? 'selected="selected"' : '';?> value="1">Code postal</option>
			<option <?php echo $show == 'on' ? '': 'selected="selected"';?> value="2">Coordonnées</option>
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
		<p>

		<h3><?php $plxPlugin->lang('L_POS') ?></h3>
		<p class="field"><label for="id_latitude"><?php $plxPlugin->lang('L_LATITUDE') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('latitude',$latitude,'text','20-30') ?>
		<p class="field"><label for="id_longitude"><?php $plxPlugin->lang('L_LONGITUDE') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('longitude',$longitude,'text','20-30') ?>
		<p class="field"><label for="id_zoom"><?php $plxPlugin->lang('L_FRAME_ZOOM') ?>&nbsp;:</label></p>
		<?php plxUtils::printSelect('zoom',$aZoom,$zoom); ?>
		<p>

		<h3><?php $plxPlugin->lang('L_POP_UP') ?></h3>
		<p><label for="id_showpopup"><?php $plxPlugin->lang('L_SHOW_POP_UP') ?>&nbsp;:</label><input type="checkbox" id="id_showpopup" name="showpopup" onclick="toogle();" <?php echo $showpopup == 'on' ? 'checked="checked"' : ''; ?>></p>
		<div id="optionsPopUp" <?php echo $showpopup == 'on' ? '': 'style="display:none;"';?>>
			<p class="field"><label for="id_popupLatitude"><?php $plxPlugin->lang('L_POP_UP_LATITUDE') ?>&nbsp;:</label></p>
			<?php plxUtils::printInput('popupLatitude',$popupLatitude,'text','20-30') ?>
			<p class="field"><label for="id_popupLongitude"><?php $plxPlugin->lang('L_POP_UP_LONGITUDE') ?>&nbsp;:</label></p>
			<?php plxUtils::printInput('popupLongitude',$popupLongitude,'text','20-30') ?>
			<p class="field"><label for="id_popupTexte"><?php $plxPlugin->lang('L_POP_UP_TXT') ?>&nbsp;:</label></p>
			<?php plxUtils::printInput('popupTexte',$popupTexte,'text','20-120') ?>
			<p>
		</div>

		<h3><?php $plxPlugin->lang('L_OPTIONAL') ?></h3>
		<p class="field"><label for="id_itemnom"><?php $plxPlugin->lang('L_SOURCE_ITEM_NOM') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('itemnom',$itemnom,'text','20-12') ?>
		<p class="field"><label for="id_itemval"><?php $plxPlugin->lang('L_SOURCE_ITEM_VAL') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('itemval',$itemval,'text','20-12') ?>
		<p class="field"><label for="id_dataval"><?php $plxPlugin->lang('L_DATA_ITEM_VAL') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('dataval',$dataval,'text','20-120') ?>
		<p class="field"><label for="id_itemcoord"><?php $plxPlugin->lang('L_SOURCE_ITEM_COORD') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('itemcoord',$itemcoord,'text','20-12') ?>
		<p class="field"><label for="id_datacoord"><?php $plxPlugin->lang('L_DATA_ITEM_COORD') ?>&nbsp;:</label></p>
		<?php plxUtils::printInput('datacoord',$datacoord,'text','20-120') ?>
		<p></p>
		<p>
			<?php echo plxToken::getTokenPostMethod() ?>
			<input type="submit" name="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" />
		</p>
	</fieldset>
</form>
</div>
<script type="text/javascript">
	var toogle = function() {
		var myDiv = document.getElementById('optionsPopUp');
		
		if (myDiv.style.display == 'none') {
			myDiv.style.display = 'block';
		} else {
			myDiv.style.display = 'none';
		}
	}
	var toogleType = function() {
		var Cp = document.getElementById('divCp');
		var Coord = document.getElementById('divCoord');
		
		if (Cp.style.display == 'none') {
			Cp.style.display = 'block';
			Coord.style.display = 'none';
		} else {
			Cp.style.display = 'none';
			Coord.style.display = 'block';
		}
	}
</script>