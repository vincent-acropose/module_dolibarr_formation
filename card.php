<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_user = GETPOST('fk_user');
$fk_product = GETPOST('fk_product');

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
	$error = 0;
	switch ($action) {

		case 'create':
			if (GETPOST('create') == "newTraining") {
				$object->set_values($_REQUEST); // Set standard attributes
				$object->save($action);

				if (!empty($object->errors)) {
					setEventMessages($object->errors, "", 'errors');
				}
				else {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
					exit();
				}
			}
			
			break;

		case 'confirm_clone':
			if (!empty($user->rights->formation->write)) $clone = $object->clone();
			
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$clone->id);
			exit;
			break;
		case 'confirm_valid':
			if (!empty($user->rights->formation->write)) $object->setValid();

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;
		case 'confirm_delete':
			if (!empty($user->rights->formation->write)) $object->delete();
			
			header('Location: '.dol_buildpath('/formation/list.php', 1));
			exit;
			break;
		case 'confirm_prediction':
			if (!empty($user->rights->formation->write)) $object->setPredict();

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'confirm_finish':
			if (!empty($user->rights->formation->write)) $object->setFinish();

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'confirm_cancel':
			if (!empty($user->rights->formation->write)) $object->setCancel();

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			break;
		// link from llx_element_element
		case 'dellink':
			$object->generic->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'edituser':
			setEventMessages('Edit de l\'utilisateur');
			break;
	}
}
				
/**
 * View
 */
$form = new Form($db);
$text=$langs->trans('Validate');

$title=$langs->trans("formation");
llxHeader('',$title);

