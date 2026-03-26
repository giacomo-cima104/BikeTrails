<?php
include 'db.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM percorsi WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header('Location: storico.php?deleted=1');
    exit;
}

$query = "SELECT id, data_creazione, durata, distanza FROM percorsi ORDER BY data_creazione DESC";
$result = $conn->query($query);

$stats = $conn->query("SELECT COUNT(*) as total_corse, SUM(distanza) as total_km, AVG(distanza) as avg_km, MAX(distanza) as max_km FROM percorsi")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico — BikeTrails</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #080a0f;
            --surface: #0f1218;
            --surface2: #161b24;
            --border: #1f2535;
            --accent: #f7b731;
            --accent2: #ff4d2e;
            --green: #00e676;
            --blue: #448aff;
            --text: #eef0f8;
            --muted: #5a6380;
            --muted2: #3a4055;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--accent2), var(--accent), var(--blue));
            z-index: 100;
        }

        body::after {
            content: '';
            position: fixed;
            top: -100px; right: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(247,183,49,0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 20px 80px;
            position: relative;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 36px;
            padding-top: 16px;
            animation: fadeDown 0.5s ease both;
        }

        .logo { display: flex; align-items: center; gap: 10px; }

        .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(145deg, var(--accent2), var(--accent));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 16px rgba(247,183,49,0.2);
        }

        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 800;
            letter-spacing: 0.5px; text-transform: uppercase;
        }

        .logo h1 span { color: var(--accent); }

        .header-sub {
            font-size: 12px; color: var(--muted);
            margin-top: 4px; margin-left: 48px;
            letter-spacing: 0.5px;
        }

        .btn-back {
            font-family: 'Syne', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 9px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
            margin-top: 4px;
        }

        .btn-back:hover { color: var(--accent); border-color: var(--accent); background: rgba(247,183,49,0.06); }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 32px;
            animation: fadeUp 0.5s 0.1s ease both;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 16px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.2s;
        }

        .stat-card:hover { border-color: var(--muted2); transform: translateY(-2px); }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent2), var(--accent));
            opacity: 0.7;
        }

        .stat-label {
            font-family: 'Syne', sans-serif;
            font-size: 9px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 34px; font-weight: 800;
            line-height: 1; color: var(--accent);
        }

        .stat-unit { font-size: 14px; color: var(--muted); font-weight: 400; }

        /* Section title */
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
            animation: fadeUp 0.5s 0.15s ease both;
        }

        /* Table */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            animation: fadeUp 0.5s 0.2s ease both;
        }

        table { width: 100%; border-collapse: collapse; }

        thead {
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
        }

        thead th {
            padding: 14px 18px;
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); text-align: left;
        }

        tbody tr {
            border-bottom: 1px solid rgba(31,37,53,0.8);
            transition: background 0.15s;
            animation: rowSlide 0.4s ease both;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(247,183,49,0.03); }

        @keyframes rowSlide {
            from { opacity: 0; transform: translateX(-8px); }
            to { opacity: 1; transform: translateX(0); }
        }

        tbody td { padding: 16px 18px; }

        .td-id {
            font-family: 'Syne', sans-serif;
            font-size: 11px; color: var(--muted2);
            font-weight: 700;
        }

        .td-date .date { font-weight: 500; font-size: 15px; }
        .td-date .time { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .td-dist {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 800;
            color: var(--accent);
        }

        .td-dist span { font-size: 13px; color: var(--muted); font-weight: 400; }

        .td-dur { color: var(--muted); font-size: 14px; }

        /* Distance bar */
        .dist-bar-wrap { margin-top: 5px; height: 3px; background: var(--border); border-radius: 2px; width: 80px; }
        .dist-bar { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--accent2), var(--accent)); transition: width 0.6s ease; }

        .actions { display: flex; gap: 8px; align-items: center; }

        .btn-detail {
            font-family: 'Syne', sans-serif;
            font-size: 11px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--accent); text-decoration: none;
            padding: 7px 14px;
            border: 1px solid rgba(247,183,49,0.3);
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-detail:hover { background: var(--accent); color: var(--bg); border-color: var(--accent); }

        .btn-delete {
            font-family: 'Syne', sans-serif;
            font-size: 11px; font-weight: 700;
            background: none;
            color: var(--muted2);
            border: 1px solid var(--border);
            padding: 7px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover { color: #ff4d4d; border-color: rgba(255,77,77,0.4); background: rgba(255,77,77,0.07); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 70px 20px;
            color: var(--muted);
        }

        .empty-state .icon { font-size: 48px; margin-bottom: 16px; opacity: 0.7; }

        .empty-state h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 800;
            margin-bottom: 8px;
            color: var(--text);
        }

        .empty-state p { font-size: 14px; line-height: 1.5; }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--bg);
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            padding: 12px 22px;
            border-radius: 10px;
            text-decoration: none;
            transition: filter 0.2s;
        }

        .empty-state a:hover { filter: brightness(1.1); }

        /* Toast */
        .toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(120px);
            background: rgba(0,230,118,0.15);
            border: 1px solid rgba(0,230,118,0.3);
            color: var(--green);
            padding: 13px 22px;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            transition: transform 0.5s cubic-bezier(0.34,1.56,0.64,1);
            z-index: 999;
        }

        .toast.show { transform: translateX(-50%) translateY(0); }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
            thead th:nth-child(1), tbody td:nth-child(1) { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <div class="logo">
                    <div class="logo-icon">🚲</div>
                    <h1>Bike<span>Trails</span></h1>
                </div>
                <p class="header-sub">I tuoi progressi su due ruote</p>
            </div>
            <a href="index.php" class="btn-back">← Tracker</a>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Distanza Totale</div>
                <div class="stat-value"><?php echo number_format($stats['total_km'] ?? 0, 1); ?><span class="stat-unit"> km</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sessioni</div>
                <div class="stat-value"><?php echo $stats['total_corse'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Media Sessione</div>
                <div class="stat-value"><?php echo number_format($stats['avg_km'] ?? 0, 1); ?><span class="stat-unit"> km</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Percorso Max</div>
                <div class="stat-value"><?php echo number_format($stats['max_km'] ?? 0, 1); ?><span class="stat-unit"> km</span></div>
            </div>
        </div>

        <div class="section-title">Tutti i percorsi</div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Distanza</th>
                        <th>Durata</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $max_km = $stats['max_km'] ?: 1;
                        $i = 0;
                        while ($row = $result->fetch_assoc()):
                            $pct = min(100, ($row['distanza'] / $max_km) * 100);
                            $delay = $i * 50;
                            $i++;
                    ?>
                    <tr style="animation-delay: <?php echo $delay; ?>ms">
                        <td class="td-id">#<?php echo $row['id']; ?></td>
                        <td class="td-date">
                            <div class="date"><?php echo date('d/m/Y', strtotime($row['data_creazione'])); ?></div>
                            <div class="time"><?php echo date('H:i', strtotime($row['data_creazione'])); ?></div>
                        </td>
                        <td class="td-dist">
                            <?php echo number_format($row['distanza'], 2); ?><span> km</span>
                            <div class="dist-bar-wrap">
                                <div class="dist-bar" style="width: <?php echo round($pct); ?>%"></div>
                            </div>
                        </td>
                        <td class="td-dur"><?php echo $row['durata'] ?: '—'; ?></td>
                        <td>
                            <div class="actions">
                                <a href="dettaglio.php?id=<?php echo $row['id']; ?>" class="btn-detail">Vedi →</a>
                                <form method="POST" onsubmit="return confirm('Eliminare questo percorso?')">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-delete" title="Elimina">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5">
                        <div class="empty-state">
                            <div class="icon">🚴</div>
                            <h3>Nessun percorso ancora</h3>
                            <p>Inizia a pedalare per registrare il tuo primo tracciato.</p>
                            <a href="index.php">⚡ Inizia ora</a>
                        </div>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="toast <?php echo isset($_GET['deleted']) ? 'show' : ''; ?>" id="toast">🗑 Percorso eliminato</div>

    <?php if (isset($_GET['deleted'])): ?>
    <script>setTimeout(() => document.getElementById('toast').classList.remove('show'), 3000);</script>
    <?php endif; ?>
</body>
</html>
