<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('formation@formation');

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref');
$fk_product = GETPOST('fk_product');
$document=GETPOST('document');

$upload_dir = DOL_DATA_ROOT.'/formation';

$mode = 'view';
if (empty($user->rights->formation->write)) $mode = 'view'; // Force 'view' mode if can't edit object
else if ($action == 'create' || $action == 'edit') $mode = 'edit';

$object = new Formation($db);

if (!empty($id)) $object->fetch($id);

if ($object->fk_product_fournisseur_price) {
	$fournisseur = new Fournisseur($db);
	$fournisseur->fetch($object->fk_product_fournisseur_price->fourn_id);
}

$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',"","",1);
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
		case 'create':
			if (GETPOST('create') == "newTraining") {
				$object->set_values($_REQUEST); // Set standard attributes
				$object->create();

				if (empty($object->errors)) {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
					exit();
				}
			}
			
			break;

		case 'edit':
			if (GETPOST('edit') == "edit") {
				if ($object->status == $object::STATUS_PROGRAM) {
					$object->status = $object::STATUS_VALIDATED;
				}

				$object->set_values($_REQUEST); // Set standard attributes
				
				$object->save();

				if (empty($object->errors)) {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
					exit();
				}
			}
			
			break;

		case 'addUser':

			$newUser = new User($db);
			$newUser->fetch(GETPOST('user'));

			$object->addUser($newUser->id);

			if (empty($object->errors)) {
				header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
				exit;
			}
			
			break;

		case 'delUser':

			$oldUser = new User($db);
			$oldUser->fetch(GETPOST('user'));

			$object->delUser($oldUser->id);

			if (empty($object->errors)) {
				header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
				exit;
			}

			break;

		case 'addFournPrice':
			$object->addFournPrice(GETPOST('fourn_price_id'));

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'editFournPrice':
			if (GETPOST('fourn_price_id')) {
				$object->deleteFournPrice($object->id);
				$object->addFournPrice(GETPOST('fourn_price_id'));
			}

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'delFournPrice':
			$object->deleteFournPrice($object->id);

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
			exit;
			break;

		case 'confirm_clone':
			if (!empty($user->rights->formation->write)) {
				$clone = $object->clone_object();
				if ($clone != -1) {

					$eventLabel = "Formation créé depuis ".$object->ref."";
					$eventNote = "La formation ".$clone->ref." a été créé par ".$user->login." depuis la fromation ".$object->ref;
					$clone->addEvent($user->id, $eventLabel, $eventNote);

				}

				if (empty($clone->errors)) {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$clone->id);
					exit;
				}
			}
			break;

			header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$clone->id);
			exit;
			break;

		case 'confirm_valid':
			if (!empty($user->rights->formation->write)) {

				$object->setValid();

				if (empty($object->errors)) {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
					exit;
				}
			}
			break;

		case 'confirm_delete':
			if (!empty($user->rights->formation->write)) $object->delete();
			
			header('Location: '.dol_buildpath('/formation/list.php', 1));
			exit;
			break;

		case 'confirm_program':
			if (!empty($user->rights->formation->write)) {

				$object->setProgram();

				if (empty($object->errors)) {
					header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
					exit;
				}

			}
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

		case 'delfile':
            if (unlink($upload_dir.'/'.$document)) {

                header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
            }
            else {
                setEventMessages('Problème lors de la suppression du fichier','','errors');
            }
            break;

        case 'send':
        	$sendFile = false;

		    foreach($filearray as $key => $file) {
		    	if ($file['name'] == $document) {
		    		$sendFile = $file;
		    		break;
		    	}
		    }

		    if ($sendFile) {
	        	foreach ($object->users as $userTraining) {
	        		$mail = $object->mail;
	        		$mail = str_replace("[prenom]", $userTraining->firstname, $mail);
	        		$mail = str_replace("[nom]", $userTraining->lastname, $mail);
	        		$mail = str_replace("[libelle]", $object->label, $mail);
	        		$mail = str_replace("[signature]", $user->signature, $mail);

					$mailfile = new CMailFile("Formation : ".$object->label, $userTraining->email, $user->email, $mail, [$sendFile["fullname"]], ['application/pdf'], [$sendFile["name"]]);
					$mailfile->sendfile();
	        	}

	        	$eventLabel = "Mail envoyé aux collaborateurs";
				$eventNote = "Document ".$sendFile["name"]." envoyé aux collaborateurs par ".$userTraining->login;
				$object->addEvent($userTraining->id, $eventLabel, $eventNote);

	        	header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
	        }
	        else {
	        	$object->errors = "Problème lors de l'envoi.";
	        }

        	break;

        case 'newFournPrice':
	        if (!empty($_REQUEST['newSupplierPrice']) && !empty($_REQUEST['supplierId']) && !empty($_REQUEST['tva_tx'])) {
	        	if ($object->newFournPrice($_REQUEST) != -1) {
	        		header('Location: '.dol_buildpath('/formation/card.php', 1).'?id='.$object->id);
	        	}
	        }
	        else {
	        	$object->errors = "Une erreur est survenu lors de l'ajout d\'un nouveau prix fournisseur: Aucun prix ou fournisseur ou tva n'est indiqué";
	        }

        	break;
	}

	$object->displayErrors();
}
				
