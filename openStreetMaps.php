<?php
/**
 * Plugin openStreetMaps
 *
 * @author	Cyril MAGUIRE
 **/
require_once PLX_PLUGINS.'openStreetMaps/lib/medoo.min.php';
class openStreetMaps extends plxPlugin {

	public $list; # Tableau des codes postaux sources
	public $plxGlob_sources; # Objet listant les fichiers sources

	/**
	 * Constructeur de la classe
	 *
	 * @param	default_lang	langue par défaut
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
	public function __construct($default_lang) {

        # appel du constructeur de la classe plxPlugin (obligatoire)
        parent::__construct($default_lang);

		$this->list = array('valides' => ''); #init
		# droits pour accèder à la page config.php du plugin
		$this->setConfigProfil(PROFIL_ADMIN);

        # déclaration des hooks
        # pas de traitement javascript si l'on n'est pas sur la page localisation
		if(isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING']!='' AND preg_match('/(localisation)/', $_SERVER['QUERY_STRING']) ) {
			$this->addHook('ThemeEndHead', 'ThemeEndHead');
			$this->addHook('ThemeEndBody', 'ThemeEndBody');
		}
        $this->addHook('plxMotorPreChauffageBegin', 'plxMotorPreChauffageBegin');
		$this->addHook('plxShowConstruct', 'plxShowConstruct');
        $this->addHook('plxShowStaticListEnd', 'plxShowStaticListEnd');
		$this->addHook('plxShowPageTitle', 'plxShowPageTitle');
		$this->addHook('SitemapStatics', 'SitemapStatics');

    }

	/**
	 * Méthode de traitement du hook plxShowConstruct
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
    public function plxShowConstruct() {

		# infos sur la page statique
		$string  = "if(\$this->plxMotor->mode=='openStreetMaps') {";
		$string .= "	\$array = array();";
		$string .= "	\$array[\$this->plxMotor->cible] = array(
			'name'		=> '".$this->getParam('pageName')."',
			'menu'		=> '',
			'url'		=> 'localisation.html',
			'readable'	=> 1,
			'active'	=> 1,
			'group'		=> ''
		);";
		$string .= "	\$this->plxMotor->aStats = array_merge(\$this->plxMotor->aStats, \$array);";
		$string .= "}";
		echo "<?php ".$string." ?>";
    }

	/**
	 * Méthode de traitement du hook plxMotorPreChauffageBegin
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
    public function plxMotorPreChauffageBegin() {

		$template = $this->getParam('template')==''?'static.php':$this->getParam('template');

		$string = "
		if(\$this->get && preg_match('/^localisation\/?/',\$this->get)) {
			if (isset(\$this->plxPlugins->aPlugins['adhesion'])){
				if (isset(\$_SESSION['account'])) {
					\$this->mode = 'openStreetMaps';
					\$this->cible = '../../plugins/openStreetMaps/static';
					\$this->template = '".$template."';
					return true;
				} else {
					header('location:'.\$this->racine);
					return true;
				}
			} else {
				\$this->mode = 'openStreetMaps';
				\$this->cible = '../../plugins/openStreetMaps/static';
				\$this->template = '".$template."';
				return true;
			}
		}
		";

		echo "<?php ".$string." ?>";
    }

	/**
	 * Méthode de traitement du hook plxShowStaticListEnd
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
    public function plxShowStaticListEnd() {

		# ajout du menu pour accèder à la page de localisation
		if($this->getParam('mnuDisplay')) {
			echo "<?php \$class = \$this->plxMotor->mode=='openStreetMaps'?'active':'noactive'; ?>";
			# Si le plugin adhesion est présent et activé
			echo '<?php if (isset($this->plxMotor->plxPlugins->aPlugins["adhesion"])){
				# Utilisateur connecté
				if (isset($_SESSION["account"])) {
					foreach ($menus as $key => $value) {
						if (strpos($value, "annuaire") !== false) {
							$tmp = preg_replace(\'/<li class="([a-z]+)">(.+)(<\/li>)/i\', \'<li class="$1">$2
								<ul>
									\', $value);
							$menus[$key] = str_replace(\'<ul>\', \'<ul>
									<li class="static \'.$class.\'"><a href="\'.$this->plxMotor->urlRewrite("?localisation.html").\'">'.$this->getParam("mnuName").'</a></li>
								</ul>
							</li>\', $tmp);
						}
					}
				}
			} else {
				array_splice($menus, '.($this->getParam('mnuPos')-1).', 0, \'<li class="static \'.$class.\'"><a href="\'.$this->plxMotor->urlRewrite("?localisation.html").\'">'.$this->getParam('mnuName').'</a></li>\');
			}?>';	
		}
    }


	/**
	 * Méthode qui ajoute le fichier css dans l'entête du thème
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
	public function ThemeEndHead(){
		echo "\t".'<link rel="stylesheet" type="text/css" href="'.PLX_PLUGINS.'openStreetMaps/leaflet.css" media="screen"/>'."\n";
	}

	/**
	 * Méthode qui renseigne le titre de la page dans la balise html <title>
	 *
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function plxShowPageTitle() {
		echo '<?php
			if($this->plxMotor->mode == "openStreetMaps") {
				$this->plxMotor->plxPlugins->aPlugins["openStreetMaps"]->lang("'.$this->getParam('pageName').'");
				return true;
			}
		?>';
	}

	/**
	 * Méthode qui retourne les informations $output en analysant
	 * le nom du fichier de l'adhérent $filename
	 *
	 * @param	filename	fichier de l'adhérent à traiter
	 * @return	array		information à récupérer
	 * @author	Stephane F
	 **/
	public function recInfoFromFilename($filename) {

		# On effectue notre capture d'informations
		if(preg_match('/(_?[0-9]{5}).([a-z0-9-]*).([a-z0-9-]*).([0-9]{10}).xml$/',$filename,$capture)) {
			return array(
				'Id'		=> $capture[1],
				'Nom'		=> $capture[2],
				'Prenom'	=> $capture[3],
				'Date'		=> $capture[4],
			);
		}
	}
	/**
	 * Méthode qui récupère les informations enregistrées dans le fichier xml source
	 *
	 * @param $filename ressource le chemin vers le fichier source indiqué dans la configuration
	 * @return array
	 *
	 * @author Cyril MAGUIRE
	 */
	public function getRecords($filename) {
		
		if(!is_file($filename)) return;
		
		# Mise en place du parseur XML
		$data = implode('',file($filename));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		
		if(isset($iTags[$this->getParam('item_principal')]) AND isset($iTags[$this->getParam('itemcp')])) {
			$nb = sizeof($iTags[$this->getParam('itemcp')]);
			$size=ceil(sizeof($iTags[$this->getParam('item_principal')])/$nb);
			for($i=0;$i<$nb;$i++) {
				$val = '';
				$coord = '';
				$nom = '';
				$tmp = $this->recInfoFromFilename($filename);
				# Récupération de la ville
				$fullname = trim(strtoupper(plxUtils::removeAccents(plxUtils::getValue($values[$iTags[$this->getParam('itemville')][$i]]['value']))));
				$ville = str_replace(array(' ','-','_','Cedex','CEDEX','cedex','0','1','2','3','4','5','6','7','8','9'),'',$fullname);
				
				if  ($this->getParam('itemval') != '') {
					# Récupération de la validité de l'inscription
					$val = plxUtils::getValue($values[$iTags[$this->getParam('itemval')][$i]]['value']);
				}
				if  ($this->getParam('itemcoord') != '') {
					# Récupération de l'autorisation de diffusion des coordonnées
					$coord = plxUtils::getValue($values[$iTags[$this->getParam('itemcoord')][$i]]['value']);
				}
				if  ($this->getParam('itemnom') != '') {
					# Récupération du nom à afficher dans la pop-up
					$nom = strtoupper(plxUtils::removeAccents(plxUtils::getValue($values[$iTags[$this->getParam('itemnom')][$i]]['value'])));
					//$nom = strtoupper($tmp['Nom']);
				}
				$this->list[$ville][] = array(
					'NUM' => $i,//$tmp['Id']
					'VAL' => $val,
					'COORD'=> $coord,
					'NOM'	=> $nom,
					'CP' => plxUtils::getValue($values[$iTags[$this->getParam('itemcp')][$i]]['value']),
					'VILLE' => $fullname,
				);
				if ($val == 1) {
					$this->list['valides'] .= $nom;
				}
			}
		}
		elseif(isset($iTags[$this->getParam('item_principal_coord')]) AND isset($iTags[$this->getParam('itemlat')]) AND isset($iTags[$this->getParam('itemlong')])) {
			unset($this->list);
			$nb = sizeof($iTags[$this->getParam('itemlat')]);
			for($i=0;$i<$nb;$i++) {
				$val = '';// incertain de l'utilité de cette ligne (plugin adhesion) 
				$nom = '';
				$lat = '';
				$long = '';
				if  ($this->getParam('itemval') != '') {// incertain de l'utilité de ce if 
					# Récupération de la validité de l'inscription
					$val = plxUtils::getValue($values[$iTags[$this->getParam('itemval')][$i]]['value']);
				}
				if  ($this->getParam('itemlat') != '') {
					# Récupération de la lattitude
					$lat = plxUtils::getValue($values[$iTags[$this->getParam('itemlat')][$i]]['value']);
				}
				if  ($this->getParam('itemlong') != '') {
					# Récupération de la lattitude
					$long = plxUtils::getValue($values[$iTags[$this->getParam('itemlong')][$i]]['value']);
				}
				if  ($this->getParam('itemnom') != '') {
					# Récupération du nom à afficher dans la pop-up
					//~ $nom = strtoupper(plxUtils::removeAccents(plxUtils::getValue($values[$iTags[$this->getParam('itemnom')][$i]]['value'])));
					$nom = plxUtils::getValue($values[$iTags[$this->getParam('itemnom')][$i]]['value']);
				}
				$this->list[$i] = array(
					'NUM' => $i,//$tmp['Id']
					'NOM'	=> $nom,
					'LAT' => $lat,
					'LONG' => $long,
				);
				if ($val == $this->getParam('itemval')) {$this->list[$i]['valides'] = $nom;}// incertain de l'utilité de cette ligne 
			}
		}
		return $this->list;
	}

