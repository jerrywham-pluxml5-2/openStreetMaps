<?php
/**
 * Plugin openStreetMaps
 *
 * @author	Cyril MAGUIRE
 **/
class openStreetMaps extends plxPlugin {

	public $list = array(); # Tableau des codes postaux sources
	public $gps = array(); # Tableau des coordonnées GPS

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

    public function onActivate() {
    	# Si le dossier des coordonnées gps n'existe pas, on le crée
    	if (!is_dir(PLX_ROOT.'plugins/openStreetMaps/gps')) {
    		mkdir(PLX_ROOT.'plugins/openStreetMaps/gps');
    		chmod(PLX_ROOT.'plugins/openStreetMaps/gps', 0777);
    		# On crée les fichiers de coordonnées par code postal
    		$this->recGPS();
    		# On remet les bons droits d'accès
    		chmod(PLX_ROOT.'plugins/openStreetMaps/gps', 0644);
    	}
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
						if (strpos($value, "annuaire.html") !== false) {
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
	 * Méthode qui récupère les codes postaux enregistrés dans le fichier xml source
	 * 
	 * @param $filename ressource le chemin vers le fichier source indiqué dans la configuration
	 * @return array
	 * 
	 * @author Cyril MAGUIRE
	 */
	public function getCp($filename) {
		
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

				# Récupération de la ville
				$ville = str_replace(array(' ','-','_','Cedex','CEDEX','cedex','0','1','2','3','4','5','6','7','8','9'),'',trim(strtoupper(plxUtils::removeAccents(plxUtils::getValue($values[$iTags[$this->getParam('itemville')][$i]]['value'])))));
				
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
				}
				$this->list[$ville][] = array(
					'VAL' => $val,
					'COORD'=> $coord,
					'NOM'	=> $nom,
					# Récupération du cp
					'CP'	=> $this->arrondir(plxUtils::getValue($values[$iTags[$this->getParam('itemcp')][$i]]['value']),1)
				);
			}
		}
		return $this->list;
	}


	/**
	 * Méthode qui récupère les coordonnées enregistrées dans le fichier xml source
	 * 
	 * @param $filename ressource le chemin vers le fichier source indiqué dans la configuration
	 * @return array
	 * 
	 * @author Cyril MAGUIRE
	 */
	public function getCoordonnees($filename) {

		if(!is_file($filename)) return;
		
		# Mise en place du parseur XML
		$data = implode('',file($filename));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		if(isset($iTags[$this->getParam('item_principal_coord')]) AND isset($iTags[$this->getParam('itemlat')])) {
			$nb = sizeof($iTags[$this->getParam('itemlat')]);
			$size=ceil(sizeof($iTags[$this->getParam('item_principal_coord')])/$nb);
			for($i=0;$i<$nb;$i++) {
				$nom = '';
				if  ($this->getParam('itemnom') != '') {
					# Récupération du nom de la localisation
					$nom = plxUtils::getValue($values[$iTags[$this->getParam('itemnom')][$i]]['value']);
				}
				$this->list[] = array(
					'NOM'	=> $nom,
					# Récupération de la latitude
					'LAT'	=> plxUtils::getValue($values[$iTags[$this->getParam('itemlat')][$i]]['value']),
					# Récupération de la longitude
					'LONG'  => plxUtils::getValue($values[$iTags[$this->getParam('itemlong')][$i]]['value'])
				);
			}
		}
		return $this->list;
	}


	/**
	 * Méthode qui dispatche les coordonnées gps des villes dans plusieurs fichiers selon leur code postal
	 * 
	 * @return array
	 * 
	 * @author Cyril MAGUIRE
	 */
	public function recGPS() {
		$villes = PLX_ROOT.'plugins/openStreetMaps/villes.csv';
		if(!is_file($villes)) return 'Aucun fichier';
		# Récupération des données
		$data = file($villes);

		for ($i=0;$i<36000;$i++) {
			if (isset($data[$i])) {
				$v = explode(';', $data[$i]);
				file_put_contents(PLX_ROOT.'plugins/openStreetMaps/gps/'.$v[0], $v[2],FILE_APPEND);
			}
		}
		return true;
	}

	/**
	 * Méthode permettant de faire un arrondi supérieur ou inférieur en fonction de la précision choisie
	 * 
	 * @param $value integer       le nombre à arrondir
	 * @param $precision integer   1 la précision sera sur les dizaines
	 * 							   2 la précision sera sur les centaines, etc.
	 * @param $arrondiSup bool 	   arrondi supérieur par défaut
	 * @return integer
	 * 
	 * @author Cyril MAGUIRE
	 */
	public function arrondir($value,$precision=1,$arrondiSup=true) {
			$p = pow(10,$precision);
			if ($arrondiSup) return ceil($value/$p)*$p;
			else return $value-($value%$p);
	}

	/**
	 * Méthode qui récupère les coordonnées gps d'une ville selon son code postal
	 * 
	 * @param $cp integer le code postal de la ville à chercher
	 * @param $ville string le nom de la ville à chercher
	 * @param $decalage integer le décalage à appliquer s'il y a plusieurs markers avec les mêmes coordonnées
	 * @return array
	 * 
	 * @author Cyril MAGUIRE
	 */
	public function getGPS($cp,$ville,$decalage = 0) {
		(int) $cp;
		$gps = PLX_ROOT.'plugins/openStreetMaps/gps/';
		$CP = $cp;
		if (!is_file($gps.$cp)) {
			//Tous les codes postaux n'étant pas disponibles
			//On arrondi le code postal à la dizaine supérieure
			$CP = $this->arrondir($cp,1);
			//On vérifie à nouveau
			if (!is_file($gps.$CP)) {
				//Si toujours pas disponible
				//On arrondi le code postal à la centaine supérieure
				$CP = $this->arrondir($cp,2);
				//On vérifie à nouveau
				if (!is_file($gps.$CP)) {
					//Si toujours pas disponible
					//On arrondi le code postal à la centaine inférieure
					$CP = $this->arrondir($cp,2,false);
					//On vérifie à nouveau
					if (!is_file($gps.$CP)) {
						//Si toujours pas disponible
						//On arrondi le code postal au millier supérieur
						$CP = $this->arrondir($cp,3);
						//On vérifie à nouveau
						if (!is_file($gps.$CP)) {
							//Si toujours pas disponible
							//On arrondi le code postal au millier inférieur
							$CP = $this->arrondir($cp,3,false);
							//On vérifie à nouveau
							if (!is_file($gps.$CP)) {
								//Si toujours pas disponible
								//On arrondi le code postal à la centaine de millier supérieure
								$CP = $this->arrondir($cp,4);
								//On vérifie à nouveau
								if (!is_file($gps.$CP)) {
									//Si toujours pas disponible
									//On arrondi le code postal à la centaine de millier inférieure
									$CP = $this->arrondir($cp,4,false);
									//On vérifie à nouveau
									if (!is_file($gps.$CP)) {
										$CP = $cp;
										return false;
									}
								}
							}
						}
					}
				}
			}
		}
		$cp = $CP;
		# Récupération des données
		$data = file($gps.$cp);
		foreach ($data as $k => $json) {
			if(strpos($json, $ville)) {
				$json = trim($json);
				# Lorsqu'il y a plusieurs enregistrements avec les mêmes coordonnées
				# on les décale les uns par rapport aux autres pour afficher plusieurs markers
				# On supprime l'accolade de fin
				$tmp = substr($json, 0, strrpos($json, '.')+1);
				# et on récupère la partie non entière de la longitude
				$fin = '0.'.substr($json,strrpos($json, '.')+1,-1);
				$fin = $fin+($decalage/100);
				# On applique le décalage
				$json = $tmp.substr($fin,2).'}';
				return $json;
			}
		}
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
			$CP = $this->getCp(PLX_ROOT.$this->getParam('source'));
		} else {
			$dir = trim($this->getParam('source'),'/').'/';
			if (is_dir(PLX_ROOT.$dir)) {
				$this->plxGlob_sources = plxGlob::getInstance(PLX_ROOT.$dir,false,true,'arts');
				foreach ($this->plxGlob_sources->aFiles as $key => $file) {
					$CP = $this->getCp(PLX_ROOT.$dir.$file);
				}
			}
		}

		$map = '';
		# Récupération des coordonnées
		foreach ($CP as $ville => $marker) {
			foreach ($marker as $k => $v) {
				$dec = $k;
				$GPS = trim($this->getGPS($v['CP'],$ville,$dec));
				if (!empty($GPS)) {
					if ($this->getParam('itemnom') != '') {
						// Si l'affichage nécessite une autorisation, on vérifie que les paramètres ne sont pas vides
						if ($v['VAL'] == $this->getParam('dataval') && $this->getParam('dataval') != '' && $v['COORD'] == $this->getParam('datacoord') && $this->getParam('datacoord') != '') {
							$map .= str_replace('}',', click : \''.$v['NOM'].'\' }', $GPS).','."\n";
							//S'il faut un affichage et qu'il ne nécessite pas d'autorisation
						} elseif($this->getParam('dataval') == '' && $this->getParam('datacoord') == '') {
							$map .= str_replace('}',', click : \''.$v['NOM'].'\' }', $GPS).','."\n";
							//Sinon
						} else {
							$map .= str_replace('}',', click : \'Non renseigné\' }', $GPS).','."\n";
						}
					} else {
						$map .= $GPS.','."\n";
					}	
				}
			}
		}
		$map = substr($map, 0,-2);
		else :
		# Récupération des coordonnées à afficher sur la carte
		$COORD = $this->getCoordonnees(PLX_ROOT.$this->getParam('source'));

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