<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
dol_include_once('/formation/class/formation.class.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('formation@formation');

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');

$PDOdb = new TPDOdb;
$object = new Tformation;

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
/*$sql = 'SELECT t.rowid, t.ref, t.label, t.date_cre, t.date_maj, \'\' AS action';

$sql.= ' FROM '.MAIN_DB_PREFIX.'formation t ';

$sql.= ' WHERE 1=1';*/
//$sql.= ' AND t.entity IN ('.getEntity('formation', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;

if ($id > 0) {

	$object = new User($db);
	$object->fetch($id, '', '', 1);
	$object->getrights();
	$object->fetch_clicktodial();


	$head = user_prepare_head($object);

	$title = $langs->trans("User");

	dol_fiche_head($head, 'formation', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);

	$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_formation', 'GET');

	$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

	print load_fiche_titre($langs->trans("ListOfTraining"),'','');

    $formations=new UserFormation($db);
    $formationslist = $formations->listFormationForUser($object->id);

	$r = new TListviewTBS('formation');
	echo $r->render($PDOdb, $sql, array(
		'view_type' => 'list' // default = [list], [raw], [chart]
		,'limit'=>array(
			'nbLine' => $nbLine
		)
		,'subQuery' => array()
		,'link' => array()
		,'type' => array(
			'date_cre' => 'date' // [datetime], [hour], [money], [number], [integer]
			,'date_maj' => 'date'
		)
		,'search' => array(
			'ref' => array('recherche' => true, 'table' => 't', 'field' => 'ref')
			,'date_cre' => array('recherche' => 'calendars', 'allow_is_null' => true)
			,'date_maj' => array('recherche' => 'calendars', 'allow_is_null' => false)
			,'company' => array('recherche' => true, 'table' => array('t', 't'), 'field' => array('company', 'description')) // input text de recherche sur plusieurs champs
			,'status' => array('recherche' => TFormation::$TStatus, 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
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
			,'company' => $langs->trans('Company')
			,'date_cre' => $langs->trans('DateCre')
			,'date_maj' => $langs->trans('DateMaj')
			,'status' => $langs->trans('Status')
		)
		,'eval'=>array(
	//		'fk_user' => '_getUserNomUrl(@val@)' // Si on a un fk_user dans notre requête
		)
	));

	$parameters=array('sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	$formcore->end_form();

}

llxFooter('');

$db->close();

/**
 * TODO remove if unused
 */
function _getUserNomUrl($fk_user)
{
	global $db;
	
	$u = new User($db);
	if ($u->fetch($fk_user) > 0)
	{
		return $u->getNomUrl(1);
	}
	
	return '';
}