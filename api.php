<?php
// kubikasiku — API sinkronisasi cloud (dipakai oleh index.html via fetch)
// Auth: token acak per perangkat. Password di-hash (password_hash).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require __DIR__.'/config.php';

function j($o){ echo json_encode($o, JSON_UNESCAPED_UNICODE); exit; }

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Exception $e){ j(['ok'=>false,'error'=>'db']); }

// pastikan tabel ada (dijalankan otomatis)
$pdo->exec("CREATE TABLE IF NOT EXISTS kb_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS kb_tokens (
  token CHAR(64) PRIMARY KEY,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS kb_data (
  user_id INT PRIMARY KEY,
  payload MEDIUMTEXT NOT NULL,
  updated_at BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$in = json_decode(file_get_contents('php://input'), true);
if(!is_array($in)) $in = [];
$action = $in['action'] ?? '';

function newToken($pdo,$uid){
  $t = bin2hex(random_bytes(32));
  $pdo->prepare("INSERT INTO kb_tokens(token,user_id) VALUES(?,?)")->execute([$t,$uid]);
  // batasi 5 token (perangkat) per user, yang terlama dibuang
  $pdo->prepare("DELETE FROM kb_tokens WHERE user_id=? AND token NOT IN (
    SELECT token FROM (SELECT token FROM kb_tokens WHERE user_id=? ORDER BY created_at DESC LIMIT 5) x
  )")->execute([$uid,$uid]);
  return $t;
}
function authUser($pdo,$in){
  $t = $in['token'] ?? '';
  if(!is_string($t) || !preg_match('/^[0-9a-f]{64}$/',$t)) return 0;
  $r = $pdo->prepare("SELECT user_id FROM kb_tokens WHERE token=?");
  $r->execute([$t]);
  $row = $r->fetch();
  return $row ? (int)$row['user_id'] : 0;
}

if($action==='ping'){ j(['ok'=>true]); }

if($action==='register' || $action==='login'){
  $u = trim((string)($in['username'] ?? ''));
  $p = (string)($in['password'] ?? '');
  if(!preg_match('/^[a-zA-Z0-9_]{3,30}$/',$u)) j(['ok'=>false,'error'=>'username']);
  if(strlen($p)<4 || strlen($p)>72) j(['ok'=>false,'error'=>'password']);
  if($action==='register'){
    $r = $pdo->prepare("SELECT id FROM kb_users WHERE username=?");
    $r->execute([$u]);
    if($r->fetch()) j(['ok'=>false,'error'=>'exists']);
    $pdo->prepare("INSERT INTO kb_users(username,pass_hash) VALUES(?,?)")
        ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
    $uid = (int)$pdo->lastInsertId();
  } else {
    $r = $pdo->prepare("SELECT id,pass_hash FROM kb_users WHERE username=?");
    $r->execute([$u]);
    $row = $r->fetch();
    if(!$row || !password_verify($p,$row['pass_hash'])) j(['ok'=>false,'error'=>'auth']);
    $uid = (int)$row['id'];
  }
  j(['ok'=>true,'token'=>newToken($pdo,$uid),'username'=>$u]);
}

$uid = authUser($pdo,$in);
if(!$uid) j(['ok'=>false,'error'=>'token']);

if($action==='logout'){
  $pdo->prepare("DELETE FROM kb_tokens WHERE token=?")->execute([(string)($in['token'] ?? '')]);
  j(['ok'=>true]);
}
if($action==='pull'){
  $r = $pdo->prepare("SELECT payload,updated_at FROM kb_data WHERE user_id=?");
  $r->execute([$uid]);
  $row = $r->fetch();
  j(['ok'=>true,
     'payload'=>$row?json_decode($row['payload'],true):null,
     'updated_at'=>$row?(int)$row['updated_at']:0]);
}
if($action==='push'){
  $payload = $in['payload'] ?? null;
  $ts = (int)($in['updated_at'] ?? 0);
  $base = (int)($in['base'] ?? 0);
  if(!is_array($payload) || $ts<=0) j(['ok'=>false,'error'=>'data']);
  $js = json_encode($payload, JSON_UNESCAPED_UNICODE);
  if($js===false || strlen($js)>2000000) j(['ok'=>false,'error'=>'too_big']); // maks ~2MB
  $r = $pdo->prepare("SELECT payload,updated_at FROM kb_data WHERE user_id=?");
  $r->execute([$uid]);
  $row = $r->fetch();
  $srv = $row ? (int)$row['updated_at'] : 0;
  if($srv > $base){
    // perangkat lain menyimpan lebih baru -> konflik, client harus pull dulu
    j(['ok'=>false,'error'=>'conflict',
       'payload'=>json_decode($row['payload'],true),'updated_at'=>$srv]);
  }
  $pdo->prepare("INSERT INTO kb_data(user_id,payload,updated_at) VALUES(?,?,?)
    ON DUPLICATE KEY UPDATE payload=VALUES(payload), updated_at=VALUES(updated_at)")
    ->execute([$uid,$js,$ts]);
  j(['ok'=>true,'updated_at'=>$ts]);
}
j(['ok'=>false,'error'=>'action']);
