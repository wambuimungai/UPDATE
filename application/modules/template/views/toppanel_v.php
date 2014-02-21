<div id="top-panel">
	<!--Logo and Banner Details-->
	<div class="top_logo">
		<div id="system_title">
			<?php
			$this -> load -> view('banner_v');
			?>
			<div id="facility_name">
				<span class="firm_name"> <?php echo $firm_name;?></span>
			</div>
		</div>
	</div>
	<!--Menu-->
	<div id="top_menu">
		<?php $this -> load -> view('menu_v');?>
		<!--Welcome Message-->
		<div class="welcome_msg">
			Welcome <b><?php echo $this -> session -> userdata('Name');?></b>
			<a href='<?php echo base_url() . "login/logout";?>'> Logout</a>
			<br/>
			<span class="date"><?php echo date('l,jS M Y');?></span>
		</div>
	</div>
</div>