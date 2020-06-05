<?php
include_once("dbconfig.php");
include_once("functions.php");

function addCalendar($st, $et, $sub, $ade)
{
	$ret = array();

	$con = new DBConnection();
	$dbh = $con->getDBHandle();
	$dbh->beginTransaction();

	try {
		$sql = "insert into `jqcalendar` (`Subject`, `StartTime`, `EndTime`, `IsAllDayEvent`) values ("
			.":subject, :starttime, :endtime, :isalldayevent)";
		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':subject', trim($sub), PDO::PARAM_STR);
		$prepare->bindValue(':starttime', php2MySqlTime(js2PhpTime($st)), PDO::PARAM_STR);
		$prepare->bindValue(':endtime', php2MySqlTime(js2PhpTime($et)), PDO::PARAM_STR);
		$prepare->bindValue(':isalldayevent', trim($ade), PDO::PARAM_INT);
		$prepare->execute();

		$ret['IsSuccess'] = true;
		$ret['Msg'] = '作成しました。';
		$ret['Data'] = $dbh->lastInsertId('id');

		$dbh->commit();
	} catch (Exception $e) {
		$dbh->rollback();
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}

	return $ret;
}

function addDetailedCalendar($st, $et, $sub, $ade, $dscr, $loc, $color, $tz)
{
	$ret = array();

	$con = new DBConnection();
	$dbh = $con->getDBHandle();
	$dbh->beginTransaction();

	try {
		$sql = "insert into `jqcalendar` (`Subject`, `StartTime`, `EndTime`, `IsAllDayEvent`, `Description`, `Location`, `Color`) values ("
			.":subject, :starttime, :endtime, :isalldayevent, :desc, :loc, :color)";

		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':subject', trim($sub), PDO::PARAM_STR);
		$prepare->bindValue(':starttime', php2MySqlTime(js2PhpTime($st)), PDO::PARAM_STR);
		$prepare->bindValue(':endtime', php2MySqlTime(js2PhpTime($et)), PDO::PARAM_STR);
		$prepare->bindValue(':isalldayevent', trim($ade), PDO::PARAM_INT);
		$prepare->bindValue(':desc', trim($dscr), PDO::PARAM_STR);
		$prepare->bindValue(':loc', trim($loc), PDO::PARAM_STR);
		$prepare->bindValue(':color', trim($color), PDO::PARAM_STR);
		$prepare->execute();

		$ret['IsSuccess'] = true;
		$ret['Msg'] = '作成しました。';
		$ret['Data'] = $dbh->lastInsertId('id');

		$dbh->commit();
	} catch (Exception $e) {
		$dbh->rollback();
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}

	return $ret;
}

function listCalendarByRange($sd, $ed)
{
	$ret = array();
	$ret['events'] = array();
	$ret['issort'] =true;
	$ret['start'] = php2JsTime($sd);
	$ret['end'] = php2JsTime($ed);
	$ret['error'] = null;

	try {
		$con = new DBConnection();
		$dbh = $con->getDBHandle();

		$sql = "select * from `jqcalendar` where `StartTime` between :starttime and :endtime ORDER BY StartTime ASC";
		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':starttime', php2MySqlTime($sd), PDO::PARAM_STR);
		$prepare->bindValue(':endtime', php2MySqlTime($ed), PDO::PARAM_STR);
		$prepare->execute();

		$rows = $prepare->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) {
			$ret['events'][] = array(
				$row['Id'],
				$row['Subject'],
				php2JsTime(mySql2PhpTime($row['StartTime'])),
				php2JsTime(mySql2PhpTime($row['EndTime'])),
				$row['IsAllDayEvent'],
				($row['IsAllDayEvent'] != 1 && date("Y-m-d", mySql2PhpTime($row['EndTime']))
					>date("Y-m-d", mySql2PhpTime($row['StartTime']))? 1 : 0), //more than one day event
				//$row->InstanceType'],
				0,//Recurring event,
				$row['Color'],
				1,//editable
				$row['Location'],
				''//$attends
			);
		}
	} catch (Exception $e) {
		$ret['error'] = $e->getMessage();
	}
	return $ret;
}

function listCalendar($day, $type)
{
	$phpTime = js2PhpTime($day);
	switch ($type) {
	case "month":
		$st = mktime(0, 0, 0, date("m", $phpTime), 1, date("Y", $phpTime));
		$et = mktime(0, 0, -1, date("m", $phpTime)+1, 1, date("Y", $phpTime));
		break;
	case "week":
		//suppose first day of a week is monday
		$monday  =  date("d", $phpTime) - date('N', $phpTime) + 1;
		//echo date('N', $phpTime);
		$st = mktime(0, 0, 0, date("m", $phpTime), $monday, date("Y", $phpTime));
		$et = mktime(0, 0, -1, date("m", $phpTime), $monday+7, date("Y", $phpTime));
		break;
	case "day":
		$st = mktime(0, 0, 0, date("m", $phpTime), date("d", $phpTime), date("Y", $phpTime));
		$et = mktime(0, 0, -1, date("m", $phpTime), date("d", $phpTime)+1, date("Y", $phpTime));
		break;
	}
	return listCalendarByRange($st, $et);
}

