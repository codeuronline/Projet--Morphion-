<?php
/*
Script de morpion avec IA Code inspiré d'un tutoriel 
*/

session_start();

$signe_ordi = 1;
$signe_joueur = 2;

$info_niveau = isset($_GET['niveau']) ? $_GET['niveau'] : '';

// On détermine la difficulté de l'IA
if ($info_niveau == 'facile') // Facile, possibilité de gagner
{
	$niveau_ia = 1;
	$niveau = 'facile';
} elseif ($info_niveau == 'moyen') // Moyen (juste un peu :-) plus dur
{
	$niveau_ia = 2;
	$niveau = 'moyen';
} elseif ($info_niveau == 'difficile') // > Difficile = IA imbattable
{
	$niveau_ia = 5;

	$niveau = 'difficile';
} else {
	$niveau_ia = 5; // Par défaut: IA imbattable
	$niveau = 'difficile';
}

include('header.php');

$temps_debut = microtime(true); // Démarrage du compteur juste avant intervention de l'IA

function afficher_formulaire($jouer = true) // Affiche le formulaire
{
	global $signe_ordi, $signe_joueur, $niveau;

	$formulaire = 'Choisissez la difficulté de l\'ordinateur: ';
	$formulaire .= ($niveau == 'facile') ? '<strong><ins>Facile</ins></strong> ' : '<a href="index.php?niveau=facile">Facile</a> ';
	$formulaire .= ($niveau == 'moyen') ? ' <strong><ins>Moyen</ins></strong> ' : ' <a href="index.php?niveau=moyen">Moyen</a>';
	$formulaire .= ($niveau == 'difficile') ? ' <strong><ins>Difficile</ins></strong>' : ' <a href="index.php?niveau=difficile">Difficile</a>';
	$formulaire .= '<form action="index.php?niveau=' . $niveau . '" method="post"><table>';
	for ($i = 0; $i < 3; $i++) {
		$formulaire .= '<tr>';
		for ($j = 0; $j < 3; $j++) {
			if ($_SESSION['morpion'][$i][$j] === 0) // Case vide
			{
				$nb = 3 * $i + $j;
				$formulaire .= '<td>';

				if ($jouer)
					$formulaire .= '<input name="' . $nb . '" src="blanc.png" type="image" value="submit" align="middle">';

				$formulaire .= '</td>';
			} else // Case occupée
			{
				if ($_SESSION['morpion'][$i][$j] === $signe_joueur) // Remplacement du 1 et 2 par les images X et O
					$sign = '<img src="cercle.png">';
				else
					$sign = '<img src="croix.png">';

				$formulaire .= '<td>' . $sign . '</td>';
			}
		}
		$formulaire .= '</tr>';
	}
	$formulaire .= '</table></form>';

	echo $formulaire;
}

function intelligence_artificielle($profondeur) // Joue le coup
{
	global $signe_ordi, $signe_joueur;

	$maximum = -10000;

	$jeu = $_SESSION['morpion']; // On copie le jeu dans une variable temporaire

	for ($i = 0; $i < 3; $i++) {
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$i][$j] === 0) // La case est libre: on lance l'IA
			{
				$jeu[$i][$j] = $signe_ordi;
				$tmp = valeur_mini($jeu, $profondeur - 1);

				if ($tmp > $maximum) {
					$maximum = $tmp;
					$max_i = $i;
					$max_j = $j;
				}
				$jeu[$i][$j] = 0;
			}
		}
	}

	$_SESSION['morpion'][$max_i][$max_j] = $signe_ordi; // On joue le (meilleur) coup
}

function valeur_maxi($jeu, $profondeur)
{
	global $signe_ordi, $signe_joueur;

	$maximum = -10000;

	if ($profondeur == 0 || gagnant($jeu) != 0)
		return evaluer($jeu);

	for ($i = 0; $i < 3; $i++) {
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$i][$j] === 0) // La case est libre: on lance l'IA
			{
				$jeu[$i][$j] = $signe_ordi;
				$tmp = valeur_mini($jeu, $profondeur - 1);

				if ($tmp > $maximum)
					$maximum = $tmp;

				$jeu[$i][$j] = 0;
			}
		}
	}

	return $maximum;
}

