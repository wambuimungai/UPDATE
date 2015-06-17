<?php
class Other_Illnesses extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar',150);
		$this -> hasColumn('indicator', 'varchar',20);
		$this -> hasColumn('active', 'int','5');

	}

	public function setUp() {
		$this -> setTableName('other_illnesses');
	}

	public static function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("other_illnesses")->where("active=1")->orderBy("name asc");
		$illnesses = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $illnesses;
	}

}
