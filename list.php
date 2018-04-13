<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
dol_include_once('/formation/class/formation.class.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('formation@formation');

$action=GETPOST('action','alpha');

$PDOdb = new TPDOdb;
$object = new formation($db);

$hookmanager->initHooks(array('formationlist'));

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// do action from GETPOST ... 
}


/*
 * View
 */

llxHeader('',$langs->trans('formationList'),'','');

//$type = GETPOST('type');
//if (empty($user->rights->formation->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
$sql = 'SELECT t.rowid, t.ref, t.dated, t.fk_statut';
$sql.= ' FROM '.MAIN_DB_PREFIX.'formation t ';

//$sql.= ' AND t.entity IN ('.getEntity('formation', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;

$r = new TListviewTBS('formation');
echo $r->render($PDOdb, $sql, array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'subQuery' => array()
	,'link' => array(
	)
	,'type' => array(
		'dated' => 'date' // [datetime], [hour], [money], [number], [integer]
	)
	,'search' => array(
	)
	,'translate' => array()
	,'hide' => array(
		'rowid'
	)
	,'liste' => array(
		'titre' => $langs->trans('formationList')
		,'image' => img_picto('','title_generic.png', '', 0)
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('Noformation')
		,'picto_search' => img_picto('','search.png', '', 0)
	)
	,'title'=>array(
		'ref' => $langs->trans('Ref.')
		,'dated' => $langs->trans('begin')
		,'fk_statut' => $langs->trans('Status')
	)
	,'eval'=>array(
		'ref' => '_getFormationNomUrl(@val@)'
		,'fk_statut' => '_getStatus(@val@)'
	)
));

llxFooter('');

$db->close();

/**
 * TODO remove if unused
 */
function _getFormationNomUrl($rowid)
{
	global $db;
	
	$f = new Formation($db);

	if (preg_match('#^PROV#', $rowid)) {
		$rowid = explode("V", $rowid)[1];
	}
	elseif ($rowid<0) {
		$rowid = $rowid*-1;
	}

	$f->fetch($rowid);
	return $f->getNomUrl(1);

}

function _getStatus($statut)
{
	global $db;

	return Formation::LibStatut($statut, 0);
}