	/**
	* Search a town by its name
	*
	* @param string $town Town name
	* @return array
	*
	* @author Cyril MAGUIRE
	*/
	public function search($town,$cp) {
		$dbTowns = new medoo(array(
			'db' => 'gps',
			'database_type' => 'sqlite',
			'database_file' => PLX_PLUGINS.'openStreetMaps/gps/gps.sqlite'
			)
		);
		$town = strtolower($town);
		$result = $dbTowns->select('towns', array('lat', 'lon'), array(
			'AND'=> array(
		   		'cp' => $cp,
		   		'nom' => $town
		   		)
		    )
		);
		if (count($result) == 1) {
			$result = $result[0];
		}
		if (empty($result)) {
			$result = $this->Nominatim($town,$cp);
			$last_id = $dbTowns->insert('towns', array(
					'lat' => $result['lat'],
					'lon' => $result['lon'],
					'cp' => $cp,
					'nom' => $town
				)
			);
		}
		 return $result;
	}

	public function Nominatim($town,$cp) { /*updated*/
		$c = file_get_contents('http://nominatim.openstreetmap.org/search?format=json&country=france&city='.urlencode($town).'&postalcode='.$cp);
		$c = json_decode($c);
		$result['lat'] = $c[0]->lat;
		$result['lon'] = $c[0]->lon;
		return $result;
	}
	/**
	 * Méthode qui ajoute les fichiers js dans le pied de page du thème
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE, updated:GeoJson+leaflet1.0.3 Thomas Ingles
	 **/
	public function ThemeEndBody() {
		if ($this->getParam('type') == 1) :
		# Récupération des codes postaux à afficher sur la carte
		if (is_file(PLX_ROOT.$this->getParam('source'))) {
			$infos = $this->getRecords(PLX_ROOT.$this->getParam('source'));
		} else {
			$dir = trim($this->getParam('source'),'/').'/';
			if (is_dir(PLX_ROOT.$dir)) {
				$this->plxGlob_sources = plxGlob::getInstance(PLX_ROOT.$dir,false,true,'arts');
				foreach ($this->plxGlob_sources->aFiles as $key => $file) {
					$infos = $this->getRecords(PLX_ROOT.$dir.$file);
				}
			}
		}
		$adherents = md5($infos['valides']);
		unset($infos['valides']);
		$map = '';
		$GPS = array();
		$coordonnees = scandir(PLX_PLUGINS.'openStreetMaps/listing');
			if(!isset($coordonnees[2]) || $coordonnees[2] != $adherents.'.txt') {
				if (isset($coordonnees[2]) ) {
					unlink(PLX_PLUGINS.'openStreetMaps/listing/'.$coordonnees[2]);
				}
				# Récupération des coordonnées
				foreach ($infos as $ville => $marker) {
					if (!empty($ville)) {
						if (!isset($GPS[$ville])) {
							$GPS[$ville] = $this->search(trim(str_replace(array('CEDEX','0','1','2','3','4','5','6','7','8','9'),'',$marker[0]['VILLE'])),$marker[0]['CP']);
						}
						foreach ($marker as $k => $v) {
							$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($k*0.0001);
							if (!empty($GPS[$ville]) && !empty($GPS[$ville]['lon']) && !empty($GPS[$ville]['lat']) ) {
							$map .= '
			{
				"geometry": {
					"type": "Point",
					"coordinates": ['.$GPS[$ville]['lon'].', '.$GPS[$ville]['lat'].']
				},
				"type": "Feature",
				"properties": {
				';
							if ($this->getParam('itemnom') != '') {
								$map .= '		"popupContent": "'.$v['VILLE'].' : '.$v['NOM'].'"';
							} else {
								$map .= '		"popupContent": "&nbsp;"';
							}
							$map .= '
				},
				"id": '.$v['NUM'].'
			},';
							}
						}
						if ($map != '') {
						$mapFeatures = 'var geosmjsonFeatures = {
    "type": "FeatureCollection",
    "features": ['.substr($map, 0,-1)/* del last comma ',' of loop{feature} */.'
			]
		};
		'."\n";
						}
					}
				}
				$map = substr($mapFeatures, 0,-2);
				file_put_contents(PLX_PLUGINS.'openStreetMaps/listing/'.$adherents.'.txt', $map);
			} else {
				$map = file_get_contents(PLX_PLUGINS.'openStreetMaps/listing/'.$coordonnees[2]);
			}
		else :
		# Récupération des coordonnées à afficher sur la carte
		$COORD = $this->getRecords(PLX_ROOT.$this->getParam('source'));

