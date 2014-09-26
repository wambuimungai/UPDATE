<?php
//error_reporting(0);
class auto_management extends MY_Controller {
	var $nascop_url = "";
	var $viral_load_url="";
	function __construct() {
		parent::__construct();

		ini_set("max_execution_time", "100000");
		ini_set("memory_limit", '2048M');
		ini_set("allow_url_fopen", '1');

	    $dir = realpath($_SERVER['DOCUMENT_ROOT']);
	    $link = $dir . "\\ADT\\assets\\nascop.txt";
		$this -> nascop_url = file_get_contents($link);
		$this -> eid_url="http://nascop.org/eid/";
	}

	public function index($manual=FALSE){
		$message ="";
		$today = (int)date('Ymd');

		//get last update time of log file for auto_update
		$log=Migration_Log::getLog('auto_update');
		$last_update = (int)$log['last_index'];

		//if not updated today
		if ($today != $last_update || $manual==TRUE) {
			//function to update destination column to 1 in drug_stock_movement table for issued transactions that have name 'pharm'
			$message .= $this->updateIssuedTo();
			//function to update source_destination column in drug_stock_movement table where it is zero
			$message .= $this->updateSourceDestination();
			//function to update ccc_store_sp column in drug_stock_movement table for pharmacy transactions
			$message .= $this->updateCCC_Store();
			//function to update patients without current_regimen with last regimen dispensed
			$message .= $this->update_current_regimen(); 
			//function to send eid statistics to nascop dashboard
			$message .= $this->updateEid();
			//function to update patient data such as active to lost_to_follow_up	
			$message .= $this->updatePatientData();
			//function to update data bugs by applying query fixes
			$message .= $this->updateFixes();
			//function to get viral load data
			$message .= $this->updateViralLoad();
			//function to add new facilities list
			$message .= $this->updateFacilties();
			//function to create new tables into adt
			$message .= $this->update_database_tables();
			//function to create new columns into table
			$message .= $this->update_database_columns();
			//function to update hash value of system to nascop
			$message .= $this->update_system_version();
	        //finally update the log file for auto_update 
	        if ($this -> session -> userdata("curl_error") != 1) {
	        	$sql="UPDATE migration_log SET last_index='$today' WHERE source='auto_update'";
				$this -> db -> query($sql);
				$this -> session -> set_userdata("curl_error", "");
			} 
	    }

	    if($manual==TRUE){
          	$message="<div class='alert alert-info'><button type='button' class='close' data-dismiss='alert'>&times;</button>".$message."</div>";
	    }
	    echo $message;
	}

