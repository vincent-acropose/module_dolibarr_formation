<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/commande/index.php
 *	\ingroup    commande
 *	\brief      Home page of customer order module
 */

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
dol_include_once('/formation/class/formation.class.php');

if (!$user->rights->formation->read) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('formation@formation');

$object = new Formation($db);
$product = new Product($db);

// Security check
/*$socid=GETPOST('socid','int');
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}*/



/*
 * View
 */

llxHeader('',$langs->trans('TrainingArea'),'','');
print load_fiche_titre($langs->trans("TrainingArea"),'','formation@formation');

$sql = " SELECT f.rowid, f.ref, f.fk_statut, f.fk_user, f.fk_product";
$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element." as f";
$sql.= " WHERE f.fk_statut = ".$object::STATUS_DRAFT;

$result = $db->query($sql);

$result?$nbTraining = $db->num_rows($result):"";

print '<div class="fichecenter"><div class="fichethirdleft">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("TrainingDraft").($nbTraining?' <span class="badge">'.$nbTraining.'</span>':'').'</th></tr>';

while ($obj = $db->fetch_object($result)) {

	$object->fetch($obj->rowid);
	$product->fetch($obj->fk_product);

	print '<tr class="oddeven">';
	print '<td align="left" class="nowrap">'.$object->getNomUrl(1).'</td>';
	print '<td class="nowrap"><a href="/product/card.php?socid=">'.$product->getNomUrl(1).'</a></td>';
	print '<td align="right" class="nowrap">'.$object->LibStatut($obj->fk_statut, 0).'</td>';
	print '</tr>';

}

print '</table>';
print '</div></div>';

$sql = " SELECT f.rowid, f.ref, f.fk_statut, f.fk_user, f.fk_product";
$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element." as f";
$sql.= " WHERE f.fk_statut = ".$object::STATUS_VALIDATED." OR f.fk_statut = ".$object::STATUS_PREDICTION;

$result = $db->query($sql);

$result?$nbTraining = $db->num_rows($result):"";

print '<div class="fichetwothirdright"><div class="ficheaddleft">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("TrainingOpen").($nbTraining?' <span class="badge">'.$nbTraining.'</span>':'').'</th></tr>';

while ($obj = $db->fetch_object($result)) {

	$object->fetch($obj->rowid);
	$product->fetch($obj->fk_product);

	print '<tr class="oddeven">';
	print '<td align="left" class="nowrap">'.$object->getNomUrl(1).'</td>';
	print '<td class="nowrap"><a href="/product/card.php?socid=">'.$product->getNomUrl(1).'</a></td>';
	print '<td align="right" class="nowrap">'.$object->LibStatut($obj->fk_statut, 0).'</td>';
	print '</tr>';

}

print '</table><br />';

$sql = " SELECT f.rowid, f.ref, f.fk_statut, f.fk_user, f.fk_product";
$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element." as f";
$sql.= " WHERE f.fk_statut = ".$object::STATUS_PREDICTION." AND YEAR(f.date_cre)=".date('Y');

$result = $db->query($sql);

$result?$nbTraining = $db->num_rows($result):"";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("TrainingPrediction")." ".date('Y').($nbTraining?' <span class="badge">'.$nbTraining.'</span>':'').'</th></tr>';

while ($obj = $db->fetch_object($result)) {

	$object->fetch($obj->rowid);
	$product->fetch($obj->fk_product);

	print '<tr class="oddeven">';
	print '<td align="left" class="nowrap">'.$object->getNomUrl(1).'</td>';
	print '<td class="nowrap"><a href="/product/card.php?socid=">'.$product->getNomUrl(1).'</a></td>';
	print '<td align="right" class="nowrap">'.$object->LibStatut($obj->fk_statut, 0).'</td>';
	print '</tr>';

}

print '</table>';

print '</div></div>';


llxFooter();

$db->close();
