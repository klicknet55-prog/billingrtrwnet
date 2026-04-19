<?php

register_menu("Radius Online Users", true, "radon_users", 'RADIUS', '');

function radon_users()
{
	global $ui;
	_admin();
	$ui->assign('_title', 'Radius Online Users');
	$ui->assign('_system_menu', 'radius');
	$admin = Admin::_info();
	$ui->assign('_admin', $admin);

	$error = [];
	$success = [];

	// Handle AJAX disconnection request
	if (isset($_POST['ajax_disconnect'])) {
		header('Content-Type: application/json');
		$username = _post('username');
		$response = ['success' => false, 'message' => ''];

		if (empty($username)) {
			$response['message'] = Lang::T("Username is required.");
			echo json_encode($response);
			exit;
		}

		$coaport = 3799;

		// Send disconnect packets to NAS (errors are logged but don't stop the process)
		$packetResult = radon_send_disconnect_packets($username, $coaport);

		// Update radacct table to mark sessions as stopped (always do this, even if packets failed)
		$db = ORM::get_db();
		$stmt = $db->prepare("UPDATE radacct SET acctstoptime = ? WHERE username = ? AND acctstoptime IS NULL");
		$stmt->execute([date('Y-m-d H:i:s'), $username]);
		$affectedRows = $stmt->rowCount();

		if ($affectedRows > 0) {
			$response['success'] = true;
			$message = Lang::T("User $username disconnected successfully.");
			if ($packetResult['failed'] > 0) {
				$message .= " " . Lang::T("Note: Some disconnect packets failed, but database was updated.");
			}
			$response['message'] = $message;
			_log(Lang::T("User $username disconnected successfully. Packets sent: {$packetResult['sent']}, failed: {$packetResult['failed']}"));
		} else {
			$response['message'] = Lang::T("Username: $username has no active session.");
			_log(Lang::T("Username: $username has no active session."));
		}

		echo json_encode($response);
		exit;
	}

	// Handle AJAX mass disconnection request
	if (isset($_POST['ajax_mass_disconnect'])) {
		header('Content-Type: application/json');
		$selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
		$response = ['success' => false, 'disconnected' => 0, 'failed' => 0, 'message' => ''];

		if (empty($selectedUsers) || !is_array($selectedUsers)) {
			$response['message'] = Lang::T("No users selected.");
			echo json_encode($response);
			exit;
		}

		$db = ORM::get_db();
		$disconnectedCount = 0;
		$failedCount = 0;
		$stopTime = date('Y-m-d H:i:s');
		$coaport = 3799;
		$totalPacketsSent = 0;
		$totalPacketsFailed = 0;

		// Process each user: send disconnect packets first, then update database
		foreach ($selectedUsers as $username) {
			// Send disconnect packets to NAS (errors are logged but don't stop the process)
			$packetResult = radon_send_disconnect_packets($username, $coaport);
			$totalPacketsSent += $packetResult['sent'];
			$totalPacketsFailed += $packetResult['failed'];

			// Update radacct table to mark sessions as stopped (always do this, even if packets failed)
			$stmt = $db->prepare("UPDATE radacct SET acctstoptime = ? WHERE username = ? AND acctstoptime IS NULL");
			$stmt->execute([$stopTime, $username]);
			if ($stmt->rowCount() > 0) {
				$disconnectedCount++;
			} else {
				$failedCount++;
			}
		}

		$response['success'] = true;
		$response['disconnected'] = $disconnectedCount;
		$response['failed'] = $failedCount;
		$response['message'] = Lang::T("Successfully disconnected $disconnectedCount user(s).");
		if ($failedCount > 0) {
			$response['message'] .= " " . Lang::T("Failed to disconnect $failedCount user(s).");
		}
		if ($totalPacketsFailed > 0) {
			$response['message'] .= " " . Lang::T("Note: Some disconnect packets failed, but database was updated.");
		}
		_log(Lang::T("Mass disconnect: $disconnectedCount user(s) disconnected. Packets sent: $totalPacketsSent, failed: $totalPacketsFailed"));

		echo json_encode($response);
		exit;
	}

	// Handle single user disconnection (legacy form submission)
	if (isset($_POST['kill']) && !isset($_POST['mass_kill'])) {
		$username = _post('username');
		$coaport = 3799;

		// Send disconnect packets to NAS (errors are logged but don't stop the process)
		$packetResult = radon_send_disconnect_packets($username, $coaport);

		// Update radacct table to mark sessions as stopped (always do this, even if packets failed)
		$db = ORM::get_db();
		$stmt = $db->prepare("UPDATE radacct SET acctstoptime = ? WHERE username = ? AND acctstoptime IS NULL");
		$stmt->execute([date('Y-m-d H:i:s'), $username]);
		$affectedRows = $stmt->rowCount();

		if ($affectedRows > 0) {
			$message = Lang::T("User $username disconnected successfully.");
			if ($packetResult['failed'] > 0) {
				$message .= " " . Lang::T("Note: Some disconnect packets failed, but database was updated.");
			}
			$success[] = $message;
			_log(Lang::T("User $username disconnected successfully. Packets sent: {$packetResult['sent']}, failed: {$packetResult['failed']}"));
		} else {
			$error[] = Lang::T("Username: $username has no active session.");
			_log(Lang::T("Username: $username has no active session."));
		}
	}

	// Handle mass disconnection (legacy form submission)
	if (isset($_POST['mass_kill']) && isset($_POST['selected_users'])) {
		$selectedUsers = $_POST['selected_users'];
		$disconnectedCount = 0;
		$failedCount = 0;
		$db = ORM::get_db();
		$stopTime = date('Y-m-d H:i:s');
		$coaport = 3799;
		$totalPacketsSent = 0;
		$totalPacketsFailed = 0;

		// Process each user: send disconnect packets first, then update database
		foreach ($selectedUsers as $username) {
			// Send disconnect packets to NAS (errors are logged but don't stop the process)
			$packetResult = radon_send_disconnect_packets($username, $coaport);
			$totalPacketsSent += $packetResult['sent'];
			$totalPacketsFailed += $packetResult['failed'];

			// Update radacct table to mark sessions as stopped (always do this, even if packets failed)
			$stmt = $db->prepare("UPDATE radacct SET acctstoptime = ? WHERE username = ? AND acctstoptime IS NULL");
			$stmt->execute([$stopTime, $username]);
			if ($stmt->rowCount() > 0) {
				$disconnectedCount++;
			} else {
				$failedCount++;
			}
		}

		if ($disconnectedCount > 0) {
			$message = Lang::T("Successfully disconnected $disconnectedCount user(s).");
			if ($totalPacketsFailed > 0) {
				$message .= " " . Lang::T("Note: Some disconnect packets failed, but database was updated.");
			}
			$success[] = $message;
			_log(Lang::T("Mass disconnect: $disconnectedCount user(s) disconnected. Packets sent: $totalPacketsSent, failed: $totalPacketsFailed"));
		}
		if ($failedCount > 0) {
			$error[] = Lang::T("Failed to disconnect $failedCount user(s) - no active session found.");
		}
	}

	// Get online users
	$useron = ORM::for_table('radacct')
		->where_raw("acctstoptime IS NULL")
		->order_by_asc('acctsessiontime')
		->find_many();

	$totalCount = ORM::for_table('radacct')
		->where_raw("acctstoptime IS NULL")
		->count();

	// Calculate total data usage
	$totalUpload = 0;
	$totalDownload = 0;
	$totalUsage = 0;
	$totalUptime = 0;
	$onlineUsernames = [];

	foreach ($useron as $user) {
		$totalUpload += (int)$user['acctinputoctets'];
		$totalDownload += (int)$user['acctoutputoctets'];
		$totalUptime += (int)$user['acctsessiontime'];
		$onlineUsernames[] = $user['username'];
	}
	$totalUsage = $totalUpload + $totalDownload;
	
	// Get full customer name
	$customerFullNames = [];
	if (!empty($onlineUsernames)) {
		$customers = ORM::for_table('tbl_customers')
						->select('username')
						->select('fullname') // Assuming 'fullname' is the column name for full name
						->where_in('username', array_unique($onlineUsernames))
						->find_many();
		
		foreach ($customers as $customerRecord) {
			$customerFullNames[$customerRecord['username']] = $customerRecord['fullname'];
		}
	}

	$ui->assign('error', $error);
	$ui->assign('success', $success);
	$ui->assign('useron', $useron);
	$ui->assign('customerFullNames', $customerFullNames);
	$ui->assign('totalCount', $totalCount);
	$ui->assign('totalUpload', $totalUpload);
	$ui->assign('totalDownload', $totalDownload);
	$ui->assign('totalUsage', $totalUsage);
	$ui->assign('totalUptime', $totalUptime);
	$ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
		<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
		<style>
			.stats-card {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 20px;
				border-radius: 10px;
				margin-bottom: 20px;
				box-shadow: 0 4px 6px rgba(0,0,0,0.1);
			}
			.stats-card.success {
				background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
			}
			.stats-card.info {
				background: linear-gradient(135deg, #3494E6 0%, #EC6EAD 100%);
			}
			.stats-card.warning {
				background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			}
			.stats-card.danger {
				background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
			}
			.stats-value {
				font-size: 32px;
				font-weight: bold;
				margin: 10px 0;
			}
			.stats-label {
				font-size: 14px;
				opacity: 0.9;
				text-transform: uppercase;
				letter-spacing: 1px;
			}
			.table-actions {
				margin-bottom: 15px;
				padding: 10px;
				background: #f8f9fa;
				border-radius: 5px;
			}
			#onlineTable_wrapper {
				overflow-x: auto;
			}
			.checkbox-column {
				width: 30px;
			}
		</style>');
	$ui->display('radon.tpl');
}

