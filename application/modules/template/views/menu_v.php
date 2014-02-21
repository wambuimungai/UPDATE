<?php
$menus = $this -> session -> userdata('menu_items');
$current_url = $this -> router -> class;
$active_link = "";

//Is current equal to home link
if ($current_url == $default_home_controller) {
	$active_link = " top_menu_active ";
}
?>

<a href="<?php echo site_url($default_home_controller);?>" class="top_menu_link  first_link <?php echo $active_link;?> "><i class="icon-home"></i>Home </a>
<?php
//Loop through menus
if ($menus) {
  foreach ($menus as $menu) {
    $url = $menu['url'];
    $text = $menu['text'];
    if ($current_url == $url) {
      $active_link = " top_menu_active ";
    }else{
      $active_link="";	
    }
?>
<a href = "<?php echo site_url($url);?>" class="top_menu_link <?php echo $active_link;?> "> <?php echo $text;?></a>
<?php
  }
}
?>

<div  class="btn-group" id="div_profile">
	<a href="#" class="top_menu_link btn dropdown-toggle" data-toggle="dropdown"  id="my_profile"><i class="icon-user icon-black"></i> Profile <span class="caret"></span></a>
	<ul class="dropdown-menu" id="profile_list" role="menu">
		<li>
			<a href="#edit_user_profile" data-toggle="modal"><i class="icon-edit"></i> Edit Profile</a>
		</li>
		<li id="change_password_link">
			<a href="#user_change_pass" data-toggle="modal"><i class=" icon-asterisk"></i> Change Password</a>
		</li>
	</ul>
</div>