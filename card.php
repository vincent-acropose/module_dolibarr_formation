<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');

$mode = 'view';
if (empty($user->rights->formation->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$PDOdb = new TPDOdb;
$object = new Tformation;

if (!empty($id)) $object->load($PDOdb, $id);
elseif (!empty($ref)) $object->loadBy($PDOdb, $ref, 'ref');

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
		case 'save':
			$object->set_values($_REQUEST); // Set standard attributes
			
//			$object->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($object->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}
			
			// ... 
			
			if ($error > 0)
			{
				$mode = 'edit';
				break;
			}
			
			$object->save($PDOdb, empty($object->ref));
			
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->getId());
			exit;
			
			break;
		case 'confirm_clone':
			$object->cloneObject($PDOdb);
			
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'modif':
			if (!empty($user->rights->formation->write)) $object->setDraft($PDOdb);
				
			break;
		case 'confirm_validate':
			if (!empty($user->rights->formation->write)) $object->setValid($PDOdb);
			
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->getId());
			exit;
			break;
		case 'confirm_delete':
			if (!empty($user->rights->formation->write)) $object->delete($PDOdb);
			
			header('Location: '.dol_buildpath('/formation/list.php', 1));
			exit;
			break;
		// link from llx_element_element
		case 'dellink':
			$object->generic->deleteObjectLinked(null, '', null, '', GETPOST('dellinkid'));
			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->getId());
			exit;
			break;
	}
}


/**
 * View
 */

$title=$langs->trans("formation");
llxHeader('',$title);

if ($action == 'create' && $mode == 'edit')
{
	load_fiche_titre($langs->trans("Newformation"));
	dol_fiche_head();
}
else
{
	$head = formation_prepare_head($object);
	$picto = 'generic';
	dol_fiche_head($head, 'card', $langs->trans("formation"), 0, $picto);
}

$formcore = new TFormCore;
$formcore->Set_typeaff($mode);

$form = new Form($db);

$formconfirm = getFormConfirm($PDOdb, $form, $object, $action);
if (!empty($formconfirm)) echo $formconfirm;

$TBS=new TTemplateTBS();
$TBS->TBS->protect=false;
$TBS->TBS->noerr=true;

if ($mode == 'edit') echo $formcore->begin_form($_SERVER['PHP_SELF'], 'form_formation');

$linkback = '<a href="'.dol_buildpath('/formation/list.php', 1).'">' . $langs->trans("BackToList") . '</a>';
print $TBS->render('tpl/card.tpl.php'
	,array() // Block
	,array(
		'object'=>$object
		,'view' => array(
			'mode' => $mode
			,'action' => 'save'
			,'urlcard' => dol_buildpath('/formation/card.php', 1)
			,'urllist' => dol_buildpath('/formation/list.php', 1)
			,'showRef' => ($action == 'create') ? $langs->trans('Draft') : $form->showrefnav($object->generic, 'ref', $linkback, 1, 'ref', 'ref', '')
			,'showLabel' => $formcore->texte('', 'label', $object->label, 80, 255)
//			,'showNote' => $formcore->zonetexte('', 'note', $object->note, 80, 8)
			,'showStatus' => $object->getLibStatut(1)
		)
		,'langs' => $langs
		,'user' => $user
		,'conf' => $conf
		,'Tformation' => array(
			'STATUS_DRAFT' => Tformation::STATUS_DRAFT
			,'STATUS_VALIDATED' => Tformation::STATUS_VALIDATED
			,'STATUS_REFUSED' => Tformation::STATUS_REFUSED
			,'STATUS_ACCEPTED' => Tformation::STATUS_ACCEPTED
		)
	)
);

if ($mode == 'edit') echo $formcore->end_form();

if ($mode == 'view' && $object->getId()) $somethingshown = $form->showLinkedObjectBlock($object->generic);

llxFooter();