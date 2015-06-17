<?php
class Who_Stage extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 50);
	}

	public function setUp() {
		$this -> setTableName('who_stage');
	}

	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("who_stage");
		$stages = $query -> execute();
		return $stages;
	}

	public function getAllHydrated() {
		$query = Doctrine_Query::create() -> select("*") -> from("who_stage");
		$stages = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $stages;
	}

}
?>