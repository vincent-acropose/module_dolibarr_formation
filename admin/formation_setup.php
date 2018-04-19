<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/formation.php
 * 	\ingroup	formation
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/formation.lib.php';
dol_include_once('/formation/class/formation.class.php');

// Translations
$langs->load("formation@formation");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

$object = new Formation($db);

/*
 * Actions
 */
if ($action == "set_MAIL") {
	$object->setMail(GETPOST('mail'));
	header('Location: '.$_SERVER["PHP_SELF"]);
}

/*
 * View
 */
$page_name = "formationSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = formationAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module1000001Name"),
    0,
    "formation@formation"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100" colspan=2>'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabelMail").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="set_MAIL">';
print '<textarea name="mail">'.$object->mail.'</textarea>';
print '<div class="classfortooltip inline-block inline-block" style="vertical-align: top;;">';
print '<img src="/dolibarr/htdocs/theme/eldy/img/info.png" alt="" title="" style="vertical-align: middle;">';
print '</div>';
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '<td align="center"></td></tr>';

print '</table>';

print '<div id="tiptip_holder" class="tip_left_top" style="max-width: 700px; margin: 293px 0px 0px 1420px; display: none;">';
print '<div id="tiptip_arrow" style="margin-left: 320px; margin-top: 164px;">';
print '<div id="tiptip_arrow_inner">';
print '</div>';
print '</div>';
print '<div id="tiptip_content">';
print '<u>Codes disponibles :</u><br>
[prenom]: <font class="ok">Prénom du destinataire</font><br>
[nom]: <font class="ok">Nom du destinataire</font><br>
[libelle]: <font class="ok">Libelle de la formation</font>';
print '</div>';
print '</div>';

llxFooter();

$db->close();