<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$admin = current_admin();
$flash = get_flash();
$active_tab = $active_tab ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? 'Quản Trị') ?> - Sabi Omakase</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --bg: #0f0d0c;
  --panel: #1a1613;
  --panel2: #221c18;
  --line: #2e2621;
  --text: #e9e2da;
  --muted: #9a8f85;
  --red: #b3261e;
  --red2: #d0453b;
  --gold: #c9a961;
  --gold-light: #e6d36a;
  --ok: #7fb069;
  --warn: #e0a03a;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans', sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; }
h1, h2, h3 { font-family: 'Noto Serif', serif; font-weight: 600; }
a { color: inherit; text-decoration: none; }
.app { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }

/* Aside / Sidebar */
aside { background: #0a0908; border-right: 1px solid var(--line); padding: 22px 14px; position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; z-index: 10; }
.brand { display: flex; gap: 10px; align-items: center; padding: 0 8px 22px; border-bottom: 1px solid var(--line); margin-bottom: 14px; }
.brand b { width: 38px; height: 38px; border: 1px solid var(--gold); color: var(--gold); display: grid; place-items: center; font-size: 20px; font-family: 'Noto Serif', serif; flex-shrink: 0; }
.brand span { font-family: 'Noto Serif', serif; font-size: 15px; letter-spacing: 1px; }
.brand small { display: block; font-size: 10px; color: var(--muted); letter-spacing: 2px; font-family: 'Noto Sans'; }
nav { display: flex; flex-direction: column; gap: 4px; overflow-y: auto; flex: 1; }
nav a { display: flex; align-items: center; gap: 12px; width: 100%; text-align: left; background: none; border-left: 3px solid transparent; color: var(--muted); padding: 11px 12px; font: inherit; cursor: pointer; transition: all 0.2s; border-radius: 0 4px 4px 0; }
nav a:hover { color: var(--text); background: var(--panel); }
nav a.active { color: var(--text); background: var(--panel); border-left-color: var(--red2); font-weight: 600; }
nav a i { width: 18px; text-align: center; font-size: 15px; color: var(--gold); }
.side-foot { margin-top: auto; padding: 14px 8px 0; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; display: flex; flex-direction: column; gap: 8px; }
.side-foot .user-info { display: flex; align-items: center; gap: 10px; }
.side-foot .avatar { width: 32px; height: 32px; border-radius: 50%; background: #2a201b; border: 1px solid var(--gold); display: grid; place-items: center; color: var(--gold); font-size: 13px; font-family: 'Noto Serif', serif; font-weight: bold; }
.side-foot a.link-out { color: var(--gold); display: inline-flex; align-items: center; gap: 6px; font-size: 12px; }

/* Main Content */
main { padding: 28px 32px; min-width: 0; overflow-y: auto; }
.head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; }
.head h1 { font-size: 26px; }
.head p { color: var(--muted); margin-top: 4px; }
.head-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* Buttons & Inputs */
.btn { background: var(--red); color: #fff; border: 1px solid var(--red); padding: 9px 16px; font: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border-radius: 2px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s; }
.btn:hover { background: var(--red2); border-color: var(--red2); color: #fff; }
.btn.ghost { background: transparent; border-color: #49403a; color: var(--text); }
.btn.ghost:hover { border-color: var(--gold); color: var(--gold); background: rgba(201,169,97,0.08); }
.btn.sm { padding: 5px 10px; font-size: 12px; }
.btn.danger { background: #6b2420; border-color: var(--red2); }
.btn.danger:hover { background: var(--red); }
input, select, textarea { background: #0f0d0c; border: 1px solid #3a312b; color: var(--text); padding: 9px 12px; font: inherit; font-size: 13px; border-radius: 2px; }
input:focus, select:focus, textarea:focus { outline: 1px solid var(--gold); border-color: var(--gold); }
textarea { resize: vertical; }

/* KPIs Grid */
.kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
.kpi { background: var(--panel); border: 1px solid var(--line); padding: 18px 16px; border-radius: 4px; }
.kpi .l { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
.kpi .v { font-family: 'Noto Serif', serif; font-size: 24px; margin: 6px 0 4px; color: var(--text); font-weight: 600; }
.kpi .d { font-size: 12px; color: var(--muted); }

/* Panels & Tables */
.panel { background: var(--panel); border: 1px solid var(--line); padding: 20px; border-radius: 4px; margin-bottom: 18px; }
.panel h2 { font-size: 16px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; }
.grid2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 16px; margin-bottom: 16px; }
.tools { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; }
.tools input { min-width: 240px; flex: 1; }
.tw { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { font-weight: 600; color: var(--muted); text-align: left; font-size: 12px; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; text-transform: uppercase; letter-spacing: 0.5px; }
td { padding: 12px 10px; border-bottom: 1px solid var(--line); vertical-align: middle; font-size: 13px; }
tr:hover td { background: var(--panel2); }
.sub { color: var(--muted); font-size: 12px; }

/* Badges */
.badge { display: inline-block; padding: 3px 9px; font-size: 11px; border: 1px solid; border-radius: 2px; white-space: nowrap; font-weight: 500; }
.b-ok { color: var(--ok); border-color: #3d5a33; background: rgba(127,176,105,0.12); }
.b-warn { color: var(--warn); border-color: #6b4d17; background: rgba(224,160,58,0.12); }
.b-bad { color: var(--red2); border-color: #6b2420; background: rgba(208,69,59,0.12); }
.b-gold { color: var(--gold); border-color: #6b5a2c; background: rgba(201,169,97,0.12); }
.b-mute { color: var(--muted); border-color: #3a312b; background: rgba(154,143,133,0.1); }

/* Flash message alert */
.alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; justify-content: space-between; }
.alert-ok { background: rgba(127,176,105,0.15); border: 1px solid #3d5a33; color: #a4e08a; }
.alert-err { background: rgba(208,69,59,0.15); border: 1px solid #6b2420; color: #ff9b94; }
.alert-warn { background: rgba(224,160,58,0.15); border: 1px solid #6b4d17; color: #f5c477; }

/* Modal popup */
.modal { position: fixed; inset: 0; background: rgba(0,0,0,0.78); display: none; place-items: center; padding: 16px; z-index: 50; }
.modal.open { display: grid; }
.box { background: var(--panel); border: 1px solid #49403a; width: min(600px, 100%); max-height: 92vh; overflow-y: auto; padding: 26px; border-radius: 4px; }
.box h2 { margin-bottom: 14px; font-size: 18px; color: var(--gold); }
.box label { display: block; font-size: 12px; color: var(--muted); margin: 12px 0 5px; text-transform: uppercase; letter-spacing: 0.5px; }
.box input, .box select, .box textarea { width: 100%; }
.two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.acts { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.empty { text-align: center; color: var(--muted); padding: 32px 16px; }

/* Visual Chart bars */
.bars { display: flex; align-items: flex-end; gap: 8px; height: 180px; padding-top: 14px; }
.bar { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; min-width: 0; }
.bar i { display: block; width: 100%; background: linear-gradient(var(--red2), var(--red)); min-height: 3px; border-radius: 2px 2px 0 0; }
.bar em { font-style: normal; font-size: 10px; color: var(--muted); margin-top: 6px; white-space: nowrap; }
.bar u { text-decoration: none; font-size: 10px; color: var(--gold); margin-bottom: 4px; }
.hbar { display: grid; grid-template-columns: 140px 1fr 90px; gap: 12px; align-items: center; margin: 10px 0; font-size: 13px; }
.hbar div.t { height: 10px; background: #0f0d0c; border: 1px solid var(--line); border-radius: 2px; overflow: hidden; }
.hbar div.t i { display: block; height: 100%; background: var(--gold); }

@media (max-width: 900px) {
  .app { grid-template-columns: 1fr; }
  aside { position: static; height: auto; }
  nav { flex-direction: row; overflow-x: auto; }
  nav a { white-space: nowrap; border-left: 0; border-bottom: 3px solid transparent; }
  nav a.active { border-bottom-color: var(--red2); }
  .side-foot { display: none; }
  main { padding: 18px; }
}
</style>
</head>
<body>
<div class="app">
<?php require_once __DIR__ . '/sidebar.php'; ?>
<main>
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'ok' ? 'ok' : ($flash['type'] === 'warn' ? 'warn' : 'err') ?>">
    <span><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($flash['message']) ?></span>
    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
  </div>
<?php endif; ?>
