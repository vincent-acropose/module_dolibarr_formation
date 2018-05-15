<?php

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
dol_include_once('/formation/class/formation.class.php');

if(empty($user->rights->formation->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('formation@formation');

$action=GETPOST('action','alpha');

$PDOdb = new TPDOdb;

$hookmanager->initHooks(array('formationlist'));
$object = new Formation($db);

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

llxHeader('',$langs->trans('Catalog'),'','');

//$type = GETPOST('type');
//if (empty($user->rights->formation->all->read)) $type = 'mine';

// TODO ajouter les champs de son objet que l'on souhaite afficher
$sql = "SELECT p.rowid 'formation', p.label, p.duration, fp.price, s.rowid 'formateur' FROM ".MAIN_DB_PREFIX."product p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price fp ON (p.rowid = fp.fk_product)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (fp.fk_soc = s.rowid)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product cp ON (p.rowid = cp.fk_product)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie c ON (cp.fk_categorie = c.rowid)";
$sql .= " WHERE c.rowid = ".$object->tag;

//$sql.= ' AND t.entity IN ('.getEntity('formation', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;

$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_mymodule', 'GET');
$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

$r = new TListviewTBS('catalog');
echo $r->render($PDOdb, $sql, array(
	'view_type' => 'list' // default = [list], [raw], [chart]
	,'limit'=>array(
		'nbLine' => $nbLine
	)
	,'subQuery' => array()
	,'link' => array(
	)
	,'type' => array(
	)
	,'search' => array(
		'formateur' => array('recherche' => true, 'table' => 's', 'field' => 'nom')
		,'formation' => array('recherche' => true, 'table' => 'p', 'field' => 'ref')
		,'label' => array('recherche' => true, 'table' => 'p', 'field' => 'label')
		,'price' => array('recherche' => true, 'table' => 'fp', 'field' => 'price')
	)
	,'translate' => array()
	,'hide' => array(
		'rowid'
	)
	,'liste' => array(
		'titre' => $langs->trans('Catalog')
		,'image' => img_picto('','title_generic.png', '', 0)
		,'picto_precedent' => '<'
		,'picto_suivant' => '>'
		,'noheader' => 0
		,'messageNothing' => $langs->trans('empty')
		,'picto_search' => img_picto('','search.png', '', 0)
	)
	,'title'=>array(
		'ref' => $langs->trans('Ref.')
		,"duration" => $langs->trans('Duration')
		,'formateur' => $langs->trans('Supplier')
		,'formation' => $langs->trans('Training')
		,'label' => $langs->trans('Label')
		,'price' => $langs->trans('HourPrice')
	)
	,'eval'=>array(
		'formateur' => '_getSupplierNomUrl(@formateur@)'
		,'formation' => '_getProductNomUrl(@formation@)'
		,'price' => '_getPrice(@price@)'
	)
));

$formcore->end_form();

llxFooter('');

$db->close();

function _getProductNomUrl($rowid) {

	global $db;
	
	$p = new Product($db);
	$p->fetch($rowid);
	return $p->getNomUrl(1);

}

function _getSupplierNomUrl($rowid=false) {

	global $db;
	
	if ($rowid) {
		$f = new Fournisseur($db);
		$f->fetch($rowid);
		return $f->getNomUrl(1);
	}
	else {
		return null;
	}
}

function _getPrice($price=false) {
	if ($price) {
		return number_format($price, 2, ',', '');
	}
	else {
		return null;
	}
}