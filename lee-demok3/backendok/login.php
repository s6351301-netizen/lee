<?php
require __DIR__.'/config.php';
if(current_admin()) redirect_to('dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){ $email=trim($_POST['email']??''); $pwd=$_POST['password']??''; $st=db()->prepare("SELECT * FROM LEE_members WHERE email=? AND role IN ('admin','editor') LIMIT 1"); $st->execute([$email]); $u=$st->fetch(); if($u && ($pwd==='demo' || password_verify($pwd,$u['password_hash']))){ $_SESSION['admin_user']=$u; log_action('admin login'); redirect_to('dashboard.php'); } $error='帳號或密碼錯誤'; }
?><!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><title>後台登入</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/admin.css" rel="stylesheet"></head><body class="login"><form method="post" class="login-card"><h1>後台管理</h1><p>帳號 admin@example.org，密碼 demo</p><?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?><input class="form-control" name="email" value="admin@example.org"><input class="form-control" name="password" type="password" value="demo"><button class="btn btn-primary">登入</button></form></body></html>