/**
 * View
 */
$form = new Form($db);
$formfile = new FormFile($db);

$title=$langs->trans("formation");
llxHeader('',$title);

// Fiche d'une formation
if ($id > 0) {

	if ($action == "edit") {

		print load_fiche_titre($langs->trans("Editformation"));
		dol_fiche_head();

		print '<form name="addprop" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="action" value="edit">';
		print '<input type="hidden" name="edit" value="edit">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<table class="border" width="100%">';
		// Ref new object
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td><input type=text name="ref" value=' . $object->ref . '></td></tr>';
		// Label new object
		print '<tr><td class="titlefieldcreate">' . $langs->trans('Label') . '</td><td><input type=text name="label" value=' . $object->label . '></td></tr>';
		// Ref training product
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('RefTraining') . '</td><td>';
		print $form->select_produits($object->fk_product->id, 'fk_product', 1,20, 0, 1, 2, '', 1);
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

		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('durationH') . '</td><td><input type=text name="duration" value='.number_format($object->duration, 2, '.', '').'></td></tr>';

		print '<tr><td class="titlefieldcreate">' . $langs->trans('HelpOPCA') . '</td><td><input type=text name="help" value='.number_format($object->help, 2, '.', '').'></td></tr>';

		// Lieu
		print '<tr><td class="titlefieldcreate">' . $langs->trans('Place') . '</td><td>';
		print '<select class="flat" name="lieu">';

		if ($object->lieu == "externe") {
			print '<option value="Interne">Interne</option>';
			print '<option value="Externe" selected=selected>Externe</option>';
		}
		else {
			print '<option value="Interne" selected=selected>Interne</option>';
			print '<option value="Externe">Externe</option>';
		}

		print '</select>';
		print '</td></tr>';

		// Date
		print '<tr><td>' . $langs->trans('DateTraining') . '</td><td>';
		$form->select_date($object->dated, 'dated', '', '', '', "addprop", 1, 1);
		print '</td></tr>';
		print '<tr><td>' . $langs->trans('DateTrainingFinish') . '</td><td>';
		$form->select_date($object->datef, 'datef', '', '', '', "addprop", 1, 1);
		print '</td></tr>';
		print "</table>";

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
		print '</div>';

		print "</form>";

	}

	else {

		if (!empty($object->users)) {

			$tabParticipate = "";

			foreach ($object->users as $user) {

				$tabParticipate .= '<tr class="oddeven"><td>'.$user->getNomUrl(1).'</td>';	
				$tabParticipate .= '<td>'.$user->job.'</td>';	
				if ($object->status < $object::STATUS_PROGRAM) $tabParticipate .= '<td><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delUser&user='.$user->id.'"><img src="'.dol_buildpath('/theme/eldy/img/delete.png', 1).'" alt="" title="Supprimer" style="float: right" class="pictodelete"></a></td>';

			}
		}

		elseif ($users != -1 && $users->num_rows == 0) {
			$tabParticipate .= '<tr class="oddeven"><td>'.$langs->trans('NoParticipate').'</td></tr>';	
		}

		else {
			$object->displayErrors();
		}


		$head = formation_prepare_head($object);
		$picto = 'formation@formation';
		dol_fiche_head($head, 'card', $langs->trans("Training"), -1, $picto);

		$linkback = '<a href="'.dol_buildpath('/formation/list.php', 1).'">'.$langs->trans("BackToList").'</a>';

	    $object->next_prev_filter="id";

	    $morehtmlstatus = $object->getLibStatut();

	    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '<div class="refidno">'.$object->label.'</div>', '', 0, '', $morehtmlstatus);

	    print '<div class="fichecenter">';
	    print '<div class="fichehalfleft">';
	    print '<div class="underbanner clearboth"></div>';

	    print '<table class="border tableforfield" width="100%">';

	    print '<tr><td class="titlefield">'.$langs->trans('RefTraining');
	    print '</td>';
	    print '<td colspan="2">'.$object->fk_product->getNomUrl(1).' - '.substr($object->fk_product->label, 0, 30).'</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('DateTraining');
	    print '</td>';
	    print '<td colspan="2">'.date("d/m/Y", strtotime($object->dated)).'</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('DateTrainingFinish');
	    print '</td>';
	    print '<td colspan="2">'.date("d/m/Y", strtotime($object->datef)).'</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('durationH');
	    print '</td>';
	    print '<td colspan="2">'.number_format($object->duration, 2, ',', '').' '.$langs->trans('Hours').'</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('durationD');
	    print '</td>';
	    print '<td colspan="2">'.number_format(($object->duration/7), 2, ',', '').' '.$langs->trans('Days').'</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('Place');
	    print '</td>';
	    print '<td colspan="2">'.$object->lieu.'</td></tr>';

	    print '</table>';

	    print '</div>';

	    print '<div class="fichehalfright">';
	    print '<div class="underbanner clearboth"></div>';

	    print '<table class="border tableforfield" width="100%">';

	    print '<tr><td class="titlefield">'.$langs->trans('PriceP').'</td>';
	    print '<td colspan="2">'.number_format($object->total_ht, 2, ',', '').' €</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('PriceS').'</td>';
	    print '<td colspan="2">'.number_format($object->total_salariale, 2, ',', '').' €</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('PriceT').'</td>';
	    print '<td colspan="2">'.number_format($object->total_salariale+$object->total_ht, 2, ',', '').' €</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('HelpOPCA');
	    print '</td><td colspan="2">'.number_format($object->help, 2, ',', '').' €</td></tr>';

	    print '<tr><td class="titlefield">'.$langs->trans('InCharge').'</td>';
	    print '<td colspan="2">'.number_format($object->total_reste, 2, ',', '').' €</td></tr>';

	    print '</table>';

	    print '</div></div>';

	    print '<div class="clearboth"></div><br />';

	    /* Supplier Price */

	    print '<div class="div-table-responsive">';
	    print '<table id="tablelines" class="noborder noshadow" width="100%"><tbody>';
	    print '<form action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'" method="POST">';

	    if ($object->fk_product_fournisseur_price) {

	    	if ($object->status <= $object::STATUS_VALIDATED) {

			    print '<input type="hidden" name="action" value="editFournPrice">';
			    print '<tr class="liste_titre nodrag nodrop">';
			    print '<td class="linecoldescription">'.$langs->trans('EditSupplier').'</td>';
				print '<td class="linecoldescription" colspan="3">'.$form->select_produits_fournisseurs_list(0, "", "fourn_price_id", "", "", $object->fk_product->ref).'</td>';
			    print '<td class="linecoldescription"><input type="submit" class="button" value="' . $langs->trans("Modify") . '"></td>';
			    print '<td class="linecoldescription"></td>';
			    print '</tr>';

			}

		    print '<tr class="liste_titre nodrag nodrop">';
		    print '<td class="linecoldescription">'.$langs->trans('Supplier').'</td>';
		    print '<td class="linecoldescription">'.$langs->trans('TVA').'</td>';
		    print '<td class="linecoldescription">'.$langs->trans('UnitPrice').'</td>';
		    print '<td class="linecoldescription" colspan="3"></td>';
			print '</tr>';
			print '</form>';

			print '<td class="linecoldescription">'.$fournisseur->getNomUrl(1).'</td>';
			print '<td class="linecoldescription">'.number_format($object->fk_product_fournisseur_price->fourn_tva_tx, 2, ',', '').'</td>';
			print '<td class="linecoldescription">'.number_format($object->fk_product_fournisseur_price->fourn_unitprice, 2, ',', '').'</td>';
			if ($object->status < $object::STATUS_PROGRAM) print '<td class="linecoldescription"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delFournPrice"><img src="'.dol_buildpath('/theme/eldy/img/delete.png', 1).'" alt="" title="Supprimer" style="float: right" class="pictodelete"></a></td>';

		}

	    else {

		    print '<input type="hidden" name="action" value="addFournPrice">';
		    print '<tr class="liste_titre nodrag nodrop">';
		    print '<td class="linecoldescription">'.$langs->trans('AddSupplier').'</td>';
		    $fournisseur = $form->select_produits_fournisseurs_list(0, "", "fourn_price_id", "", "", $object->fk_product->ref);
			print '<td class="linecoldescription" colspan="3">'.$fournisseur."</td>";
		    print '<td class="linecoldescription left" colspan="2"><input type="submit" class="button" value="' . $langs->trans("Add") . '"></td>';
		    print '<td class="linecoldescription left" colspan="2"></td>';
			print '</tr>';
			print '</form>';

			print '<form action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="action" value="newFournPrice">';
			print '<tr class="liste_titre nodrag nodrop">';
		    print '<td class="linecoldescription">'.$langs->trans('AddNewSupplier').'</td>';
		    print '<td class="linecoldescription">'.$form->select_company("", "supplierId", "code_fournisseur IS NOT NULL")."</td>";
		    print '<td class="linecoldescription"><input type="text" name="newSupplierPrice" placeholder="Nouveau prix"></td>';
		    print '<td class="linecoldescription"><input type="text" name="duree" value="'.number_format($object->duration, 2, ',', '').'" placeholder="' . $langs->trans("Duration") . '"></td>';
		    print '<td class="linecoldescription"><input type="text" name="tva_tx" value=20></td>';
		    print '<td class="linecoldescription" colspan=2><input type="submit" class="button" value="' . $langs->trans("Create") . '"></td>';
			print '</tr>';
			print '</form>';

			print '<td class="linecoldescription" colspan=7>'.$langs->trans('NoSupplier').'</td>';
			
		}

		print '</tr>';
		print '</tbody></table>';
	    print '</div></div>';
		
		/* End */


	    print '<div class="clearboth"></div>';

	    print '<div class="tabsAction">';

	    switch ($object->status) {
	    	case $object::STATUS_DRAFT:
	    		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=valid">'.$langs->trans('Validate').'</a></div>';
	    		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=edit">'.$langs->trans('Modify').'</a></div>';
	    		break;

	    	case $object::STATUS_VALIDATED:
	  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=program">'.$langs->trans('Program').'</a></div>';
	  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=edit">'.$langs->trans('Modify').'</a></div>';
	  			print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=cancel">'.$langs->trans('Cancel').'</a></div>';
	    		break;

	    	case $object::STATUS_PROGRAM:
	    		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/formation/card.php', 1).'?id='.$object->id.'&action=edit">'.$langs->trans('Modify').'</a></div>';
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

	    /* Add Collaborators */

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<form action="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'" method="POST">';
		print '<table class="centpercent notopnoleftnoright">';
		print '<input type="hidden" name="action" value="addUser">';
		print '<tbody>';
		print '<tr>';
		print '<td class="nobordernopadding widthpictotitle" valign="middle"><img src="/dolibarr/htdocs/theme/eldy/img/title_commercial.png" alt="" title="" class="valignmiddle" id="pictotitle"></td>';
		print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('Collaborator').'</div></td>';
		if ($object->status <= $object::STATUS_VALIDATED) {
			print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$form->select_dolusers('', 'user', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'maxwidth300',1).'</div></td>';
			print '<td class="nobordernopadding" valign="middle"><div class="titre"><input type="submit" class="button" value="'.$langs->trans("Add").'"></div></td>';
		}
		if ($object->status == $object::STATUS_PROGRAM) {
			print '<td class="nobordernopadding" valign="middle"><div class="titre">';
			print '<input type="hidden" name="action" value="send">';
			print '<input type="submit" class="button" value="'.$langs->trans("SendConvocation").'">';
			print '</div></td>';
		}
		if ($object->status == $object::STATUS_FINISH) {
			print '<td class="nobordernopadding" valign="middle"><div class="titre">';
			print '<input type="hidden" name="action" value="send">';
			print '<input type="submit" class="button" value="'.$langs->trans("SendQuestionnaire").'">';
			print '</div></td>';
		}
		print '</tr>';
		print '</tbody>';
		print '</table></form>';

		/* End */

		/* Show Collaborateur */

		print '<div class="div-table-responsive">';
		print '<table class="noborder listactions" width="100%"><tbody>';
		print '<tr class="liste_titre"><th class="liste_titre">Utilisateur</th><th class="liste_titre">Poste</th><th></th></tr>';

		print $tabParticipate;
		
		print '</tbody></table>';

	    /* End */

	    /* Show documents */

	    print '<table class="centpercent notopnoleftnoright" style="margin-top: 20px;"><tbody>';
	    print '<tr>';
	    print '<td class="nobordernopadding widthpictotitle" valign="middle"><img src="/dolibarr/htdocs/theme/eldy/img/title_generic.png" alt="" title="" class="valignmiddle" id="pictotitle"></td>';
	    print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('joinFile').'</div></td>';
	    print '</tr>';
	    print '</tbody></table>';

	    print '<div class="div-table-responsive-no-min">';
	    print '<table id="tablelines" class="liste" width="100%"><tbody>';

	    print '<tr class="liste_titre nodrag nodrop">';
	    print '<th class="liste_titre" align="left"></th>';
	    print '<th class="liste_titre" align="right"></th>';
	    print '<th class="liste_titre" align="center"></th>';
	    print '<th class="liste_titre" align="center"></th>';
	    print '<th class="liste_titre"></th>';
	    print '<th class="liste_titre"></th>';
	    print '<th class="liste_titre"></th>';
	    print '</tr>';

	    foreach($filearray as $key => $file)
	    {
	        if (explode("_", $file['name'])[0] == $object->ref) {

	            $fileExist = true;

	            $image = $object->check_extension($file['name']);

	            print '<tr id="row-2231" class="drag drop oddeven">';

	            print '<td class="tdoverflowmax300">';
	            print '<a class="pictopreview documentpreview" href="'.DOL_DATA_ROOT."/formation/".$file["name"].'" target="_blank">';
	            print '<img src="/dolibarr/htdocs/theme/eldy/img/detail.png" alt="" title="Aperçu '.$file["name"].'" class="inline-block valigntextbottom">';
	            print '</a>';
	            print '<a class="paddingleft" href="'.DOL_DATA_ROOT."/formation/".$file["name"].'">';
	            print '<img src="/dolibarr/htdocs/theme/common/mime/'.$image.'" alt="" title="'.$file["name"].' ('.$file["size"]." ".$langs->trans("bytes").')" class="inline-block valigntextbottom"> ';
	            print $file["name"];
	            print '</a>';
	            print '</td>';

	            print '<td width="130px" align="right">';
	            print $file["size"]." ".$langs->trans("bytes");
	            print '</td>';

	            $date = date_create();
	            date_timestamp_set($date, $file['date']);
	            print '<td width="130px" align="center">';
	            print date_format($date, 'd/m/Y H:i');
	            print '</td>';

	            print '<td align="center">&nbsp;</td>';
	            print '<td class="valignmiddle right">';
	            print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&document='.$file["name"].'&action=send" class="editfilelink">';
	            print '<img src="/dolibarr/htdocs/theme/common/octicons/lib/svg/mail.svg" alt="" title="Envoyer Par Mail" class="pictomail">';
	            print '</a>';
	            print '</td>';

	            print '<td align="center">&nbsp;</td>';
	            print '<td class="valignmiddle right">';
	            print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&document='.$file["name"].'&action=delfile" class="editfilelink">';
	            print '<img src="/dolibarr/htdocs/theme/eldy/img/delete.png" alt="" title="Supprimer" class="pictodelete">';
	            print '</a>';
	            print '</td>';

	            print '</tr>';
	        }

	    }

	    if (!$fileExist) {
	        print '<tr id="row-2231" class="drag drop oddeven">';
	        print '<td class="tdoverflowmax300">'.$langs->trans('NoFile').'</td>';
	        print '</tr>';
	    }

	    print '</tbody></table>';

	    print '</div>';
	    print '</div></div>';

	    /* End */

		/* List of actions on element */

		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';

        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions=new FormActions($db);
        $somethingshown=$formactions->showactions($object,'formation');

        print '</div>';
        print '</div>';

        print '</div>';

        /* End */

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
			case "program":
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ProgramTraining'), $langs->trans('ConfirmProgram')." ".$object->ref, 'confirm_program', '', 0, 1);
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
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReopenTraining'), $langs->trans('ConfirmReopen')." ".$object->ref, 'confirm_program', '', 0, 1);
				print $formconfirm;
				break;
		}

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
		print '<input type="hidden" name="ref" value="">';
		print '<table class="border" width="100%">';
		// Ref new object
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $langs->trans("Draft") . '</td></tr>';

		// Label new object
		print '<tr><td class="titlefieldcreate">' . $langs->trans('Label') . '</td><td><input type=text name="label"></td></tr>';

		// Ref training product
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('RefTraining') . '</td><td>';
		print $form->select_produits('', 'fk_product', 1,20, 0, 0, 2, '', 1);
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

		// Durée
		print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('durationH') . '</td><td><input type=text name="duration"></td></tr>';

		// Help
		print '<tr><td class="titlefieldcreate">' . $langs->trans('HelpOPCA') . '</td><td><input type=text name="help"></td></tr>';

		// Lieu
		print '<tr><td class="titlefieldcreate">' . $langs->trans('Place') . '</td><td>';
		print '<select class="flat" name="lieu">';
		print '<option value="Interne" selected=selected>Interne</option>';
		print '<option value="Externe">Externe</option>';
		print '</select>';
		print '</td></tr>';

		// Date
		print '<tr><td>' . $langs->trans('DateTraining') . '</td><td>';
		$form->select_date('', 'dated', '', '', '', "addprop", 1, 1);
		print '</td></tr>';
		print '<tr><td>' . $langs->trans('DateTrainingFinish') . '</td><td>';
		$form->select_date('', 'datef', '', '', '', "addprop", 1, 1);
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