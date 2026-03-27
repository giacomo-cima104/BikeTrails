<?php
date_default_timezone_set('Europe/Rome');
include 'db.php';

// Statistiche globali
$stats = $conn->query("
    SELECT 
        COUNT(*) AS totale_uscite,
        COALESCE(SUM(distanza), 0) AS km_totali
    FROM percorsi
")->fetch_assoc();

// Velocità media globale (solo percorsi con distanza e durata valide)
$vel_media_globale = null;
$res = $conn->query("SELECT distanza, durata FROM percorsi WHERE distanza > 0 AND durata IS NOT NULL AND durata != ''");
$tot_km = 0; $tot_min = 0;
while ($r = $res->fetch_assoc()) {
    $parts = explode(':', $r['durata']);
    if (count($parts) == 3) $min = $parts[0] * 60 + $parts[1] + $parts[2] / 60;
    elseif (count($parts) == 2) $min = $parts[0] + $parts[1] / 60;
    else continue;
    if ($min > 0) { $tot_km += $r['distanza']; $tot_min += $min; }
}
if ($tot_min > 0) $vel_media_globale = $tot_km / ($tot_min / 60);

// Ultimo percorso salvato
$ultimo = $conn->query("SELECT * FROM percorsi ORDER BY data_creazione DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BikeTrails — Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(247,183,49,0.04) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent2) 0%, var(--accent) 50%, var(--blue) 100%);
            z-index: 200;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px 16px 60px;
            position: relative;
            z-index: 1;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
            box-shadow: 0 4px 16px rgba(247,183,49,0.25);
        }

        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .logo h1 span { color: var(--accent); }

        .nav-link {
            font-family: 'Syne', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-link:hover { color: var(--accent); border-color: var(--accent); background: rgba(247,183,49,0.06); }

        /* Riepilogo PHP */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
            animation: fadeDown 0.5s 0.05s ease both;
        }

        .summary-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 12px;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--blue), var(--accent));
        }

        .summary-label {
            font-family: 'Syne', sans-serif;
            font-size: 9px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 6px;
        }

        .summary-value {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 800;
            color: var(--blue);
            line-height: 1;
        }

        .summary-unit { font-size: 11px; color: var(--muted); font-weight: 400; }

        /* Ultimo percorso */
        .last-route {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: var(--text);
            transition: border-color 0.2s, background 0.2s;
            animation: fadeDown 0.5s 0.1s ease both;
        }

        .last-route:hover { border-color: var(--accent); background: rgba(247,183,49,0.04); }

        .last-route-label {
            font-family: 'Syne', sans-serif;
            font-size: 9px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 4px;
        }

        .last-route-info {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700;
        }

        .last-route-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }

        .last-route-arrow { font-size: 20px; color: var(--accent); }

        /* Status */
        .status-bar {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted);
            animation: fadeDown 0.5s 0.15s ease both;
        }

        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--muted2);
            transition: all 0.4s;
            flex-shrink: 0;
        }

        .status-dot.active {
            background: var(--green);
            box-shadow: 0 0 10px var(--green);
            animation: pulse 1.4s infinite;
        }

        .status-dot.saving {
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        #map {
            height: 300px; width: 100%;
            border-radius: 16px;
            margin-bottom: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            animation: fadeUp 0.5s 0.2s ease both;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            animation: fadeUp 0.5s 0.25s ease both;
        }

        .stats-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
            animation: fadeUp 0.5s 0.3s ease both;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 14px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .stat-card.active-card {
            border-color: rgba(247,183,49,0.3);
            box-shadow: 0 0 20px rgba(247,183,49,0.08);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(247,183,49,0.04) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card.active-card::before { opacity: 1; }

        .stat-label {
            font-family: 'Syne', sans-serif;
            font-size: 9px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 5px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 34px; font-weight: 800;
            line-height: 1; color: var(--text);
            transition: color 0.3s;
        }

        .stat-card.active-card .stat-value { color: var(--accent); }

        .stat-unit { font-size: 14px; font-weight: 400; color: var(--muted); }
        .stat-value.sm { font-size: 22px; }
        #speedCard .stat-value { font-size: 28px; }

        .btn-area { animation: fadeUp 0.5s 0.35s ease both; }

        .btn-start {
            width: 100%;
            background: linear-gradient(135deg, var(--accent2) 0%, var(--accent) 100%);
            color: #fff; border: none;
            padding: 18px;
            border-radius: 14px;
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 6px 24px rgba(247,183,49,0.3);
        }

        .btn-start:hover {
            filter: brightness(1.1);
            box-shadow: 0 8px 30px rgba(247,183,49,0.45);
            transform: translateY(-1px);
        }

        .btn-start:active { transform: scale(0.98) translateY(0); }

        .btn-stop {
            width: 100%;
            background: transparent;
            color: #ff4d4d;
            border: 2px solid rgba(255,77,77,0.4);
            padding: 16px; border-radius: 14px;
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800;
            letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            display: none;
        }

        .btn-stop:hover {
            background: rgba(255,77,77,0.1);
            border-color: #ff4d4d;
            box-shadow: 0 0 20px rgba(255,77,77,0.2);
        }

        .gps-info {
            font-size: 11px; color: var(--muted);
            text-align: center;
            margin-top: 10px;
            font-family: 'Syne', sans-serif;
            letter-spacing: 1px;
            min-height: 16px;
        }

        .toast {
            position: fixed;
            bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(120px);
            padding: 14px 22px;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            letter-spacing: 0.5px;
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 999;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .toast.success { background: rgba(0,230,118,0.15); border: 1px solid rgba(0,230,118,0.3); color: var(--green); }
        .toast.error { background: rgba(255,77,77,0.15); border: 1px solid rgba(255,77,77,0.3); color: #ff6b6b; }
        .toast.show { transform: translateX(-50%) translateY(0); }

        .overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            z-index: 300;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .overlay.show { display: flex; }

        .dialog {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px 24px;
            max-width: 320px; width: 100%;
            text-align: center;
            animation: scaleIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }

        @keyframes scaleIn {
            from { transform: scale(0.85); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .dialog-icon { font-size: 36px; margin-bottom: 12px; }

        .dialog h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 800;
            margin-bottom: 8px;
        }

        .dialog p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .dialog-btns { display: flex; gap: 10px; }

        .dialog-btns button {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-cancel { background: var(--surface); border: 1px solid var(--border) !important; color: var(--muted); }
        .btn-cancel:hover { color: var(--text); border-color: var(--muted2) !important; }
        .btn-confirm { background: linear-gradient(135deg, var(--accent2), var(--accent)); color: #fff; }
        .btn-confirm:hover { filter: brightness(1.1); }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">🚲</div>
                <h1>Bike<span>Trails</span></h1>
            </div>
            <a href="storico.php" class="nav-link">Storico →</a>
        </header>

        <!-- Statistiche globali da DB -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Uscite</div>
                <div class="summary-value">
                    <?php echo (int)$stats['totale_uscite']; ?>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Km totali</div>
                <div class="summary-value">
                    <?php echo number_format($stats['km_totali'], 1); ?>
                    <span class="summary-unit">km</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Vel. media</div>
                <div class="summary-value">
                    <?php echo $vel_media_globale !== null ? number_format($vel_media_globale, 1) : '—'; ?>
                    <?php if ($vel_media_globale !== null): ?>
                        <span class="summary-unit">km/h</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ultimo percorso -->
        <?php if ($ultimo): ?>
        <a href="dettaglio.php?id=<?php echo $ultimo['id']; ?>" class="last-route">
            <div>
                <div class="last-route-label">Ultima uscita</div>
                <div class="last-route-info">
                    <?php echo date('d M Y', strtotime($ultimo['data_creazione'])); ?>
                    · <?php echo number_format($ultimo['distanza'], 2); ?> km
                </div>
                <div class="last-route-sub">
                    Durata: <?php echo $ultimo['durata'] ?: '—'; ?>
                    · ore <?php echo date('H:i', strtotime($ultimo['data_creazione'])); ?>
                </div>
            </div>
            <div class="last-route-arrow">→</div>
        </a>
        <?php endif; ?>

        <!-- Stato tracking -->
        <div class="status-bar">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">In attesa</span>
        </div>

        <div id="map"></div>

        <div class="stats-grid">
            <div class="stat-card" id="cardDist">
                <div class="stat-label">Distanza</div>
                <div class="stat-value">
                    <span id="distanzaLabel">0.00</span><span class="stat-unit"> km</span>
                </div>
            </div>
            <div class="stat-card" id="cardTime">
                <div class="stat-label">Tempo</div>
                <div class="stat-value sm" id="timerLabel">00:00</div>
            </div>
        </div>

        <div class="stats-grid-3">
            <div class="stat-card" id="speedCard">
                <div class="stat-label">Velocità</div>
                <div class="stat-value" id="speedLabel">—<span class="stat-unit" style="font-size:11px"> km/h</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vel. Max</div>
                <div class="stat-value" id="maxSpeedLabel">—<span class="stat-unit" style="font-size:11px"> km/h</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Punti GPS</div>
                <div class="stat-value" id="pointsLabel">0</div>
            </div>
        </div>

        <div class="btn-area">
            <button id="startBtn" class="btn-start">⚡ Inizia Percorso</button>
            <button id="stopBtn" class="btn-stop">⏹ Termina e Salva</button>
            <div class="gps-info" id="gpsInfo"></div>
        </div>
    </div>

    <!-- Confirm dialog -->
    <div class="overlay" id="confirmOverlay">
        <div class="dialog">
            <div class="dialog-icon">🏁</div>
            <h3>Termina il percorso?</h3>
            <p id="confirmStats">Il percorso verrà salvato nel tuo storico.</p>
            <div class="dialog-btns">
                <button class="btn-cancel" id="cancelBtn">Continua</button>
                <button class="btn-confirm" id="confirmBtn">Salva</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="script.js"></script>
</body>
</html>
