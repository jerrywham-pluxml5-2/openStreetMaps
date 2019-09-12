<?php
/**
 * Plugin openStreetMaps
 *
 * @author	Cyril MAGUIRE
 **/
require_once 'lib/medoo.min.php';
class openStreetMaps extends plxPlugin {
	public $v = '1.5.1';#leaflet release
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
		$string  = "if(\$this->plxMotor->mode=='".__CLASS__."') {";
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
					\$this->mode = '".__CLASS__."';
					\$this->cible = '../../plugins/".__CLASS__."/static';
					\$this->template = '".$template."';
					return true;
				} else {
					header('location:'.\$this->racine);
					return true;
				}
			} else {
				\$this->mode = '".__CLASS__."';
				\$this->cible = '../../plugins/".__CLASS__."/static';
				\$this->template = '".$template."';
				return true;
			}
		}
		";

		echo "<?php ".$string." ?>";
	}

	/**
	 * Méthode de traitement du hook plxShowStaticListEnd
	 * Ajoute le menu "localisation"
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
	public function plxShowStaticListEnd() {

		# ajout du menu pour accèder à la page de localisation
		if($this->getParam('mnuDisplay')) {
			echo "<?php \$class = \$this->plxMotor->mode=='".__CLASS__."'?'active':'noactive'; ?>";
			# Si le plugin adhesion est présent et activé
			echo '<?php if (isset($this->plxMotor->plxPlugins->aPlugins["adhesion"])){
				# Utilisateur connecté
				if (isset($_SESSION["account"])) {
					foreach ($menus as $key => $value) {
						if (!is_array($value) AND strpos($value, "annuaire") !== false) {
							$tmp = preg_replace(\'/<li class="([a-z]+)">(.+)(<\/li>)/i\', \'<li class="$1">$2
								<ul>
									\', $value);
							$menus[$key] = str_replace(\'<ul id="static-adhesion-account" class="sub-menu">\', \'<ul id="static-adhesion-account" class="sub-menu">
									<li class="static \'.$class.\'"><a href="\'.$this->plxMotor->urlRewrite("?localisation.html").\'">'.$this->getParam("mnuName").'</a></li>
								\', $tmp);
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
		echo "\t".'<link rel="stylesheet" type="text/css" href="'.PLX_PLUGINS.__CLASS__.'/leaflet.css?v='.$this->v.'" media="screen"/>'."\n";
	}

	/**
	 * Méthode qui renseigne le titre de la page dans la balise html <title>
	 *
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function plxShowPageTitle() {
		echo '<?php
			if($this->plxMotor->mode == "'.__CLASS__.'") {
				$this->plxMotor->plxPlugins->aPlugins["'.__CLASS__.'"]->lang("'.$this->getParam('pageName').'");
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
	 * Méthode qui récupère et charge les informations enregistrées dans le fichier xml source
	 *
	 * @param $filename ressource le chemin vers le fichier source indiqué dans la configuration
	 * @Load $this->list return void;
	 *
	 * @author Cyril MAGUIRE, Thomas Ingles
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

		if(isset($iTags[$this->getParam('item_principal')], $iTags[$this->getParam('itemcp')])) {#Codes Postaux
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
				if ($val == 1 AND $coord != 'refus') {
					$this->list['valides'] .= $nom;
					$this->list[$ville][] = array(
						'NUM' => $tmp['Id'],//$i
						'VAL' => $val,
						'COORD'=> $coord,
						'NOM'	=> $nom,
						'CP' => plxUtils::getValue($values[$iTags[$this->getParam('itemcp')][$i]]['value']),
						'VILLE' => $fullname,
					);
				}
			}
		}
		elseif(isset($iTags[$this->getParam('item_principal_coord')]) AND isset($iTags[$this->getParam('itemlat')]) AND isset($iTags[$this->getParam('itemlong')])) {#Coordonnées
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
					'NUM' => $i,
					'NOM'	=> $nom,
					'LAT' => $lat,
					'LONG' => $long,
				);
				if ($val == $this->getParam('itemval')) {$this->list[$i]['valides'] = $nom;}// incertain de l'utilité de cette ligne
			}
		}
		#return $this->list;
	}

	/**
	* Save a towns database in csv file : config sys
	*
	* @param no
	* @return download file | false
	*
	* @author Thomas Ingles
	*/
	public function db2csv($table='towns') {
		$file = PLX_PLUGINS.__CLASS__.DIRECTORY_SEPARATOR.'listing'.DIRECTORY_SEPARATOR.$table.'.csv_'.time().'.txt';#path temp file name
		$dbTowns = new medoo(array(
			'db' => 'gps',
			'database_type' => 'sqlite',
			'database_file' => PLX_PLUGINS.__CLASS__.'/gps/gps.sqlite'
			)
		);
		$csv = '';
		$all = false;
		$fields = array('lat', 'lon', 'cp', 'nom');
		if(strpos($table,'all') !== false){#if all_towns #table
			$all = true;
			$fields[] = 'reel';#add column
		}
		$where = 'ORDER BY "cp", "nom"';
		$result = $dbTowns->select($table, $fields, $where);#load
#		var_dump($all, $table, $fields, $result);exit;
		foreach($result as $data){
			$csv .= $data['cp'].';'.$data[(!$all?'nom':'reel')].';'.$data['lat'].';'.$data['lon'].';'.PHP_EOL;#create csv line
		}
		if(file_put_contents($file,$csv)){#save file
			#launch download
			ob_clean();//clean output buffer
			header('Content-Description: File Transfer');
			header('Content-Type: application/download');
			header('Content-Disposition: attachment; filename='.basename($file));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: no-cache');
			header('Content-Length: '.filesize($file));
			readfile($file);
			$_SESSION['info'] = '<i class="text-blue">'.__CLASS__.' '.$this->getLang('L_DB2CSV_NOTE').' #'.$table.' : '.count($result).' '.$this->getLang('L_DB2CSV_OK').'.</i>';
			$_SESSION[__CLASS__.'_LOG'] = $_SESSION['info'];
			@unlink($file);
			exit;
		}
		$_SESSION[__CLASS__.'_LOG'] = '<i class="alert orange">'.__CLASS__.' '.$this->getLang('L_DB2CSV_NOTE').' #'.$table.' : '.$this->getLang('L_DB2CSV_KO').'.</i>';
		return false;
	}

	/**
	* Load a towns by csv : config sys
	*
	* @param string $csv file name
	* @return bool
	*
	* @author Thomas Ingles
	*/
	public function csv2db($csv = false) {
		$_SESSION[__CLASS__.'_LOG'] = '';
		$dbTowns = new medoo(array(
			'db' => 'gps',
			'database_type' => 'sqlite',
			'database_file' => PLX_PLUGINS.__CLASS__.'/gps/gps.sqlite'
			)
		);
		if(strpos($csv,'purge') !== FALSE){
			$table = str_replace('purge','',$csv);
			$all = (strpos($csv,'_') !== FALSE);#all_

			#clear db
			$sql = 'DROP TABLE IF EXISTS "'.$table.'";';//$ok = $dbTowns->exec($sql);
			$sql .= 'CREATE TABLE "'.$table.'" ("lat" REAL NOT NULL, "lon" REAL NOT NULL, "cp" INTEGER(10) NOT NULL, "nom" VARCHAR(255) NOT NULL'.
				($all?','.PHP_EOL.'"reel" VARCHAR(255) NOT NULL':'').
				');'.PHP_EOL;
#			$sql = 'DELETE FROM "'.$table.'"';#1ST idea
			$ok = $dbTowns->exec($sql);
#			var_dump($ok,$sql);exit;
			$_SESSION[__CLASS__.'_LOG'] .= ' <i class"text-'.($ok?'green':'red').'">#'.$table.' '.$this->getLang('L_PURGED').'</i><br />'.PHP_EOL;
			return $ok;
		}
		if(strpos($csv,'coords2db') !== FALSE){#bep
			$line = str_replace('coords2db','',$csv);
			$ok = (strpos($csv,';') !== FALSE);#
			$csv = PLX_ROOT.PLX_CONFIG_PATH.'.'.__CLASS__.'_GEOTEMPOST.csv';#SHIFT to '../../data/configuration/.openStreetMaps_GEOTEMPOST.csv'
			#var_dump($line,$csv);#exit;
			$lng = 'KO';
			if($ok AND file_put_contents($csv,$line)){
				$coords2db = true;
				$lng = 'OK';
			}
			#var_dump(file_get_contents($csv),unlink($csv));exit;
			$_SESSION[__CLASS__.'_LOG'] .= '<i class"text-'.($ok?'green':'red').'">'.$line.' '.$this->getLang('L_COORDS_'.$lng).'</i><br />'.PHP_EOL;
			#return $ok;
		}
		#var_dump($csv,$dbTowns,file_exists($csv));#exit;
		if($csv AND file_exists($csv)){
			if ($handle = fopen($csv, "r")) {#open csv file
				$sql = $log = $bigD = $pog = '';
				$tot = $totadd = $totbad = $totin = $totout = 0;
				$time = time();

				#https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/ >>> Base officielle des codes postaux >>> TÉLÉCHARGER : https://www.data.gouv.fr/fr/datasets/r/554590ab-ae62-40ac-8353-ee75162c05ee
				#https://datanova.legroupe.laposte.fr/explore/dataset/laposte_hexasmal/export/?disjunctive.code_commune_insee&disjunctive.nom_de_la_commune&disjunctive.code_postal&disjunctive.libell_d_acheminement&disjunctive.ligne_5
				#https://datanova.legroupe.laposte.fr/explore/dataset/laposte_hexasmal/download/?format=csv&timezone=Europe/Berlin&use_labels_for_header=true
				#laposte_hexasmal.csv : Code_commune_INSEE;Nom_commune;Code_postal;Libelle_acheminement;Ligne_5;coordonnees_gps
				if(strpos($csv,'laposte_hexasmal') !== FALSE){
					$bigD = 'laposte';#
				}
#				NO GEOPOINT : Communes nouvelles : #laposte_commnouv.csv
#				https://datanova.legroupe.laposte.fr/explore/dataset/laposte_commnouv/export/
#				https://datanova.legroupe.laposte.fr/explore/dataset/laposte_commnouv/download/?format=csv&timezone=Europe/Berlin&use_labels_for_header=true

#				$dbTowns->exec('DELETE FROM "all_towns"');#IDEA : clear db before (opt)

				while (($data = fgetcsv($handle, 1000, ";")) !== FALSE){#read data
					@set_time_limit(30);#ré augmente le temps maximum d'execution #fix: Fatal error: Maximum execution time of 30 seconds exceeded
					$pog = ' : ';
					$tot++;
					if(!empty($data)){
						$num = count($data);
						if($bigD == 'laposte'){#laposte_hexasmal

							#SI & = Code_postal, On est sur la premiere ligne du csv (titres des colonnes)
							if(!is_numeric($data[2])){
								$pog .= $this->getLang('L_CSV_BAD_LINE') .' '.$this->getLang('L_POG_NO') . ' ' . $this->getLang('L_POG_ADDIN_DB');#dbg_lng
								$log .= '<i class="text-log text-orange"><sup><sub>#'.$tot.'#'.$bigD.$pog.'</sub></sup>&nbsp;: '.implode(';',$data).'<br /></i>'.PHP_EOL;#dbg
								$totbad++;
								continue;
							}

							$cp = str_pad($data[2], 5, '0', STR_PAD_LEFT);
							$reel = $data[1];
							$reel .= (!empty($data[4])?' '.$data[4]:'');# + Ligne 5
							$nom = plxUtils::title2filename($reel);#strtolower removeAccents ...
							$nom = str_replace(array('.','-'),'',strtoupper($nom));#UPPERCASE +

							$data[2] = $data[3] = '';#lat & lon shifts
							if(!empty($data[5])){# no geo
								$tada = explode(',',$data[5]);#Geopoints csv classique SHIFT
								$data[2] = $tada[0];#$lat shift
								$data[3] = $tada[1];#$lon shift
							}

						}else{
							$cp = str_pad($data[0], 5, '0', STR_PAD_LEFT);
							$reel = $data[1];
							$nom = plxUtils::title2filename($reel);#strtolower removeAccents ...
							$nom = str_replace(array('.','-'),'',strtoupper($nom));#UPPERCASE +
							$bigD = 'csvData';
						}

						$log .= '<i class="text-log';#dbg

						#Correctif villes_FR.csv joint #mixed CSV + #oneTime MAJ cp all_towns_big #commented #memo*
						if(strpos($data[2],'*/') !== FALSE){
							$bigD = 'bigData';#villes_FR
							#On retouche pour simplifier l'import
							#  CP ;         NOM           ; +RETOUCHE
							# 1090;AMAREINSFRANCHELEINSCES;{/*AMAREINSFRANCHELEINSCES 1090*/ latitude : 46.066667, longitude : 4.8}
							#$data[2] RETAIL
							$data[2] = str_replace(array(' ','latitude','longitude',':','/*'.$data[1],$data[0].'*/','{','}'), array('','','','','','','',''), $data[2]);
							//~ $data[2] = str_replace(array(' ','latitude','longitude',':','/*','*/','{','}'), array('','','','','','','',''), $data[2]);
							$tada = str_getcsv($data[2], ',');
							$lat = $tada[0];
							$lon = $tada[1];
/*
							#search & in big big data : all_towns_big #FaitMaison
							#BIGData
							$result = $dbTowns->select('all_towns_big', array('latitude', 'longitude', 'sort_name_ro', 'full_name_ro', 'pc'), array(
								'AND'=> array(
									'sort_name_ro' => $nom
									)
								)
							);
							if(FALSE !== $result){
								$bigD = 'BIGData';
								if(isset($result[0])){
									$result = $result[0];#shift
									$reel = $result['full_name_ro'];
									$lat = $result['latitude'];
									$lon = $result['longitude'];
									if($cp != $result['pc']){
										$log .= 'text-orange';#dbg
										$pog .= $this->getLang('L_PC_KO');#PC KO #dbg_lng
/ * #all_towns_big french cp completion #memo
										# sort_name_ro , # full_name_ro & coords
										#medoo update "all_towns_big" with "missing" french postal code #for dev 1 time #memo
										$where = array('sort_name_ro' => $nom);
										$bigdata = array('pc' => $cp);
										$bdresult = $dbTowns->update('all_towns_big', $bigdata, $where);#update($table, $data, $where = null)
										#var_dump($bdresult);exit;
* /
									}else{
										$log .= 'text-blue';#dbg
										$pog .= $this->getLang('L_PC_OK');#PC OK #dbg_lng
									}
								}else{
									$log .= 'text-red';#dbg
									$pog .= $this->getLang('L_PC_NO');#PC NO #dbg_lng
								}
							}#BIGData
*/
						}
						else{
							#Geopoints csv classique
#							$log .= 'text-green';#dbg
							$pog .= $this->getLang('L_PC_SV');#PC_SV #dbg_lng
							$lat = $data[2];
							$lon = $data[3];
							# CP;Nom réel;lat.itude;long.itude
						}


						#search in big data #FaitMaison
						$result = $dbTowns->select('all_towns', array('lat', 'lon', 'cp', 'nom', 'reel'), array(
							'AND'=> array(
								'nom' => $nom,
								'cp' => $cp
								)
							)
						);

						$pog .= ' :';
						#var_dump($num,$cp,$town,$lat,$lon,$data,$tada);#exit;
						if(isset($result[0])){
							$pog .= ' '.$this->getLang('L_POG_IN_DB');#dbg_lng
							$log .= ' text-blue sml-hide';
							$totin++;
						}else{
							if(empty($lat) OR empty($lon)){
								$last_id = false;
								$pog .= ' #'.$this->getLang('L_POG_NO_GEO').'#';#dbg_lng
							}else{
								if(empty($cp) OR empty($nom)){#not empty name
									$last_id = false;
								}else{
									$data = array(
										'lat' => $lat,
										'lon' => $lon,
										'cp' => $cp,
										'nom' => $nom,
										'reel' => $reel #strtolower()?
									);
									$last_id = $dbTowns->insert('all_towns', $data);#insert data in new db
								}
							}
							$log .= ' text-';
							if(!$last_id) {
								$pog .= ' '.$this->getLang('L_POG_NO');#dbg_lng
								$log .= 'red';#dbg
								$totout++;
							}else{
								$log .= 'green';#dbg
								$totadd++;
							}
							$pog .= ' '.$this->getLang('L_POG_ADDIN_DB');#dbg_lng
						}
						$log .= '"><sup><sub>#'.$tot.'#'.$bigD.$pog.'</sub></sup>&nbsp;: '.$cp.';'.$nom.';'.$reel.';'.$lat.';'.$lon.'<br /></i>'.PHP_EOL;#dbg
					}
				}#elihw
				if(isset($coords2db)){#del temp csv GEOTEMPOST.csv #add 1 town in db
					@unlink($csv);
					$time = '';
				}else{
					$time = time() - $time;
					$time = ' ('.$time.'s)';
				}
				$see = '';
				if($totout OR $totbad OR $totadd OR $totin){
					$see = $this->getLang('L_VIEW_HIDE').':&nbsp;'.
						(!$totout?'':'<br /><a class="text-red" href="javascript:void(0);" onclick="see(\'text-red\')">'.$totout . ' ' . $this->getLang('L_POG_NO').' '.$this->getLang('L_POG_ADDIN_DB') .'</a>').
						(!$totbad?'':'<br /><a class="text-orange" href="javascript:void(0);" onclick="see(\'text-orange\')">'.$totbad . ' ' . $this->getLang('L_POG_BAD').' ' .$this->getLang('L_POG_NO').' '.$this->getLang('L_POG_ADDIN_DB') .'</a>').
						(!$totadd?'':'<br /><a class="text-green" href="javascript:void(0);" onclick="see(\'text-green\')">'.$totadd . ' ' . $this->getLang('L_POG_ADDIN_DB') .'</a>').
						(!$totin?'':'<br /><a class="text-blue" href="javascript:void(0);" onclick="see(\'text-blue\')">'.$totin . ' ' . $this->getLang('L_POG_IN_DB') .'</a>').
						'<br />';
				}
				$_SESSION[__CLASS__.'_LOG'] .= '<br /><b>'.$this->getLang('L_LOG').' :</b><br />'.$see.$this->getLang('L_POG_TOT').' : '.$tot.' '.$this->getLang('L_POG_LNS').$time.'<br />'.$log;
				return true;
			}#fopen

		}#file exist
		return false;
	}

	/**
	* Search a town by its name
	*
	* @param string $town Town name
	* @return array
	*
	* @author Cyril MAGUIRE, Thomas Ingles
	*/
	public function search($town,$cp) {
		$log = (defined('PLX_ADMIN') AND isset($_SESSION['profil']) AND $_SESSION['profil'] < 1) ? '<i class="text-log': false;#2 debug towns
		if($log) $mode = '';
		$dbTowns = new medoo(array(
			'db' => 'gps',
			'database_type' => 'sqlite',
			'database_file' => PLX_PLUGINS.__CLASS__.'/gps/gps.sqlite'
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
			if($log){
				$result[0]['log'] = $log.' text-blue sml-hide">'.$town.' '.$cp.' : '.$this->getLang('L_GEO_OK').' '.$this->getLang('L_POG_IN_DB').' #towns lat: '.$result[0]['lat'].', lon: '.$result[0]['lon'].'<br /></i>'.PHP_EOL;
				$result[0]['logtype'] = 'blue';//
			}
			return $result[0];#is in db
		}

		if (empty($result)) {

			$bd = false;
			#search data in new all_towns db
			$nom = plxUtils::title2filename($town);#strtolower removeAccents ...
			$nom = str_replace(array('.','-'),'',strtoupper($nom));#UPPERCASE +
			$result = $dbTowns->select('all_towns', array('lat', 'lon'), array(
				'AND'=> array(
					'cp' => $cp,
					'nom' => $nom
					)
				)
			);

			$bd = !empty($result);
			if($bd){#in table all_towns
				if($log) $mode .= ' '.$this->getLang('L_GEO_OK').' '.$this->getLang('L_POG_IN_DB').' #all_town';
				$result = $result[0];#shift to 1st result
			}

			if(!$bd){#not in table all_towns
				$result = $this->Nominatim($town,$cp);#with Nominatim?
				if($log) $mode .= ' '.$this->getLang('L_GEO_KO').' #all_town! '.$this->getLang('L_GEO_'.(isset($result['lat'])?'OK':'KO')).' #Nominatim';
			}

			if(isset($result['lat'], $result['lon'])){#ok add in towns
				$last_id = $dbTowns->insert('towns', array(
						'lat' => $result['lat'],
						'lon' => $result['lon'],
						'cp' => $cp,
						'nom' => $town
					)
				);
				if($log){
#					$mode .= ' '.$result['lat'].' '.$result['lon'];
					if($last_id){
						$log .= ' text-green';
						$result['logtype'] = 'green';
					}else{
						$mode .= ' '.$this->getLang('L_POG_DB_ERR').' '.$this->getLang('L_POG_NO');
						$log .= ' text-red';
						$result['logtype'] = 'red';
					}
					$mode .= ' '.$this->getLang('L_POG_ADDIN_DB');

				}#log
			}
			else{
				if($log){
					$log .= ' text-red';
					$result['logtype'] = 'red';
				}
			}
			if($log) $result['log'] = $log.'">'.$town.' '.$cp.' : '.$mode.' #towns lat: '.@$result['lat'].', lon: '.@$result['lon'].'<br /></i>'.PHP_EOL;
			#var_dump($result['log'],$result['logtype']);exit;

		}
		return $result;
	}

	/**
	* Search a town by its name & cp in nominatim.openstreetmap.org
	*
	* @param string $town Town name
	* @return array
	*
	* @author Cyril MAGUIRE, Thomas Ingles
	*/
	public function Nominatim($town,$cp) {
		$result = array();
		$url = 'https://nominatim.openstreetmap.org/search?format=json&country=france&city='.urlencode($town).'&postalcode='.$cp;
		try{# extension_loaded('curl')
			$curl_handle=curl_init();
			curl_setopt($curl_handle, CURLOPT_URL,$url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, 'PluXml Plugin ' . __CLASS__);#Your application name
			$c = curl_exec($curl_handle);
			curl_close($curl_handle);
		}catch (Exception $e) {
			$c = @file_get_contents($url);#fix Warning: file_get_contents() with @
		}
#		var_dump('BRUT',$c);
		if($c AND $c != '[]'){#fix*  Notice: Trying to get property of non-object & Notice: Undefined offset: 0
			$c = json_decode($c);
			$result['lat'] = $c[0]->lat;#ici*
			$result['lon'] = $c[0]->lon;# et ici*
		}
#		var_dump('END',$c,$result);
		return $result;
	}

	/**
	 * Méthode qui charge la liste des adresses : mode code postal
	 * Récupére les codes postaux à afficher sur la carte
	 * @return	void
	 * @author	Thomas Ingles
	 **/
	public function getAdresses() {
		if (is_file(PLX_ROOT.$this->getParam('source'))) {
			$this->getRecords(PLX_ROOT.$this->getParam('source'));
		} else {
			$dir = trim($this->getParam('source'),'/').'/';
			if (is_dir(PLX_ROOT.$dir)) {
				$this->plxGlob_sources = plxGlob::getInstance(PLX_ROOT.$dir,false,true,'arts');
				foreach ($this->plxGlob_sources->aFiles as $key => $file) {
					$this->getRecords(PLX_ROOT.$dir.$file);
				}
			}
		}
	}

	/**
	 * Méthode qui crée le fichier js pour le pied de page du thème
	 * #origin ThemeEndBody() @Cyril MAGUIRE
	 * @return	$map
	 * @author	Thomas Ingles
	 **/
	public function getJsonFeatures() {
		if ($this->getParam('type') == 1) :#Mode CP
			$this->getAdresses();# Charge les codes postaux à afficher sur la carte ds $this->list
			$showAnnuaire = false;
			$plxMotor = plxMotor::getInstance();#utilisé pour urlRewrite() et adhesion
			if(isset($plxMotor->plxPlugins->aPlugins['adhesion'])) {
				$showAnnuaire = ($plxMotor->plxPlugins->aPlugins['adhesion']->getParam('showAnnuaire') == 'on');
			}
			$adherents = md5($this->list['valides']);
			unset($this->list['valides']);
			$map = $mapFeatures = '';#4 js
			$GPS = array();
			$coordonnees = scandir(PLX_PLUGINS.__CLASS__.'/listing');
			if(!isset($coordonnees[2]) || $coordonnees[2] != $adherents.'.txt') {
				if (isset($coordonnees[2]) ) {
					unlink(PLX_PLUGINS.__CLASS__.'/listing/'.$coordonnees[2]);
				}
				$drawMode = $this->getParam('drawMode');#drawPointsMode 0 line, 1 spiral (fibonacci), 2 dbl spiral
				# lister les coordonnées
				foreach ($this->list as $ville => $marker) {
					if (!empty($ville)) {
						if (!isset($GPS[$ville])) {
							$GPS[$ville] = $this->search(trim(str_replace(array('CEDEX','0','1','2','3','4','5','6','7','8','9'),'',$marker[0]['VILLE'])),$marker[0]['CP']);
						}
#						var_dump($ville,$GPS[$ville]);#exit(__LINE__);
						#Fix : Notice: Undefined index lon
						if(!isset($GPS[$ville]['lon'])) {
							continue;
						}

						$tep = count($marker);
						foreach ($marker as $k => $v) {
							#$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($k*0.0001);#memo #Legacy

							if($drawMode){#cargols
								$angle = 0.1 * $k;#fibonacci spiral js2php of stackoverflow.com/a/6824451
								if($drawMode == 2)
									$angle = 0.1 * $tep * $k;#interesting S : 2 spirals ?

								$x=(1+$angle)*cos($angle);
								$y=(1+$angle)*sin($angle);

								if($drawMode == 1){#clockwise spiral
									$GPS[$ville]['lat'] = $GPS[$ville]['lat']-($x*0.0001);#bottom to top left #clockwise spiral
									$GPS[$ville]['lon'] = $GPS[$ville]['lon']-($y*0.0001);#bottom to top left #clockwise spiral
								}else{#clockwise 2 spirals
									$GPS[$ville]['lat'] = $GPS[$ville]['lat']-($x*0.0001);#bottom to top right* #clockwise 2 spirals
									$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($y*0.0001);#bottom to top right* #clockwise 2 spirals
								}
#								$GPS[$ville]['lat'] = $GPS[$ville]['lat']+($x*0.0001);#top to bottom right
#								$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($y*0.0001);#top to bottom right
#								$GPS[$ville]['lat'] = $GPS[$ville]['lat']+($x*0.0001);#top to bottom left
#								$GPS[$ville]['lon'] = $GPS[$ville]['lon']-($y*0.0001);#top to bottom left
#								$GPS[$ville]['lat'] = $GPS[$ville]['lat']+($y*0.0001);#top to lrg bottom left
#								$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($x*0.0001);#top to lrg bottom left

							}else{#2 lines
								if($k%2){
#									$GPS[$ville]['lat'] = $GPS[$ville]['lat']+($k*0.0001);
									$GPS[$ville]['lon'] = $GPS[$ville]['lon']-($k*0.0002);
								}else{
#									$GPS[$ville]['lat'] = $GPS[$ville]['lat']-($k*0.0001);
									$GPS[$ville]['lon'] = $GPS[$ville]['lon']+($k*0.0002);
								}
							}

							if (!empty($GPS[$ville]) && !empty($GPS[$ville]['lon']) && !empty($GPS[$ville]['lat']) ) {
								if($showAnnuaire){#lien annuaire : ?annuaire.html#00001~ #note: les espaces avant et après les tildes (~) sont importants
									$annu = $plxMotor->urlRewrite('?annuaire.html').'#';
									$v['NOM'] = '<a href=\''.$annu.$v['CP'].'~ '.strtolower($v['NOM']).'\'>'.$v['NOM'].'</a>';
									$v['VILLE'] = '<a href=\''.$annu.' ~'.strtolower($v['VILLE']).'\'>'.$v['VILLE'].'</a>';
								}
								$map .= PHP_EOL.'{"geometry":'.
											'{"type": "Point", "coordinates": ['.$GPS[$ville]['lon'].', '.$GPS[$ville]['lat'].']},'.
											'"type": "Feature","properties": {';
								if ($this->getParam('itemnom') != '') {
									$map .= '"popupContent": "'.$v['VILLE'].' : '.$v['NOM'].'"';
								} else {
									$map .= '"popupContent": "&nbsp;"';
								}
								$map .= '}, "id": '.$v['NUM'].'},';
							}
						}#hcaerof $marker
						if ($map != '') {
							$mapFeatures = 'var geosmjsonFeatures = {"type": "FeatureCollection",'.
											'"features": ['.substr($map, 0,-1)/* del last comma ',' of loop{feature} */.']};'.PHP_EOL;
						}
					}#fi !empty(ville)
				}#hcaerof $infos as $ville => $marker
				if($map) {
					$map = $mapFeatures;#substr($mapFeatures, 0,-2);
					file_put_contents(PLX_PLUGINS.__CLASS__.'/listing/'.$adherents.'.txt', $map);
				}else{#empty coords
					$map = 'var geosmjsonFeatures = {"type": "FeatureCollection","features": []};';
				}
			} else {
				$map = file_get_contents(PLX_PLUGINS.__CLASS__.'/listing/'.$coordonnees[2]);
			}
		else :#Mode COORDS
			# Charge les coordonnées à afficher sur la carte ds $this->list
			$this->getRecords(PLX_ROOT.$this->getParam('source'));

			$map = 'var geosmjsonFeatures = {"type": "FeatureCollection","features": [';#4 js
			# Mise en forme des coordonnées (http://geojson.org/) /!\ coordonnées inversé (LONG in 1st) /!\
			foreach ($this->list as $i => $marker) {
				$map .= PHP_EOL.'{"geometry": {'.
				'"type": "Point", "coordinates": ['.$marker['LONG'].', '.$marker['LAT'].']},'.
				'"type": "Feature","properties": {';
				if ($this->getParam('itemnom') != '') {
					$map .= ' "popupContent": "'.$marker['NOM'].'"';
				} else {
					$map .= ' "popupContent": "&nbsp;"';
				}
				$map .= '}, "id": '.$marker['NUM'].'},';
			}
			$map = substr($map, 0,-1)/* del last comma ',' of loop{feature} */.']};'.PHP_EOL;
		endif;
		return $map;
	}

	/**
	 * Méthode qui ajoute les fichiers js dans le pied de page du thème
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE, Thomas Ingles
	 **/
	public function ThemeEndBody() {
		$map = $this->getJsonFeatures();
echo "\t".
'<script type="text/javascript" src="'.PLX_PLUGINS.__CLASS__.'/leaflet.js?v='.$this->v.'"></script>'."\n".'
<script type="text/javascript">
'.$map.'
	var map = L.map(\'map\').setView(['.$this->getParam('latitude').', '.$this->getParam('longitude').'], '.$this->getParam('zoom').');'."\t
	L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: 'Map data &copy; <a href=\"https://openstreetmap.org\">OpenStreetMap</a> contributors, <a href=\"http://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"openstreetmap.org\">openstreetmap.org</a>',
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
' : '').($map?'
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
