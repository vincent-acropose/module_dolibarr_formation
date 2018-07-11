<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');
dol_include_once('/formation/class/rh.class.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

// Init Classes
$rhManager = new Rh($db);
$object = new Formation($db);

// Get Action
$action = GETPOST('action');

// Init Hook
$hookmanager->initHooks(['formationcard', 'globalcard']);

// DEFAULT TRAININGS
$trainings = [];

// DEFAULT FILTERS
$filters = [
	'user' => -1
	,'beginYear' => 0
	,'finishYear' => 0
	,'statut' => -1
];

/*
 * Actions
 */
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{
	switch ($action) {
		case 'getStats':

			// GET FILTERS
			$filters = [
				'user' => GETPOST('userFilter')
				,'beginYear' => GETPOST('beginYearFilter')
				,'finishYear' => GETPOST('finishYearFilter')
				,'statut' => GETPOST('statutFilter')
			];

			// Legend
			$filters['beginYear'] == $filters['finishYear'] ? $legend = (int)$filters['beginYear'] : $legend = "De ".$filters['beginYear']." à ".$filters['finishYear'];

			// Get Trainings
			$trainings = $object->getTrainings($filters);

			// Graphs
			$stats = $object->getStats($trainings);

			$px1 = $object->getGraph(1, $stats['sumPerMonth'], $legend);
			$px2 = $object->getGraph(2, $stats['nbPerMonth'], $legend);

            $object->createStatCSV($trainings['year'], $filters['user']);
			break;
	}

	$object->displayErrors();
}

				
/**
 * View
 */
$form = new Form($db);
$formfile = new FormFile($db);

$title=$langs->trans("Statistics");
llxHeader('',$title);


// HEADER
print '<div class="fichecenter">';

print '<div class="fichehalfleft">';
print '<table class="centpercent notopnoleftnoright">';
print '<tbody>';


	// TITLE
	print '<tr>';
	print '<td class="nobordernopadding widthpictotitle" valign="middle"><img src="/dolibarr/htdocs/theme/eldy/img/title_generic.png" alt="" title="" class="valignmiddle" id="pictotitle"></td>';
	print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans("StatisticsOfTraining").'</div></td>';
	print '</tr>';
	// END TITLE


print '</tbody>';
print '</table>';
print '</div>';

print '</div>';
// END HEADER


// CONTAIN
print '<div class="fichecenter">';


	// FILTERS & DATAS
	print '<div class="fichehalfleft">';

		// FILTERS
		print '<form method="POST">';
		print '<input type="hidden" name="action" value="getStats">';
		print '<table class="centpercent noborder" style="margin-top: 20px;">';
		print '<tbody>';


			// TITLES 
			print '<tr class="liste_titre">';
			print '<th class="liste_titre" colspan="3">'.$langs->trans("Filter").'</th>';
			print '</tr>';
			// END TITLES 


			// YEARS
			print '<tr>';
			print '<td align="left">'.$langs->trans("Year").'</td>';
			print '<td align="left">De'.$object->selectYear("beginYearFilter", $filters['beginYear']).'</td>';
			print '<td align="left">&#192; '.$object->selectYear("finishYearFilter", $filters['finishYear']).'</td>';
			print '</tr>';
			// End YEARS


			// STATUT
			print '<tr>';
			print '<td align="left">'.$langs->trans("Status").'</td>';
			print '<td align="left" colspan="2">'.$object->selectStatut("statutFilter", $filters['statut']).'</td>';
			print '</tr>';
			// END STATUT


			// USER
			print '<tr>';
			print '<td align="left">'.$langs->trans("Employee").'</td>';
			print '<td align="left" colspan="2">'.$form->select_dolusers($filters['user'], "userFilter", 1).'</td>';
			print '</tr>';

			print '<tr>';
			print '<td align="center" colspan="3"><input type="submit" class="button" value='.$langs->trans("Refresh").'></td>';
			print '</tr>';
			// END USER


		print '</tbody>';
		print '</table>';
		print '</form>';
		// END FILTERS


		print '<div class="clearboth"></div>';


		// DATAS
		print '<table class="centpercent noborder" style="margin-top: 20px;">';
		print '<tbody>';


			// DEFAULT DATAS
			if (empty($trainings)) {

				// TITLES
				print '<tr class="liste_titre">';
				print '<th class="liste_titre" align="center">'.$langs->trans("Date").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("TrainingCost").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("EmployeeCost").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("Help").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("TotalCost").'</th>';
				print '</tr>';
				// END TITLES


				// TRAININGS PER YEAR
				foreach ($object->getYear() as $year) {
					// Default filters
					$filters['beginYear'] = $year['year'];
					$filters['finishYear'] = $year['year'];
					$sums = $object->getSums($filters);

					print '<tr class="oddeven">';
					print '<td align="center">'.$year['year'].'</td>';
					print '<td align="right">'.number_format($sums['total_ht'], 2, ',', '').' €</td>';
					print '<td align="right">'.number_format($sums['collab'], 2, ',', '').' €</td>';
					print '<td align="right">'.number_format($sums['help'], 2, ',', '').' €</td>';
					print '<td align="right">'.number_format($sums['reste'], 2, ',', '').' €</td>';
					print '</tr>';

					$stats["sumPerYear"][] = [$year['year'], 0, $sums['total_ht']];
					$stats["nbPerYear"][] = [$year['year'], 0, $sums['nbTotal']];
				}
				// END TRAININGS PER YEAR


				// DEFAULT GRAPHS
				$px1 = $object->getGraph(1.1, $stats['sumPerYear'], "test");
				$px2 = $object->getGraph(2.1, $stats['nbPerYear'], "test");


			}
			// END DEFAULT DATAS


			// FILTERED DATAS
			else {


				// TITLES
				print '<tr class="liste_titre">';
				print '<th class="liste_titre" align="center">'.$langs->trans("Training").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("Date").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("TrainingLabel").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("Employee").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("TrainingCost").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("EmployeeCost").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("Help").'</th>';
				print '<th class="liste_titre" align="center">'.$langs->trans("TotalCost").'</th>';
				print '</tr>';
				// END TITLES


				// FILTERED TRAININGS


					// NO TRAINING
					if ($trainings == -1) {

						print '<tr class="oddeven">';
						print '<td align="center" colspan="7">'.$langs->trans('NoTraining').'</td>';
						print '</tr>';

					}
					// END NO TRAINING


					// TRAININGS FOUND
					else {
						$total = 0; // Calcul du coût total de formation sur la période donnée
						foreach ($trainings['year'] as $training) {
							$subTotal = 0; // Calcul des coût totaux par formation

								if (empty($training->users)) {
									$total_reste = $training->total_reste; // Calcul du reste à payer

											print '<tr class="oddeven">';
											print '<td align="center">'.$training->getNomUrl(1).'</td>';
											print '<td align="center">'.date("d/m/Y", strtotime($training->dated)).'</td>';
											print '<td align="center">'.$training->label.'</td>';
											print '<td align="center">-</td>';
											print '<td align="right">'.number_format($training->total_ht, 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($training->total_salariale, 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($training->help, 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($total_reste, 2, ',', '').' €</td>';
											print '</tr>';

											$subTotal += $total_reste; // Ajout du reste à payer sur la formation
								}

								else {
									foreach ($training->users as $trainingUser) {
										if ($filters['user'] == -1 || ($filters['user'] != -1 && $filters['user'] == $trainingUser->id)) {
											$total_reste = $training->total_ht/sizeof($training->users) + $rhManager->get("salary", $trainingUser->id)->salary*$training->duration - $training->help/sizeof($training->users); // Calcul du reste à payer

											print '<tr class="oddeven">';
											print '<td align="center">'.$training->getNomUrl(1).'</td>';
											print '<td align="center">'.date("d/m/Y", strtotime($training->dated)).'</td>';
											print '<td align="center">'.$training->label.'</td>';
											print '<td align="center">'.$trainingUser->login.'</td>';
											print '<td align="right">'.number_format($training->total_ht/sizeof($training->users), 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($rhManager->get("salary", $trainingUser->id)->salary*$training->duration, 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($training->help/sizeof($training->users), 2, ',', '').' €</td>';
											print '<td align="right">'.number_format($total_reste, 2, ',', '').' €</td>';
											print '</tr>';

											$subTotal += $total_reste; // Ajout du reste à payer sur la formation
										}
									}
								}

							$total += $subTotal;

							print '<tr class="liste_total">';
							print '<td align="center">'.$langs->trans('SousTotal').'</td>';
							print '<td colspan="7" align="right">'.number_format($subTotal, 2, ',', '').' €</td>';
							print '</tr>';
							print '<tr class="oddeven">';
							print '<td colspan="8"></td>';
							print '</tr>';
						}

						print '<tr class="liste_total">';
						print '<td align="center">'.$langs->trans('Total').'</td>';
						print '<td colspan="7" align="right">'.number_format($total, 2, ',', '').' €</td>';
						print '</tr>';

					}

					print '<tr class="oddeven">';
					print '<td align="center" colspan="8">';
					print '<a href='.dol_buildpath('formation/documents/Stats.csv', 1).'><button class="button">'.$langs->trans("CSV").'</button></a>';
					print '</td>';
					print '</tr>';
					// END TRAININGS FOUND
				

				// END FILTERED TRAININGS


			}
			// END FILTERED DATAS

		print '</tbody>';
		print '</table>';
		// END DATAS


	print '</div>';
	// END FILTERS & DATAS


	// GRAPHICS
	print '<div class="fichehalfright text-center">';
	print '<table class="centpercent border" style="margin-top: 20px;">';
	print '<tbody>';

	print '<tr><td align="center">'.$px2 ->show().'</td></tr>';
	print '<tr><td align="center">'.$px1->show().'</td></tr>';

	print '</tbody>';
	print '</table>';
	print '</div>';
	// END GRAPHICS


print '</div>';
// END CONTAIN


dol_fiche_end();

llxFooter();