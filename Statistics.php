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
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$collaborator = GETPOST('user');
$year = GETPOST('year');
$trainingCost = 0;
$collabCost = 0;
$help = 0;
$total = 0;

$mode = 'view';
if (empty($user->rights->formation->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Formation($db);

if (!empty($id)) $object->fetch($id);

$hookmanager->initHooks(array('formationcard', 'globalcard'));

/*
 * Actions
 */
$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	switch ($action) {
		case 'getStats':

			if ($collaborator != -1) {
				$trainings = $object->getTrainingByDate($year, $collaborator);
				foreach ($trainings as $training) {
					$trainingCost += $training->total_ht/sizeof($training->users);
					$collabCost += $training->users[$collaborator]->array_options['options_salaire']*$training->duration;
					$help += $training->help/sizeof($training->users);
				}
				$total += $trainingCost+$collabCost-$help;
			}
			elseif ($collaborator == -1) {
				$trainings = $object->getTrainingByDate($year);
				foreach ($trainings as $training) {
					$trainingCost += $training->total_ht;
					$collabCost += $training->total_salariale;
					$help += $training->help;
					$total += $training->total_reste;
				}
			}
			else {
				$object->errors = "Impossible de récupérer les données.";
				break;
			}

			$object->createStatCSV($year, $collaborator, $trainingCost, $collabCost, $total);

			break;

		default:

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

// Fiche statistiques
include('tpl/statistics.tpl.php');

dol_fiche_end();

llxFooter();