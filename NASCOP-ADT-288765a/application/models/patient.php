<?php
class Patient extends Doctrine_Record {

	public function setTableDefinition() {
		$this -> hasColumn('Medical_Record_Number', 'varchar', 10);
		$this -> hasColumn('Patient_Number_CCC', 'varchar', 10);
		$this -> hasColumn('First_Name', 'varchar', 50);
		$this -> hasColumn('Last_Name', 'varchar', 50);
		$this -> hasColumn('Other_Name', 'varchar', 50);
		$this -> hasColumn('Dob', 'varchar', 32);
		$this -> hasColumn('Pob', 'varchar', 100);
		$this -> hasColumn('Gender', 'varchar', 2);
		$this -> hasColumn('Pregnant', 'varchar', 2);
		$this -> hasColumn('Weight', 'varchar', 5);
		$this -> hasColumn('Height', 'varchar', 5);
		$this -> hasColumn('Sa', 'varchar', 5);
		$this -> hasColumn('Phone', 'varchar', 30);
		$this -> hasColumn('Physical', 'varchar', 100);
		$this -> hasColumn('Alternate', 'varchar', 50);
		$this -> hasColumn('Other_Illnesses', 'text');
		$this -> hasColumn('Other_Drugs', 'text');
		$this -> hasColumn('Adr', 'text');
		$this -> hasColumn('Drug_Allergies', 'text');
		$this -> hasColumn('Tb', 'varchar', 2);
		$this -> hasColumn('Smoke', 'varchar', 2);
		$this -> hasColumn('Alcohol', 'varchar', 2);
		$this -> hasColumn('Date_Enrolled', 'varchar', 32);
		$this -> hasColumn('Source', 'varchar', 2);
		$this -> hasColumn('Supported_By', 'varchar', 2);
		$this -> hasColumn('Timestamp', 'varchar', 32);
		$this -> hasColumn('Facility_Code', 'varchar', 10);
		$this -> hasColumn('Service', 'varchar', 5);
		$this -> hasColumn('Start_Regimen', 'varchar', 5);
		$this -> hasColumn('Start_Regimen_Date', 'varchar', 20);
		$this -> hasColumn('Machine_Code', 'varchar', 5);
		$this -> hasColumn('Current_Status', 'varchar', 10);
		$this -> hasColumn('SMS_Consent', 'varchar', 2);
		$this -> hasColumn('Partner_Status', 'varchar', 2);
		$this -> hasColumn('Fplan', 'text');
		$this -> hasColumn('tb_category', 'varchar', 2);
		$this -> hasColumn('Tbphase', 'varchar', 2);
		$this -> hasColumn('Startphase', 'varchar', 15);
		$this -> hasColumn('Endphase', 'varchar', 15);
		$this -> hasColumn('Disclosure', 'varchar', 2);
		$this -> hasColumn('Status_Change_Date', 'varchar', 2);
		$this -> hasColumn('Support_Group', 'varchar', 255);
		$this -> hasColumn('Current_Regimen', 'varchar', 255);
		$this -> hasColumn('Start_Regimen_Merged_From', 'varchar', 20);
		$this -> hasColumn('Current_Regimen_Merged_From', 'varchar', 20);
		$this -> hasColumn('NextAppointment', 'varchar', 20);
		$this -> hasColumn('Start_Height', 'varchar', 20);
		$this -> hasColumn('Start_Weight', 'varchar', 20);
		$this -> hasColumn('Start_Bsa', 'varchar', 20);
		$this -> hasColumn('Transfer_From', 'varchar', 100);
		$this -> hasColumn('Active', 'int', 5);
		$this -> hasColumn('Drug_Allergies', 'text');
		$this -> hasColumn('Tb_Test', 'int', '11');
		$this -> hasColumn('Pep_Reason', 'int', 11);
		$this -> hasColumn('who_stage', 'int', 11);
		$this -> hasColumn('drug_prophylaxis', 'varchar', 20);
		$this -> hasColumn('isoniazid_start_date', 'varchar', 20);
		$this -> hasColumn('isoniazid_end_date', 'varchar', 20);
		$this -> hasColumn('tb_category', 'varchar', 2);
	}

