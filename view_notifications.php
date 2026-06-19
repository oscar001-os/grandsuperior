<?php
session_start();
include("connection.php");

if (!isset($_SESSION['client_id'])) {
    header("Location: client_login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

/* Fetch client name for navbar */
$stmt = $conn->prepare("SELECT name FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Fetch notifications */
$result = $conn->query(
    "SELECT id, title, message, created_at
     FROM notifications
     ORDER BY id DESC"
);

$total = $result ? $result->num_rows : 0;

function initials($name) {
    $name = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/', $name);
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    return $i;
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year'  . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day'   . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour'  . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min'   . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications · Grand Superior</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --ink:        #0d1b2a;
    --lime:       #a6ce39;
    --lime-dark:  #8bbf2f;
    --lime-light: #f0f7dc;
    --paper:      #f4f6f9;
    --surface:    #ffffff;
    --line:       #e3e7ee;
    --muted:      #65748a;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
}

/* ===== NAVBAR ===== */
.navbar {
    background: var(--ink);
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,0.18);
}

.navbar-logo {
    display: flex;
    align-items: center;
    gap: 11px;
    text-decoration: none;
}

.navbar-logo img {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid var(--lime);
    padding: 2px;
    background: #fff;
}

.navbar-logo span {
    font-family: 'Sora', sans-serif;
    font-weight: 600;
    font-size: 16px;
    color: #fff;
}

.navbar-right {
    display: flex;
    align-items: center;
    gap: 9px;
}

.nav-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--lime);
    color: var(--ink);
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nav-name {
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.85);
}

/* ===== PAGE ===== */
.page {
    max-width: 760px;
    margin: 0 auto;
    padding: 36px 20px 72px;
}

/* ===== BACK LINK ===== */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    color: var(--muted);
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 26px;
    transition: color 0.15s;
}
.back-link:hover { color: var(--ink); }
.back-link svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-header-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: var(--ink);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.page-header-icon svg {
    width: 24px;
    height: 24px;
    fill: none;
    stroke: var(--lime);
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.page-header h1 {
    font-family: 'Sora', sans-serif;
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 3px;
}

.page-header p {
    font-size: 13px;
    color: var(--muted);
}

.count-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--ink);
    color: var(--lime);
    font-family: 'Sora', sans-serif;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 16px;
    border-radius: 999px;
    flex-shrink: 0;
}

.count-badge svg {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* ===== NOTIFICATION CARD ===== */
.notif-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 14px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: box-shadow 0.15s, transform 0.15s;
    border-left: 4px solid var(--lime);
}

.notif-card:hover {
    box-shadow: 0 8px 24px rgba(13,27,42,0.1);
    transform: translateY(-2px);
}

.notif-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--lime-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.notif-icon svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: var(--lime-dark);
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.notif-body { flex: 1; min-width: 0; }

.notif-title {
    font-family: 'Sora', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 6px;
    line-height: 1.3;
}

.notif-message {
    font-size: 13.5px;
    color: var(--muted);
    line-height: 1.7;
    margin-bottom: 12px;
    word-break: break-word;
}

.notif-footer {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #9aa5b4;
}

.notif-footer svg {
    width: 13px;
    height: 13px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex-shrink: 0;
}

.notif-number {
    margin-left: auto;
    font-size: 11px;
    font-weight: 600;
    color: var(--lime-dark);
    background: var(--lime-light);
    border-radius: 6px;
    padding: 3px 8px;
    white-space: nowrap;
}

/* ===== EMPTY STATE ===== */
.empty {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 60px 24px;
    text-align: center;
}

.empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: var(--paper);
    border: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.empty-icon svg {
    width: 28px;
    height: 28px;
    fill: none;
    stroke: #c8d3e0;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.empty h3 {
    font-family: 'Sora', sans-serif;
    font-size: 17px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 8px;
}

.empty p {
    font-size: 13.5px;
    color: var(--muted);
    line-height: 1.6;
}

/* ===== DIVIDER LABEL ===== */
.section-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--line);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 600px) {
    .navbar    { padding: 0 16px; }
    .nav-name  { display: none; }
    .page      { padding: 24px 14px 52px; }
    .notif-card { flex-direction: column; gap: 12px; }
    .notif-icon { width: 34px; height: 34px; }
    .page-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="client_dashboard.php" class="navbar-logo">
        <img src="logo.jpg" alt="Grand Superior">
        <span>Grand Superior</span>
    </a>
    <div class="navbar-right">
        <div class="nav-avatar"><?php echo htmlspecialchars(initials($client['name'])); ?></div>
        <span class="nav-name"><?php echo htmlspecialchars($client['name']); ?></span>
    </div>
</nav>

<!-- PAGE -->
<div class="page">

    <a href="client_dashboard.php" class="back-link">
        <svg viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Back to dashboard
    </a>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <div class="page-header-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div>
                <h1>Notifications</h1>
                <p>Service updates, promotions &amp; announcements</p>
            </div>
        </div>
        <div class="count-badge">
            <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php echo $total; ?> notification<?php echo $total !== 1 ? 's' : ''; ?>
        </div>
    </div>

    <?php if ($total > 0): ?>

        <div class="section-label">Latest first</div>

        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>

        <div class="notif-card">
            <div class="notif-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="notif-body">
                <div class="notif-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="notif-message"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                <div class="notif-footer">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo timeAgo($row['created_at']); ?>
                    &nbsp;·&nbsp;
                    <?php echo date('M j, Y · g:i A', strtotime($row['created_at'])); ?>
                    <span class="notif-number">#<?php echo $i++; ?></span>
                </div>
            </div>
        </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <h3>No Notifications Yet</h3>
            <p>You're all caught up. Check back here for booking<br>updates, promotions and service announcements.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>