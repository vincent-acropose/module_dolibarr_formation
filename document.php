<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013      Florian Henry          <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/product/document.php
 *       \ingroup    product
 *       \brief      Page des documents joints sur les produits
 */
require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
dol_include_once('/formation/class/formation.class.php');
dol_include_once('/formation/lib/formation.lib.php');

if(empty($user->rights->formation->write)) accessforbidden();

$langs->load('formation@formation');

$id = GETPOST('id');
$action=GETPOST('action');
$document=GETPOST('document');

$hookmanager->initHooks(array('formationcard', 'globalcard'));

$object = new Formation($db);

if ($id > 0) {
    $object->fetch($id);
    $upload_dir = dol_buildpath('/formation/documents');
}


/*
 * Actions
 */
$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    switch ($action) {
        case 'upload_file':
            if(isset($_FILES['uploadfile']) && $_FILES['uploadfile']['name'] != "")
            { 
                $fichier = basename($_FILES['uploadfile']['name']);

                if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $upload_dir.'/'.$object->ref.'_'.$fichier)) {
                    header('Location: '.dol_buildpath('/formation/document.php', 1).'?id='.$object->id);
                }

                else {
                    setEventMessages('Echec de l\'envoi du fichier !','','errors');
                }
            }
            break;

        case 'delfile':
            if (unlink($upload_dir.'/'.$document)) {
                $eventLabel = $document." Supprimé de la formation ".$object->ref." par ".$user->login;
                $eventNote = "Le fichier ".$fichier." a été supprimé par ".$user->firstname." ".$user->lastname;
                $object->addEvent($user->id, $eventLabel, $eventNote);

                header('Location: '.dol_buildpath('/formation/document.php', 1).'?id='.$object->id);
            }
            else {
                setEventMessages('Problème lors de la suppression du fichier','','errors');
            }
            break;
    }
}


/*
 *	View
 */

$form = new Form($db);

$title=$langs->trans("formation");
llxHeader('',$title);

if ($object->id)
{
	$head = formation_prepare_head($object);
	$picto = 'formation@formation';
    dol_fiche_head($head, 'document', $langs->trans("Training"), 0, $picto);

	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',"","",1);

	$totalsize=0;
    $totalFile=0;
	foreach($filearray as $key => $file)
	{
        if (strstr($file['name'], $object->ref)) {
            $totalsize += $file['size'];
            $totalFile += 1;
        }
	}


    $linkback = '<a href="'.dol_buildpath('/formation/list.php', 1).'">'.$langs->trans("BackToList").'</a>';

    $morehtmlstatus = $object->getLibStatut();

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '<div class="refidno">'.$object->label.'</div>', '', 0, '', $morehtmlstatus);

    /* Détails Documents */

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.$totalFile.'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();

    /* End */


    /* Upload document */

    print '<div class="attacharea">';

    print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;"><tbody>';

    print '<tr>';
    print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('addNewFile').'</div></td>';
    print '</tr>';

    print '</tbody></table>';

    print '<form name="formuserfile" id="formuserfile" action="'.dol_buildpath('/formation/document.php', 1).'?id='.$object->id.'" enctype="multipart/form-data" method="POST">';
    print '<input type="hidden" name="action" value="upload_file">';
    print '<table class="nobordernopadding" width="100%"><tbody>';

    print '<tr>';
    print '<td valign="middle">';
    print '<input name="max_file_size" value="2097152" type="hidden">';
    print '<input class="flat minwidth400" name="uploadfile" multiple="" type="file">';
    print '<input class="button" name="sendit" value="'.$langs->trans('Send').'" type="submit">';
    print '</td>';
    print '</tr>';

    print '</tbody></table>';
    print '</form><br>';

    print '</div>';

    /* End */


    /* Show documents */

    print '<table class="centpercent notopnoleftnoright" style="margin-bottom: 2px;"><tbody>';
    print '<tr>';
    print '<td class="nobordernopadding widthpictotitle" valign="middle"><img src="/dolibarr/htdocs/theme/eldy/img/title_generic.png" alt="" title="" class="valignmiddle" id="pictotitle"></td>';
    print '<td class="nobordernopadding" valign="middle"><div class="titre">'.$langs->trans('joinFile').'</div></td>';
    print '</tr>';
    print '</tbody></table>';

    print '<div class="div-table-responsive-no-min">';
    print '<table id="tablelines" class="liste" width="100%"><tbody>';

    print '<tr class="liste_titre nodrag nodrop">';
    print '<th class="liste_titre" align="left"><a href="/dolibarr/htdocs/product/document.php?sortfield=name&amp;sortorder=asc&amp;begin=&amp;id=32&amp;id=32">'.$langs->trans('joinFile').'</a></th>';
    print '<th class="liste_titre" align="center"><a href="/dolibarr/htdocs/product/document.php?sortfield=size&amp;sortorder=asc&amp;begin=&amp;id=32&amp;id=32">'.$langs->trans('Size').'</a></th>';
    print '<th class="liste_titre" align="center"><a href="/dolibarr/htdocs/product/document.php?sortfield=date&amp;sortorder=asc&amp;begin=&amp;id=32&amp;id=32">'.$langs->trans('Date').'</a></th>';
    print '<th class="liste_titre" align="center"></th>';
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
            print '<a class="pictopreview documentpreview" href="'.dol_buildpath('/formation/documents', 1)."/".$file["name"].'" target="_blank">';
            print '<img src="/dolibarr/htdocs/theme/eldy/img/detail.png" alt="" title="Aperçu '.$file["name"].'" class="inline-block valigntextbottom">';
            print '</a>';
            print '<a class="paddingleft" href="'.dol_buildpath('/formation/documents', 1)."/".$file["name"].'">';
            print '<img src="/dolibarr/htdocs/theme/common/mime/'.$image.'" alt="" title="'.$file["name"].' ('.$file["size"]." ".$langs->trans("bytes").')" class="inline-block valigntextbottom"> ';
            print $file["name"];
            print '</a>';
            print '</td>';

            print '<td width="130px" align="center">';
            print $file["size"]." ".$langs->trans("bytes");
            print '</td>';

            $date = date_create();
            date_timestamp_set($date, $file['date']);
            print '<td width="130px" align="center">';
            print date_format($date, 'd/m/Y H:i');
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

    /* End */


}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
