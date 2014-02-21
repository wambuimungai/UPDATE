<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Recover extends MY_Controller {
	var $backup_dir = "./backup_db";
	function __construct() {
		parent::__construct();
	}

	public function index() {
		$data['backup_files'] = $this -> checkdir();
		$data['content_view'] = "recover/recover_v";
		$data['title'] = "Dashboard | System Recovery";
		$this -> template($data);
	}

	public function checkdir() {
		$dir = $this -> backup_dir;
		$backup_files = array();
		$backup_headings = array('Filename', 'Options');
		$options = '<button class="btn btn-primary btn-sm recover" >Recover</button>';

		if (is_dir($dir)) {
			$files = scandir($dir, 1);
			foreach ($files as $object) {
				if ($object != "." && $object != "..") {
					$backup_files[] = $object;
				}
			}
		} else {
			mkdir($dir);
		}
		$this -> load -> module('table');
		return $this -> table -> load_table($backup_headings, $backup_files, $options);
	}

	public function start_recovery() {
		//$file_name = $this -> input -> post("file_name", TRUE);
		$file_name="testadt.rar";
		$dir = $this -> backup_dir;
		$file_path = realpath($dir . "/" . $file_name);

		$CI = &get_instance();
		$CI -> load -> database();
		$hostname = $CI -> db -> hostname;
		$username = $CI -> db -> username;
		$password = $CI -> db -> password;
		$current_db = $CI -> db -> database;
		$recovery_status = false;

		$this -> load -> dbutil();
		if ($this -> dbutil -> database_exists($current_db)) {
			echo $real_name = $this -> uncompress_zip($file_path);die();
			$mysql_home = realpath($_SERVER['MYSQL_HOME']) . "\mysql";
			$file_path = "\"" . realpath($_SERVER['MYSQL_HOME']) . "\testadt.sql" . "\"";
			$recovery_status = true;
			$mysql_bin = str_replace("\\", "\\\\", $mysql_home);
			$mysql_con = $mysql_bin . ' -u ' . $username . ' -p' . $password . ' -h ' . $hostname . ' ' . $current_db . ' < ' . $file_path;
			exec($mysql_con);
		}
		echo $recovery_status;
	}

	public function uncompress_zip($file_path) {
		$destination_path = $_SERVER['DOCUMENT_ROOT'];
		$destination_path = str_replace("htdocs", "mysql/bin/", $destination_path);
		$this -> load -> library('unzip');
		$this -> unzip -> allow(array('sql'));
		return $this -> unzip -> extract($file_path, $destination_path);
	}

	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}
