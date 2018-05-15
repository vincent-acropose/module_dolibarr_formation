<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class Rh extends CommonObject
{

	public $table_element='rh';

	public function __construct($db)
	{
		global $langs;
		
		$this->db = $db;
	}

	/* ----------------------------- */
	/* ---------- GETTERS ---------- */
	/* ----------------------------- */
	public function getNextId() {
		$id = $this->request("SELECT MAX(rowid) AS rowid FROM ".MAIN_DB_PREFIX.$this->table_element);

		is_null($id) ? $id = 1 : $id = $id+1;

		return $id;
	}

	public function getSalary($userId)
	{
		$sql = 'SELECT salary FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE fk_user='.(int)$userId;
		$result = $this->request($sql);
		if ($result) {
			return $result;
		}
		else {
			return -1;
		}
	}

	/* ----------------------------- */
	/* ---------- SETTERS ---------- */
	/* ----------------------------- */
	public function setSalary($userId, $salary)
	{
		if ($this->getSalary($userId) == -1) {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.' (rowid, fk_user, salary) VALUES ('.$this->getNextId().', '.$userId.', '.$salary.')';
		}
		else {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET salary='.$salary.' WHERE fk_user='.$userId;
		}

		$result = $this->request($sql, 1);
		return $result;
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
}