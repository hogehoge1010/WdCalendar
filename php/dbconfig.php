<?php
class DBConnection {

	public $username	= "wdcalendar";
	public $password	= "Pasuwa-do";
	public $dsn			= 'mysql:dbname=wdcalendar;host=localhost';
	public $dbh;

	public function __construct() {
		try {
			$this->dbh = new PDO($this->dsn, $this->username, $this->password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		} catch (PDOException $e) {
			echo "Failed to connect : " . $e->getMessage();
			exit();
		}
	}

	public function getDBHandle() {
		return $this->dbh;
	}
}
?>