function valeur_mini($jeu, $profondeur)
{
	global $signe_ordi, $signe_joueur;

	$minimum = 10000;

	if ($profondeur == 0 || gagnant($jeu) != 0)
		return evaluer($jeu);

	for ($i = 0; $i < 3; $i++) {
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$i][$j] === 0) // La case est libre: on lance l'IA
			{
				$jeu[$i][$j] = $signe_joueur;
				$tmp = valeur_maxi($jeu, $profondeur - 1);

				if ($tmp < $minimum)
					$minimum = $tmp;
				$jeu[$i][$j] = 0;
			}
		}
	}

	return $minimum;
}

function nb_series($jeu, &$series_j1, &$series_j2, $n = 0) // Compte le nombre de séries de n pions alignés de chacun des joueurs
{
	$series_j1 = 0;
	$series_j2 = 0;

	$compteur1 = 0;
	$compteur2 = 0;

	//Diagonale descendante
	for ($i = 0; $i < 3; $i++) {
		if ($jeu[$i][$i] === 1) {
			$compteur1++;
			$compteur2 = 0;

			if ($compteur1 == $n)
				$series_j1++;
		} else if ($jeu[$i][$i] === 2) {
			$compteur2++;
			$compteur1 = 0;

			if ($compteur2 == $n)
				$series_j2++;
		}
	}

	$compteur1 = 0;
	$compteur2 = 0;

	// Diagonale montante
	for ($i = 0; $i < 3; $i++) {
		if ($jeu[$i][2 - $i] === 1) {
			$compteur1++;
			$compteur2 = 0;

			if ($compteur1 == $n)
				$series_j1++;
		} else if ($jeu[$i][2 - $i] === 2) {
			$compteur2++;
			$compteur1 = 0;

			if ($compteur2 == $n)
				$series_j2++;
		}
	}

	//En ligne
	for ($i = 0; $i < 3; $i++) {
		$compteur1 = 0;
		$compteur2 = 0;

		//Horizontalement
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$i][$j] === 1) {
				$compteur1++;
				$compteur2 = 0;

				if ($compteur1 == $n)
					$series_j1++;
			} else if ($jeu[$i][$j] === 2) {
				$compteur2++;
				$compteur1 = 0;

				if ($compteur2 == $n)
					$series_j2++;
			}
		}

		$compteur1 = 0;
		$compteur2 = 0;

		//Verticalement
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$j][$i] === 1) {
				$compteur1++;
				$compteur2 = 0;

				if ($compteur1 == $n)
					$series_j1++;
			} else if ($jeu[$j][$i] === 2) {
				$compteur2++;
				$compteur1 = 0;

				if ($compteur2 == $n)
					$series_j2++;
			}
		}
	}
}

function evaluer($jeu) // Fonction d'évaluation d'un plateau
{
	global $signe_ordi, $signe_joueur;

	$nb_de_pions = 0;

	//On compte le nombre de pions présents sur le plateau
	for ($i = 0; $i < 3; $i++) {
		for ($j = 0; $j < 3; $j++) {
			if ($jeu[$i][$j] != 0)
				$nb_de_pions++;
		}
	}

	if (gagnant($jeu) != 0) {
		$vainqueur = gagnant($jeu);

		if ($vainqueur == $signe_ordi)
			return 1000 - $nb_de_pions;
		else if ($vainqueur == $signe_joueur)
			return -1000 + $nb_de_pions;
		else
			return 0;
	}

	// On compte le nombre de séries de 2 pions alignés de chacun des joueurs
	$series_j1 = 0;
	$series_j2 = 0;

	nb_series($jeu, $series_j1, $series_j2, 2);

	return $series_j1 - $series_j2;
}

