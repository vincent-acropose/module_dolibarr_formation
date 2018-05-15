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

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$collaborator = GETPOST('user');
$yearbFilter = GETPOST('yearb');
$yearfFilter = GETPOST('yearf');
$statusFilter = GETPOST('status');

$rhManager = new Rh($db);

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

$trainings = [];
$trainingsLast = [];

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

	switch ($action) {
		case 'getStats':

			$trainings = $object->getTrainingByDate($yearbFilter, $yearfFilter, $collaborator, $statusFilter);
            $trainingsLast = $object->getTrainingByDate(($yearbFilter-1), $yearbFilter-1, $collaborator, $statusFilter);

            $object->createStatCSV($trainings, $collaborator);

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

// Graphs
if ($yearbFilter == $yearfFilter) {
	$px1 = $object->getGraph(1, $trainings, $trainingsLast, $yearbFilter);
	$px2 = $object->getGraph(2, $trainings, $trainingsLast, $yearbFilter);
}
else {
	$px1 = $object->getGraph(1, $trainings, $trainingsLast, "De ".$yearbFilter." à ".$yearfFilter);
	$px2 = $object->getGraph(2, $trainings, $trainingsLast, "De ".$yearbFilter." à ".$yearfFilter);
}


// Fiche statistiques
include('tpl/statistics.tpl.php');

dol_fiche_end();

llxFooter();