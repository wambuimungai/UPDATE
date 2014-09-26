<div id="top_reports">
	<ul class="breadcrumb">
	  <li><?php echo anchor('report_management','Reports') ?><span class="divider">/</span></li>
	  <li class="active"><?php echo $selected_report_type.' - '.$report_title ?></li>
	</ul>
	
	<ul class="nav nav-tabs">  
		<li id="standard_report" class="active reports_tabs"><a  href="#">Standard Reports</a> </li>   
		<li id="visiting_patient" class="reports_tabs"><a  href="#">Visiting Patients</a></li> 
		<li id="early_warning_indicators" class="reports_tabs"><a  href="#">Early Warning Indicators</a> </li>   
		<li id="drug_inventory" class="reports_tabs"><a  href="#">Drug Inventory</a></li>   
		<li id="moh_forms" class="reports_tabs"><a  href="#">MOH Forms</a></li> 
	</ul> 
	
	<div id="report_container">
		<?php echo $this->load->view('reports/report_home_types_v'); ?>
	</div>	
</div>
<h2 id="facility_name" style="text-align: center"><?php if(isset($facility_name)){echo $facility_name;}  ?></h2>