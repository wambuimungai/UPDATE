<div class="external-content" style="zoom:1">
	<div class="browser">
		<div id="dvContents" class="dvContents">
			<div class="content">
				<script>
					$(function() {
						$("#wizard").steps({
							headerTag : "h2",
							bodyTag : "section",
							transitionEffect : "slideLeft",
							stepsOrientation : "vertical",
							onStepChanging : function(event, currentIndex, newIndex) {
								if(currentIndex == 0) {
									var server_status = $("#log1_status").val();
									if(server_status == 1) {
										return true;
									} else {
										return false;
									}
								} else if(currentIndex == 1) {
									var server_status = $("#log2_status").val();
									if(server_status == 1) {
										return true;
									} else {
										return false;
									}
								}
							},
							onFinishing:function(event, currentIndex)  {
								var server_status = $("#log3_status").val();
								  if(server_status == 1) {
								  	alert("Recovery Complete");
									return true;
								  } else {
								  	alert("Recovery not complete");
									return false;
								  }
							}
						});
					});

				</script>
				<div id="wizard">
					<h2>Server Configuration</h2>
					<section>
						<form class="form-horizontal" id="checkServerFrm">
							<div class="control-group">
								<label class="control-label" for="inputHost">Database Hostname</label>
								<div class="controls">
									<input type="hidden" id="log1_status" name="log1_status" value="0" />
									<input type="text" id="inputHost" name="inputHost" placeholder="localhost" required/>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="inputUser">Database User</label>
								<div class="controls">
									<input type="text" id="inputUser" name="inputUser" placeholder="root" required/>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="inputPassword">Database Password</label>
								<div class="controls">
									<input type="password" id="inputPassword" name="inputPassword" placeholder=".....">
								</div>
							</div>
							<div class="control-group form-actions">
								<label class="control-label"></label>
								<div class="controls">
									<button class="btn btn-primary">
										Test Connection
									</button>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="inputLog1">Database Log</label>
								<div class="controls">
									<textarea rows="7" style="width:100%" id="inputLog1" readonly></textarea>
								</div>
							</div>
						</form>
					</section>
					<h2>Database Configuration</h2>
					<section>
						<form class="form-horizontal" id="checkDatabaseFrm">
							<div class="control-group">
								<label class="control-label" for="inputDb">Database Name</label>
								<div class="controls">
									<input type="hidden" id="log2_status" name="log2_status" value="0" />
									<input type="text" id="inputDb" name="inputDb" placeholder="testdb" required/>
								</div>
							</div>
							<div class="control-group form-actions">
								<label class="control-label"></label>
								<div class="controls">
									<button type="submit" class="btn btn-primary">
										Check Database
									</button>
								</div>
							</div>
							<div class="control-group">
								<label class="control-label" for="inputLog2">Database Log</label>
								<div class="controls">
									<textarea rows="7" style="width:100%" id="inputLog2" readonly></textarea>
								</div>
							</div>
						</form>
					</section>
					<h2>Recovery Setup</h2>
					<section>
						<form class="form-horizontal" id="checkRecoveryFrm">
							<div class="control-group">
								<label class="control-label" for="inputUpload">Recovery Upload</label>
								<div class="controls">
									<input type="hidden" id="log3_status" name="log3_status" value="0" />
									<input type="file" id="file" name="file"  required/>
								</div>
							</div>
							<div class="control-group form-actions">
								<label class="control-label"></label>
								<div class="controls" id="backup_files">
									<?php echo $backup_files;?>
								</div>
							</div>
						</form>
					</section>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
    <?php $timestamp = time();?>
	$(function() {
		//Submit Server Configuarion Form
		$('#checkServerFrm').on('submit', function(e) {
			$.ajax({
				type : 'post',
				url : 'recover/check_server',
				data : $('form').serialize(),
				success : function(data) {
					if(data == 0) {
						var mystatus = "Connection Failed!";
					} else {
						var mystatus = "Connection Success!";
					}
					$("#log1_status").val(data);
					$("#inputLog1").text(mystatus);
				}
			});
			e.preventDefault();
		});
        //Submit Database Configuarion Form
		$('#checkDatabaseFrm').on('submit', function(e) {
			$.ajax({
				type : 'post',
				url : 'recover/check_database',
				data : $('form').serialize(),
				success : function(data) {
					if(data == 0) {
						var data = "Database does not exist! \nError creating database!";
						$("#log2_status").val(0);
					} else {
						$("#log2_status").val(1);
					}
					var mystatus = $("#inputLog1").text() + "\n" + data;
					$("#inputLog2").text(mystatus);
				}
			});
			e.preventDefault();
		});
	    //File Upload Form	
		$('#file').uploadify({
			    'method'  : 'post',
				'formData'     : {
					'timestamp' : '<?php echo $timestamp;?>',
					'token'     : '<?php echo md5('unique_salt' . $timestamp);?>'
				},
				'swf'      : '<?php echo base_url()."assets/images/" ?>uploadify.swf',
				'uploader' : 'recover/start_database',
				'onUploadSuccess' : function(file, data, response) {
					if(data==1){
                     alert('The file ' + file.name + ' was successfully');
                     $("#backup_files").load("recover/showdir",function(){
								$('.dataTables').dataTable({
									"bJQueryUI" : true,
									"sPaginationType" : "full_numbers",
									"bProcessing" : true,
									"bServerSide" : false,
								});
	                     });
	                 }else{
	                 	alert('The file ' + file.name + ' upload failed '+data);
	                 }
                 }
		});
		
		
		$('.recover').live('click', function(e) {
			var current_row = $(this).closest('tr').children('td');
			var file_name = current_row.eq(1).text();
			var link='recover/start_recovery/';
			$.ajax({
				url : link,
				type : 'POST',
				data : {
					"file_name" : file_name
				},
				success : function(data) {
					if(data==1){
						alert("Recovery Successful!");
						$("#log3_status").val(1);
					}else{
						alert("Recovery Failed!");
						$("#log3_status").val(0);
					}
				}
			});
			e.preventDefault();
		});
	});

</script>