	public function setUp() {
		$this -> setTableName('patient');
		$this -> hasOne('District as PDistrict', array('local' => 'Pob', 'foreign' => 'id'));
		$this -> hasOne('Gender as PGender', array('local' => 'Gender', 'foreign' => 'id'));
		$this -> hasOne('Patient_Source as PSource', array('local' => 'Source', 'foreign' => 'id'));
		$this -> hasOne('Supporter as PSupporter', array('local' => 'Supported_By', 'foreign' => 'id'));
		$this -> hasOne('Regimen_Service_Type as PService', array('local' => 'Service', 'foreign' => 'id'));
		$this -> hasOne('Regimen as SRegimen', array('local' => 'Start_Regimen', 'foreign' => 'id'));
		$this -> hasOne('Regimen as Parent_Regimen', array('local' => 'Current_Regimen', 'foreign' => 'id'));
		$this -> hasOne('Patient_Status as Parent_Status', array('local' => 'Current_Status', 'foreign' => 'id'));
		$this -> hasOne('Facilities as TFacility', array('local' => 'Transfer_From', 'foreign' => 'facilitycode'));
		$this -> hasOne('Pep_Reason as PReason', array('local' => 'Pep_Reason', 'foreign' => 'id'));
		$this -> hasOne('Who_Stage as PStage', array('local' => 'who_stage', 'foreign' => 'id'));
	}

	public function getPatientNumbers($facility) {
		$query = Doctrine_Query::create() -> select("count(*) as Total_Patients") -> from("Patient") -> where("Facility_Code = $facility");
		$total = $query -> execute();
		return $total[0]['Total_Patients'];
	}

	public function getPagedPatients($offset, $items, $machine_code, $patient_ccc, $facility) {
		$query = Doctrine_Query::create() -> select("p.*") -> from("Patient p") -> leftJoin("Patient p2") -> where("p2.Patient_Number_CCC = '$patient_ccc' and p2.Machine_Code = '$machine_code' and p2.Facility_Code=$facility and p.Facility_Code=$facility") -> offset($offset) -> limit($items);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getAllPatients($facility) {
		$query = Doctrine_Query::create() -> select("*") -> from("patient") -> where("Facility_Code='$facility'");
		$patients = $query -> execute();
		return $patients;
	}

	public function getPagedFacilityPatients($offset, $items, $facility) {
		$query = Doctrine_Query::create() -> select("*") -> from("Patient") -> where("Facility_Code=$facility") -> offset($offset) -> limit($items);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getPatientAllergies($patient_no) {
		$query = Doctrine_Query::create() -> select("Adr") -> from("Patient") -> where("Patient_Number_CCC='$patient_no'") -> limit(1);
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients[0];
	}

	public function getEnrollment($period_start, $period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "adult_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "adult_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "child_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "child_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("p.PSource.Name as source_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled BETWEEN '$period_start' AND '$period_end' $condition") -> groupBy("p.Source");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getStages($period_start, $period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "adult_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "adult_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "child_male") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "child_female") {
			$condition = "AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_start',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("p.PStage.name as stage_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled BETWEEN '$period_start' AND '$period_end' $condition") -> groupBy("p.who_stage");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

	public function getPregnant($period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "F163") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "D163") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("'$indicator' as status_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled <='$period_end' AND p.Pregnant='1' $condition") -> groupBy("p.Pregnant");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}
	
	public function getAllArv($period_end, $indicator) {
		$adult_age = 15;
		if ($indicator == "G164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "F164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)>=$adult_age and p.Active='1'";
		} else if ($indicator == "E164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'male' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		} else if ($indicator == "D164") {
			$condition = "AND p.Parent_Status.Name LIKE '%active%' AND p.PService.Name LIKE '%art%' AND p.PGender.name LIKE 'female' AND round(datediff('$period_end',p.dob)/360)<$adult_age and p.Active='1'";
		}
		$query = Doctrine_Query::create() -> select("'$indicator' as status_name,COUNT(*) as total") -> from("Patient p") -> where("p.Date_Enrolled <='$period_end' AND p.Pregnant !='1' $condition");
		$patients = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $patients;
	}

}
