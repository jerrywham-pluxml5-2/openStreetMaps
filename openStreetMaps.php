<?php
/**
 * Plugin openStreetMaps
 *
 * @author	Cyril MAGUIRE
 **/
require_once PLX_PLUGINS.'openStreetMaps/lib/medoo.php';
class openStreetMaps extends plxPlugin {

	public $list = array('valides' => ''); # Tableau des codes postaux sources
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
					//$nom = strtoupper(plxUtils::removeAccents(plxUtils::getValue($values[$iTags[$this->getParam('itemnom')][$i]]['value'])));
					$nom = strtoupper($tmp['Nom']);
				}
				$this->list[$ville][] = array(
					'NUM' => $tmp['Id'],
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
	
    public function Nominatim($town,$cp) {
    	$c = file_get_contents('http://nominatim.openstreetmap.org/search/?format=json&country=france&city='.urlencode($town).'&postcode='.$cp);
		$c = json_decode($c);
		$result['lat'] = $c[0]->lat;
		$result['lon'] = $c[0]->lon;
		return $result;
    }
	/**
	 * Méthode qui ajoute les fichiers js dans le pied de page du thème
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
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
								if ($this->getParam('itemnom') != '') {
									// Si l'affichage nécessite une autorisation, on vérifie que les paramètres ne sont pas vides
									if ($v['VAL'] == $this->getParam('dataval') && $this->getParam('dataval') != '' && $v['COORD'] == $this->getParam('datacoord') && $this->getParam('datacoord') != '') {
										$map .= '{latitude : '.$GPS[$ville]['lat'].', longitude : '.$GPS[$ville]['lon'].', click : \''.$v['VILLE'].' : '.$v['NOM'].'\' },'."\n";
										//S'il faut un affichage et qu'il ne nécessite pas d'autorisation
									} elseif($this->getParam('dataval') == '' && $this->getParam('datacoord') == '') {
										$map .= '{latitude : '.$GPS[$ville]['lat'].', longitude : '.$GPS[$ville]['lon'].', click : \''.$v['VILLE'].' : '.$v['NOM'].'\'
										},'."\n";
										//Sinon
									} else {
										$map .= '{latitude : '.$GPS[$ville]['lat'].', longitude : '.$GPS[$ville]['lon'].', click : \''.$v['VILLE'].' : Non renseigné\' },'."\n";
									}
								} else {
									$map .= '{/*'.$v['VILLE'].'*/ latitude : '.$GPS[$ville]['lat'].', longitude : '.$GPS[$ville]['lon'].'},'."\n";
								}	
							}  else {
								$map .= '//'.$v['VILLE'].' '.$v['NUM']."\n";
							}
						}
					}
				}
				$map = substr($map, 0,-2);
				file_put_contents(PLX_PLUGINS.'openStreetMaps/listing/'.$adherents.'.txt', $map);
			} else {
				$map = file_get_contents(PLX_PLUGINS.'openStreetMaps/listing/'.$coordonnees[2]);
			}
		else :
		# Récupération des coordonnées à afficher sur la carte
		$COORD = $this->getRecords(PLX_ROOT.$this->getParam('source'));

		$map = '';
		# Mise en forme des coordonnées
		foreach ($COORD as $i => $marker) {
				if ($this->getParam('itemnom') != '') {
					$map .= '{ latitude : '.$marker['LAT'].', longitude : '.$marker['LONG'].', click : \''.$marker['NOM'].'\'},'."\n";
				} else {
					$map .= '{ latitude : '.$marker['LAT'].', longitude : '.$marker['LONG'].'},'."\n";
				}	
		}
		$map = substr($map, 0,-2);

		endif;

		echo "\t".'<script type="text/javascript">
				if(typeof(jQuery) === "undefined") {document.write(\'<script  type="text/javascript" src="<?php echo PLX_PLUGINS; ?>openStreetMaps/jQuery.1.8.1.min.js"><\/script>\');}
			</script>'."\n";
		echo "\t".'<script type="text/javascript" src="'.PLX_PLUGINS.'openStreetMaps/leaflet-0.4.js"></script>'."\n";
		echo "\t".'<script type="text/javascript" src="'.PLX_PLUGINS.'openStreetMaps/osmLeaflet.jquery.js"></script>'."\n";
		echo "\t".'<script>
					    $(document).ready(function () {
					        var $mini_map = $("#mini_map");

					       // Map de démo centrale
					        $mini_map.osmLeaflet({
					            zoom      : '.$this->getParam('zoom').',
					            latitude  : '.$this->getParam('latitude').',
					            longitude : '.$this->getParam('longitude').'
					        });'.($this->getParam('showpopup') == 'on' ?
							'$mini_map.osmLeaflet(\'addPopup\', {
					            latitude  : '.$this->getParam('popupLatitude').',
					            longitude : '.$this->getParam('popupLongitude').',
					            content   : \''.str_replace("'","&#039;",$this->getParam('popupTexte')).'\'
					        });' : '').'
					        $mini_map.osmLeaflet(\'addMarker\', [
					            '.$map.'
					        ]);
					    });
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