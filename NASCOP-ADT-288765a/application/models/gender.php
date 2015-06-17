<?php
class Gender extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('name', 'varchar', 20);		
	}

	public function setUp() {
		$this -> setTableName('gender');
	}
	
	public function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("gender");
		$gender = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $gender;
	}

	
}