// Helper function to send disconnect packets to NAS for a username
function radon_send_disconnect_packets($username, $coaport = 3799) {
	// Gather active sessions first (before marking them stopped)
	$activeSessions = ORM::for_table('radacct')
		->select('nasipaddress')
		->select('acctsessionid')
		->where('username', $username)
		->where_raw('acctstoptime IS NULL')
		->find_many();

	$packetsSent = 0;
	$packetsFailed = 0;

	// Send Disconnect-Request to each NAS involved
	foreach ($activeSessions as $session) {
		$targetIp = isset($session['nasipaddress']) ? $session['nasipaddress'] : '';
		$acctSessionId = isset($session['acctsessionid']) ? $session['acctsessionid'] : '';
		$secret = null;

		if (!empty($targetIp)) {
			// Try exact NAS entry first
			$nasEntry = ORM::for_table('nas')
				->select('nasname')
				->select('secret')
				->where('nasname', $targetIp)
				->find_one();

			if ($nasEntry) {
				$secret = isset($nasEntry['secret']) ? $nasEntry['secret'] : null;
			} else {
				// Fallback to wildcard NAS (0.0.0.0/0) if configured
				$wild = ORM::for_table('nas')
					->select('secret')
					->where('nasname', '0.0.0.0/0')
					->find_one();
				if ($wild) {
					$secret = isset($wild['secret']) ? $wild['secret'] : null;
				}
			}
		}

		if (!empty($targetIp) && !empty($secret)) {
			// Build attributes safely and call radclient with small retry/timeout
			$format = "User-Name = %s\n";
			$args = [escapeshellarg($username)];
			if (!empty($acctSessionId)) {
				$format .= "Acct-Session-Id = %s\n";
				$args[] = escapeshellarg($acctSessionId);
			}

			$cmd = "printf " . escapeshellarg($format);
			foreach ($args as $a) {
				$cmd .= " " . $a;
			}
			$cmd .= " | radclient -r 1 -t 2 " . escapeshellarg($targetIp . ':' . $coaport) . " disconnect " . escapeshellarg($secret) . " 2>&1";

			$output = [];
			$retcode = 0;
			exec($cmd, $output, $retcode);

			if ($retcode !== 0) {
				$packetsFailed++;
				_log("Disconnect packet failed for $username on $targetIp. Output: " . implode("\n", $output));
				if (function_exists('sendTelegram')) {
					sendTelegram("Disconnect packet failed for $username on $targetIp. Output: " . implode("\n", $output));
				}
			} else {
				$packetsSent++;
				_log("Disconnect packet sent for $username on $targetIp (session: $acctSessionId)");
			}
		} else {
			// No NAS target or secret available; skip sending DM
			_log("No NAS/secret for $username session on $targetIp; skipping disconnect packet");
		}
	}

	return ['sent' => $packetsSent, 'failed' => $packetsFailed];
}