// Fiche d'une formation
if ($id > 0) {

	$fk_user = new User($db);
	$fk_user->fetch($object->fk_user);

	$product = new Product($db);
	$product->fetch($object->fk_product);

	$head = formation_prepare_head($object);
	$picto = 'formation@formation';
	dol_fiche_head($head, 'card', $langs->trans("Training"), 0, $picto);

	$linkback = '<a href="'.dol_buildpath('/formation/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
	$shownav = 1;

    $object->next_prev_filter="";

    $morehtmlstatus = $object->getLibStatut();

    dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid', 'ref', '', '', 0, '', $morehtmlstatus);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans('Ref');
    print '</td>';
    print '<td colspan="2">'.$object->getNomUrl(1).'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('RefTraining');
    print '<a class="notinparentview quickEditButton" href="#" onclick="quickEditField(5433,this)" style="float:right"><img src="/dolibarr/htdocs/theme/md/img/edit.png" alt="" title="Modifier" class="pictoedit"></a>';
    print '</td>';
    print '<td colspan="2">'.$product->getNomUrl(1).'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('TrainingDate');
    print '<a class="notinparentview quickEditButton" href="#" onclick="quickEditField(5433,this)" style="float:right"><img src="/dolibarr/htdocs/theme/md/img/edit.png" alt="" title="Modifier" class="pictoedit"></a>';
    print '</td>';
    print '<td colspan="2">'.$object->dated.'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('DalayH');
    print '<a class="notinparentview quickEditButton" href="#" onclick="quickEditField(5433,this)" style="float:right"><img src="/dolibarr/htdocs/theme/md/img/edit.png" alt="" title="Modifier" class="pictoedit"></a>';
    print '</td>';
    print '<td colspan="2">'.$object->delayH.'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('DelayD');
    print '<a class="notinparentview quickEditButton" href="#" onclick="quickEditField(5433,this)" style="float:right"><img src="/dolibarr/htdocs/theme/md/img/edit.png" alt="" title="Modifier" class="pictoedit"></a>';
    print '</td>';
    print '<td colspan="2">'.$object->delayD.'</td></tr>';

    print '</table>';

    print '</div>';

    print '<div class="fichehalfright">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans('PriceP').'</td>';
    print '<td colspan="2">'.$object->total_ht.'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('PriceS').'</td>';
    print '<td colspan="2">'.$object->total_peda.'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('PriceT').'</td>';
    print '<td colspan="2">0</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('Help').'</td>';
    print '<td colspan="2">'.$object->help.'</td></tr>';

    print '<tr><td class="titlefield">'.$langs->trans('InCharge').'</td>';
    print '<td colspan="2">0</td></tr>';

    print '</table>';

    print '</div></div>';

    print '<div class="clearboth"></div>';

    print '<div class="tabsAction">';

    switch ($object->status) {
    	case $object::STATUS_DRAFT:
    		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=valid">'.$langs->trans('Validate').'</a></div>';
    		break;
    	
    	case $object::STATUS_VALIDATED:
  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=prediction">'.$langs->trans('Predict').'</a></div>';
    		break;

    	case $object::STATUS_PREDICTION:
  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=finish">'.$langs->trans('Finish').'</a></div>';
  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=cancel">'.$langs->trans('Cancel').'</a></div>';
    		break;

    	case $object::STATUS_FINISH:
  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=reopen">'.$langs->trans('Reopen').'</a></div>';
    		break;

    	case $object::STATUS_CANCEL:
  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=reopen">'.$langs->trans('Reopen').'</a></div>';
    		break;
    }
	
	print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=clone">'.$langs->trans('Cloner').'</a></div>';
	print '<div class="inline-block divButAction"><a class="butAction butActionDelete" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=del">'.$langs->trans('Delete').'</a></div>';


    print '</div>';

    print '<div class="clearboth"></div>';

    switch ($action) {

		case "valid":
			$newref = $object->getNumero();

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateTraining'), $langs->trans('ConfirmValidate')." ".$newref, 'confirm_valid', '', 0, 1);
			print $formconfirm;

			break;

		case "clone":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloneTraining'), $langs->trans('ConfirmClone')." ".$object->ref, 'confirm_clone', '', 0, 1);
			print $formconfirm;

			break;

		case "del":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeletreTraining'), $langs->trans('ConfirmDelete')." ".$object->ref, 'confirm_delete', '', 0, 1);
			print $formconfirm;

			break;

		case "prediction":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('PredictTraining'), $langs->trans('ConfirmPrediction')." ".$object->ref, 'confirm_prediction', '', 0, 1);
			print $formconfirm;

			break;

		case "finish":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('FinishTraining'), $langs->trans('ConfirmFinish')." ".$object->ref, 'confirm_finish', '', 0, 1);
			print $formconfirm;

			break;

		case "cancel":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CancelTraining'), $langs->trans('ConfirmCancel')." ".$object->ref, 'confirm_cancel', '', 0, 1);
			print $formconfirm;

			break;

		case "reopen":
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReopenTraining'), $langs->trans('ConfirmReopen')." ".$object->ref, 'confirm_prediction', '', 0, 1);
			print $formconfirm;

			break;

	}

	dol_fiche_end();
}
// Création
else {
	if ($action == 'create' && $mode == 'edit')
	{
		print load_fiche_titre($langs->trans("Newformation"));
		dol_fiche_head();

		print '<form name="addprop" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="create" value="newTraining">';
		print '<table class="border" width="100%">';
		// Ref new object
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $langs->trans("Draft") . '</td></tr>';
		// Ref training product
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('RefTraining') . '</td><td>';
		print $form->select_produits('', 'fk_product', 1,20, 0, 1, 2, '', 1);
		// reload page to retrieve customer informations
		if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
		{
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#fk_product").change(function() {
					var fk_product = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&fk_product="+fk_product).val();
				});
			});
			</script>';
		}
		print '</td></tr>';

		// Date
		print '<tr><td>' . $langs->trans('DateTraining') . '</td><td>';
		$form->select_date('', 'dated', '', '', '', "addprop", 1, 1);
		print '</td></tr>';
		print "</table>";

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("CreateDraft") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
		print '</div>';

		print "</form>";
	}

	else
	{
		$head = formation_prepare_head($object);
		$picto = 'generic';
		dol_fiche_head($head, 'card', $langs->trans("formation"), 0, $picto);
	}
}

llxFooter();