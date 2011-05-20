=== Canalblog Importer ===
Contributors: oncletom
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=752034
Tags: canalblog, wordpress, migration, import, admin, importer
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.2.3


Fatigué(e) d'avoir à gérer un blog sur Canalblog ? Cette extension va vous permettre de TOUT récupérer en quelques clics.


== Description ==

**Ne fonctionne(ra) pas sur un hébergement Windows**

Cette extension récupère tout votre contenu public de n'importe quel blog Canalblog. Il faut juste un peu de patience pendant que tout se fait tout seul.

**Ce qui est rappatrié**

 * Les articles
 * Les catégories
 * Les mots-clefs
 * Les médias
 * Les commentaires
 * Beaucoup d'heures de votre travail
 
En bonus, si vous avez lié vos articles entre eux sur Canalblog, l'outil va corriger les liens pour qu'ils pointent vers leur nouvelle adresse. La classe.

**Ce qui n'est pas rappatrié**

 * Votre liste de liens amis
 * Le référencement
 * L'absence de réponses de Canalblog à vos questions


**Traductions intégrées**

* Anglais
* Français
* Biélorusse (par [Marcis G.](http://pc.de/))

**Remarque**

Cette extension nécessite au minimum PHP 5.1. Si vous avez un bon hébergeur, vous n'aurez même pas besoin d'y penser.
Sinon vous verrez plein d'erreurs et rien ne fonctionnera.

== Installation ==

L'installation se fait de manière on ne peut plus classique :

1. Uploadez le plugin dans votre répertoire `wp-content/plugins` ou cherchez-le depuis l'outil d'installation de WordPress
1. Activez-le depuis votre interface d'administration
1. Rendez-vous dans la rubrique Outils > Importer

Encore quelques clics et ça sera terminé !


== Changelog ==
= Version 1.2.3 =

* désactivation automatique du plugin WordPress Importer si nécessaire

= Version 1.2.2 =

* correction liée à la manière dont WordPress 3.1 gère les imports
* correction du nombre de billets importés

= Version 1.2.1 =

* amélioration de la détection des billets de blog
* correction d'une division par zéro
* dédoublonnage des permaliens à inspecter (accélère l'import)
* nettoyage du code

= Version 1.2 =

* amélioration de l'import : vous voyez tout ce qui se passe

= Version 1.1.7 =

* contournement de la pagination boguée de Canalblog (se bloquant à 5 articles)

= Version 1.1.6 =

* correction d'un bug de récupération des articles depuis les changements de Canalblog en février 2011

= Version 1.1.5 =

* correction d'une erreur de détection de média (props [lacath](http://www.lacath.com/))

= Version 1.1.4 =

* ajout de la traduction biélorusse par [Marcis G.](http://pc.de/)
* compatibilité avec WordPress 3.0
* nécessite l'installation de l'importeur WordPress pour récupérer les images de votre blog Canalblog

= Version 1.1.3 =

 * bridage de l'extension pour Windows (fonctions indisponibles)
 * amélioration de la vérification de la présence d'un commentaire dans la corbeille (provoquait une erreur dans certains cas)

= Version 1.1.2 =

 * correction d'un bout de code restant et gênant lors de l'import

= Version 1.1.1 =

 * correction de l'import des archives, qui ne prenait qu'un article par mois

= Version 1.1 =

 * possibilité de forcer l'import du contenu (pratique pour corriger le problème des accents sans avoir à tout réinstaller)
 * correction du bug des statuts des commentaires/rétroliens
 * ajout de traductions manquantes
 * les articles/commentaires mis dans la corbeille sont automatiquement restaurés

= Version 1.0.3 =

 * gestion des coupures de connexion avec Canalblog (5 tentatives)
 * meilleure gestion des thèmes exotiques
 * correction d'encodage (problème avec les caractères accentués) − merci à **ricola** pour le coup de pouce

= Version 1.0.2 =

 * détection de la présence de PHP5 afin d'éviter des erreurs peu sympathiques
 * traduction de la description de l'extension sur la liste des extensions
 * ajout d'un raccourci d'import dans la liste des extensions − une fois activé
 * correction de l'import des commentaires : ça devrait fonctionner à tous les coups

= Version 1.0.1 =

 * correction d'un bug stoppant l'import sur des mois particulièrement chargés en archives
 * prise en compte de l'ancienne structure de médias de Canalblog, pour les blogs datant d'avant juin 2006

= Version 1.0 =

 * version initiale, avec tout ce qu'il faut pour devenir autonome


== Screenshots ==

1. Écran d'accueil
2. Une fois terminé, c'est cet écran que vous verrez
