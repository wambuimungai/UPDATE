<div class="external-content">
	<div class="browser">
		<div id="dvContents" class="dvContents">
				<?php
                if($this->session->userdata("msg_success")){
				?>
				<div class="alert alert-success">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<?php 
				echo $this->session->userdata("msg_success");
				$this -> session -> unset_userdata("msg_success");
				?>
				</div>
				<?php
				}else if($this->session->userdata("msg_error")){
				?>
				<div class="alert alert-danger">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<?php 
				echo $this->session->userdata("msg_error");
				$this -> session -> unset_userdata("msg_error");
				?>
				</div>
				<?php
				}
				?>
			</div>
			<div></div>
			<?php
            if($update_status==1){
			?>
			<a id="updater" href="<?php echo base_url().'github/runGithubUpdater/';?>" class="btn btn-success" style="float:right;">Update Available</a>
			<?php
			}
			?>
			<?php
			echo $update_log;
			?>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		var online = navigator.onLine;
		if(online == true) {
			$("#updater").show();
		} else {
			$("#updater").hide();
		}
	});

</script>