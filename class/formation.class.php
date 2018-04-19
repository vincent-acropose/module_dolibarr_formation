<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

class Formation extends CommonObject
{

	public $table_element='formation';
	public $table_link_user='formation_users';
	public $table_conf='formation_conf';

	public $id;
	public $ref;
	public $label;
	public $fk_product;
	public $dated;
	public $datef;
	public $help;
	public $duration = 0;
	public $fk_product_fournisseur_price;
	public $mail;

	// Statut
	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_PREDICTION = 2;
	const STATUS_PROGRAM = 3;
	const STATUS_FINISH = 4;
	const STATUS_CANCEL = 5;
	
	// Statut Array
	public static $TStatus = array(
		self::STATUS_DRAFT => 'Draft'
		,self::STATUS_VALIDATED => 'Validate'
		,self::STATUS_PREDICTION => 'Prediction'
		,self::STATUS_PROGRAM => 'Program'
		,self::STATUS_FINISH => 'Finish'
		,self::STATUS_CANCEL => 'Cancel'
	);


	public function __construct($db)
	{
		global $langs;
		
		$this->db = $db;
		$this->status = self::STATUS_DRAFT;
		$this->mail = $this->getMail();
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

	public function getNextId() {
		$id = $this->request("SELECT MAX(rowid) AS rowid FROM ".MAIN_DB_PREFIX.$this->table_element);
		$id != -1 ? $id = $id->rowid : $this->errors = "Une erreur est survenu lors de la création de la formation: Impossible de récupérer le bon ID";

		is_null($id) ? $id = 1 : $id = $id+1;

		return $id;
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
				if (sizeof(explode("/", $value['dated']) > 2)) {
					$dated = explode("/", $value['dated'])[2]."-".explode("/", $value['dated'])[1]."-".explode("/", $value['dated'])[0];
				}
				else {
					$dated = $value['dated'];
				}
				$this->dated = date("Y-m-d", strtotime($dated));
			}

			if (!empty($value['datef'])) {
				if (sizeof(explode("/", $value['datef']) > 2)) {
					$datef = explode("/", $value['datef'])[2]."-".explode("/", $value['datef'])[1]."-".explode("/", $value['datef'])[0];
				}
				else {
					$dated = $value['datef'];
				}
				$this->datef = date("Y-m-d", strtotime($datef));
			}

			if (!empty($value['ref'])) {
				$this->ref = $value['ref'];
			}

			if (!empty($value['label'])) {
				$this->label = $value['label'];
			}

			if (!empty($value['id'])) {
				$this->id = $value['id'];
			}

			if (!empty($value['duration'])) {
				$this->duration = $value['duration'];
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
		$this->status = self::STATUS_PREDICTION;

		return $this->save();
	}

	public function setProgram()
	{
		if ($this->fk_product_fournisseur_price == 0) {
			$this->errors = "Une erreur est survenu lors de la programmation de la fromation: Aucun tarif n'a été défini";
		}
		elseif ($this->getUsers()->num_rows == 0) {
			$this->errors = "Une erreur est survenu lors de la programmation de la fromation: Aucun collaborateur n'a été ajouté";
		}
		else {
			$this->status = self::STATUS_PROGRAM;
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

	public function setMail($mail) {
		if (is_string($mail)) {
			$this->$mail = $mail;
			$this->setConf("mail", $this->$mail);
		}
	}

	public function getMail() {
		return $this->request("SELECT * FROM ".MAIN_DB_PREFIX.$this->table_conf." WHERE nom='mail'")->value;
	}

	public function setConf($name, $value) {
		if ($this->request("SELECT * FROM ".MAIN_DB_PREFIX.$this->table_conf." WHERE nom='".$name."'")->value != "")  {
			$this->request("UPDATE ".MAIN_DB_PREFIX.$this->table_conf." SET value='".$value."' WHERE nom='".$name."'", 1);
		}
		else {
			$this->request('INSERT INTO '.MAIN_DB_PREFIX.$this->table_conf.' (nom, value) VALUES ("'.$name.'", "'.$value.'")', 1);
		}
	}

	/* ----------------------------- */
	/* ------------ CRUD ----------- */
	/* ----------------------------- */


	public function create()
	{
		$this->setNextId();
		$this->ref = "(PROV".$this->id.")";
		$product = new Product($this->db);
		$product->fetch($this->fk_product);

		if (!$this->duration && $product->duration) {
			$this->duration = explode("h", $product->duration)[0];
		}

		$trainingCreate = $this->request('INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.' (rowid, ref, label, date_cre, date_maj, fk_product, fk_statut, dated, datef, duration, help) VALUES ('.(int)$this->id.', "'.$this->ref.'", "'.$this->label.'", NOW(), NOW(), '.$this->fk_product.', '.$this->status.', "'.$this->dated.'", "'.$this->datef.'", '.$this->duration.', '.(float)$this->help.')', 1);

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
		$param['datef'] = $this->datef;
		$param['help'] = $this->help;
		$param['duration'] = $this->duration;

		$newForm = new Formation($this->db);
		$newForm->set_values($param);

		$newForm->create();

		return $newForm;
	}

	public function delete() {
		if ($this->fk_product_fournisseur_price) {
			$trainingDelete = $this->request("DELETE FROM ".MAIN_DB_PREFIX.$this->table_link_user." WHERE fk_formation=".$this->id, 1);
		}

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
		$sql .= ", label='".$this->label."'";
		$sql .= ", fk_statut=".$this->status;
		$sql .= ", dated='".$this->dated."'";
		$sql .= ", datef='".$this->datef."'";
		$sql .= ", help=".$this->help;
		$sql .= ", duration=".$this->duration;
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

	public function displayErrors($message="") {
		if (!empty($this->errors)) {
			setEventMessage($this->errors, 'errors');
			$this->errors = null;
		}
	}

	function fetch($id)
	{
		$sql = " SELECT f.rowid, f.ref, f.label, f.date_cre, f.dated, f.datef, f.help, f.fk_statut, f.fk_product, f.duration, f.fk_product_fournisseur_price";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as f";
		$sql.= " WHERE f.rowid=".$id;
		$res = $this->db->query($sql);

		if ($res) {
			$obj = $this->db->fetch_object($res);

			$this->id = $obj->rowid;
			$this->ref = $obj->ref;
			$this->label = $obj->label;
			$this->date_cre = $obj->date_cre;
			$this->dated = $obj->dated;
			$this->datef = $obj->datef;
			$this->status = $obj->fk_statut;
			$this->fk_product = $obj->fk_product;
			$this->help = $obj->help;
			$this->duration = $obj->duration;
			$this->fk_product_fournisseur_price = $obj->fk_product_fournisseur_price;

			if($this->id > 1) $this->ref_previous = $this->id - 1;
			if($this->id < $this->getNextId() - 1) $this->ref_next = $this->id + 1;
			return 1;
		}
		else {
			return -1;
		}
	}

	function check_extension($name) {
		$extension = strrchr($name, '.');
        switch ($extension) {
            case '.pdf':
                $image = "pdf.png";
                break;
            
            case '.png':
                $image = "image.png";
                break;

            case '.jpg':
                $image = "image.png";
                break;

            case '.jpeg':
                $image = "image.png";
                break;

            case '.doc':
                $image = "doc.png";
                break;

            case '.docx':
                $image = "doc.png";
                break;

            case '.odt':
                $image = "ooffice.png";
                break;

            default:
                $image = "other.png";
                break;
        }
        return $image;
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
		if ($status==self::STATUS_PROGRAM) { $statustrans='statut3'; $keytrans='formationStatusProgram'; $shortkeytrans='Program'; }
		if ($status==self::STATUS_FINISH) { $statustrans='statut4'; $keytrans='formationStatusFinish'; $shortkeytrans='Finish'; }
		if ($status==self::STATUS_CANCEL) { $statustrans='statut9'; $keytrans='formationStatusCancel'; $shortkeytrans='Cancel'; }

		
		if ($mode == 0) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 1) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($keytrans);
		elseif ($mode == 2) return $langs->trans($keytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 3) return $langs->trans($keytrans)." ".img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($shortkeytrans);
		elseif ($mode == 4) return $langs->trans($shortkeytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
	}
	
}