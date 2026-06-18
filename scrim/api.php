<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS', true);

// basic auth check (ปรับ path ตามโครงโปรเจค)
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../utils/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success'=>false, 'error'=>'Not authenticated']);
    exit;
}

function send($arr){ echo json_encode($arr); exit; }

// Helper: run SQL with PDO or mysqli
$run = function($sql, $params = []) use (&$pdo, &$conn, &$mysqli) {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } elseif (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) {
        // simple mysqli prepare wrapper
        // for brevity we only support simple queries without params here
        if (!empty($params)) {
            // fallback: do basic escaping for numeric params
            foreach ($params as $k => $v) {
                $sql = str_replace($k, "'" . $conn->real_escape_string($v) . "'", $sql);
            }
        }
        return $conn->query($sql);
    } elseif (isset($mysqli) && $mysqli instanceof mysqli) {
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                $sql = str_replace($k, "'" . $mysqli->real_escape_string($v) . "'", $sql);
            }
        }
        return $mysqli->query($sql);
    }
    return false;
};

// check if user is manager and get team_id
$user_team_id = null;
if (isset($pdo) && $pdo instanceof PDO) {
    $mgr = $pdo->prepare("SELECT team_id FROM teams WHERE manager_id = :uid LIMIT 1");
    $mgr->execute([':uid'=>$user_id]);
    $user_team_id = $mgr->fetchColumn();
} elseif (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) {
    $res = $conn->query("SELECT team_id FROM teams WHERE manager_id = ".intval($user_id)." LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) $user_team_id = $row['team_id'];
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $res = $mysqli->query("SELECT team_id FROM teams WHERE manager_id = ".intval($user_id)." LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) $user_team_id = $row['team_id'];
}

// action handlers
if ($action === 'reserve' && $method === 'POST') {
    try {
        $scrim_id = isset($input['scrim_id']) ? intval($input['scrim_id']) : 0;
        if (!$scrim_id) send(['success'=>false, 'error'=>'Invalid scrim_id']);

        // user must be manager of a team
        if (empty($user_team_id)) {
            send(['success'=>false, 'error'=>'Only team managers can reserve scrims']);
        }

        // get scrim owner team and start time
        $scrim_owner_team = null;
        $scrim_start = null;
        if (isset($pdo) && $pdo instanceof PDO) {
            $tq = $pdo->prepare("SELECT team_id, scrim_start FROM scrims WHERE scrim_id = :id LIMIT 1");
            $tq->execute([':id'=>$scrim_id]);
            $srow = $tq->fetch(PDO::FETCH_ASSOC);
            if (!$srow) send(['success'=>false, 'error'=>'Scrim not found']);
            $scrim_owner_team = $srow['team_id'];
            $scrim_start = $srow['scrim_start'];
            if ((int)$user_team_id === (int)$scrim_owner_team) {
                send(['success'=>false, 'error'=>'Cannot reserve your own team\'s scrim']);
            }
            // check existing reservation
            $chk = $pdo->prepare("SELECT reservation_id, status FROM scrim_reservations WHERE scrim_id = :sid AND user_id = :uid LIMIT 1");
            $chk->execute([':sid'=>$scrim_id, ':uid'=>$user_id]);
            $exist = $chk->fetch(PDO::FETCH_ASSOC);
            if ($exist) {
                send(['success'=>false, 'error'=>'You have already reserved this scrim']);
            }

            // atomic update + insert reservation
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE scrims SET reserved_count = reserved_count + 1 WHERE scrim_id = :id AND reserved_count < slots AND scrim_start > NOW()");
            $stmt->execute([':id'=>$scrim_id]);
            if ($stmt->rowCount() === 1) {
                $ins = $pdo->prepare("INSERT INTO scrim_reservations (scrim_id, user_id, created_at) VALUES (:sid, :uid, NOW())");
                $ins->execute([':sid'=>$scrim_id, ':uid'=>$user_id]);
                $reservation_id = $pdo->lastInsertId();

                // create notification for team manager
                $mgrStmt = $pdo->prepare("SELECT t.manager_id, t.team_id, t.team_name FROM scrims s JOIN teams t ON t.team_id = s.team_id WHERE s.scrim_id = :sid LIMIT 1");
                $mgrStmt->execute([':sid'=>$scrim_id]);
                $mgrRow = $mgrStmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($mgrRow['manager_id'])) {
                    $notif = $pdo->prepare("INSERT INTO scrim_reservation_notifications (reservation_id, manager_id, team_id, scrim_id) VALUES (:rid, :mid, :tid, :sid)");
                    $notif->execute([':rid'=>$reservation_id, ':mid'=>$mgrRow['manager_id'], ':tid'=>$mgrRow['team_id'], ':sid'=>$scrim_id]);
                }

                // --- NOTIFY ALL MEMBERS OF BOTH TEAMS (owner team + reserver's team) ---
                // fetch scrim start time and team names
                $sInfo = $pdo->prepare("SELECT s.scrim_start, t.team_id AS owner_team_id, t.team_name AS owner_team_name FROM scrims s JOIN teams t ON t.team_id = s.team_id WHERE s.scrim_id = :sid LIMIT 1");
                $sInfo->execute([':sid'=>$scrim_id]);
                $sRow = $sInfo->fetch(PDO::FETCH_ASSOC) ?: [];
                $ownerTeamId = intval($sRow['owner_team_id'] ?? $mgrRow['team_id'] ?? 0);
                $ownerTeamName = $sRow['owner_team_name'] ?? $mgrRow['team_name'] ?? null;
                $scrimStart = $sRow['scrim_start'] ?? null;

                // reserver's team (from $user_team_id detected earlier)
                $reserverTeamId = intval($user_team_id ?? 0);
                $reserverTeamName = null;
                if ($reserverTeamId) {
                    $rt = $pdo->prepare("SELECT team_name FROM teams WHERE team_id = :tid LIMIT 1");
                    $rt->execute([':tid'=>$reserverTeamId]);
                    $reserverTeamName = $rt->fetchColumn();
                }

                // build title/body and meta
                $scrimTimeStr = $scrimStart ? date('Y-m-d H:i', strtotime($scrimStart)) : null;
                $title_for_members = "Scrim reservation: {$ownerTeamName}";
                $body_for_reserver = "You reserved a slot vs {$ownerTeamName} — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                $body_for_owner = "{$reserverTeamName} reserved a slot vs your team — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                $meta_common = json_encode(['owner_team_id'=>$ownerTeamId, 'owner_team_name'=>$ownerTeamName, 'reserver_team_id'=>$reserverTeamId, 'reserver_team_name'=>$reserverTeamName, 'scrim_id'=>$scrim_id, 'reservation_id'=>$reservation_id, 'scrim_start'=>$scrimStart]);

                // collect member ids of both teams (unique)
                $memberStmt = $pdo->prepare("SELECT user_id FROM team_members WHERE team_id IN (:t1, :t2)");
                // PDO cannot bind same param repeated in IN easily, build dynamic simple approach:
                $teams = array_filter([ $ownerTeamId, $reserverTeamId ]);
                if (!empty($teams)) {
                    // safer: use query with placeholders based on count
                    $placeholders = implode(',', array_fill(0, count($teams), '?'));
                    $mQ = $pdo->prepare("SELECT DISTINCT user_id FROM team_members WHERE team_id IN ($placeholders)");
                    $mQ->execute($teams);
                    $memberIds = $mQ->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($memberIds)) {
                        $uNotif = $pdo->prepare("INSERT INTO user_notifications (user_id, type, title, body, meta) VALUES (:uid, :type, :title, :body, :meta)");
                        foreach ($memberIds as $mid) {
                            $mid = intval($mid);
                            // choose body depending on which team member belongs to
                            // determine membership team
                            $mTeamQ = $pdo->prepare("SELECT team_id FROM team_members WHERE user_id = :uid LIMIT 1");
                            $mTeamQ->execute([':uid'=>$mid]);
                            $mTeam = intval($mTeamQ->fetchColumn() ?: 0);
                            $body = ($mTeam === $ownerTeamId) ? $body_for_owner : $body_for_reserver;
                            $type = 'scrim_reservation_created';
                            $uNotif->execute([':uid'=>$mid, ':type'=>$type, ':title'=>$title_for_members, ':body'=>$body, ':meta'=>$meta_common]);
                        }
                    }
                }
                // --- END notify members ---

                $pdo->commit();
                send(['success'=>true]);
            } else {
                $pdo->rollBack();
                send(['success'=>false, 'error'=>'Full or expired']);
            }
        } else {
            // mysqli path
            // get scrim row
            $res = $run("SELECT team_id, scrim_start FROM scrims WHERE scrim_id = ".intval($scrim_id)." LIMIT 1");
            $srow = $res ? $res->fetch_assoc() : null;
            if (!$srow) send(['success'=>false, 'error'=>'Scrim not found']);
            $scrim_owner_team = $srow['team_id'];
            if ((int)$user_team_id === (int)$scrim_owner_team) {
                send(['success'=>false, 'error'=>'Cannot reserve your own team\'s scrim']);
            }

            // check existing reservation
            $resChk = $run("SELECT reservation_id FROM scrim_reservations WHERE scrim_id = ".intval($scrim_id)." AND user_id = ".intval($user_id)." LIMIT 1");
            $exists = $resChk ? $resChk->fetch_assoc() : null;
            if ($exists) send(['success'=>false, 'error'=>'You have already reserved this scrim']);

            // try update reserved_count
            $sql = "UPDATE scrims SET reserved_count = reserved_count + 1 WHERE scrim_id = ".intval($scrim_id)." AND reserved_count < slots AND scrim_start > NOW()";
            $resUp = $run($sql);
            $affected = 0;
            if (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) $affected = $conn->affected_rows;
            elseif (isset($mysqli) && $mysqli instanceof mysqli) $affected = $mysqli->affected_rows ?? 0;

            if ($resUp && $affected > 0) {
                // insert reservation and notification
                if (isset($conn)) {
                    $insSql = "INSERT INTO scrim_reservations (scrim_id, user_id, created_at) VALUES (".intval($scrim_id).", ".intval($user_id).", NOW())";
                    if (!$conn->query($insSql)) {
                        // duplicate or other DB error
                        $conn->query("UPDATE scrims SET reserved_count = GREATEST(0, reserved_count - 1) WHERE scrim_id = ".intval($scrim_id));
                        send(['success'=>false, 'error'=>'Database error: '.$conn->error]);
                    }
                    $reservation_id = $conn->insert_id;
                    $mgrRes = $conn->query("SELECT t.manager_id, t.team_id, t.team_name FROM scrims s JOIN teams t ON t.team_id = s.team_id WHERE s.scrim_id = ".intval($scrim_id)." LIMIT 1");
                    if ($mgrRes && $mr = $mgrRes->fetch_assoc()) {
                        $conn->query("INSERT INTO scrim_reservation_notifications (reservation_id, manager_id, team_id, scrim_id) VALUES (".intval($reservation_id).", ".intval($mr['manager_id']).", ".intval($mr['team_id']).", ".intval($scrim_id).")");
                        // notify members of both teams (mysqli branch)
                        $ownerTeamId = intval($mr['team_id']);
                        $ownerTeamName = $conn->real_escape_string($mr['team_name'] ?? '');
                        $reserverTeamId = intval($user_team_id ?? 0);
                        $reserverTeamName = '';
                        if ($reserverTeamId) {
                            $rtRes = $conn->query("SELECT team_name FROM teams WHERE team_id = ".$reserverTeamId." LIMIT 1");
                            if ($rtRes && $rtr = $rtRes->fetch_assoc()) $reserverTeamName = $conn->real_escape_string($rtr['team_name'] ?? '');
                        }
                        $sRes = $conn->query("SELECT scrim_start FROM scrims WHERE scrim_id = ".intval($scrim_id)." LIMIT 1");
                        $scrimStart = ($sRes && $sr = $sRes->fetch_assoc()) ? $sr['scrim_start'] : null;
                        $scrimTimeStr = $scrimStart ? date('Y-m-d H:i', strtotime($scrimStart)) : null;
                        $title_for_members = "Scrim reservation: {$ownerTeamName}";
                        $body_for_reserver = "You reserved a slot vs {$ownerTeamName} — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                        $body_for_owner = ($reserverTeamName ?: 'A team') . " reserved a slot vs your team — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                        $meta_common = $conn->real_escape_string(json_encode(['owner_team_id'=>$ownerTeamId,'owner_team_name'=>$ownerTeamName,'reserver_team_id'=>$reserverTeamId,'reserver_team_name'=>$reserverTeamName,'scrim_id'=>$scrim_id,'reservation_id'=>$reservation_id,'scrim_start'=>$scrimStart]));
                        // get members
                        $teamList = array_filter([ $ownerTeamId, $reserverTeamId ]);
                        if (!empty($teamList)) {
                            $in = implode(',', array_map('intval', $teamList));
                            $mRes = $conn->query("SELECT DISTINCT user_id, team_id FROM team_members WHERE team_id IN ($in)");
                            if ($mRes) {
                                while ($mrw = $mRes->fetch_assoc()) {
                                    $mid = intval($mrw['user_id']);
                                    $memberTeam = intval($mrw['team_id']);
                                    $body = ($memberTeam === $ownerTeamId) ? $body_for_owner : $body_for_reserver;
                                    $conn->query("INSERT INTO user_notifications (user_id, type, title, body, meta) VALUES (".intval($mid).", 'scrim_reservation_created', '".$conn->real_escape_string($title_for_members)."', '".$conn->real_escape_string($body)."', '".$meta_common."')");
                                }
                            }
                        }
                    }
                } elseif (isset($mysqli)) {
                    if (!$mysqli->query("INSERT INTO scrim_reservations (scrim_id, user_id, created_at) VALUES (".intval($scrim_id).", ".intval($user_id).", NOW())")) {
                        $mysqli->query("UPDATE scrims SET reserved_count = GREATEST(0, reserved_count - 1) WHERE scrim_id = ".intval($scrscrim_id));
                        send(['success'=>false, 'error'=>'Database error: '.$mysqli->error]);
                    }
                    $reservation_id = $mysqli->insert_id;
                    $mgrRes = $mysqli->query("SELECT t.manager_id, t.team_id, t.team_name FROM scrims s JOIN teams t ON t.team_id = s.team_id WHERE s.scrim_id = ".intval($scrim_id)." LIMIT 1");
                    if ($mgrRes && $mr = $mgrRes->fetch_assoc()) {
                        $mysqli->query("INSERT INTO scrim_reservation_notifications (reservation_id, manager_id, team_id, scrim_id) VALUES (".intval($reservation_id).", ".intval($mr['manager_id']).", ".intval($mr['team_id']).", ".intval($scrim_id).")");
                        // notify members of both teams (mysqli branch)
                        $ownerTeamId = intval($mr['team_id']);
                        $ownerTeamName = $mysqli->real_escape_string($mr['team_name'] ?? '');
                        $reserverTeamId = intval($user_team_id ?? 0);
                        $reserverTeamName = '';
                        if ($reserverTeamId) {
                            $rtRes = $mysqli->query("SELECT team_name FROM teams WHERE team_id = ".$reserverTeamId." LIMIT 1");
                            if ($rtRes && $rtr = $rtRes->fetch_assoc()) $reserverTeamName = $mysqli->real_escape_string($rtr['team_name'] ?? '');
                        }
                        $sRes = $mysqli->query("SELECT scrim_start FROM scrims WHERE scrim_id = ".intval($scrim_id)." LIMIT 1");
                        $scrimStart = ($sRes && $sr = $sRes->fetch_assoc()) ? $sr['scrim_start'] : null;
                        $scrimTimeStr = $scrimStart ? date('Y-m-d H:i', strtotime($scrimStart)) : null;
                        $title_for_members = "Scrim reservation: {$ownerTeamName}";
                        $body_for_reserver = "You reserved a slot vs {$ownerTeamName} — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                        $body_for_owner = ($reserverTeamName ?: 'A team') . " reserved a slot vs your team — scheduled at " . ($scrimTimeStr ?: 'TBD') . ".";
                        $meta_common = $mysqli->real_escape_string(json_encode(['owner_team_id'=>$ownerTeamId,'owner_team_name'=>$ownerTeamName,'reserver_team_id'=>$reserverTeamId,'reserver_team_name'=>$reserverTeamName,'scrim_id'=>$scrim_id,'reservation_id'=>$reservation_id,'scrim_start'=>$scrimStart]));
                        // get members
                        $teamList = array_filter([ $ownerTeamId, $reserverTeamId ]);
                        if (!empty($teamList)) {
                            $in = implode(',', array_map('intval', $teamList));
                            $mRes = $mysqli->query("SELECT DISTINCT user_id, team_id FROM team_members WHERE team_id IN ($in)");
                            if ($mRes) {
                                while ($mrw = $mRes->fetch_assoc()) {
                                    $mid = intval($mrw['user_id']);
                                    $memberTeam = intval($mrw['team_id']);
                                    $body = ($memberTeam === $ownerTeamId) ? $body_for_owner : $body_for_reserver;
                                    $mysqli->query("INSERT INTO user_notifications (user_id, type, title, body, meta) VALUES (".intval($mid).", 'scrim_reservation_created', '".$mysqli->real_escape_string($title_for_members)."', '".$mysqli->real_escape_string($body)."', '".$meta_common."')");
                                }
                            }
                        }
                    }
                }
                send(['success'=>true]);
            } else {
                send(['success'=>false, 'error'=>'Full or expired']);
            }
        }
    } catch (PDOException $ex) {
        // rollback if in transaction
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        // if duplicate key detected, return friendly message
        $msg = $ex->getMessage();
        if (strpos($msg, 'Duplicate entry') !== false) {
            send(['success'=>false, 'error'=>'You have already reserved this scrim']);
        }
        send(['success'=>false, 'error'=>'Database error: '.$msg]);
    } catch (Exception $ex) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        send(['success'=>false, 'error'=>'Server error: '.$ex->getMessage()]);
    }
}
elseif ($action === 'respond_reservation' && $method === 'POST') {
    $reservation_id = isset($input['reservation_id']) ? intval($input['reservation_id']) : 0;
    $response = ($input['response'] ?? '');
    if (!$reservation_id || !in_array($response, ['accept','decline'], true)) send(['success'=>false, 'error'=>'Invalid parameters']);

    // find notification and validate manager
    if (isset($pdo) && $pdo instanceof PDO) {
        $q = $pdo->prepare("SELECT n.id, n.status, n.manager_id, n.scrim_id, n.team_id, r.user_id AS reserver_user_id FROM scrim_reservation_notifications n JOIN scrim_reservations r ON r.reservation_id = n.reservation_id WHERE n.reservation_id = :rid LIMIT 1");
        $q->execute([':rid'=>$reservation_id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) send(['success'=>false, 'error'=>'Not found']);
        if (intval($row['manager_id']) !== intval($user_id)) send(['success'=>false, 'error'=>'Not authorized']);
        if ($row['status'] !== 'pending') send(['success'=>false, 'error'=>'Already responded']);

        $pdo->beginTransaction();
        $newStatus = ($response === 'accept') ? 'accepted' : 'declined';
        $u1 = $pdo->prepare("UPDATE scrim_reservation_notifications SET status = :st WHERE reservation_id = :rid");
        $u1->execute([':st'=>$newStatus, ':rid'=>$reservation_id]);

        $u2 = $pdo->prepare("UPDATE scrim_reservations SET status = :st WHERE reservation_id = :rid");
        $u2->execute([':st'=>$newStatus, ':rid'=>$reservation_id]);

        if ($response === 'decline') {
            // free up slot
            $u3 = $pdo->prepare("UPDATE scrims SET reserved_count = GREATEST(0, reserved_count - 1) WHERE scrim_id = :sid");
            $u3->execute([':sid'=>$row['scrim_id']]);
        }
        // notify ALL members of both teams about decision
        $sinfo = $pdo->prepare("SELECT s.scrim_start, t.team_id AS owner_team_id, t.team_name AS owner_team_name FROM scrims s JOIN teams t ON t.team_id = s.team_id WHERE s.scrim_id = :sid LIMIT 1");
        $sinfo->execute([':sid'=>$row['scrim_id']]);
        $srec = $sinfo->fetch(PDO::FETCH_ASSOC) ?: [];
        $ownerTeamId = intval($srec['owner_team_id'] ?? $row['team_id']);
        $ownerTeamName = $srec['owner_team_name'] ?? null;
        $scrimStart = $srec['scrim_start'] ?? null;
        // fetch reserver's team id
        $reserverTeamId = 0;
        $rr = $pdo->prepare("SELECT team_id FROM users WHERE user_id = :uid LIMIT 1");
        $rr->execute([':uid'=>$row['reserver_user_id']]);
        $reserverTeamId = intval($rr->fetchColumn() ?: 0);
        $reserverTeamName = null;
        if ($reserverTeamId) {
            $rt = $pdo->prepare("SELECT team_name FROM teams WHERE team_id = :tid LIMIT 1");
            $rt->execute([':tid'=>$reserverTeamId]);
            $reserverTeamName = $rt->fetchColumn();
        }
        $scrimTimeStr = $scrimStart ? date('Y-m-d H:i', strtotime($scrimStart)) : null;
        $decisionTitle = ($response === 'accept') ? 'Scrim accepted' : 'Scrim declined';
        $decisionBodyOwner = ($response === 'accept')
            ? ("You accepted the scrim with {$reserverTeamName}. Scheduled at ".($scrimTimeStr ?: 'TBD').".")
            : ("You declined the scrim with {$reserverTeamName}. Scheduled at ".($scrimTimeStr ?: 'TBD').".");
        $decisionBodyReserver = ($response === 'accept')
            ? ("Your reservation vs {$ownerTeamName} was accepted. Scheduled at ".($scrimTimeStr ?: 'TBD').".")
            : ("Your reservation vs {$ownerTeamName} was declined. Scheduled at ".($scrimTimeStr ?: 'TBD').".");
        $meta_dec = json_encode(['owner_team_id'=>$ownerTeamId,'owner_team_name'=>$ownerTeamName,'reserver_team_id'=>$reserverTeamId,'reserver_team_name'=>$reserverTeamName,'scrim_id'=>$row['scrim_id'],'reservation_id'=>$reservation_id,'scrim_start'=>$scrimStart]);
        // get members
        $teams = array_filter([$ownerTeamId, $reserverTeamId]);
        if (!empty($teams)) {
            $placeholders = implode(',', array_fill(0, count($teams), '?'));
            $mQ = $pdo->prepare("SELECT DISTINCT user_id, team_id FROM team_members WHERE team_id IN ($placeholders)");
            $mQ->execute($teams);
            $insN = $pdo->prepare("INSERT INTO user_notifications (user_id, type, title, body, meta) VALUES (:uid, :type, :title, :body, :meta)");
            while ($mrow = $mQ->fetch(PDO::FETCH_ASSOC)) {
                $mid = intval($mrow['user_id']);
                $mteam = intval($mrow['team_id']);
                $body = ($mteam === $ownerTeamId) ? $decisionBodyOwner : $decisionBodyReserver;
                $insN->execute([':uid'=>$mid, ':type'=>($response==='accept'?'scrim_accepted':'scrim_declined'), ':title'=>$decisionTitle, ':body'=>$body, ':meta'=>$meta_dec]);
            }
        }
        $pdo->commit();
        send(['success'=>true]);
    } else {
        // mysqli path (simplified)
        $mgrRes = $run("SELECT n.id, n.status, n.manager_id, n.scrim_id FROM scrim_reservation_notifications n WHERE n.reservation_id = ".intval($reservation_id)." LIMIT 1");
        $nr = $mgrRes->fetch_assoc();
        if (!$nr) send(['success'=>false, 'error'=>'Not found']);
        if (intval($nr['manager_id']) !== intval($user_id)) send(['success'=>false, 'error'=>'Not authorized']);
        if ($nr['status'] !== 'pending') send(['success'=>false, 'error'=>'Already responded']);

        $newStatus = ($response === 'accept') ? 'accepted' : 'declined';
        $run("UPDATE scrim_reservation_notifications SET status = '".addslashes($newStatus)."' WHERE reservation_id = ".intval($reservation_id));
        $run("UPDATE scrim_reservations SET status = '".addslashes($newStatus)."' WHERE reservation_id = ".intval($reservation_id));
        if ($response === 'decline') {
            $run("UPDATE scrims SET reserved_count = GREATEST(0, reserved_count - 1) WHERE scrim_id = ".intval($nr['scrim_id']));
        }
        send(['success'=>true]);
    }
}
elseif ($action === 'create' && $method === 'POST') {
    try {
        // ตรวจสอบว่า user เป็น manager และมี team_id
        if (empty($user_team_id)) {
            send(['success' => false, 'error' => 'Only team managers can create scrims']);
        }

        // รับข้อมูลจาก request
        $scrim_start = $input['scrim_start'] ?? null;
        $format = $input['format'] ?? null;
        $map = $input['map'] ?? null;
        $slots = intval($input['slots'] ?? 0);
        $desired_rank = $input['desired_rank'] ?? 'Unranked';

        // ตรวจสอบข้อมูลที่จำเป็น
        if (!$scrim_start || !$format || $slots <= 0) {
            send(['success' => false, 'error' => 'Missing required fields']);
        }

        // เพิ่ม scrim ลงในฐานข้อมูล
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("
                INSERT INTO scrims (team_id, scrim_start, format, map, slots, desired_rank, is_published)
                VALUES (:team_id, :scrim_start, :format, :map, :slots, :desired_rank, 1)
            ");
            $stmt->execute([
                ':team_id' => $user_team_id,
                ':scrim_start' => $scrim_start,
                ':format' => $format,
                ':map' => $map,
                ':slots' => $slots,
                ':desired_rank' => $desired_rank,
            ]);
            send(['success' => true]);
        } elseif (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) {
            $sql = "
                INSERT INTO scrims (team_id, scrim_start, format, map, slots, desired_rank, is_published)
                VALUES ('" . intval($user_team_id) . "', '" . $conn->real_escape_string($scrim_start) . "', '" . $conn->real_escape_string($format) . "', '" . $conn->real_escape_string($map) . "', " . intval($slots) . ", '" . $conn->real_escape_string($desired_rank) . "', 1)
            ";
            if ($conn->query($sql)) {
                send(['success' => true]);
            } else {
                send(['success' => false, 'error' => 'Database error: ' . $conn->error]);
            }
        } else {
            send(['success' => false, 'error' => 'Database connection not found']);
        }
    } catch (Exception $e) {
        send(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}

send(['success'=>false, 'error'=>'Action not found']);