function updateCalendar($id, $st, $et)
{
	$ret = array();

	$con = new DBConnection();
	$dbh = $con->getDBHandle();
	$dbh->beginTransaction();

	try {
		$sql = "update `jqcalendar` set"
			. " `StartTime`=:starttime, `EndTime`=:endtime"
			." where`Id`=:id";

		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':starttime', php2MySqlTime(js2PhpTime($st)), PDO::PARAM_STR);
		$prepare->bindValue(':endtime', php2MySqlTime(js2PhpTime($et)), PDO::PARAM_STR);
		$prepare->bindValue(':id', $id, PDO::PARAM_INT);
		$prepare->execute();

		$ret['IsSuccess'] = true;
		$ret['Msg'] = '更新しました';
		$dbh->commit();
	} catch (Exception $e) {
		$dbh->rollback();
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}
	return $ret;
}

function updateDetailedCalendar($id, $st, $et, $sub, $ade, $dscr, $loc, $color, $tz)
{
	$ret = array();

	$con = new DBConnection();
	$dbh = $con->getDBHandle();
	$dbh->beginTransaction();

	try {
		$sql = "update `jqcalendar` set"
			. " `StartTime`=:starttime, "
			. " `EndTime`=:endtime, "
			. " `Subject`=:subject, "
			. " `Isalldayevent`=:isalldayevent, "
			. " `Description`=:desc, "
			. " `Location`=:loc, "
			. " `Color`=:color "
			. "where `Id`=:id";

		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':subject', trim($sub), PDO::PARAM_STR);
		$prepare->bindValue(':starttime', php2MySqlTime(js2PhpTime($st)), PDO::PARAM_STR);
		$prepare->bindValue(':endtime', php2MySqlTime(js2PhpTime($et)), PDO::PARAM_STR);
		$prepare->bindValue(':isalldayevent', trim($ade), PDO::PARAM_INT);
		$prepare->bindValue(':desc', trim($dscr), PDO::PARAM_STR);
		$prepare->bindValue(':loc', trim($loc), PDO::PARAM_STR);
		$prepare->bindValue(':color', trim($color), PDO::PARAM_STR);
		$prepare->bindValue(':id', $id, PDO::PARAM_INT);
		$prepare->execute();

		$ret['IsSuccess'] = true;
		$ret['Msg'] = '更新しました';
		$dbh->commit();
	} catch (Exception $e) {
		$dbh->rollback();
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}
	return $ret;
}

function removeCalendar($id)
{
	$ret = array();

	$con = new DBConnection();
	$dbh = $con->getDBHandle();
	$dbh->beginTransaction();

	try {
		$sql = "delete from `jqcalendar` where `Id`=:id";

		$prepare = $dbh->prepare($sql);
		$prepare->bindValue(':id', $id, PDO::PARAM_INT);
		$prepare->execute();

		$ret['IsSuccess'] = true;
		$ret['Msg'] = '削除しました';
		$dbh->commit();
	} catch (Exception $e) {
		$dbh->rollback();
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}
	return $ret;
}

header('Content-type:text/javascript;charset=UTF-8');
$method = $_GET["method"];
switch ($method) {
	case "add":
		$ret = addCalendar($_POST["CalendarStartTime"], $_POST["CalendarEndTime"], $_POST["CalendarTitle"], $_POST["IsAllDayEvent"]);
		break;
	case "list":
		$ret = listCalendar($_POST["showdate"], $_POST["viewtype"]);
		break;
	case "update":
		$ret = updateCalendar($_POST["calendarId"], $_POST["CalendarStartTime"], $_POST["CalendarEndTime"]);
		break;
	case "remove":
		$ret = removeCalendar($_POST["calendarId"]);
		break;
	case "adddetails":
		$st = $_POST["stpartdate"] . " " . $_POST["stparttime"];
		$et = $_POST["etpartdate"] . " " . $_POST["etparttime"];
		if (isset($_GET["id"]) && $_GET["id"] > 0) {
			$ret = updateDetailedCalendar(
				$_GET["id"],
				$st,
				$et,
				$_POST["Subject"],
				isset($_POST["IsAllDayEvent"])?1:0,
				$_POST["Description"],
				$_POST["Location"],
				$_POST["colorvalue"],
				$_POST["timezone"]
			);
		} else {
			$ret = addDetailedCalendar($st, $et, $_POST["Subject"], isset($_POST["IsAllDayEvent"])?1:0, $_POST["Description"], $_POST["Location"], $_POST["colorvalue"], $_POST["timezone"]);
		}
		break;
}
echo json_encode($ret);