function gagnant($jeu) // Quel est l'état du jeu ?
{
	$j1 = 0;
	$j2 = 0;

	nb_series($jeu, $j1, $j2, 3);

	if ($j1)
		return 1;
	else if ($j2)
		return 2;
	else {
		//Si le jeu n'est pas fini et que personne n'a gagné, on renvoie 0
		for ($i = 0; $i < 3; $i++) {
			for ($j = 0; $j < 3; $j++) {
				if ($jeu[$i][$j] === 0)
					return 0;
			}
		}
	}

	//Si le jeu est fini et que personne n'a gagné, on renvoie 3
	return 3;
}

if (count($_POST) > 0) // On a joué
{
	
	foreach ($_POST as $nombre => $osef)
		//correction apporte pour que l'element défini se rapporte a un chiffre et non une chaine de caractere
		$caseJoueeParJoueur = (preg_match('/_x/', $nombre, $matches)) ? intval(str_replace('_x', '', $nombre)) : intval(str_replace('_y', '', $nombre));

	$i = floor($caseJoueeParJoueur / 3);
	$j = $caseJoueeParJoueur % 3;

	$_SESSION['morpion'][$i][$j] = $signe_joueur;

	$case_joueur = 0;
	$case_IA = 0;

	for ($i = 0; $i < 3; $i++) // On compte chaque case pour chaque joueur
	{
		for ($j = 0; $j < 3; $j++) {
			if ($_SESSION['morpion'][$i][$j] === $signe_joueur)
				$case_joueur++;
			if ($_SESSION['morpion'][$i][$j] === $signe_ordi)
				$case_IA++;
		}
	}

	if ($case_joueur > ($case_IA + 1)) {
		// Protection anti-triche: si le nombre de cases occupées par le joueur est trop important, on arrête la partie
		unset($_SESSION['morpion']); // On détruit les variables de la session
		session_destroy(); // On détruit la session pour le prochain jeu
		echo "On ne triche pas svp :-)<br /><a href='index.php?niveau=$niveau'>Rejouer?</a><br />";
		exit();
	}

	if ($case_joueur + $case_IA == 9) // Dernier tour, le jeu va se finir
	{
		$resultat = gagnant($_SESSION['morpion']); // Récupération des résultats

		afficher_formulaire(false); // Affichage du formulaire

		if ($resultat == 1)
			echo 'Vous avez perdu';
		elseif ($resultat == 2)
			echo 'Vous avez gagné';
		elseif ($resultat == 3)
			echo 'Match nul';

		echo "<br /><a href='index.php?niveau=$niveau'>Rejouer?</a><br />";
	} else // Il reste des coups à jouer
	{
		$resultat = gagnant($_SESSION['morpion']); // Récupération des résultats

		if ($resultat == 2) // Si le joueur a gagné
		{
			afficher_formulaire(false); // Affichage du formulaire
			echo "Vous avez gagné.<br /><a href='index.php?niveau=$niveau'>Rejouer?</a><br />";
			unset($_SESSION['morpion']); // On détruit les variables de la session
			session_destroy(); // On détruit la session pour le prochain jeu
		} else {
			intelligence_artificielle($niveau_ia); // On fait jouer l'IA, la profondeur varie selon le niveau

			$resultat = gagnant($_SESSION['morpion']); // Récupération du résultat

			if ($resultat == 1) // Si l'IA a gagné
			{
				afficher_formulaire(false); // Affichage du formulaire
				echo "Vous avez perdu.<br /><a href='index.php?niveau=$niveau'>Rejouer</a><br />";
				unset($_SESSION['morpion']); // On détruit les variables de la session
				session_destroy(); // On détruit la session pour le prochain jeu
			} else
				afficher_formulaire(); // Affichage du formulaire
		}
	}
} else // Sinon affichage du formulaire
{
	$_SESSION['morpion'] = // On initialise la session
		[
			[0, 0, 0],
			[0, 0, 0],
			[0, 0, 0]
		];
	afficher_formulaire(); // Affichage du formulaire
}

$temps_fin = microtime(true); // Fin du compteur

echo "Temps d'exécution : " . round($temps_fin - $temps_debut, 4) . " secondes";

include('footer.php');