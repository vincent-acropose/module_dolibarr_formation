<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/class/rh.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

$action = GETPOST('action');
$idUser = GETPOST('id', 'int');

$object = new User($db);
$object->fetch($idUser);

$rhManager = new Rh($db);

// Action
switch ($action) {
	case 'edit':
		$salary = GETPOST('salary');
		if($rhManager->setSalary($object->id, $salary)) {
			header('Location: '.dol_buildpath('/formation/rh.php', 1).'?id='.$object->id);
			exit;
		}
		else {
			setEventMessage("Problème rencontré lors de la modification du salaire", "errors");
		}

		break;
}

// Vue
llxHeader('',$langs->trans("RHCard"));
$head = user_prepare_head($object);
$picto = 'user';

dol_fiche_head($head, 'rh', $langs->trans("User"), -1, $picto);

if ($action == "modify") {
	print '<form method=POST action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'">';
	print '<input type=hidden name="action" value="edit">';
	print '<table class="border" width="100%">';

	// Salary
	print '<tr><td class="titlefieldcreate">' . $langs->trans('Salary') . '</td><td><input type=text name="salary" value=' . $rhManager->getSalary($object->id)->salary . '></td></tr>';

	print "</table>";
	print '<div class="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';
	print '</form>';
}

else {
	$linkback = '<a href="'.dol_buildpath('/user/index.php', 1).'">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';
	print '<tbody>';
	print '<tr>';

	print '<td class="titlefield">'.$langs->trans('Salary').'</td>';
	print '<td>'.$rhManager->getSalary($object->id)->salary.'</td>';

	print '</tr>';
	print '</tbody>';
	print '</table>';

	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction">';
	print '<a class="butAction" href="'.dol_buildpath('/formation/rh.php', 1).'?id='.$object->id.'&action=modify">'.$langs->trans('Modify').'</a>';
	print '</div>';
	print '</div>';

	print '</div>';
	print '</div>';
}

?>