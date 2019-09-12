#idées : "M.A.J. des données" de la BDD par CSV #tools #param

## 1.2.1 - 12/09/2019 ##
[+] Compatible PluXml 5.7
[*] info.xml
	<compatible>5.2+</compatible>
	<scope></scope>
[+] Config : param z-index
[+] Variables LANGS augmentées
[+] Licence, contrib & readme.md
[+] Nominatim funk + curl method
[+] config : Les url "action" codés en dur du formulaire enlevés & les "header location" modifiés
Fix adhesion v2.2.x : Aucun point affichés #Oups
Fix adhesion v2.2.x : Absence du menu localisation
Fix adhesion v2.2.x : adherent "refus" visibles
Fix adhesion v2.2.x : Points (des communes) exponentiels
[+] adhesion v2.2.2 : si showAnnuary : affiche ds les bulles les liens d'accés a l'annuaire (recherche préremplis par adhésion : url# ~NomDeCommune OU url#codepostal~nom)
[+] config: Type code postal: Nouveau param: Dessiner les points des mêmes communes: en ligne, en spirale de fibonacci, en 2 spirale
:+: page statique: Affiche les points des mêmes communes: en ligne, en spirale de fibonacci, en 2 spirales
[+] config: Type code postal: Nouveaux outils
#BDD: Purge*, (ex|im)port #CSV ::: db2csv()*, csv2db*
#TEST Adresses : verif & journalise les cp + nom
::: Permet de découvrir quelles sont les villes en erreurs :
::: +Lorsque des adhérents inscrits ds adhesion remplissent une commune ou un code postal érroné (ou les deux)
::: +Donc impossible de découvrir ses géoPoints avec la BDD* ou Nominatim ;)
::: +Retourne une liste qui affiche les point (a) vérifiés.


* Purger permet de la vidé
* La base de données sqlite "all_towns" a été revue
* Les tables vidée (Purgée) afin de réduire le poids de l'archive
* La table "all_towns" a été minifiée au niveau de ses champs et est utilisé lors des recherches,
:::est préremplis avec les données des deux CSV (45,863 communes triées)**
* Il est possible:
* d'exporter les tables en csv : ordonnées par cp & nom
* d'importer des données a partir d'un fichier csv de volume important ds cette table,
::: Juste des insert, aucune "M.A.J. des données" existante (cp+nom) de la BDD par CSV #PLM #idée #tools #param (utiliser adminer)[https://www.adminer.org/]
* d'ouvrir les "Médias" pour compléter le champs et avoir la possibilité de les téléversés
* d'utiliser les fichiers du dossier csv listés sous "Importer"


** voici le résumé des imports (laposte+ville_FR):
csv/laposte_hexasmal.csv ===> csv/all_towns_laposte_38931_triees.csv
38931 insérées ds la BDD / Total : 39202 ligne(s) (4508s)
+
csv/villes_FR.csv
6964 insérées ds la BDD
28287 déja ds la BDD
Total : 35251 ligne(s) (1249s)
=
45,863 communes triées
csv/all_towns_laposte+villes_45,863_triees.csv