		$map = 'var geosmjsonFeatures = {
    "type": "FeatureCollection",
    "features": [';
		# Mise en forme des coordonnées (http://geojson.org/) /!\ coordonnées inversé (LONG in 1st) /!\
		foreach ($COORD as $i => $marker) {
			$map .= '
			{
				"geometry": {
					"type": "Point",
					"coordinates": ['.$marker['LONG'].', '.$marker['LAT'].']
				},
				"type": "Feature",
				"properties": {
				';
				if ($this->getParam('itemnom') != '') {
					$map .= ' "popupContent": "'.$marker['NOM'].'"';
				} else {
					$map .= ' "popupContent": "&nbsp;"';
				}	
				$map .= '
				},
				"id": '.$marker['NUM'].'
			},';
		}
		$map = substr($map, 0,-1)/* del last comma ',' of loop{feature} */.'
		]
	};
	'."\n";
	endif;

echo "\t".'<script type="text/javascript" src="'.PLX_PLUGINS.'openStreetMaps/leaflet.js"></script>'."\n";

echo '
<script type="text/javascript">
'.$map.'
	var map = L.map(\'map\').setView(['.$this->getParam('latitude').', '.$this->getParam('longitude').'], '.$this->getParam('zoom').');'."\t
	L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: 'Map data &copy; <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors, <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"openstreetmap.org\">openstreetmap.org</a>',
		maxZoom: 18
	}).addTo(map);

	function onEachFeature(feature, layer) {
		var popupContent = feature.properties.popupContent;
		layer.bindPopup(popupContent);
	}
	".($this->getParam('showpopup') == 'on' ?
		'var popup = L.popup()
		.setLatLng(['.$this->getParam('popupLatitude').', '.$this->getParam('popupLongitude').'])
		.setContent("'.str_replace("'","&#039;",$this->getParam('popupTexte')).'")
		.openOn(map);
' : '').($marker?'
	L.geoJson(geosmjsonFeatures, {
		style: function (feature) {return feature.properties && feature.properties.style;},
		onEachFeature: onEachFeature /*,
		pointToLayer: function (feature, latlng) {return L.circleMarker(latlng, {radius: 7,fillColor: "#ff7800",color: "#000",weight: 1,opacity: 1,fillOpacity: 0.8});}*/
	}).addTo(map);
':'').'
</script>
';
	}

	/**
	 * Méthode qui référence la page de localisation dans le sitemap
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
	public function SitemapStatics() {
		echo '<?php
		echo "\n";
		echo "\t<url>\n";
		echo "\t\t<loc>".$plxMotor->urlRewrite("?localisation.html")."</loc>\n";
		echo "\t\t<changefreq>monthly</changefreq>\n";
		echo "\t\t<priority>0.8</priority>\n";
		echo "\t</url>\n";
		?>';
	}
}
?>
