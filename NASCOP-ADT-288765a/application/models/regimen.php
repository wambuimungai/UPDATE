<?php
class Regimen extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Regimen_Code', 'varchar', 20);
		$this -> hasColumn('Regimen_Desc', 'varchar', 50);
		$this -> hasColumn('Category', 'varchar', 30);
		$this -> hasColumn('Line', 'varchar', 4);
		$this -> hasColumn('Type_Of_Service', 'varchar', 20);
		$this -> hasColumn('Remarks', 'varchar', 30);
		$this -> hasColumn('Enabled', 'varchar', 4);
		$this -> hasColumn('Source', 'varchar', 10);
		$this -> hasColumn('Optimality', 'varchar', 1);
		$this -> hasColumn('Merged_To', 'varchar', 50);
		$this -> hasColumn('map', 'int', 11);
	}

	public function setUp() {
		$this -> setTableName('regimen');
		$this -> hasOne('Regimen_Category as Regimen_Category', array('local' => 'Category', 'foreign' => 'id'));
		$this -> hasOne('Regimen_Service_Type as Regimen_Service_Type', array('local' => 'Type_Of_Service', 'foreign' => 'id'));
		$this -> hasMany('Regimen_Drug as Drugs', array('local' => 'id', 'foreign' => 'Regimen'));
		$this -> hasOne('Sync_Regimen as S_Regimen', array('local' => 'map', 'foreign' => 'id'));
	}

	public function getAll($source = 0) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where('Source = "' . $source . '" or Source ="0"') -> orderBy("Regimen_Desc asc");
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getAllEnabled($source = 0) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where('enabled="1"') -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getAllObjects($source = 0) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where('Source = "' . $source . '" or Source ="0"') -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getAllHydrated($source = 0, $access_level = "") {
		if ($access_level == "" || $access_level == "facility_administrator") {
			$displayed_enabled = "Source='0' or Source !='0'";
		} else {
			$displayed_enabled = "(Source='$source' or Source='0') AND Enabled='1'";
		}
		$query = Doctrine_Query::create() -> select("r.Regimen_Code, r.Regimen_Desc,Line,rc.Name as Regimen_Category, rst.Name as Regimen_Service_Type,r.Enabled,r.Merged_To,r.map") -> from("Regimen r") -> leftJoin('r.Regimen_Category rc, r.Regimen_Service_Type rst') -> where($displayed_enabled) -> orderBy("r.id desc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

	public function getTotalNumber($source = 0) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Regimens") -> from("Regimen") -> where('Source = "' . $source . '" or Source ="0"');
		$total = $query -> execute();
		return $total[0]['Total_Regimens'];
	}

	public function getPagedRegimens($offset, $items, $source = 0) {
		$query = Doctrine_Query::create() -> select("Regimen_Code,Regimen_Desc,Category,Line,Type_Of_Service,Remarks,Enabled") -> from("Regimen") -> where('Source = "' . $source . '" or Source ="0"') -> offset($offset) -> limit($items);
		$regimens = $query -> execute();
		return $regimens;
	}

	public function getOptimalityRegimens($optimality) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where('Optimality = "' . $optimality . '" and Source ="0"') -> orderBy("Regimen_Desc asc");
		$regimens = $query -> execute();
		return $regimens;
	}

	public static function getRegimen($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where("id = '$id'");
		$regimens = $query -> execute();
		return $regimens[0];

	}

	public static function getHydratedRegimen($id) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where("id = '$id'");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;

	}

	public function getRegimens() {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where("Enabled = '1'") -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}
	public function getNonMappedRegimens() {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where("Enabled = '1' AND (map='' OR map='0')") -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

	public function getLineRegimens($service) {
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen") -> where("Enabled = '1' and Type_Of_Service='$service'") -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}
		
	public function getChildRegimens(){
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen r") -> where("(r.Regimen_Category.Name LIKE '%paed%' OR r.Regimen_Category.Name LIKE '%child%'  OR r.Regimen_Category.Name LIKE '%oi%')  AND r.Enabled = '1'") -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

	public function getAdultRegimens(){
		$query = Doctrine_Query::create() -> select("*") -> from("Regimen r") -> where("(r.Regimen_Category.Name LIKE '%adult%' OR r.Regimen_Category.Name LIKE '%mother%' OR r.Regimen_Category.Name LIKE '%oi%')  AND r.Enabled = '1'") -> orderBy("Regimen_Code asc");
		$regimens = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $regimens;
	}

}
?>