// Function to format bytes into KB, MB, GB or TB
function radon_formatBytes($bytes, $precision = 2)
{
	$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}

// Convert seconds into months, days, hours, minutes, and seconds.
function radon_secondsToTimeFull($ss)
{
	$s = $ss % 60;
	$m = floor(($ss % 3600) / 60);
	$h = floor(($ss % 86400) / 3600);
	$d = floor(($ss % 2592000) / 86400);
	$M = floor($ss / 2592000);

	return "$M months, $d days, $h hours, $m minutes, $s seconds";
}

function radon_secondsToTime($inputSeconds)
{
	$secondsInAMinute = 60;
	$secondsInAnHour = 60 * $secondsInAMinute;
	$secondsInADay = 24 * $secondsInAnHour;

	// Extract days
	$days = floor($inputSeconds / $secondsInADay);

	// Extract hours
	$hourSeconds = $inputSeconds % $secondsInADay;
	$hours = floor($hourSeconds / $secondsInAnHour);

	// Extract minutes
	$minuteSeconds = $hourSeconds % $secondsInAnHour;
	$minutes = floor($minuteSeconds / $secondsInAMinute);

	// Extract the remaining seconds
	$remainingSeconds = $minuteSeconds % $secondsInAMinute;
	$seconds = ceil($remainingSeconds);

	// Format and return
	$timeParts = [];
	$sections = [
		'day' => (int) $days,
		'hour' => (int) $hours,
		'minute' => (int) $minutes,
		'second' => (int) $seconds,
	];

	foreach ($sections as $name => $value) {
		if ($value > 0) {
			$timeParts[] = $value . ' ' . $name . ($value == 1 ? '' : 's');
		}
	}

	return implode(', ', $timeParts);
}

