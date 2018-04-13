<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

class Formation extends CommonObject
{

	public $table_element='formation';
	public $table_link_user='formation_users';

	public $id;
	public $ref;
	public $fk_product;
	public $dated;
	public $help;
	public $delayh = 0;
	public $fk_product_fournisseur_price;

	// Statut
	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_PREDICTION = 2;
	const STATUS_FINISH = 3;
	const STATUS_CANCEL = 4;
	
	// Statut Array
	public static $TStatus = array(
		self::STATUS_DRAFT => 'Draft'
		,self::STATUS_VALIDATED => 'Validate'
		,self::STATUS_PREDICTION => 'Prediction'
		,self::STATUS_FINISH => 'Finish'
		,self::STATUS_CANCEL => 'Cancel'
	);


	public function __construct($db)
	{
		global $langs;
		
		$this->db = $db;
		$this->status = self::STATUS_DRAFT;
	}

	/* ----------------------------- */
	/* ---------- GETTERS ---------- */
	/* ----------------------------- */

	public function getUsers() {

		$users = $this->request("SELECT fk_user FROM ".MAIN_DB_PREFIX.$this->table_link_user." WHERE fk_formation=".$this->id,0,"*");

		if($users != -1) {
			return $users;
		}
		else {
			$this->errors = "Une erreur est survenu lors de la récupération des collaborateurs";
			return -1;
		}
		return -1;

	}
	
	public function getNumero()
	{
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
		{
			return $this->getNextNumero();
		}
		
		return $this->ref;
	}

	private function getNextNumero()
	{
	
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		
		$mask = !empty($conf->global->MYMODULE_REF_MASK) ? $conf->global->MYMODULE_REF_MASK : 'FF{yy}{mm}-{0000}';
		$numero = get_next_value($this->db, $mask, 'formation', 'ref');
		
		return $numero;
	}

	public function getLibStatut($mode=0)
    {
        return self::LibStatut($this->status, $mode);
    }
	
	public function getNomUrl($withpicto=0, $get_params='')
	{
		global $langs;

        $result='';
        $label = '<u>' . $langs->trans("Showformation") . '</u>';
        if (! empty($this->ref)) $label.= '<br /><b>'.$langs->trans('Ref').':</b> '.$this->ref;
        if ($this->total_ht != 0) $label.= '<br /><b>'.$langs->trans('Totalht').':</b> '.$this->total_ht;
        
        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $link = '<a href="'.dol_buildpath('/formation/card.php', 1).'?id='.$this->id. $get_params .$linkclose;
       
        $linkend='</a>';

        $picto='formation@formation';
		
        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
		
        $result.=$link.$this->ref.$linkend;
		
        return $result;
	}

	/* ----------------------------- */
	/* ---------- SETTERS ---------- */
	/* ----------------------------- */

	public function setNextId() {
		$id = $this->request("SELECT MAX(rowid) AS rowid FROM ".MAIN_DB_PREFIX.$this->table_element);
		$id != -1 ? $id = $id->rowid : $this->errors = "Une erreur est survenu lors de la création de la formation: Impossible de récupérer le bon ID";

		is_null($id) ? $this->id = 1 : $this->id = $id+1;
	}

	public function set_values($value) {
		if (is_array($value)) {

			if (empty($value['fk_product'])) {
				$this->errors = "Les champs obligatoire n'ont été remplis.";
				return -1;
			}

			if (!empty($value['fk_product'])) {
				$this->fk_product = $value['fk_product'];
			}

			if (!empty($value['dated'])) {
				/*if (preg_match('#^[0-9]+/[0-9]+/[0-9]+$#', $value['dated'])) {
					$date = explode("/", $value['dated']);
					$this->dated = $date[2]."-".$date[1]."-".$date[0];
				}*/
				$this->dated = $value['datedyear']."-".$value['datedmonth']."-".$value['datedday'];
			}

			if (!empty($value['ref'])) {
				$this->ref = $value['ref'];
			}
			if (!empty($value['id'])) {
				$this->id = $value['id'];
			}

			if (!empty($value['delayh'])) {
				$this->delayh = $value['delayh'];
			}

			if (!empty($value['help'])) {
				$this->help = $value['help'];
			}

			if (!empty($value['fk_product_fournisseur_price'])) {
				$this->fk_product_fournisseur_price = $value['fk_product_fournisseur_price'];
			}

		}
	}

