<?php

$LANG = array(
	'L_DRAW_MODE'                        => 'Afficher les points (des adhérents) de la même commune',
	'L_DRAW_MODE_0'                      => 'en lignes',
	'L_DRAW_MODE_1'                      => 'en simple spirales',
	'L_DRAW_MODE_2'                      => 'en doubles spirales',
	'L_DRAW_MODE_TIPS'                   => 'Pour rendre effectif, veuillez purger le dossier &laquo;listing&raquo; après tout changement.',

	'L_MENU_DISPLAY'                     => 'Afficher le menu de la page de localisation',
	'L_STATIC_PAGE_TITLE'                => 'Titre de la page de localisation',
	'L_MENU_TITLE'                       => 'Titre du menu de la page de localisation',
	'L_DEFAULT_MENU_NAME'                => 'Localisation',
	'L_MENU_POS'                         => 'Position du menu',
	'L_MENU_TEMPLATE'                    => 'Template',
	'L_SOURCE_FILE'                      => 'Chemin vers le fichier xml ou le dossier à analyser',

	'L_FILE_TYPE'                        => 'Type de fichier à analyser',
	'L_PC'                               => 'Code postal',
	'L_COORDS'                           => 'Coordonnées',

	'L_EXPORT_BTN'                       => 'Exporter en csv',
	'L_DB2CSV_NOTE'                      => 'Note export',
	'L_DB2CSV_KO'                        => '0 resultat, cette table est vide. vous pouvez fermer cette page',
	'L_DB2CSV_OK'                        => 'resultats exporté en csv',

	'L_COORDS_TIPS'                      => 'Intègre les coordonnées saisies ds la table &laquo;all_town&raquo;',
	'L_COORDS_TITLE'                     => 'Intégrer des coordonnées&nbsp;',
	'L_CP'                               => 'Code postal&nbsp;',
	'L_NOM'                              => 'Nom de la commune&nbsp;',
	'L_LAT'                              => 'Latitude&nbsp;',
	'L_LON'                              => 'Longitude&nbsp;',
	'L_COORDS_BTN'                       => 'Intégrer ces coordonnées',
	'L_COORDS_KO'                        => 'Erreur de coordonnées',
	'L_COORDS_OK'                        => 'Coordonnées intégrées ds la table &laquo;all_town&raquo;',

	'L_CSV_TITLE'                        => 'Importer a partir d\'un fichier csv',
	'L_CSV_MAIN'                         => 'Ajouter les données dans la base de données',
	'L_IMPORT_BTN'                       => 'Importer',
	'L_CSV_FILE'                         => 'Fichier csv',
	'L_CSV_SELECTION'                    => 'Sélectionner un fichier de type csv avec le gestionnaire de medias',
	'L_CSV_PRE_SEL'                      => 'Fichier(s) présent(s) ds le dossier &laquocsv&raquo; du plugin&nbsp;',
	'L_CSV_PRE_SEL_PH'                   => 'Pré sélectionner ce fichier',
	'L_CSV_FILE_TIPS'                    => 'Importe le csv dans la table &laquo;all_towns&raquo;.<br />Les données (de chaque ligne) doivent être séparées par des points virgules<br />et etre organisées ainsi&nbsp;:<br />CP;Nom réel;lat.itude;lon.gitude',
	'L_CSV_KO'                           => 'Erreur de données',
	'L_CSV_OK'                           => 'Les données ont été ajouter a la base de données',

	'L_SOURCE_MAIN_ITEM'                 => 'Item principal du fichier xml',
	'L_SOURCE_ITEM_VILLE'                => 'Item secondaire indiquant la ville',
	'L_SOURCE_ITEM_CP'                   => 'Item secondaire indiquant le code postal',

	'L_SIZE'                             => 'Taille de la carte',
	'L_FRAME_WIDTH'                      => 'Largeur de la carte',
	'L_WIDTH_UNIT'                       => 'Unité de largeur de la carte',
	'L_FRAME_HEIGHT'                     => 'Hauteur de la carte <br/>(obligatoirement en px)',
	'L_FRAME_ZINDEX'                     => 'Z-index de la carte',

	'L_SOURCE_ITEM_LATITUDE'             => 'Item indiquant la latitude',
	'L_SOURCE_ITEM_LONGITUDE'            => 'Item indiquant la longitude',
	'L_SOURCE_ITEM_NOM_COORD_FACULTATIF' => 'Nom de l\'item de localisation (facultatif)',

	'L_POS'                              => 'Localisation de la carte',
	'L_LATITUDE'                         => 'Latitude du point initial',
	'L_LONGITUDE'                        => 'Longitude du point initial',
	'L_FRAME_ZOOM'                       => 'Zoom initial',

	'L_POP_UP'                           => 'Pop-up d\'accueil',
	'L_SHOW_POP_UP'                      => 'Afficher une pop-up d\'accueil',
	'L_POP_UP_LATITUDE'                  => 'Latitude de la pop-up',
	'L_POP_UP_LONGITUDE'                 => 'Longitude de la pop-up',
	'L_POP_UP_TXT'                       => 'Texte de la pop-up <br/>(accepte les balises &lt;b&gt;, &lt;strong&gt;, &lt;u&gt;, &lt;em&gt; ou &lt;a&gt;)',

	'L_OPTIONAL'                         => 'Items facultatifs <br/>(pop-up multiples)',
	'L_SOURCE_ITEM_NOM'                  => 'Items des données à afficher dans la pop-up <br/>(exple:nom)',
	'L_SOURCE_ITEM_VAL'                  => 'Item indiquant si les données sont validées <br/>(exple:validation)',
	'L_DATA_ITEM_VAL'                    => 'Valeur indiquant que les données sont validées <br/>(exple:1)',
	'L_SOURCE_ITEM_COORD'                => 'Items autorisant l\'affichage des données dans la pop-up <br/>(exple:coordonnees)',
	'L_DATA_ITEM_COORD'                  => 'Valeur autorisant l\'affichage des données dans la pop-up (exple:rec)',
	'L_SAVE'                             => 'Enregistrer',
	'L_PURGE_BTN'                        => 'Purger',
	'L_PURGE_DB'                         => 'La table',#(all_)towns
	'L_PURGE_LIST'                       => 'Le dossier',
	'L_DELETED'                          => 'Le fichier %s a été éffacé.',
	'L_PURGED'                           => 'Nettoyage fait.',
	'L_WAIT'                             => 'Patience',
	'L_WAIT1'                            => 'Çela peut-être long',
	'L_WAIT2'                            => 'Et ceci est prévus, surtout avec une grande liste',
	'L_WAIT3'                            => 'Laissez tourner cette page pour y revenir plus tard',
	'L_WAIT4'                            => '#idées&nbsp;: Sortir, lire un livre, se désaltérer ou naviguer avec les autres onglets',

	'L_LOG'                              => 'Log',
	'L_GEO_KO'                           => 'Géo KO',
	'L_GEO_OK'                           => 'Géo OK',
	'L_POG_DB_ERR'                       => 'erreur BDD',
	'L_TEST_ADRESSES_BTN'                => 'Tester les adresses',
	'L_TEST_ADRESSES_TITLE'              => 'Détrminer les adresses en erreur (mauvais nom ou CP)',
#DEV* #medoo update "all_towns_big" with "missing" french postal code #for dev 1 time #memo* maybe never reused (only if all_towns_big #DB & ville_FR.csv #OLD MIXED CSV)
	'L_PC_KO'                            => 'Code postal BIG DATA KO',#*
	'L_PC_OK'                            => 'Code postal BIG DATA OK',#*
	'L_PC_NO'                            => 'Code postal BIG DATA NO',#*
	'L_PC_SV'                            => 'CSV',
	'L_POG_IN_DB'                        => 'déja ds la BDD',#is in DB
	'L_POG_NO_GEO'                       => '0 GEOPOINT',
	'L_POG_NO'                           => 'NON',#NOT
	'L_POG_ADDIN_DB'                     => 'insérées ds la BDD',#added in DB
	'L_POG_TOT'                          => 'Total',
	'L_POG_LNS'                          => 'ligne(s)',
	'L_POG_BAD'                          => 'données inadaptées',
	'L_CSV_BAD_LINE'                     => 'Ligne vide ou inadaptée (par ex: titres des colonnes',
	'L_VIEW_HIDE'                        => 'Voir/Cacher&nbsp;',
#exemple
	'L_SMPL_SRC'                         => 'Exemples de chemin vers le fichier xml source',
	'L_SMPL_FILE'                        => 'Exemples de fichier xml source',
	'L_SMPL_TYPE_CP'                     => 'Type Code postal',
	'L_SMPL_TYPE_COORDS'                 => 'Type Coordonnées',
	'L_SMPL_TXT1'                        => 'Les trois items les plus importants sont ceux surlignés en rouge.',
	'L_SMPL_TXT2'                        => 'Dans le premier exemple ci-dessus, l\'item principal est',
	'L_SMPL_TXT3'                        => 'Les items secondaires sont',
	'L_SMPL_TXT4'                        => 'Vous pouvez avoir des noms différents mais vous devez conserver la structure de base (constituée par les 3 items rouges).',
);
?>