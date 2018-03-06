<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

class Formation extends CommonObject
{

	public $table_element='formation';

	public $id;
	public $ref;
	public $subject;
	public $fk_user;
	public $fk_product;
	public $date_d;
	public $date_f;

	/* totaux */
	public $total_ht;
	public $total_peda;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Ongoing status
	 */
	const STATUS_PREDICTION = 2;
	/**
	 * Finish status
	 */
	const STATUS_FINISH = 3;
	/**
	 * Cancel status
	 */
	const STATUS_CANCEL = 4;
	
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

	public function create()
	{

		$request = "SELECT MAX(rowid) AS rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
		$rowid = $this->db->query($request);
		$rowid = $this->db->fetch_object($rowid)->rowid;

		is_null($rowid) ? $this->id = 1 : $this->id = $rowid+1;

		$this->ref = "(PROV".$this->id.")";

		$request = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.' (rowid, ref, date_cre, date_maj, fk_product, fk_statut, dated) VALUES ('.(int)$this->id.', "'.$this->ref.'", NOW(), NOW(), '.$this->fk_product.', '.$this->status.', "'.$this->dated.'")';
		return $this->db->query($request);

	}

	public function set_values($value) {
		if (is_array($value)) {
			if (!empty($value['fk_product'])) {
				$product = new Product($this->db);
				$product->fetch($value['fk_product']);

				$this->fk_product = $product->id;
				if (!empty($value['dated'])) {
					if (preg_match('#^[0-9]+/[0-9]+/[0-9]+$#', $value['dated'])) {

						$date = explode("/", $value['dated']);
						$this->dated = $date[2]."-".$date[1]."-".$date[0];
					}
					else {
						$this->dated = $value['dated'];
					}
				}
			}
			else {
				$this->errors = "Le champ service est obligatoire.";
			}
		}
	}

	public function save($action) {
		switch ($action) {
			case 'create':
				if ($this->create()) {
					return 0;
				}
				break;
			case 'modifyStatus':
				$request = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ref='".$this->ref."', fk_statut=".$this->status.", date_maj=NOW() WHERE rowid=".$this->id;

				if ($this->db->query($request)) {
					return 0;
				}
				break;

		return -1;

		}
	}

	public function clone() {
		$param = [];
		$param['fk_product'] = $this->fk_product;
		$param['dated'] = $this->dated;

		$newForm = new Formation($this->db);
		$newForm->set_values($param);

		$newForm->save("create");

		return $newForm;
	}

	public function delete() {
		$request = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid=".$this->id;

		return $this->db->query($request);
	}

	/*public function save(&$PDOdb, $addprov=false)
	{
		global $user;
		
		if (!$this->getId()) $this->fk_user_author = $user->id;
		parent::set_table(MAIN_DB_PREFIX."formations");
		$res = parent::save($PDOdb);
		
		if ($addprov || !empty($this->is_clone))
		{
			$this->ref = '(PROV'.$this->getId().')';
			
			if (!empty($this->is_clone)) $this->status = self::STATUS_DRAFT;
			
			$wc = $this->withChild;
			$this->withChild = false;
			$res = parent::save($PDOdb);
			$this->withChild = $wc;
		}
		
		return $res;
	}*/

	function fetch($id)
	{
		$sql = " SELECT f.rowid, f.ref, f.date_cre, f.dated, f.datef, f.fk_statut, f.fk_user, f.fk_product, f.total_ht, f.total_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as f";
		$sql.= " WHERE f.rowid=".$id;
		$res = $this->db->query($sql);

		if ($res) {
			$obj = $this->db->fetch_object($res);

			$this->id = $obj->rowid;
			$this->ref = $obj->ref;
			$this->date_cre = $obj->date_cre;
			$this->dated = $obj->dated;
			$this->datef = $obj->datef;
			$this->status = $obj->fk_statut;
			$this->fk_user = $obj->fk_user;
			$this->fk_product = $obj->fk_product;
			$this->total_ht = $obj->total_ht;
			$this->total_ttc = $obj->total_ttc;
			return 1;
		}
		else {
			return -1;
		}
	}
	
	/*public function delete(&$PDOdb)
	{
		$this->generic->deleteObjectLinked();
		
		parent::delete($PDOdb);
	}*/
	
	/*public function fetchObjectLinked()
	{
		$this->generic->fetchObjectLinked($this->getId());
	}*/

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
//		global $user;
		
		$this->ref = $this->getNumero();
		$this->status = self::STATUS_VALIDATED;

		return $this->save("modifyStatus");
	}

	public function setPredict() {
		$this->status = self::STATUS_PREDICTION;

		return $this->save("modifyStatus");
	}

	public function setFinish() {
		$this->status = self::STATUS_FINISH;

		return $this->save("modifyStatus");
	}

	public function setCancel() {
		$this->status = self::STATUS_CANCEL;

		return $this->save("modifyStatus");
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
	
	public function getNomUrl($withpicto=0, $get_params='')
	{
		global $langs;

        $result='';
        $label = '<u>' . $langs->trans("Showformation") . '</u>';
        if (! empty($this->ref)) $label.= '<br /><b>'.$langs->trans('Ref').':</b> '."$this->ref";
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

	public function listFormationForUser($idUser) {

		$sql = "SELECT f.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as f";
		$sql.= " WHERE f.fk_user=".$idUser;

		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$newFormation=new Formation($this->db);
				$newFormation->fetch($obj->rowid);
				$ret[$obj->rowid]=$newFormation;
			}

			return $ret;
		}

		else {
			return -1;
		}

	}
	
	/*public static function getStaticNomUrl($id, $withpicto=0)
	{
		global $PDOdb;
		
		if (empty($PDOdb)) $PDOdb = new TPDOdb;
		
		$object = new Tformation;
		$object->load($PDOdb, $id, false);
		
		return $object->getNomUrl($withpicto);
	}*/
	
	public function getLibStatut($mode=0)
    {
        return self::LibStatut($this->status, $mode);
    }
	
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