	public function setDraft()
	{
		if ($this->status == self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_DRAFT;
			return $this->save();
		}
		
		return 0;
	}
	
	public function setValid()
	{
		if ($this->status == self::STATUS_DRAFT) {
			$this->ref = $this->getNumero();
		}
		$this->status = self::STATUS_VALIDATED;

		return $this->save();
	}

	public function setPredict() {
		if ($this->fk_product_fournisseur_price > 0) {
			$this->status = self::STATUS_PREDICTION;
		}
		else {
			$this->errors = "Une erreur est survenu lors de la prévision de la fromation: Aucun tarif n'a été défini";
		}

		return $this->save();
	}

	public function setFinish() {
		$this->status = self::STATUS_FINISH;

		return $this->save();
	}

	public function setCancel() {
		$this->status = self::STATUS_CANCEL;

		return $this->save();
	}

	/* ----------------------------- */
	/* ------------ CRUD ----------- */
	/* ----------------------------- */


	public function create()
	{
		$this->setNextId();

		$this->ref = "(PROV".$this->id.")";

		$trainingCreate = $this->request('INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.' (rowid, ref, date_cre, date_maj, fk_product, fk_statut, dated, delayh, help) VALUES ('.(int)$this->id.', "'.$this->ref.'", NOW(), NOW(), '.$this->fk_product.', '.$this->status.', "'.$this->dated.'", '.$this->delayh.', '.(float)$this->help.')', 1);

		if ($trainingCreate) {
			return 0;
		}
		else {
			$this->errors = "Une erreur est survenu lors de la création de la fromation.";
			return -1;
		}

	}

	public function clone() {
		$param = [];
		$param['fk_product'] = $this->fk_product;
		$param['dated'] = $this->dated;
		$param['help'] = $this->help;
		$param['delayh'] = $this->delayh;

		$newForm = new Formation($this->db);
		$newForm->set_values($param);

		$newForm->save("create");

		return $newForm;
	}

	public function delete() {
		$trainingDelete = $this->request("DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid=".$this->id, 1);

		return $trainingDelete;
	}

	public function addUser($userId) {

		$users = $this->getUsers();

		foreach ($users as $user) {

			if ($user['fk_user'] == $userId) {
				$this->errors = "Une erreur est survenu lors de l'ajout d'un collaborateur: L'utilisateur est déjà liée à la formation.";
				return -1;
			}
		}

		if ($userId != 0) {

			$id = $this->request("SELECT MAX(rowid) AS rowid FROM ".MAIN_DB_PREFIX.$this->table_link_user);
			$id != -1 ? $id = $id->rowid : $this->errors = "Une erreur est survenu lors de l'ajout d'un collaborateur: Impossible de récupérer le bon ID";

			is_null($id) ? $rowid = 1 : $rowid = $id+1;

			$addUser = $this->request('INSERT INTO '.MAIN_DB_PREFIX.$this->table_link_user.' (rowid, fk_user, fk_formation) VALUES ('.$rowid.','.$userId.','.$this->id.')',1);

			if ($addUser) {
				return 0;
			}
			else {
				$this->errors = "Une erreur est survenu lors de l'ajout d'un collaborateur";
				return -1;
			}
		}
		else {
			$this->errors = "Une erreur est survenu lors de l'ajout d'un collaborateur: Aucun collaborateur n\'a été choisi.";
			return -1;
		}
	}

	public function delUser($id) {

		if ($id > 0) {

			$delUser = $this->request("DELETE FROM ".MAIN_DB_PREFIX.$this->table_link_user." WHERE fk_user=".$id." AND fk_formation=".$this->id, 1);

			if ($this->db->query($delUser)) {
				return 0;
			}

			else {
				$this->errors = 'Une erreur est survenu lors de l\'ajout d\'un collaborateur';
				return -1;
			}
		}
		else {
			$this->errors = 'Aucun collaborateur n\'a été choisi !';
			return -1;
		}
	}

	public function addFournPrice($id) {

		if ($id) {
			$request = $this->request("UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET fk_product_fournisseur_price=".$id.", date_maj=NOW() WHERE rowid=".$this->id, 1);

			if ($request) {
				return 0;
			}
			else {
				$this->errors = 'Une erreur est survenu lors de l\'ajout d\'un prix fournisseur';
				return -1;
			}
		}
		else {
			$this->errors = 'Une erreur est survenu lors de l\'ajout d\'un prix fournisseur: Aucun prix choisi';
			return -1;
		}

	}

	public function deleteFournPrice($id) {

		if ($id) {
			$request = $this->request("UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET fk_product_fournisseur_price=NULL, date_maj=NOW() WHERE rowid=".$this->id, 1);

			if ($request) {
				return 0;
			}
			else {
				$this->errors = 'Une erreur est survenu lors de la suppression d\'un prix fournisseur';
				return -1;
			}
		}
		else {
			$this->errors = 'Une erreur est survenu lors de la suppression d\'un prix fournisseur: Aucun fournisseur choisi';
			return -1;
		}

	}

	public function save() {

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .=" SET ref='".$this->ref."'";
		$sql .= ", fk_statut=".$this->status;
		$sql .= ", dated='".$this->dated."'";
		$sql .= ", help=".$this->help;
		$sql .= ", delayh=".$this->delayh;
		$sql .= ", fk_product=".$this->fk_product;
		if ($this->fk_product_fournisseur_price) $sql .= ", fk_product_fournisseur_price=".$this->fk_product_fournisseur_price;
		$sql .= ", date_maj=NOW() WHERE rowid=".$this->id;

		$request = $this->request($sql, 1);

		if ($request) {
			return 0;
		}
		else {
			$this->errors = "Une erreur est survenue lors de la sauvegarde";
			return -1;
		}
	}

	/* ----------------------------- */
	/* ---------- METHODS ---------- */
	/* ----------------------------- */

	/**
	 * function request 
	 * 		$request => Requête à effectué sur la base de donnée
	 * 		$type => 0:(SELECT), 1:(INSERT, UPDATE, DELETE)
	 */
	public function request($request, $type=0, $line=1) {

		switch ($type) {
			case 0:
				$result = $this->db->query($request);

				if ($result) {
					if ($line == 1) {
						return $this->db->fetch_object($result);
					}
					else {
						return $result;
					}
				}
				else {
					return -1;
				}

				break;

			case 1:
				return $this->db->query($request);
				break;
			
			default:
				return -1;
				break;
		}

	}

	public function displayErrors() {
		if (!empty($this->errors)) {
			setEventMessage($this->errors, 'errors');
			$this->errors = null;
		}
	}

	function fetch($id)
	{
		$sql = " SELECT f.rowid, f.ref, f.date_cre, f.dated, f.help, f.fk_statut, f.fk_product, f.delayh, f.fk_product_fournisseur_price";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as f";
		$sql.= " WHERE f.rowid=".$id;
		$res = $this->db->query($sql);

		if ($res) {
			$obj = $this->db->fetch_object($res);

			$this->id = $obj->rowid;
			$this->ref = $obj->ref;
			$this->date_cre = $obj->date_cre;
			$this->dated = explode('-', $obj->dated)[2]."/".explode('-', $obj->dated)[1]."/".explode('-', $obj->dated)[0];
			$this->status = $obj->fk_statut;
			$this->fk_product = $obj->fk_product;
			$this->help = $obj->help;
			$this->delayh = $obj->delayh;
			$this->fk_product_fournisseur_price = $obj->fk_product_fournisseur_price;
			return 1;
		}
		else {
			return -1;
		}
	}

	/* ----------------------------- */
	/* ---------- STATICS ---------- */
	/* ----------------------------- */
	
	public static function LibStatut($status, $mode)
	{
		global $langs;
		$langs->load('formation@formation');

		if ($status==self::STATUS_DRAFT) { $statustrans='statut0'; $keytrans='formationStatusDraft'; $shortkeytrans='Draft'; }
		if ($status==self::STATUS_VALIDATED) { $statustrans='statut1'; $keytrans='formationStatusValidated'; $shortkeytrans='Validate'; }
		if ($status==self::STATUS_PREDICTION) { $statustrans='statut3'; $keytrans='formationStatusPrediction'; $shortkeytrans='Prediction'; }
		if ($status==self::STATUS_FINISH) { $statustrans='statut4'; $keytrans='formationStatusFinish'; $shortkeytrans='Finish'; }
		if ($status==self::STATUS_CANCEL) { $statustrans='statut9'; $keytrans='formationStatusCancel'; $shortkeytrans='Cancel'; }

		
		if ($mode == 0) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 1) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($keytrans);
		elseif ($mode == 2) return $langs->trans($keytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 3) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($shortkeytrans);
		elseif ($mode == 4) return $langs->trans($shortkeytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
	}
	
}