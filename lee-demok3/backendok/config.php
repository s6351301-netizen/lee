<?php
declare(strict_types=1);
session_start();
define('DB_HOST','127.0.0.1'); define('DB_NAME','lee_clan_demo'); define('DB_USER','root'); define('DB_PASS','');
function db(): PDO { static $pdo=null; if(!$pdo){ $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect_to($u): void { header('Location: '.$u); exit; }
function current_admin(): ?array { return $_SESSION['admin_user'] ?? null; }
function require_login(): void { if(!current_admin()) redirect_to('login.php'); }
function flash($m): void { $_SESSION['flash']=$m; }
function get_flash(): string { $m=$_SESSION['flash']??''; unset($_SESSION['flash']); return $m; }
function log_action($a): void { $uid=current_admin()['member_id']??null; db()->prepare('INSERT INTO LEE_logs(member_id,action,logged_at) VALUES(?,?,NOW())')->execute([$uid,$a]); }