	public function updateDrugId() {
		//function to update drug_id column in drug_stock_movement table where drug_id column is zero
		//Get batches for drugs which are associateed with those drugs
		$sql = "SELECT batch_number
				FROM  `drug_stock_movement` 
				WHERE drug =0 AND batch_number!=''
				ORDER BY  `drug_stock_movement`.`drug` ";

		$query = $this -> db -> query($sql);
		$res = $query -> result_array();
		$counter = 0;
		if($res){
			foreach ($res as $value) {
				$batch_number = $value['batch_number'];
				//Get drug  id from drug_stock_balance
				$sql = "SELECT drug_id FROM drug_stock_balance WHERE batch_number = '$batch_number' LIMIT 1";
				$query = $this -> db -> query($sql);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$drug_id = $res[0]['drug_id'];
					//Update drug id in drug stock movement
					$sql = "UPDATE drug_stock_movement SET drug = '$drug_id' WHERE batch_number = '$batch_number' AND drug = 0 ";
					$query = $this -> db -> query($sql);
					$counter++;
				}
			}
		}
		$message="";
		if($counter>0){
			$message=$counter . " records have been updated!<br/>";
		}
		return $message;
	}

	public function updateDrugPatientVisit() {
		//function to update drug column in patient_visit table where drug column is zero
		//Get batches for drugs which are associateed with those drugs
		$sql = "SELECT batch_number
				FROM  `patient_visit` 
				WHERE drug_id =0 AND batch_number!=''
				ORDER BY  `patient_visit`.`drug_id` ";

		$query = $this -> db -> query($sql);
		$res = $query -> result_array();
		$counter = 0;
		if($res){
			foreach ($res as $value) {
				$batch_number = $value['batch_number'];
				//Get drug  id from drug_stock_balance
				$sql = "SELECT drug_id FROM drug_stock_balance WHERE batch_number = '$batch_number' LIMIT 1";
				$query = $this -> db -> query($sql);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$drug_id = $res[0]['drug_id'];
					//Update drug id in patient visit
					$sql = "UPDATE patient_visit SET drug_id = '$drug_id' WHERE batch_number = '$batch_number' AND drug_id = '0' ";
					//echo $sql;die();
					$query = $this -> db -> query($sql);
					$counter++;
				}
			}
		}
		$message="";
		if($counter>0){
			$message=$counter . " records have been updated!<br/>";
		}
		return $message;
	}

	public function updateIssuedTo(){
		$sql="UPDATE drug_stock_movement
		      SET destination='1'
		      WHERE destination LIKE '%pharm%'";
		$this->db->query($sql);
		$count=$this->db->affected_rows();
		$message="(".$count.") issued to transactions updated!<br/>";
		$message="";
		if($count>0){
			$message="(".$count.") issued to transactions updated!<br/>";
		}
		return $message;
	}

	public function updateSourceDestination(){
		$values=array(
			      'received from'=>'source',
			      'returns from'=>'destination',
			      'issued to'=>'destination',
			      'returns to'=>'source'
			      );
		$message="";
		foreach($values as $transaction=>$column){
				$sql="UPDATE drug_stock_movement dsm
					  LEFT JOIN transaction_type t ON t.id=dsm.transaction_type
					  SET dsm.source_destination=IF(dsm.$column=dsm.facility,'1',dsm.$column)
				      WHERE t.name LIKE '%$transaction%'
					  AND(dsm.source_destination IS NULL OR dsm.source_destination='' OR dsm.source_destination=0)";
                $this->db->query($sql);
                $count=$this->db->affected_rows();
                $message.=$count." ".$transaction." transactions missing source_destination(".$column.") have been updated!<br/>";
		}
		if($count<=0){
			$message="";
		}
		return $message;
	}

	public function updateCCC_Store(){
        $facility_code=$this->session->userdata("facility");
		$sql="UPDATE drug_stock_movement dsm
		      SET ccc_store_sp='1'
		      WHERE dsm.source !=dsm.destination
		      AND ccc_store_sp='2' 
		      AND (dsm.source='$facility_code' OR dsm.destination='$facility_code')";
        $this->db->query($sql);
        $count=$this->db->affected_rows();
        $message="(".$count.") transactions changed from main pharmacy to main store!<br/>";

        if($count<=0){
			$message="";
		}
		return $message;
	}

	public function update_current_regimen() {
		$count=1;
		//Get all patients without current regimen and who are not active
		$sql_get_current_regimen = "SELECT p.id,p.patient_number_ccc, p.current_regimen ,ps.name
									FROM patient p 
									INNER JOIN patient_status ps ON ps.id = p.current_status
									WHERE current_regimen = '' 
									AND ps.name != 'active'";
		$query = $this -> db -> query($sql_get_current_regimen);
		$result_array = $query -> result_array();
		if($result_array){
			foreach ($result_array as $value) {
				$patient_id = $value['id'];
				$patient_ccc = $value['patient_number_ccc'];
				//Get last regimen
				$sql_last_regimen = "SELECT pv.last_regimen FROM patient_visit pv WHERE pv.patient_id='" . $patient_ccc . "' ORDER BY id DESC LIMIT 1";
				$query = $this -> db -> query($sql_last_regimen);
				$res = $query -> result_array();
				if (count($res) > 0) {
					$last_regimen_id = $res[0]['last_regimen'];
					$sql = "UPDATE patient p SET p.current_regimen ='" . $last_regimen_id . "'  WHERE p.id = '" . $patient_id . "'";
					$query = $this -> db -> query($sql);
					$count++;
				}
			}   
		}     
        $message="(".$count.") patients without current_regimen have been updated with last dispensed regimen!<br/>";
        if($count<=0){
			$message="";
		}
		return $message;
	}

	public function updateEid() {
		$message="";
		$adult_age = 3;
		$facility_code = $this -> session -> userdata("facility");
		$url = $this -> nascop_url . "sync/eid/" . $facility_code;
		$sql = "SELECT patient_number_ccc as patient_no,
		               facility_code,
		               g.name as gender,
		               p.dob as birth_date,
		               rst.Name as service,
		               CONCAT_WS(' | ',r.regimen_code,r.regimen_desc) as regimen,
		               p.date_enrolled as enrollment_date,
		               ps.name as source,
		               s.name as status
				FROM patient p
				LEFT JOIN gender g ON g.id=p.gender
				LEFT JOIN regimen_service_type rst ON rst.id=p.service
				LEFT JOIN regimen r ON r.id=p.start_regimen
				LEFT JOIN patient_source ps ON ps.id=p.source
				LEFT JOIN patient_status s ON s.id=p.current_status
				WHERE p.active='1'
				AND round(datediff(p.date_enrolled,p.dob)/360)<$adult_age";
		$query = $this -> db -> query($sql);
		$results = $query -> result_array();
		if($results){
			$json_data = json_encode($results, JSON_PRETTY_PRINT);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('json_data' => $json_data));
			$json_data = curl_exec($ch);
			if (empty($json_data)) {
				$message = "cURL Error: " . curl_error($ch);
				$this -> session -> set_userdata("curl_error", 1);
			} else {
				$messages = json_decode($json_data, TRUE);
				$message = $messages[0];
			}
			curl_close($ch);
		}
		return $message."<br/>";
	}
    
    public function updateSms() {
    	$alert="";
		$facility_name=$this -> session -> userdata('facility_name');
		$facility_phone=$this->session->userdata("facility_phone");
		$facility_sms_consent=$this->session->userdata("facility_sms_consent");

		if($facility_sms_consent==TRUE){
			/* Find out if today is on a weekend */
			$weekDay = date('w');
			if ($weekDay == 6) {
				$tommorrow = date('Y-m-d', strtotime('+2 day'));
			} else {
				$tommorrow = date('Y-m-d', strtotime('+1 day'));
			}

			$nextweek=date('Y-m-d', strtotime('+1 week'));

			$phone_minlength = '8';
			$phone = "";
			$phone_list = "";
			$messages_list="";
			$first_part = "";
			$kenyacode = "254";
			$arrDelimiters = array("/", ",", "+");

			/*Get All Patient Who Consented Yes That have an appointment Tommorow */
			$sql = "SELECT p.phone,p.patient_number_ccc,p.nextappointment,temp.patient,temp.appointment,temp.machine_code as status,temp.id
						FROM patient p
						LEFT JOIN 
						(SELECT pa.id,pa.patient, pa.appointment, pa.machine_code
						FROM patient_appointment pa
						WHERE pa.appointment IN ('$tommorrow','$nextweek')
						GROUP BY pa.patient) as temp ON temp.patient=p.patient_number_ccc
						WHERE p.sms_consent =  '1'
						AND p.nextappointment =temp.appointment
						AND char_length(p.phone)>$phone_minlength
						AND temp.machine_code !='s'
						GROUP BY p.patient_number_ccc";

			$query = $this -> db -> query($sql);
			$results = $query -> result_array();
			$phone_data=array();

			if ($results) {
				foreach ($results as $result) {
					$phone = $result['phone'];
					$appointment = $result['appointment'];
					$newphone = substr($phone, -$phone_minlength);
					$first_part = str_replace($newphone, "", $phone);
					$message = "You have an Appointment on " . date('l dS-M-Y', strtotime($appointment)) . " at $facility_name Contact Phone: $facility_phone";

					if (strlen($first_part) < 7) {
						if ($first_part === '07') {
							$phone = "+" . $kenyacode . substr($phone, 1);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						} else if ($first_part == '7') {
							$phone = "0" . $phone;
							$phone = "+" . $kenyacode . substr($phone, 1);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						} else if ($first_part == '+' . $kenyacode . '07') {
							$phone = str_replace($kenyacode . '07', $kenyacode . '7', $phone);
							$phone_list .= $phone;
							$messages_list .= "+" .$message;
						}

					} else {
						/*If Phone Does not meet requirements*/
						$phone = str_replace($arrDelimiters, "-|-", $phone);
						$phones = explode("-|-", $phone);

						foreach ($phones as $phone) {
							$newphone = substr($phone, -$phone_minlength);
							$first_part = str_replace($newphone, "", $phone);
							if (strlen($first_part) < 7) {
								if ($first_part === '07') {
									$phone = "+" . $kenyacode . substr($phone, 1);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								} else if ($first_part == '7') {
									$phone = "0" . $phone;
									$phone = "+" . $kenyacode . substr($phone, 1);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								} else if ($first_part == '+' . $kenyacode . '07') {
									$phone = str_replace($kenyacode . '07', $kenyacode . '7', $phone);
									$phone_list .= $phone;
									$messages_list .= "+" .$message;
									break;
								}
							}
						}
					}
					$stmt = "update patient_appointment set machine_code='s' where id='" . $result['id'] . "'";
					$q = $this -> db -> query($stmt);
				}
				$phone_list = substr($phone_list, 1);
				$messages_list = substr($messages_list, 1);

				$phone_list = explode("+", $phone_list);
			    $messages_list = explode("+", $messages_list);
			
				foreach ($phone_list as $counter=>$contact) {
					$message = urlencode($messages_list[$counter]);
					file("http://41.57.109.242:13000/cgi-bin/sendsms?username=clinton&password=ch41sms&to=$contact&text=$message");
				}
				$alert = "Patients notified (<b>" . sizeof($phone_list) . "</b>)";
			}
		}
		return $alert;
	}

	public function updatePatientData() {
		$days_to_lost_followup = 90;
		$days_to_pep_end = 30;
		$days_in_year = date("z", mktime(0, 0, 0, 12, 31, date('Y'))) + 1;
		$adult_age = 12;
		$active = 'active';
		$lost = 'lost';
		$pep = 'pep';
		$pmtct = 'pmtct';
		$two_year_days = $days_in_year * 2;
		$adult_days = $days_in_year * $adult_age;
		$message = "";

		//Get Patient Status id's
		$status_array = array($active, $lost, $pep, $pmtct);
		foreach ($status_array as $status) {
			$s = "SELECT id,name FROM patient_status ps WHERE ps.name LIKE '%$status%'";
			$q = $this -> db -> query($s);
			$rs = $q -> result_array();
			if($rs){
			    $state[$status] = $rs[0]['id'];
			}	
		}

		/*Change Last Appointment to Next Appointment*/
		$sql['Change Last Appointment to Next Appointment'] = "(SELECT patient_number_ccc,nextappointment,temp.appointment,temp.patient
					FROM patient p
					LEFT JOIN 
					(SELECT MAX(pa.appointment)as appointment,pa.patient
					FROM patient_appointment pa
					GROUP BY pa.patient) as temp ON p.patient_number_ccc =temp.patient
					WHERE p.nextappointment !=temp.patient
					AND DATEDIFF(temp.appointment,p.nextappointment)>0
					GROUP BY p.patient_number_ccc) as p1
					SET p.nextappointment=p1.appointment";

		/*Change Active to Lost_to_follow_up*/
		$sql['Change Active to Lost_to_follow_up'] = "(SELECT patient_number_ccc,nextappointment,DATEDIFF(CURDATE(),nextappointment) as days
				   FROM patient p
				   LEFT JOIN patient_status ps ON ps.id=p.current_status
				   WHERE ps.Name LIKE '%$active%'
				   AND (DATEDIFF(CURDATE(),nextappointment )) >=$days_to_lost_followup) as p1
				   SET p.current_status = '$state[$lost]'";

		/*Change Lost_to_follow_up to Active */
		$sql['Change Lost_to_follow_up to Active'] = "(SELECT patient_number_ccc,nextappointment,DATEDIFF(CURDATE(),nextappointment) as days
				   FROM patient p
				   LEFT JOIN patient_status ps ON ps.id=p.current_status
				   WHERE ps.Name LIKE '%$lost%'
				   AND (DATEDIFF(CURDATE(),nextappointment )) <$days_to_lost_followup) as p1
				   SET p.current_status = '$state[$active]' ";

		/*Change Active to PEP End*/
		$sql['Change Active to PEP End'] = "(SELECT patient_number_ccc,rst.name as Service,ps.Name as Status,DATEDIFF(CURDATE(),date_enrolled) as days_enrolled
				   FROM patient p
				   LEFT JOIN regimen_service_type rst ON rst.id=p.service
				   LEFT JOIN patient_status ps ON ps.id=p.current_status
				   WHERE (DATEDIFF(CURDATE(),date_enrolled))>=$days_to_pep_end 
				   AND rst.name LIKE '%$pep%' 
				   AND ps.Name NOT LIKE '%$pep%') as p1
				   SET p.current_status = '$state[$pep]' ";

		/*Change PEP End to Active*/
		$sql['Change PEP End to Active'] = "(SELECT patient_number_ccc,rst.name as Service,ps.Name as Status,DATEDIFF(CURDATE(),date_enrolled) as days_enrolled
				   FROM patient p
				   LEFT JOIN regimen_service_type rst ON rst.id=p.service
				   LEFT JOIN patient_status ps ON ps.id=p.current_status
				   WHERE (DATEDIFF(CURDATE(),date_enrolled))<$days_to_pep_end 
				   AND rst.name LIKE '%$pep%' 
				   AND ps.Name NOT LIKE '%$active%') as p1
				   SET p.current_status = '$state[$active]' ";

		/*Change Active to PMTCT End(children)*/
		$sql['Change Active to PMTCT End(children)'] = "(SELECT patient_number_ccc,rst.name AS Service,ps.Name AS Status,DATEDIFF(CURDATE(),dob) AS days
				   FROM patient p
				   LEFT JOIN regimen_service_type rst ON rst.id = p.service
				   LEFT JOIN patient_status ps ON ps.id = p.current_status
				   WHERE (DATEDIFF(CURDATE(),dob )) >=$two_year_days
				   AND (DATEDIFF(CURDATE(),dob)) <$adult_days
				   AND rst.name LIKE  '%$pmtct%'
				   AND ps.Name NOT LIKE  '%$pmtct%') as p1
				   SET p.current_status = '$state[$pmtct]'";

		/*Change PMTCT End to Active(Adults)*/
		$sql['Change PMTCT End to Active(Adults)'] = "(SELECT patient_number_ccc,rst.name AS Service,ps.Name AS Status,DATEDIFF(CURDATE(),dob) AS days
				   FROM patient p
				   LEFT JOIN regimen_service_type rst ON rst.id = p.service
				   LEFT JOIN patient_status ps ON ps.id = p.current_status 
				   WHERE (DATEDIFF(CURDATE(),dob)) >=$two_year_days 
				   AND (DATEDIFF(CURDATE(),dob)) >=$adult_days 
				   AND rst.name LIKE '%$pmtct%'
				   AND ps.Name LIKE '%$pmtct%') as p1
				   SET p.current_status = '$state[$active]'";

				foreach ($sql as $i => $q) {
					$stmt1 = "UPDATE patient p,";
					$stmt2 = " WHERE p.patient_number_ccc=p1.patient_number_ccc;";
					$stmt1 .= $q;
					$stmt1 .= $stmt2;
					$q = $this -> db -> query($stmt1);
					if ($this -> db -> affected_rows() > 0) {
						$message .= $i . "(<b>" . $this -> db -> affected_rows() . "</b>) rows affected<br/>";
					}
				}
		return $message;
	}

	public function updateFixes(){
		//Rename the prophylaxis cotrimoxazole
        $fixes[]="UPDATE drug_prophylaxis
        	      SET name='cotrimoxazole'
        	      WHERE name='cotrimozazole'";
        //Remove start_regimen_date in OI only patients records
        $fixes[]="UPDATE patient p
                  LEFT JOIN regimen_service_type rst ON p.service=rst.id
                  SET p.start_regimen_date='' 
                  WHERE rst.name LIKE '%oi%'
                  AND p.start_regimen_date IS NOT NULL";
        //Update status_change_date for lost_to_follow_up patients
        $fixes[]="UPDATE patient p,
				 (SELECT p.id, INTERVAL 90 DAY + p.nextappointment AS choosen_date
				  FROM patient p
				  LEFT JOIN patient_status ps ON ps.id = p.current_status
				  WHERE ps.Name LIKE  '%lost%') as test 
				 SET p.status_change_date=test.choosen_date
				 WHERE p.id=test.id";
	    //Update patients without service lines ie Pep end status should have pep as a service line
        $fixes[]="UPDATE patient p
			 	  LEFT JOIN patient_status ps ON ps.id=p.current_status,
			 	  (SELECT id 
			 	   FROM regimen_service_type
			 	   WHERE name LIKE '%pep%') as rs
			 	  SET p.service=rs.id
			 	  WHERE ps.name LIKE '%pep end%'
			 	  AND p.service=''";
		//Updating patients without service lines ie PMTCT status should have PMTCT as a service line
        $fixes[]= "UPDATE patient p
				   LEFT JOIN patient_status ps ON ps.id=p.current_status,
				   (SELECT id 
				 	FROM regimen_service_type
				 	WHERE name LIKE '%pmtct%') as rs
				    SET p.service=rs.id
				    WHERE ps.name LIKE '%pmtct end%'
				 	AND p.service=''";
		//Remove ??? in drug instructions
		$fixes[]="UPDATE drug_instructions 
				  SET name=REPLACE(name, '?', '.')
				  WHERE name LIKE '%?%'";

		$facility_code=$this->session->userdata("facility");
		//Auto Update Supported and supplied columns for satellite facilities
		$fixes[] = "UPDATE facilities f, 
						(SELECT facilitycode,supported_by,supplied_by
					     FROM facilities 
					     WHERE facilitycode='$facility_code') as temp
	                SET f.supported_by=temp.supported_by,
	                f.supplied_by=temp.supplied_by
	                WHERE f.parent='$facility_code'
	                AND f.parent !=f.facilitycode";
	    //Auto Update to trim other_drugs,adr and other_illnesses
	    $fixes[]="UPDATE patient p
				  SET p.other_drugs = TRIM(Replace(Replace(Replace(p.other_drugs,'\t',''),'\n',''),'\r','')),
				  p.other_illnesses = TRIM(Replace(Replace(Replace(p.other_illnesses,'\t',''),'\n',''),'\r','')),
				  p.adr = TRIM(Replace(Replace(Replace(p.adr,'\t',''),'\n',''),'\r',''))";

		//Execute fixes
		$total=0;
		foreach ($fixes as $fix) {
			//will exempt all database errors
			$db_debug = $this->db->db_debug;
			$this->db->db_debug = false;
			$this -> db -> query($fix);
			$this->db->db_debug = $db_debug;
			//count rows affected by fixes
			if ($this -> db -> affected_rows() > 0) {
				$total += $this -> db -> affected_rows();
			}
	    }
        
        $message="(".$total.") rows affected by fixes applied!<br/>";
	    if($total>0){
			$message="";
		}
        return $message;
	}

	public function updateViralLoad(){
		$facility_code = $this -> session -> userdata("facility");
		$url = $this -> eid_url . "vlapi.php?mfl=" . $facility_code;
		$patient_tests=array();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$json_data = curl_exec($ch);
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch)."<br/>";
			$this -> session -> set_userdata("curl_error", 1);
		} else {
			$data = json_decode($json_data, TRUE);
			$lab_data=$data['posts'];
			foreach($lab_data as $lab){
				foreach($lab as $tests){
				   $ccc_no=trim($tests['Patient']);
				   $result=$tests['Result'];
				   $date_tested=$tests['DateTested'];
				   $patient_tests[$ccc_no][]=array('date_tested'=>$date_tested,'result'=>$result);
                }
			}
		    $message="Viral Load Download Success!<br/>";
		}
		curl_close($ch);
        //write to file
		$fp = fopen('assets/viral_load.json', 'w');
		fwrite($fp, json_encode($patient_tests,JSON_PRETTY_PRINT));
		fclose($fp);
		return $message;
	}

	public function updateFacilties(){
		$total=Facilities::getTotalNumber();
		$message="";
		if($total < 9800){
			$this -> load -> library('PHPExcel');
			$inputFileType = 'Excel5';
			$inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/ADT/assets/facility_list.xls';
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objPHPExcel = $objReader -> load($inputFileName);
			$highestColumm = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestColumn();
			$highestRow = $objPHPExcel -> setActiveSheetIndex(0) -> getHighestRow();
			$arr = $objPHPExcel -> getActiveSheet() -> toArray(null, true, true, true);
			$facilities=array();
			$facility_code=$this->session->userdata("facility");
			$lists=Facilities::getParentandSatellites($facility_code);

			for ($row = 2; $row < $highestRow; $row++) {
				$facility_id=$arr[$row]['A'];
				$facility_name=$arr[$row]['B'];
				$facility_type_name=str_replace(array("'"), "", $arr[$row]['G']);
				$facility_type_id=Facility_Types::getTypeID($facility_type_name);
				$district_name=str_replace(array("'"), "", $arr[$row]['E']);
				$district_id=District::getID($district_name);
				$county_name=str_replace(array("'"), "", $arr[$row]['D']);
				$county_id=Counties::getID($county_name);
				$email=$arr[$row]['T'];
				$phone=$arr[$row]['R'];
				$adult_age=15;
				$weekday_max='';
				$weekend_max='';
				$supported_by='';
				$service_art=0;
				if(strtolower($arr[$row]['AD'])=="y"){
					$service_art=1;
				}
				$service_pmtct=0;
				if(strtolower($arr[$row]['AR'])=="y"){
					$service_pmtct=1;
				}
				$service_pep=0;
				$supplied_by='';
				$parent='';
				$map=0;
		        //if is this facility or satellite of this facility
				if(in_array($facility_id,$lists)){
					$details=Facilities::getCurrentFacility($facility_id);
					if($details){
	                   	$parent=$details[0]['parent'];
						$supported_by=$details[0]['supported_by'];
						$supplied_by=$details[0]['supplied_by'];
						$service_pep=$details[0]['service_pep'];
						$weekday_max=$details[0]['weekday_max'];
					    $weekend_max=$details[0]['weekend_max'];
					    $map=$details[0]['map'];
					}
				}
				//append to facilities data array
				$facilities[$row]=array(
					                'facilitycode'=>$facility_id,
					                'name'=>$facility_name,
					                'facilitytype'=>$facility_type_id,
					                'district'=>$district_id,
					                'county'=>$county_id,
					                'email'=>$email,
					                'phone'=>$phone,
					                'adult_age'=>$adult_age,
					                'weekday_max'=>$weekday_max,
					                'weekend_max'=>$weekend_max,
					                'supported_by'=>$supported_by,
					                'service_art'=>$service_art,
					                'service_pmtct'=>$service_pmtct,
					                'service_pep'=>$service_pep,
					                'supplied_by'=>$supplied_by,
					                'parent'=>$parent,
					                'map'=>$map);
			}
			$sql="TRUNCATE facilities";
			$this->db->query($sql);
			$this->db->insert_batch('facilities',$facilities);
			$counter=count($facilities);
			$message=$counter . " facilities have been added!<br/>";
	    }
		return $message;
	}
	public function update_database_tables(){
		$count=0;
		$message="";
		$tables['dependants'] = "CREATE TABLE dependants(
									id int(11),
									parent varchar(30),
									child varchar(30),
									PRIMARY KEY (id)
									);";
        $tables['spouses']= "CREATE TABLE spouses(
								id int(11),
								primary_spouse varchar(30),
								secondary_spouse varchar(30),
								PRIMARY KEY (id)
								);";
        $tables['drug_instructions']="CREATE TABLE IF NOT EXISTS `drug_instructions` (
									  `id` int(11) NOT NULL AUTO_INCREMENT,
									  `name` varchar(255) NOT NULL,
									  `active` int(11) NOT NULL,
									  PRIMARY KEY (`id`)
									) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35;
									INSERT INTO `drug_instructions` (`id`, `name`, `active`) VALUES
									(1, 'Warning. May cause drowsiness', 1),
									(2, 'Warning. May cause drowsiness. If affected to do not drive or operate machinery.Avoid alcoholic drink', 1),
									(3, 'Warning. May cause drowsiness. If affected to do not drive or operate machinery.', 1),
									(4, 'Warning. Avoid alcoholic drink', 1),
									(5, 'Do not take indigestion remedies at the same time of the day as this medicine', 1),
									(6, 'Do not take indigestion remedies or medicines containing Iron or Zinc at the same time of a day as this medicine', 1),
									(7, 'Do not take milk, indigestion remedies, or medicines containing Iron or Zinc at the same time of day as this medicine', 1),
									(8, 'Do not stop taking this medicine except on your doctor''s advice', 1),
									(9, 'Take at regular intervals. Complete the prescribed course unless otherwise directed', 1),
									(10, 'Warning. Follow the printed instruction you have been given with this medicine', 1),
									(11, 'Avoid exposure of skin to direct sunlight or sun lamps', 1),
									(12, 'Do not take anything containing aspirin while taking  this medicine', 1),
									(13, 'Dissolve or mix with water before taking', 1),
									(14, 'This medicine may colour the urine', 1),
									(15, 'Caution flammable: Keep away from fire or flames', 1),
									(16, 'Allow to dissolve under the tongue. Do not transfer from this container. Keep tightly closed. Discard 8 weeks after opening.', 1),
									(17, 'Do not take more than??.in 24 hours', 1),
									(18, 'Do not take more than ?..in 24 hours or?. In any one week', 1),
									(19, 'Warning. Causes drowsiness which may continue the next day. If affected do not drive or operate machinery. Avoid alcoholic drink', 1),
									(20, '??..with or after food', 1),
									(21, '???.half to one hour after food', 1),
									(22, '????..an hour before food or on an empty stomach', 1),
									(23, '???.an hour before food or on an empty stomach', 1),
									(24, '???. sucked or chewed', 1),
									(25, '??? swallowed whole, not chewed', 1),
									(26, '???dissolved under the tongue', 1),
									(27, '????with plenty of water', 1),
									(28, 'To be spread thinly?..', 1),
									(29, 'Do not take more than  2 at any one time. Do not take more than 8 in 24 hours', 1),
									(30, 'Do not take with any other paracetamol products.', 1),
									(31, 'Contains aspirin and paracetamol. Do not take with any other paracetamol products', 1),
									(32, 'Contains aspirin', 1),
									(33, 'contains an apirin-like medicine', 1),
									(34, 'Avoid a lot of fatty meals together with efavirenz', 1);";
		$tables['sync_regimen_category']="CREATE TABLE IF NOT EXISTS `sync_regimen_category` (
										  `id` int(2) NOT NULL AUTO_INCREMENT,
										  `Name` varchar(50) NOT NULL,
										  `Active` varchar(2) NOT NULL,
										  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
										  PRIMARY KEY (`id`),
										  KEY `ccc_store_sp` (`ccc_store_sp`)
										) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14;
										INSERT INTO `sync_regimen_category` (`id`, `Name`, `Active`, `ccc_store_sp`) VALUES
										(4, 'Adult First Line', '1', 2),
										(5, 'Adult Second Line', '1', 2),
										(6, 'Other Adult ART', '1', 2),
										(7, 'Paediatric First Line', '1', 2),
										(8, 'Paediatric Second Line', '1', 2),
										(9, 'Other Pediatric Regimen', '1', 2),
										(10, 'PMTCT Mother', '1', 2),
										(11, 'PMTCT Child', '1', 2),
										(12, 'PEP Adult', '1', 2),
										(13, 'PEP Child', '', 2);";
        foreach($tables as $table=>$statements){
            if (!$this->db->table_exists($table)){
            	$statements=explode(";",$statements);
            	foreach($statements as $statement){
            		$this->db->query($statement);
            	}
		        $count++;
			}
        }

        if($count>0){
 			$message="(".$count.") tables created!<br/>";
        }
        return $message;
	}

	public function update_database_columns(){
		$message='';
		$statements['isoniazid_start_date']='ALTER TABLE patient ADD isoniazid_start_date varchar(20)';
		$statements['isoniazid_end_date']='ALTER TABLE patient ADD isoniazid_end_date varchar(20)';
		$statements['tb_category']='ALTER TABLE patient ADD tb_category varchar(2)';
		$statements['spouses']='ALTER TABLE `spouses` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT';
		$statements['dependants']='ALTER TABLE `dependants` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT';
		if ($statements) {
			foreach ($statements as $column => $statement) {
				if ($statement != null) {
				    $db_debug = $this->db->db_debug;
					$this->db->db_debug = false;
					$this -> db -> query($statement);
					$this->db->db_debug = $db_debug;
				}
			}
		}
		return $message;
	}
	public function update_system_version(){
		$url = $this -> nascop_url . "sync/gitlog";
		$facility_code = $this -> session -> userdata("facility");
		$hash=Git_Log::getLatestHash();
		$results = array("facility_code" => $facility_code, "hash_value" => $hash);
		$json_data = json_encode($results, JSON_PRETTY_PRINT);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('json_data' => $json_data));
		$json_data = curl_exec($ch);
		if (empty($json_data)) {
			$message = "cURL Error: " . curl_error($ch);
		} else {
			$messages = json_decode($json_data, TRUE);
			$message = $messages[0];
		}
		curl_close($ch);
		return $message;
	}
}
?>