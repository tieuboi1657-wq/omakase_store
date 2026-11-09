<?php
require_once __DIR__ . '/config/auth.php';
require_admin();

$admin = current_admin();

// 1. Lấy danh mục
$cats_stmt = $pdo->query("SELECT ten_loai FROM loai_set ORDER BY thu_tu ASC");
$db_categories = $cats_stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($db_categories)) {
    $db_categories = ['Omakase Tối Cao Cấp', 'Kaiseki 12 Món Truyền Thống', 'Omakase Trưa (Lunch)', 'Set Theo Mùa Đặc Biệt', 'Set Bò Wagyu A5 & Otoro'];
}

// 2. Lấy danh sách sản phẩm từ DB
$prods_stmt = $pdo->query("
    SELECT s.id, s.ma_set, s.ten_set, s.ten_tieng_nhat, l.ten_loai, s.gia, s.so_mon, s.thoi_luong_phut, s.nhan, s.trang_thai, s.hinh_anh, s.mo_ta_ngan
    FROM set_omakase s
    LEFT JOIN loai_set l ON s.loai_set_id = l.id
    ORDER BY s.id ASC
");
$raw_products = $prods_stmt->fetchAll();
$db_products = [];
foreach ($raw_products as $p) {
    $db_products[] = [
        'id'       => $p['ma_set'],
        'db_id'    => (int)$p['id'],
        'name'     => $p['ten_set'],
        'jp'       => $p['ten_tieng_nhat'] ?: '',
        'cat'      => $p['ten_loai'] ?: $db_categories[0],
        'price'    => (float)$p['gia'],
        'courses'  => (int)$p['so_mon'],
        'duration' => (int)$p['thoi_luong_phut'],
        'badge'    => $p['nhan'] ?: '',
        'status'   => $p['trang_thai'],
        'img'      => !empty($p['hinh_anh']) ? $p['hinh_anh'] : 'images/kiwami.jpg',
        'desc'     => $p['mo_ta_ngan'] ?: ''
    ];
}

// 3. Lấy danh sách khách hàng từ DB
$cust_stmt = $pdo->query("
    SELECT k.id, k.ma_kh, k.ho_ten, k.so_dien_thoai, k.email, k.hang_thanh_vien, k.diem_tich_luy, k.ngay_tao
    FROM khach_hang k
    ORDER BY k.id ASC
");
$raw_cust = $cust_stmt->fetchAll();
$db_customers = [];
foreach ($raw_cust as $c) {
    $db_customers[] = [
        'id'     => $c['ma_kh'],
        'db_id'  => (int)$c['id'],
        'name'   => $c['ho_ten'],
        'phone'  => $c['so_dien_thoai'],
        'email'  => $c['email'] ?: '',
        'tier'   => $c['hang_thanh_vien'] ?: 'Thành viên',
        'points' => (int)$c['diem_tich_luy'],
        'joined' => substr($c['ngay_tao'], 0, 10)
    ];
}

// 4. Lấy danh sách đơn đặt bàn từ DB
$ord_stmt = $pdo->query("
    SELECT d.id, d.ma_don, d.ngay_dung, d.gio_dung, d.so_khach, d.tong_tien, d.phuong_thuc_tt, d.trang_thai, d.ghi_chu,
           k.ma_kh, COALESCE(d.ten_lien_he, k.ho_ten) AS cname,
           s.ten_set, s.gia AS set_price
    FROM don_dat_ban d
    LEFT JOIN khach_hang k ON d.khach_hang_id = k.id
    LEFT JOIN chi_tiet_don c ON d.id = c.don_id
    LEFT JOIN set_omakase s ON c.set_id = s.id
    ORDER BY d.ngay_dung DESC, d.gio_dung DESC
");
$raw_ord = $ord_stmt->fetchAll();
$db_orders = [];
$seen = [];
foreach ($raw_ord as $o) {
    if (isset($seen[$o['id']])) continue;
    $seen[$o['id']] = true;
    $db_orders[] = [
        'id'     => $o['ma_don'] ?: ('SB-' . $o['id']),
        'db_id'  => (int)$o['id'],
        'cid'    => $o['ma_kh'] ?: 'KH101',
        'cname'  => $o['cname'] ?: 'Khách hàng',
        'set'    => $o['ten_set'] ?: 'Set Kiwami Grand Omakase',
        'price'  => (float)($o['set_price'] ?: round($o['tong_tien'] / max(1, $o['so_khach']))),
        'guests' => (int)$o['so_khach'],
        'date'   => $o['ngay_dung'],
        'time'   => substr($o['gio_dung'], 0, 5),
        'pay'    => $o['phuong_thuc_tt'] ?: 'Thẻ',
        'status' => $o['trang_thai'] ?: 'Chờ xác nhận',
        'note'   => $o['ghi_chu'] ?: ''
    ];
}

// 5. Lấy danh sách đánh giá từ DB
$rev_stmt = $pdo->query("
    SELECT d.id, d.so_sao, d.noi_dung, d.loai, d.phan_hoi, d.da_xu_ly, d.ngay_danh_gia,
           k.ho_ten AS cname, s.ten_set
    FROM danh_gia d
    JOIN khach_hang k ON d.khach_hang_id = k.id
    JOIN set_omakase s ON d.set_id = s.id
    ORDER BY d.id DESC
");
$raw_rev = $rev_stmt->fetchAll();
$db_reviews = [];
foreach ($raw_rev as $r) {
    $db_reviews[] = [
        'id'       => (int)$r['id'],
        'cname'    => $r['cname'],
        'set'      => $r['ten_set'],
        'stars'    => (int)$r['so_sao'],
        'text'     => $r['noi_dung'] ?: '',
        'type'     => $r['loai'] === 'Khiếu nại' ? 'complaint' : 'review',
        'date'     => substr($r['ngay_danh_gia'], 0, 10),
        'reply'    => $r['phan_hoi'] ?: '',
        'resolved' => (bool)$r['da_xu_ly']
    ];
}

// 6. Lấy danh sách nhân viên từ DB
$staff_stmt = $pdo->query("SELECT id, ma_nv, ho_ten, bo_phan, chuc_danh FROM nhan_vien ORDER BY id ASC");
$raw_staff = $staff_stmt->fetchAll();
$db_staff = [];
foreach ($raw_staff as $s) {
    $db_staff[] = [
        'id'    => $s['ma_nv'] ?: ('NV' . $s['id']),
        'name'  => $s['ho_ten'],
        'role'  => $s['bo_phan'],
        'title' => $s['chuc_danh']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản trị - Sabi Omakase</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0d0c;--panel:#1a1613;--panel2:#221c18;--line:#2e2621;--text:#e9e2da;--muted:#9a8f85;--red:#b3261e;--red2:#d0453b;--gold:#c9a961;--ok:#7fb069;--warn:#e0a03a}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Noto Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5}
h1,h2,h3{font-family:'Noto Serif',serif;font-weight:600}
.app{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
aside{background:#0a0908;border-right:1px solid var(--line);padding:22px 14px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column}
.brand{display:flex;gap:10px;align-items:center;padding:0 8px 22px}
.brand b{width:38px;height:38px;border:1px solid var(--gold);color:var(--gold);display:grid;place-items:center;font-size:20px;font-family:'Noto Serif',serif}
.brand span{font-family:'Noto Serif',serif;font-size:15px;letter-spacing:1px}.brand small{display:block;font-size:10px;color:var(--muted);letter-spacing:2px;font-family:'Noto Sans'}
nav button{display:block;width:100%;text-align:left;background:none;border:0;border-left:3px solid transparent;color:var(--muted);padding:11px 12px;font:inherit;cursor:pointer}
nav button:hover{color:var(--text);background:var(--panel)}
nav button.on{color:var(--text);background:var(--panel);border-left-color:var(--red2)}
.side-foot{margin-top:auto;padding:12px 8px;border-top:1px solid var(--line);color:var(--muted);font-size:12px}
.side-foot a{color:var(--gold);text-decoration:none}
main{padding:28px 32px;min-width:0}
.head{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.head h1{font-size:26px}.head p{color:var(--muted);margin-top:4px}
.btn{background:var(--red);color:#fff;border:1px solid var(--red);padding:9px 16px;font:inherit;cursor:pointer}
.btn:hover{background:var(--red2)}
.btn.ghost{background:transparent;border-color:#49403a;color:var(--text)}.btn.ghost:hover{border-color:var(--gold)}
.btn.sm{padding:5px 10px;font-size:12px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:var(--panel);border:1px solid var(--line);padding:16px}
.kpi .l{color:var(--muted);font-size:12px}.kpi .v{font-family:'Noto Serif',serif;font-size:24px;margin:4px 0}.kpi .d{font-size:12px;color:var(--muted)}
.grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;margin-bottom:16px}
.panel{background:var(--panel);border:1px solid var(--line);padding:18px;min-width:0}
.panel h2{font-size:16px;margin-bottom:14px}
.tools{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
input,select,textarea{background:#0f0d0c;border:1px solid #3a312b;color:var(--text);padding:9px 10px;font:inherit}
input:focus,select:focus,textarea:focus{outline:1px solid var(--gold)}
.tools input{min-width:240px;flex:1}
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{font-weight:500;color:var(--muted);text-align:left;font-size:12px;padding:10px 8px;border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:11px 8px;border-bottom:1px solid var(--line);vertical-align:middle}
tr:hover td{background:var(--panel2)}
.sub{color:var(--muted);font-size:12px}
.badge{display:inline-block;padding:2px 9px;font-size:11px;border:1px solid;white-space:nowrap}
.b-ok{color:var(--ok);border-color:#3d5a33}.b-warn{color:var(--warn);border-color:#6b4d17}.b-bad{color:var(--red2);border-color:#6b2420}.b-gold{color:var(--gold);border-color:#6b5a2c}.b-mute{color:var(--muted);border-color:#3a312b}
.pager{display:flex;gap:6px;margin-top:14px}
.pager button{background:none;border:1px solid var(--line);color:var(--muted);padding:5px 11px;cursor:pointer}.pager button.on{border-color:var(--gold);color:var(--gold)}
.bars{display:flex;align-items:flex-end;gap:6px;height:170px;padding-top:10px}
.bar{flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;min-width:0}
.bar i{display:block;width:100%;background:linear-gradient(var(--red2),var(--red));min-height:2px}
.bar em{font-style:normal;font-size:10px;color:var(--muted);margin-top:5px;white-space:nowrap}
.bar u{text-decoration:none;font-size:10px;color:var(--gold);margin-bottom:3px}
.hbar{display:grid;grid-template-columns:130px 1fr 80px;gap:10px;align-items:center;margin:9px 0;font-size:13px}
.hbar div.t{height:10px;background:#0f0d0c;border:1px solid var(--line)}.hbar div.t i{display:block;height:100%;background:var(--gold)}
.donut{width:140px;height:140px;border-radius:50%;position:relative;flex:none}
.donut:after{content:"";position:absolute;inset:26px;background:var(--panel);border-radius:50%}
.legend{display:grid;gap:7px;font-size:13px}.legend i{display:inline-block;width:10px;height:10px;margin-right:8px}
.row{display:flex;gap:22px;align-items:center;flex-wrap:wrap}
.rv{border:1px solid var(--line);background:var(--panel);padding:16px;margin-bottom:12px}
.rv .top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
.stars{color:var(--gold);letter-spacing:2px}
.reply{margin-top:10px;padding:10px 12px;border-left:3px solid var(--gold);background:var(--panel2)}
.rv textarea{width:100%;min-height:60px;margin-top:10px}
.sch th,.sch td{text-align:center;padding:6px 4px;font-size:12px}
.sch td:first-child,.sch th:first-child{text-align:left;padding-left:8px;white-space:nowrap}
.cell{display:inline-block;min-width:54px;padding:5px 4px;cursor:pointer;border:1px solid var(--line);user-select:none}
.cell[data-s=O]{color:#6d635b}.cell[data-s=L]{background:#2b2a12;color:#e6d36a;border-color:#4a4717}.cell[data-s=D]{background:#1c2733;color:#7fb3e6;border-color:#2b4157}.cell[data-s=F]{background:#2c1a17;color:#e58b80;border-color:#5a2c26}
.cov.bad{color:var(--red2);font-weight:600}.cov.good{color:var(--ok)}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.72);display:none;place-items:center;padding:16px;z-index:20}.modal.open{display:grid}
.box{background:var(--panel);border:1px solid #49403a;width:min(540px,100%);max-height:92vh;overflow:auto;padding:24px}
.box h2{margin-bottom:12px}.box label{display:block;font-size:12px;color:var(--muted);margin:10px 0 4px}.box input,.box select,.box textarea{width:100%}
.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.err{color:var(--red2);font-size:12px;min-height:18px;margin-top:8px}
.acts{display:flex;justify-content:flex-end;gap:10px;margin-top:14px}
.empty{text-align:center;color:var(--muted);padding:26px}
@media(max-width:860px){.app{grid-template-columns:1fr}aside{position:static;height:auto}nav{display:flex;overflow:auto}nav button{white-space:nowrap;border-left:0;border-bottom:3px solid transparent}nav button.on{border-bottom-color:var(--red2)}.side-foot{display:none}main{padding:18px}.hbar{grid-template-columns:90px 1fr 70px}}
</style>
</head>
<body>
<div class="app">
<aside>
  <div class="brand"><b>祥</b><span>SABI OMAKASE<small>QUẢN TRỊ</small></span></div>
  <nav id="nav">
    <button data-t="overview">Thống kê &amp; Tổng quan</button>
    <button data-t="products">Quản lý Sản phẩm</button>
    <button data-t="orders">Quản lý Order</button>
    <button data-t="customers">Quản lý Khách hàng</button>
    <button data-t="revenue">Quản lý Doanh thu</button>
    <button data-t="reviews">Đánh giá &amp; Phản hồi</button>
    <button data-t="staff">Lịch làm việc nhân viên</button>
  </nav>
  <div class="side-foot">
    Đăng nhập: <?= htmlspecialchars($admin['ho_ten'] ?? 'Daiki S.') ?><br>
    <a href="../php/index.php" target="_blank">← Về website khách</a> &nbsp;|&nbsp;
    <a href="logout.php" style="color:var(--gold);">Đăng xuất</a>
  </div>
</aside>
<main id="view"></main>
</div>

<div class="modal" id="modal"><div class="box" id="box"></div></div>

<script>
/* ============ TIỆN ÍCH ============ */
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const money = n => Math.round(n).toLocaleString('vi-VN') + 'đ';
const short = n => (n / 1e6).toFixed(1).replace('.0', '') + 'tr';
const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
const dm = s => s.slice(8) + '/' + s.slice(5, 7);
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
const today = new Date(); today.setHours(0, 0, 0, 0);
const TODAY = iso(today);

const csv = (name, head, rows) => {
  const t = '\ufeff' + [head, ...rows].map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
  const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([t], { type: 'text/csv;charset=utf-8' })); a.download = name; a.click();
};
const pageOf = (list, p, per) => { const n = Math.max(1, Math.ceil(list.length / per)); p = Math.min(p, n); return { rows: list.slice((p - 1) * per, p * per), n, p }; };
const pager = (n, p) => n < 2 ? '' : '<div class="pager">' + Array.from({ length: n }, (_, i) => `<button class="${i + 1 === p ? 'on' : ''}" data-p="${i + 1}">${i + 1}</button>`).join('') + '</div>';

/* ============ DỮ LIỆU TỪ MYSQL DATABASE ============ */
const CATEGORIES = <?= json_encode($db_categories, JSON_UNESCAPED_UNICODE) ?>;
let products = <?= json_encode($db_products, JSON_UNESCAPED_UNICODE) ?>;
let customers = <?= json_encode($db_customers, JSON_UNESCAPED_UNICODE) ?>;
let orders = <?= json_encode($db_orders, JSON_UNESCAPED_UNICODE) ?>;
let reviews = <?= json_encode($db_reviews, JSON_UNESCAPED_UNICODE) ?>;
const STAFF = <?= json_encode($db_staff, JSON_UNESCAPED_UNICODE) ?>;

let SETS = products.map(p => [p.name, p.price]);
const syncSets = () => { SETS = products.map(p => [p.name, p.price]); };
const PAYS = ['Thẻ', 'Chuyển khoản', 'Tiền mặt', 'Ví điện tử'];
const OSTAT = ['Chờ xác nhận', 'Đã xác nhận', 'Hoàn tất', 'Đã hủy'];
const TIERS = ['Thành viên', 'Vàng', 'VIP'];

const SHIFT = { O: ['Nghỉ', ''], L: ['Trưa', '11:00–15:00'], D: ['Tối', '17:00–23:00'], F: ['Cả ngày', '11:00–23:00'] };
const NEED = { 'Phục vụ': [2, 2], 'Đầu bếp': [4, 5], 'Tạp vụ': [1, 1], 'Quản lý': [1, 1] };
const overrides = {};
function shiftOf(s, dateStr) {
  const key = s.id + '|' + dateStr; if (overrides[key]) return overrides[key];
  const d = new Date(dateStr + 'T00:00:00'), w = Math.floor(d / 604800000), dow = (d.getDay() + 6) % 7;
  const idx = STAFF.filter(x => x.role === s.role).indexOf(s), k = (idx + dow + w) % 7;
  if (k === 0 || k === 3) return 'O';
  const m = (idx + dow) % 3;
  if (s.role === 'Đầu bếp') return m === 1 ? 'D' : 'F';
  return ['L', 'D', 'F'][m];
}

/* ============ TÍNH TOÁN CHUNG ============ */
const total = o => o.price * o.guests;
const done = () => orders.filter(o => o.status === 'Hoàn tất');
const custStats = c => { const os = orders.filter(o => o.cid === c.id && o.status === 'Hoàn tất'); return { n: os.length, spend: os.reduce((a, o) => a + total(o), 0) }; };
const avgRating = () => reviews.length ? reviews.reduce((a, r) => a + r.stars, 0) / reviews.length : 0;
const oBadge = s => `<span class="badge ${{ 'Chờ xác nhận': 'b-warn', 'Đã xác nhận': 'b-gold', 'Hoàn tất': 'b-ok', 'Đã hủy': 'b-bad' }[s] || 'b-mute'}">${s}</span>`;
const revenueByDay = days => Array.from({ length: days }, (_, i) => { const d = iso(addDays(today, i - days + 1)); return { d, v: done().filter(o => o.date === d).reduce((a, o) => a + total(o), 0) }; });

/* ============ ĐIỀU HƯỚNG TABS ============ */
let tab = (location.hash || '#overview').slice(1);
const S = { pq: '', pc: '', ps: '', pp: 1, oq: '', os: '', op: 1, cq: '', ct: '', cp: 1, rgDays: 14, rt: '', rs: '', rst: '', wr: '', wk: 0 };
const view = $('view');
function go(t) { tab = TABS[t] ? t : 'overview'; location.hash = tab; document.querySelectorAll('#nav button').forEach(b => b.classList.toggle('on', b.dataset.t === tab)); TABS[tab](); scrollTo(0, 0); }
$('nav').onclick = e => { if (e.target.dataset.t) go(e.target.dataset.t); };
const head = (h, p, actions = '') => `<div class="head"><div><h1>${h}</h1><p>${p}</p></div><div>${actions}</div></div>`;
const modal = html => { $('box').innerHTML = html; $('modal').classList.add('open'); };
const closeM = () => $('modal').classList.remove('open');
$('modal').onclick = e => { if (e.target.id === 'modal') closeM(); };
document.addEventListener('keydown', e => e.key === 'Escape' && closeM());

// Gọi API ngầm để lưu vào MySQL
function postAPI(action, data) {
  fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, data })
  }).catch(e => console.error('API error:', e));
}

/* ============ 1. TỔNG QUAN ============ */
function overview() {
  const ds = done(), rev = ds.reduce((a, o) => a + total(o), 0), canc = orders.filter(o => o.status === 'Đã hủy').length;
  const pend = orders.filter(o => o.status === 'Chờ xác nhận').length, days = revenueByDay(14), max = Math.max(...days.map(x => x.v), 1);
  const bySet = {}; ds.forEach(o => bySet[o.set] = (bySet[o.set] || 0) + total(o));
  const top = Object.entries(bySet).sort((a, b) => b[1] - a[1]).slice(0, 5), tmax = top.length ? top[0][1] : 1;
  const stCol = { 'Chờ xác nhận': '#e0a03a', 'Đã xác nhận': '#c9a961', 'Hoàn tất': '#7fb069', 'Đã hủy': '#d0453b' };
  let acc = 0; const seg = orders.length ? OSTAT.map(s => { const n = orders.filter(o => o.status === s).length, a = acc; acc += n / orders.length * 100; return `${stCol[s]} ${a}% ${acc}%`; }).join(',') : '#444 0% 100%';
  const dist = [5, 4, 3, 2, 1].map(s => [s, reviews.filter(r => r.stars === s).length]);
  const openC = reviews.filter(r => r.type === 'complaint' && !r.resolved).length, noRep = reviews.filter(r => !r.reply).length;
  const onDuty = STAFF.filter(s => shiftOf(s, TODAY) !== 'O').length;
  view.innerHTML = head('Thống kê & Tổng quan', 'Tình hình kinh doanh thực tế từ cơ sở dữ liệu nhà hàng Sabi Omakase.') + `
  <div class="kpis">
    <div class="kpi"><div class="l">Doanh thu tích lũy</div><div class="v">${money(rev)}</div><div class="d">${ds.length} đơn hoàn tất</div></div>
    <div class="kpi"><div class="l">Tổng đơn</div><div class="v">${orders.length}</div><div class="d">${pend} đơn chờ xác nhận</div></div>
    <div class="kpi"><div class="l">Sản phẩm / Set</div><div class="v">${products.length}</div><div class="d">${products.filter(p => p.status === 'Đang bán').length} set đang phục vụ</div></div>
    <div class="kpi"><div class="l">Khách hàng</div><div class="v">${customers.length}</div><div class="d">${customers.filter(c => c.tier === 'VIP').length} VIP · ${customers.filter(c => c.tier === 'Vàng').length} Vàng</div></div>
    <div class="kpi"><div class="l">Đánh giá trung bình</div><div class="v">${avgRating().toFixed(1)} ★</div><div class="d">${reviews.length} đánh giá</div></div>
    <div class="kpi"><div class="l">Nhân sự hôm nay</div><div class="v">${onDuty}/${STAFF.length}</div><div class="d">đang có ca làm</div></div>
  </div>
  <div class="grid2">
    <div class="panel"><h2>Doanh thu theo ngày</h2><div class="bars">${days.map(x => `<div class="bar" title="${money(x.v)}"><u>${x.v ? short(x.v) : ''}</u><i style="height:${x.v / max * 100 * .8}%"></i><em>${dm(x.d)}</em></div>`).join('')}</div></div>
    <div class="panel"><h2>Trạng thái đơn</h2><div class="row"><div class="donut" style="background:conic-gradient(${seg})"></div><div class="legend">${OSTAT.map(s => `<div><i style="background:${stCol[s]}"></i>${s}: <b>${orders.filter(o => o.status === s).length}</b></div>`).join('')}</div></div></div>
  </div>
  <div class="grid2">
    <div class="panel"><h2>Top set theo doanh thu</h2>${top.map(t => `<div class="hbar"><span>${esc(t[0].replace('Set ', ''))}</span><div class="t"><i style="width:${t[1] / tmax * 100}%"></i></div><span>${short(t[1])}</span></div>`).join('') || '<div class="empty">Chưa có dữ liệu</div>'}</div>
    <div class="panel"><h2>Đánh giá tổng quan</h2>${dist.map(d => `<div class="hbar"><span class="stars">${'★'.repeat(d[0])}</span><div class="t"><i style="width:${reviews.length ? d[1] / reviews.length * 100 : 0}%"></i></div><span>${d[1]}</span></div>`).join('')}
      <p class="sub" style="margin-top:10px">${openC} khiếu nại chưa xử lý · ${noRep} đánh giá chưa phản hồi</p></div>
  </div>`;
}

/* ============ 2. SẢN PHẨM ============ */
const pBadge = s => `<span class="badge ${s === 'Đang bán' ? 'b-ok' : s === 'Tạm ngừng' ? 'b-warn' : 'b-bad'}">${s}</span>`;
function productRows() {
  let l = products.filter(p => (!S.ps || p.status === S.ps) && (!S.pc || p.cat === S.pc) && (!S.pq || (p.id + ' ' + p.name + ' ' + (p.jp || '') + ' ' + p.cat).toLowerCase().includes(S.pq.toLowerCase())));
  const p = pageOf(l, S.pp, 8); S.pp = p.p;
  $('ptb').innerHTML = p.rows.length ? p.rows.map(x => `<tr>
    <td><div style="display:flex;align-items:center;gap:12px;">
      <img src="../${x.img || 'images/kiwami.jpg'}" alt="${esc(x.name)}" style="width:44px;height:44px;border-radius:4px;object-fit:cover;border:1px solid var(--line);background:#000;flex-shrink:0;" onerror="this.onerror=null;this.src='../images/kiwami.jpg';">
      <div><b>${esc(x.name)}</b><div class="sub"><code>${esc(x.id)}</code> ${x.jp ? `· <span>${esc(x.jp)}</span>` : ''} ${x.badge ? `· <span class="badge b-gold" style="font-size:10px;padding:1px 5px;">${esc(x.badge)}</span>` : ''}</div></div>
    </div></td>
    <td>${esc(x.cat)}</td>
    <td><b>${money(x.price)}</b></td>
    <td>${x.courses} món<div class="sub">${x.duration} phút</div></td>
    <td>${pBadge(x.status)}</td>
    <td><div style="display:flex;gap:6px;"><button class="btn ghost sm" data-a="editp" data-id="${x.id}">Sửa</button><button class="btn ghost sm" data-a="delp" data-id="${x.id}">Xóa</button></div></td>
  </tr>`).join('') : '<tr><td colspan="6" class="empty">Không tìm thấy sản phẩm nào.</td></tr>';
  $('ppg').innerHTML = pager(p.n, p.p);
  $('pcnt').textContent = l.length + ' sản phẩm';
}
function products_() {
  const activeCount = products.filter(p => p.status === 'Đang bán').length;
  const avgP = products.length ? products.reduce((a, b) => a + b.price, 0) / products.length : 0;
  view.innerHTML = head('Quản lý Sản phẩm & Thực đơn', 'Quản lý danh sách set Omakase, giá bán, số món và tình trạng phục vụ.', '<button class="btn ghost" id="prcsv">Xuất CSV</button> <button class="btn" data-a="newp">+ Thêm sản phẩm</button>') + `
  <div class="kpis">
    <div class="kpi"><div class="l">Tổng sản phẩm</div><div class="v">${products.length}</div><div class="d">${CATEGORIES.length} danh mục ẩm thực</div></div>
    <div class="kpi"><div class="l">Đang phục vụ</div><div class="v">${activeCount}</div><div class="d">${products.length - activeCount} tạm ngừng / ngừng bán</div></div>
    <div class="kpi"><div class="l">Giá trung bình</div><div class="v">${money(avgP)}</div><div class="d">Giá niêm yết các set</div></div>
  </div>
  <div class="panel">
    <div class="tools">
      <input id="pq" placeholder="Tìm kiếm theo mã, tên món, tiếng Nhật..." value="${esc(S.pq)}">
      <select id="pc"><option value="">Tất cả danh mục</option>${CATEGORIES.map(c => `<option value="${c}" ${S.pc === c ? 'selected' : ''}>${c}</option>`).join('')}</select>
      <select id="ps"><option value="">Tất cả trạng thái</option>${['Đang bán', 'Tạm ngừng', 'Ngừng bán'].map(s => `<option value="${s}" ${S.ps === s ? 'selected' : ''}>${s}</option>`).join('')}</select>
      <span class="sub" id="pcnt" style="align-self:center"></span>
    </div>
    <div class="tw"><table><thead><tr><th>Sản phẩm / Set Omakase</th><th>Danh mục</th><th>Đơn giá</th><th>Quy cách</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody id="ptb"></tbody></table></div>
    <div id="ppg"></div>
  </div>`;
  productRows();
  $('pq').oninput = e => { S.pq = e.target.value; S.pp = 1; productRows(); };
  $('pc').onchange = e => { S.pc = e.target.value; S.pp = 1; productRows(); };
  $('ps').onchange = e => { S.ps = e.target.value; S.pp = 1; productRows(); };
  $('ppg').onclick = e => { if (e.target.dataset.p) { S.pp = +e.target.dataset.p; productRows(); } };
  $('prcsv').onclick = () => csv('san-pham.csv', ['Mã', 'Tên sản phẩm', 'Tiếng Nhật', 'Danh mục', 'Giá', 'Số món', 'Thời lượng (phút)', 'Nhãn', 'Trạng thái'], products.map(p => [p.id, p.name, p.jp || '', p.cat, p.price, p.courses, p.duration, p.badge || '', p.status]));
}
function productForm(p) {
  const v = p || { id: '', name: '', jp: '', cat: CATEGORIES[0], price: 2000000, courses: 12, duration: 90, badge: '', status: 'Đang bán', img: 'images/kiwami.jpg', desc: '' };
  modal(`<h2>${p ? 'Sửa sản phẩm: ' + esc(p.name) : 'Thêm sản phẩm mới'}</h2>
  <div class="two"><div><label>Mã sản phẩm *</label><input id="p_id" value="${esc(v.id)}" placeholder="VD: SET-KIWAMI" ${p ? 'readonly style="opacity:0.7"' : ''}></div><div><label>Đơn giá (VNĐ) *</label><input id="p_price" type="number" min="100000" step="50000" value="${v.price}"></div></div>
  <label>Tên sản phẩm / Set *</label><input id="p_name" value="${esc(v.name)}" placeholder="VD: Set Kiwami Grand Omakase">
  <div class="two"><div><label>Tên tiếng Nhật</label><input id="p_jp" value="${esc(v.jp)}" placeholder="VD: 極上 おまかせ"></div><div><label>Danh mục</label><select id="p_cat">${CATEGORIES.map(c => `<option value="${c}" ${c === v.cat ? 'selected' : ''}>${c}</option>`).join('')}</select></div></div>
  <div class="two"><div><label>Số món phục vụ</label><input id="p_courses" type="number" min="1" max="50" value="${v.courses}"></div><div><label>Thời lượng (phút)</label><input id="p_dur" type="number" min="30" max="300" step="15" value="${v.duration}"></div></div>
  <div class="two"><div><label>Nhãn nổi bật (Badge)</label><input id="p_badge" value="${esc(v.badge)}" placeholder="VD: SIGNATURE, BEST SELLER"></div><div><label>Trạng thái</label><select id="p_status">${['Đang bán', 'Tạm ngừng', 'Ngừng bán'].map(s => `<option value="${s}" ${s === v.status ? 'selected' : ''}>${s}</option>`).join('')}</select></div></div>
  <label>Đường dẫn hình ảnh</label><input id="p_img" value="${esc(v.img)}" placeholder="images/kiwami.jpg hoặc link URL">
  <label>Mô tả ngắn</label><textarea id="p_desc">${esc(v.desc)}</textarea>
  <div class="err" id="err"></div>
  <div class="acts"><button class="btn ghost" data-a="close">Hủy</button><button class="btn" data-a="savep" data-id="${p ? p.id : ''}">Lưu sản phẩm</button></div>`);
}
function saveProduct(id) {
  const code = $('p_id').value.trim().toUpperCase(), name = $('p_name').value.trim(), price = +$('p_price').value || 0;
  if (!code) { $('err').textContent = 'Vui lòng nhập mã sản phẩm.'; return; }
  if (!name) { $('err').textContent = 'Vui lòng nhập tên sản phẩm.'; return; }
  if (price <= 0) { $('err').textContent = 'Đơn giá phải lớn hơn 0.'; return; }
  if (!id && products.some(p => p.id === code)) { $('err').textContent = 'Mã sản phẩm này đã tồn tại.'; return; }
  const f = { id: code, name, jp: $('p_jp').value.trim(), cat: $('p_cat').value, price, courses: +$('p_courses').value || 1, duration: +$('p_dur').value || 60, badge: $('p_badge').value.trim(), status: $('p_status').value, img: $('p_img').value.trim() || 'images/kiwami.jpg', desc: $('p_desc').value.trim() };
  if (id) { Object.assign(products.find(x => x.id === id), f); } else { products.unshift(f); }
  postAPI('save_product', f);
  syncSets(); closeM(); products_();
}

/* ============ 3. ORDER ============ */
function orderRows() {
  let l = orders.filter(o => (!S.os || o.status === S.os) && (!S.oq || (o.id + o.cname + o.set).toLowerCase().includes(S.oq.toLowerCase())));
  l = l.sort((a, b) => (b.date + b.time).localeCompare(a.date + a.time));
  const p = pageOf(l, S.op, 10); S.op = p.p;
  $('otb').innerHTML = p.rows.length ? p.rows.map(o => `<tr><td><b>${o.id}</b></td><td>${esc(o.cname)}</td><td>${esc(o.set)}<div class="sub">${o.guests} khách</div></td><td>${dm(o.date)} · ${o.time}</td><td>${money(total(o))}<div class="sub">${o.pay}</div></td><td>${oBadge(o.status)}</td><td><button class="btn ghost sm" data-a="edit" data-id="${o.id}">Chi tiết</button></td></tr>`).join('') : '<tr><td colspan="7" class="empty">Không có đơn nào.</td></tr>';
  $('opg').innerHTML = pager(p.n, p.p); $('ocnt').textContent = l.length + ' đơn';
}
function orders_() {
  view.innerHTML = head('Quản lý Order', 'Theo dõi, xác nhận và cập nhật trạng thái các đơn đặt bàn.', '<button class="btn ghost" id="ocsv">Xuất CSV</button> <button class="btn" data-a="new">+ Tạo đơn thủ công</button>') + `
  <div class="panel"><div class="tools"><input id="oq" placeholder="Mã đơn, tên khách, tên set..." value="${esc(S.oq)}"><select id="os"><option value="">Tất cả trạng thái</option>${OSTAT.map(s => `<option ${S.os === s ? 'selected' : ''}>${s}</option>`).join('')}</select><span class="sub" id="ocnt" style="align-self:center"></span></div>
  <div class="tw"><table><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Set</th><th>Thời gian</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead><tbody id="otb"></tbody></table></div><div id="opg"></div></div>`;
  orderRows();
  $('oq').oninput = e => { S.oq = e.target.value; S.op = 1; orderRows(); };
  $('os').onchange = e => { S.os = e.target.value; S.op = 1; orderRows(); };
  $('opg').onclick = e => { if (e.target.dataset.p) { S.op = +e.target.dataset.p; orderRows(); } };
  $('ocsv').onclick = () => csv('orders.csv', ['Mã', 'Khách', 'Set', 'Số khách', 'Ngày', 'Giờ', 'Tổng tiền', 'Thanh toán', 'Trạng thái'], orders.map(o => [o.id, o.cname, o.set, o.guests, o.date, o.time, total(o), o.pay, o.status]));
}
function orderForm(o) {
  const v = o || { cid: customers[0]?.id || 'KH101', set: SETS[0]?.[0] || 'Set Kiwami Grand Omakase', guests: 2, date: TODAY, time: '19:00', pay: PAYS[0], status: 'Chờ xác nhận', note: '' };
  modal(`<h2>${o ? 'Đơn ' + o.id : 'Tạo đơn thủ công'}</h2>
  <label>Khách hàng</label><select id="f_c" ${o ? 'disabled' : ''}>${customers.map(c => `<option value="${c.id}" ${c.id === v.cid ? 'selected' : ''}>${esc(c.name)} — ${c.phone}</option>`).join('')}</select>
  <label>Set</label><select id="f_s">${SETS.map(s => `<option ${s[0] === v.set ? 'selected' : ''}>${s[0]}</option>`).join('')}</select>
  <div class="two"><div><label>Số khách</label><input id="f_g" type="number" min="1" max="12" value="${v.guests}"></div><div><label>Thanh toán</label><select id="f_p">${PAYS.map(p => `<option ${p === v.pay ? 'selected' : ''}>${p}</option>`).join('')}</select></div></div>
  <div class="two"><div><label>Ngày</label><input id="f_d" type="date" value="${v.date}"></div><div><label>Giờ</label><select id="f_t">${['11:30', '12:30', '18:00', '19:00', '20:00'].map(t => `<option ${t === v.time ? 'selected' : ''}>${t}</option>`).join('')}</select></div></div>
  <label>Trạng thái</label><select id="f_st">${OSTAT.map(s => `<option ${s === v.status ? 'selected' : ''}>${s}</option>`).join('')}</select>
  <label>Ghi chú (dị ứng, yêu cầu riêng)</label><textarea id="f_n">${esc(v.note)}</textarea><div class="err" id="err"></div>
  <div class="acts"><button class="btn ghost" data-a="close">Đóng</button><button class="btn" data-a="saveorder" data-id="${o ? o.id : ''}">Lưu</button></div>`);
}
function saveOrder(id) {
  const g = +$('f_g').value; if (!(g >= 1 && g <= 12)) { $('err').textContent = 'Số khách từ 1 đến 12.'; return; }
  if (!$('f_d').value) { $('err').textContent = 'Vui lòng chọn ngày.'; return; }
  const set = SETS.find(s => s[0] === $('f_s').value) || [SETS[0][0], SETS[0][1]];
  const f = { set: set[0], price: set[1], guests: g, pay: $('f_p').value, date: $('f_d').value, time: $('f_t').value, status: $('f_st').value, note: $('f_n').value.trim() };
  if (id) {
    const o = orders.find(x => x.id === id);
    Object.assign(o, f);
    postAPI('save_order', { id, cname: o.cname, cid: o.cid, ...f });
  } else {
    const c = customers.find(c => c.id === $('f_c').value) || customers[0];
    const newId = 'SB' + String(Date.now()).slice(-6);
    const newOrd = { id: newId, cid: c.id, cname: c.name, ...f };
    orders.unshift(newOrd);
    postAPI('save_order', newOrd);
  }
  closeM(); TABS[tab]();
}

/* ============ 4. KHÁCH HÀNG ============ */
const tBadge = t => `<span class="badge ${t === 'VIP' ? 'b-gold' : t === 'Vàng' ? 'b-warn' : 'b-mute'}">${t}</span>`;
function custRows() {
  const l = customers.filter(c => (!S.ct || c.tier === S.ct) && (!S.cq || (c.name + c.phone + c.email + c.id).toLowerCase().includes(S.cq.toLowerCase())));
  const p = pageOf(l, S.cp, 8); S.cp = p.p;
  $('ctb').innerHTML = p.rows.length ? p.rows.map(c => { const st = custStats(c); return `<tr><td><b>${esc(c.name)}</b><div class="sub">${c.id} · ${esc(c.email)}</div></td><td>${c.phone}</td><td>${tBadge(c.tier)}</td><td>${c.points.toLocaleString('vi-VN')} P</td><td>${st.n} lần<div class="sub">${money(st.spend)}</div></td><td><button class="btn ghost sm" data-a="editc" data-id="${c.id}">Sửa</button> <button class="btn ghost sm" data-a="delc" data-id="${c.id}">Xóa</button></td></tr>`; }).join('') : '<tr><td colspan="6" class="empty">Không có khách hàng nào.</td></tr>';
  $('cpg').innerHTML = pager(p.n, p.p); $('ccnt').textContent = l.length + ' khách';
}
function customers_() {
  view.innerHTML = head('Quản lý Khách hàng', 'Danh sách thượng khách, hạng thành viên và lịch sử chi tiêu.', '<button class="btn ghost" id="ccsv">Xuất CSV</button> <button class="btn" data-a="newc">+ Thêm khách hàng</button>') + `
  <div class="panel"><div class="tools"><input id="cq" placeholder="Tên, SĐT, email, mã khách..." value="${esc(S.cq)}"><select id="ct"><option value="">Tất cả hạng</option>${TIERS.map(t => `<option ${S.ct === t ? 'selected' : ''}>${t}</option>`).join('')}</select><span class="sub" id="ccnt" style="align-self:center"></span></div>
  <div class="tw"><table><thead><tr><th>Khách hàng</th><th>Điện thoại</th><th>Hạng</th><th>Điểm</th><th>Đã chi tiêu</th><th></th></tr></thead><tbody id="ctb"></tbody></table></div><div id="cpg"></div></div>`;
  custRows();
  $('cq').oninput = e => { S.cq = e.target.value; S.cp = 1; custRows(); };
  $('ct').onchange = e => { S.ct = e.target.value; S.cp = 1; custRows(); };
  $('cpg').onclick = e => { if (e.target.dataset.p) { S.cp = +e.target.dataset.p; custRows(); } };
  $('ccsv').onclick = () => csv('customers.csv', ['Mã', 'Tên', 'SĐT', 'Email', 'Hạng', 'Điểm', 'Số đơn', 'Chi tiêu'], customers.map(c => { const s = custStats(c); return [c.id, c.name, c.phone, c.email, c.tier, c.points, s.n, s.spend]; }));
}
function custForm(c) {
  const v = c || { name: '', phone: '', email: '', tier: 'Thành viên', points: 0 };
  modal(`<h2>${c ? 'Sửa khách hàng' : 'Thêm khách hàng'}</h2><label>Họ tên *</label><input id="c_n" value="${esc(v.name)}">
  <div class="two"><div><label>Điện thoại *</label><input id="c_p" value="${esc(v.phone)}"></div><div><label>Email</label><input id="c_e" value="${esc(v.email)}"></div></div>
  <div class="two"><div><label>Hạng</label><select id="c_t">${TIERS.map(t => `<option ${t === v.tier ? 'selected' : ''}>${t}</option>`).join('')}</select></div><div><label>Điểm tích lũy</label><input id="c_pt" type="number" min="0" value="${v.points}"></div></div>
  <div class="err" id="err"></div><div class="acts"><button class="btn ghost" data-a="close">Hủy</button><button class="btn" data-a="savec" data-id="${c ? c.id : ''}">Lưu</button></div>`);
}
function saveCust(id) {
  const f = { name: $('c_n').value.trim(), phone: $('c_p').value.trim(), email: $('c_e').value.trim(), tier: $('c_t').value, points: +$('c_pt').value || 0 };
  if (!f.name) return $('err').textContent = 'Vui lòng nhập họ tên.', 0;
  if (!/^0\d{9}$/.test(f.phone)) return $('err').textContent = 'Số điện thoại gồm 10 số, bắt đầu bằng 0.', 0;
  if (f.email && !/^\S+@\S+\.\S+$/.test(f.email)) return $('err').textContent = 'Email chưa đúng định dạng.', 0;
  if (customers.some(c => c.phone === f.phone && c.id !== id)) return $('err').textContent = 'Số điện thoại này đã có khách.', 0;
  if (id) {
    Object.assign(customers.find(c => c.id === id), f);
    orders.forEach(o => { if (o.cid === id) o.cname = f.name; });
    postAPI('save_customer', { id, ...f });
  } else {
    const newId = 'KH' + String(Date.now()).slice(-3);
    const newC = { id: newId, joined: TODAY, ...f };
    customers.unshift(newC);
    postAPI('save_customer', newC);
  }
  closeM(); customers_();
}

/* ============ 5. DOANH THU ============ */
function revenue() {
  const n = S.rgDays, from = iso(addDays(today, -n + 1)), ds = done().filter(o => o.date >= from && o.date <= TODAY);
  const rev = ds.reduce((a, o) => a + total(o), 0), days = revenueByDay(n), max = Math.max(...days.map(x => x.v), 1), guests = ds.reduce((a, o) => a + o.guests, 0);
  const grp = (key) => { const m = {}; ds.forEach(o => { const k = key(o); m[k] = m[k] || { n: 0, g: 0, v: 0 }; m[k].n++; m[k].g += o.guests; m[k].v += total(o); }); return Object.entries(m).sort((a, b) => b[1].v - a[1].v); };
  const bySet = grp(o => o.set), byPay = grp(o => o.pay);
  const row = (e) => `<tr><td>${esc(e[0])}</td><td>${e[1].n}</td><td>${e[1].g}</td><td>${money(e[1].v)}</td><td>${rev ? (e[1].v / rev * 100).toFixed(1) : 0}%</td></tr>`;
  view.innerHTML = head('Quản lý Doanh thu', 'Chỉ tính các đơn đã hoàn tất trong hệ thống.', `<select id="rg"><option value="7" ${n === 7 ? 'selected' : ''}>7 ngày qua</option><option value="14" ${n === 14 ? 'selected' : ''}>14 ngày qua</option></select> <button class="btn ghost" id="rcsv">Xuất CSV</button>`) + `
  <div class="kpis"><div class="kpi"><div class="l">Tổng doanh thu</div><div class="v">${money(rev)}</div></div><div class="kpi"><div class="l">Đơn hoàn tất</div><div class="v">${ds.length}</div></div>
  <div class="kpi"><div class="l">Trung bình / đơn</div><div class="v">${money(ds.length ? rev / ds.length : 0)}</div></div><div class="kpi"><div class="l">Trung bình / khách</div><div class="v">${money(guests ? rev / guests : 0)}</div><div class="d">${guests} lượt khách</div></div></div>
  <div class="panel" style="margin-bottom:16px"><h2>Doanh thu theo ngày</h2><div class="bars">${days.map(x => `<div class="bar" title="${money(x.v)}"><u>${x.v ? short(x.v) : ''}</u><i style="height:${x.v / max * 80}%"></i><em>${dm(x.d)}</em></div>`).join('')}</div></div>
  <div class="grid2"><div class="panel"><h2>Theo set</h2><div class="tw"><table><thead><tr><th>Set</th><th>Đơn</th><th>Khách</th><th>Doanh thu</th><th>Tỷ trọng</th></tr></thead><tbody>${bySet.map(row).join('') || '<tr><td colspan="5" class="empty">Chưa có dữ liệu</td></tr>'}</tbody></table></div></div>
  <div class="panel"><h2>Theo phương thức thanh toán</h2><div class="tw"><table><thead><tr><th>Phương thức</th><th>Đơn</th><th>Khách</th><th>Doanh thu</th><th>Tỷ trọng</th></tr></thead><tbody>${byPay.map(row).join('') || '<tr><td colspan="5" class="empty">Chưa có dữ liệu</td></tr>'}</tbody></table></div></div></div>`;
  $('rg').onchange = e => { S.rgDays = +e.target.value; revenue(); };
  $('rcsv').onclick = () => csv('doanh-thu.csv', ['Ngày', 'Doanh thu'], days.map(x => [x.d, x.v]));
}

/* ============ 6. ĐÁNH GIÁ & PHẢN HỒI ============ */
function reviews_() {
  const l = reviews.filter(r => (!S.rt || r.type === S.rt) && (!S.rs || String(r.stars) === S.rs) && (!S.rst || (S.rst === 'no' ? !r.reply : !!r.reply))).sort((a, b) => b.date.localeCompare(a.date));
  view.innerHTML = head('Đánh giá & Phản hồi', 'Trả lời đánh giá của khách và xử lý khiếu nại.') + `
  <div class="kpis"><div class="kpi"><div class="l">Điểm trung bình</div><div class="v">${avgRating().toFixed(1)} ★</div></div><div class="kpi"><div class="l">Chưa phản hồi</div><div class="v">${reviews.filter(r => !r.reply).length}</div></div><div class="kpi"><div class="l">Khiếu nại chưa xử lý</div><div class="v">${reviews.filter(r => r.type === 'complaint' && !r.resolved).length}</div></div></div>
  <div class="tools"><select id="rt"><option value="">Tất cả loại</option><option value="review" ${S.rt === 'review' ? 'selected' : ''}>Đánh giá</option><option value="complaint" ${S.rt === 'complaint' ? 'selected' : ''}>Khiếu nại</option></select>
  <select id="rs"><option value="">Mọi số sao</option>${[5, 4, 3, 2, 1].map(s => `<option value="${s}" ${S.rs === String(s) ? 'selected' : ''}>${s} sao</option>`).join('')}</select>
  <select id="rst"><option value="">Mọi trạng thái</option><option value="no" ${S.rst === 'no' ? 'selected' : ''}>Chưa phản hồi</option><option value="yes" ${S.rst === 'yes' ? 'selected' : ''}>Đã phản hồi</option></select></div>
  ${l.map(r => `<div class="rv"><div class="top"><div><b>${esc(r.cname)}</b> <span class="sub">· ${esc(r.set)} · ${dm(r.date)}</span></div><div><span class="stars">${'★'.repeat(r.stars)}${'☆'.repeat(5 - r.stars)}</span> ${r.type === 'complaint' ? `<span class="badge ${r.resolved ? 'b-ok' : 'b-bad'}">Khiếu nại · ${r.resolved ? 'Đã xử lý' : 'Chưa xử lý'}</span>` : ''}</div></div>
  <p style="margin-top:8px">${esc(r.text)}</p>${r.reply ? `<div class="reply"><div class="sub">Phản hồi của nhà hàng</div>${esc(r.reply)}</div>` : ''}
  <textarea id="rp${r.id}" placeholder="Viết phản hồi...">${esc(r.reply)}</textarea>
  <div class="acts" style="justify-content:flex-start"><button class="btn sm" data-a="reply" data-id="${r.id}">${r.reply ? 'Cập nhật phản hồi' : 'Gửi phản hồi'}</button>${r.type === 'complaint' ? `<button class="btn ghost sm" data-a="resolve" data-id="${r.id}">${r.resolved ? 'Mở lại' : 'Đánh dấu đã xử lý'}</button>` : ''}</div></div>`).join('') || '<div class="panel empty">Không có đánh giá nào.</div>'}`;
  [['rt', 'rt'], ['rs', 'rs'], ['rst', 'rst']].forEach(([id, k]) => $(id).onchange = e => { S[k] = e.target.value; reviews_(); });
}

/* ============ 7. LỊCH LÀM VIỆC ============ */
function staff() {
  const mon = addDays(today, -((today.getDay() + 6) % 7) + S.wk * 7), dates = Array.from({ length: 7 }, (_, i) => iso(addDays(mon, i)));
  const DOW = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'], list = STAFF.filter(s => !S.wr || s.role === S.wr);
  const roles = ['Phục vụ', 'Đầu bếp', 'Tạp vụ', 'Quản lý'], cnt = r => STAFF.filter(s => s.role === r).length;
  const cov = (r, d, sh) => STAFF.filter(s => s.role === r && (x => sh === 'L' ? x === 'L' || x === 'F' : x === 'D' || x === 'F')(shiftOf(s, d))).length;
  view.innerHTML = head('Lịch làm việc nhân viên', `${cnt('Phục vụ')} phục vụ · ${cnt('Đầu bếp')} đầu bếp · ${cnt('Tạp vụ')} tạp vụ. Bấm vào ô ca để đổi ca.`, `<button class="btn ghost" data-a="wk" data-v="-1">← Tuần trước</button> <button class="btn ghost" data-a="wk" data-v="0">Tuần này</button> <button class="btn ghost" data-a="wk" data-v="1">Tuần sau →</button>`) + `
  <div class="tools"><select id="wr"><option value="">Tất cả bộ phận</option>${roles.map(r => `<option ${S.wr === r ? 'selected' : ''}>${r}</option>`).join('')}</select>
  <span class="sub" style="align-self:center">Tuần ${dm(dates[0])} – ${dm(dates[6])} · <span class="cell" data-s="L">Trưa 11–15h</span> <span class="cell" data-s="D">Tối 17–23h</span> <span class="cell" data-s="F">Cả ngày</span> <span class="cell" data-s="O">Nghỉ</span></span></div>
  <div class="panel"><div class="tw"><table class="sch"><thead><tr><th>Nhân viên</th>${dates.map((d, i) => `<th>${DOW[i]}<br>${dm(d)}</th>`).join('')}</tr></thead><tbody>
  ${list.map(s => `<tr><td><b>${esc(s.name)}</b><div class="sub">${s.title}</div></td>${dates.map(d => { const c = shiftOf(s, d); return `<td><span class="cell" data-s="${c}" data-a="shift" data-id="${s.id}" data-d="${d}" title="${SHIFT[c][1]}">${SHIFT[c][0]}</span></td>`; }).join('')}</tr>`).join('')}
  </tbody></table></div></div>
  <div class="panel" style="margin-top:16px"><h2>Kiểm tra đủ người theo ca</h2><p class="sub" style="margin-bottom:10px">Tối thiểu: phục vụ ${NEED['Phục vụ'].join('/')} · đầu bếp ${NEED['Đầu bếp'].join('/')} · tạp vụ ${NEED['Tạp vụ'].join('/')} (trưa/tối). Số đỏ là thiếu người.</p>
  <div class="tw"><table class="sch"><thead><tr><th>Bộ phận · Ca</th>${dates.map((d, i) => `<th>${DOW[i]}</th>`).join('')}</tr></thead><tbody>
  ${['Phục vụ', 'Đầu bếp', 'Tạp vụ'].map(r => ['L', 'D'].map((sh, k) => `<tr><td>${r} · ${sh === 'L' ? 'Trưa' : 'Tối'}</td>${dates.map(d => { const n = cov(r, d, sh); return `<td class="cov ${n < NEED[r][k] ? 'bad' : 'good'}">${n}</td>`; }).join('')}</tr>`).join('')).join('')}
  </tbody></table></div></div>`;
  $('wr').onchange = e => { S.wr = e.target.value; staff(); };
}

const TABS = { overview, products: products_, orders: orders_, customers: customers_, revenue, reviews: reviews_, staff };

/* ============ SỰ KIỆN CHUNG ============ */
document.addEventListener('click', e => {
  const b = e.target.closest('[data-a]'); if (!b) return;
  const a = b.dataset.a, id = b.dataset.id;
  if (a === 'close') closeM();
  else if (a === 'newp') productForm();
  else if (a === 'editp') productForm(products.find(p => p.id === id));
  else if (a === 'savep') saveProduct(id);
  else if (a === 'delp') {
    const p = products.find(x => x.id === id);
    if (p && confirm(`Bạn có chắc muốn xóa sản phẩm "${p.name}" (${p.id})?`)) {
      products = products.filter(x => x !== p);
      postAPI('delete_product', { id });
      syncSets();
      products_();
    }
  }
  else if (a === 'new') orderForm();
  else if (a === 'edit') orderForm(orders.find(o => o.id === id));
  else if (a === 'saveorder') saveOrder(id);
  else if (a === 'newc') custForm();
  else if (a === 'editc') custForm(customers.find(c => c.id === id));
  else if (a === 'savec') saveCust(id);
  else if (a === 'delc') {
    const c = customers.find(x => x.id === id);
    if (confirm(`Xóa khách hàng "${c.name}"? Các đơn cũ vẫn được giữ lại.`)) {
      customers = customers.filter(x => x !== c);
      postAPI('delete_customer', { id });
      customers_();
    }
  }
  else if (a === 'reply') {
    const r = reviews.find(x => x.id == id), t = $('rp' + id).value.trim();
    if (!t) return alert('Vui lòng nhập nội dung phản hồi.');
    r.reply = t;
    postAPI('reply_review', { id, reply: t });
    reviews_();
  }
  else if (a === 'resolve') {
    const r = reviews.find(x => x.id == id);
    r.resolved = !r.resolved;
    postAPI('resolve_review', { id, resolved: r.resolved });
    reviews_();
  }
  else if (a === 'wk') { S.wk = b.dataset.v === '0' ? 0 : S.wk + +b.dataset.v; staff(); }
  else if (a === 'shift') { const s = STAFF.find(x => x.id === id), cur = shiftOf(s, b.dataset.d), order = ['O', 'L', 'D', 'F']; overrides[id + '|' + b.dataset.d] = order[(order.indexOf(cur) + 1) % 4]; staff(); }
});
go(tab);
</script>
</body>
</html>