function radon_users_cleandb()
{
	global $ui;
	_admin();
	$admin = Admin::_info();

	if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
		r2(getUrl('dashboard'), 'e', Lang::T('You do not have permission to access this page'));
	}

	$action = _get('action', 'all');
	$days = _get('days', 30);

	try {
		if ($action == 'old') {
			// Truncate records older than specified days
			$dateThreshold = date('Y-m-d H:i:s', strtotime("-$days days"));
			$deleted = ORM::for_table('radacct')
				->where_lt('acctstarttime', $dateThreshold)
				->delete_many();
			r2(U . 'plugin/radon_users', 's', Lang::T("Deleted $deleted old record(s) from RADACCT table (older than $days days)."));
		} elseif ($action == 'stopped') {
			// Truncate only stopped sessions
			$deleted = ORM::for_table('radacct')
				->where_not_null('acctstoptime')
				->delete_many();
			r2(U . 'plugin/radon_users', 's', Lang::T("Deleted $deleted stopped session(s) from RADACCT table."));
		} else {
			// Truncate all records
			ORM::get_db()->exec('TRUNCATE TABLE `radacct`');
			r2(U . 'plugin/radon_users', 's', Lang::T("RADACCT table truncated successfully."));
		}
	} catch (Exception $e) {
		r2(U . 'plugin/radon_users', 'e', Lang::T("Failed to truncate RADACCT table: " . $e->getMessage()));
	}
}

