<?php
$plxMotor = plxMotor::getInstance();
$plxPlugin = $plxMotor->plxPlugins->aPlugins['openStreetMaps'];?>
<div id="map" style="height: <?php echo $plxPlugin->getParam('height');?>px; width: <?php echo $plxPlugin->getParam('width').$plxPlugin->getParam('unit');?>;"></div>
