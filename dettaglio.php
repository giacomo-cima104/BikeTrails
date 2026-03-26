<?php
include 'db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: storico.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM percorsi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { header('Location: storico.php'); exit; }

$coords = json_decode($row['coordinate'], true) ?? [];
$punti  = count($coords);

// Calcolo velocità media
$vel_media = null;
$tot_min   = 0;
if ($row['distanza'] > 0 && $row['durata']) {
    $parts = explode(':', $row['durata']);
    if (count($parts) == 3) $tot_min = $parts[0] * 60 + $parts[1] + $parts[2] / 60;
    elseif (count($parts) == 2) $tot_min = $parts[0] + $parts[1] / 60;
    if ($tot_min > 0) $vel_media = $row['distanza'] / ($tot_min / 60);
}

// Determina badge velocità
$badge = '';
if ($vel_media !== null) {
    if ($vel_media >= 30)      $badge = ['🚀 Veloce', '#448aff'];
    elseif ($vel_media >= 20)  $badge = ['⚡ Buon ritmo', '#f7b731'];
    elseif ($vel_media >= 10)  $badge = ['🚲 Regolare', '#00e676'];
    else                       $badge = ['🏔 Lento/pianura', '#a0a8c0'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Percorso #<?php echo $id; ?> — BikeTrails</title>
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

        .container { max-width: 900px; margin: 0 auto; padding: 32px 20px 60px; }

        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
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

        .btn-back {
            font-family: 'Syne', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 9px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-back:hover { color: var(--accent); border-color: var(--accent); background: rgba(247,183,49,0.06); }

        /* Hero section */
        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 24px;
            animation: fadeUp 0.5s 0.05s ease both;
        }

        .hero-left {}

        .page-eyebrow {
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .page-date {
            font-family: 'Syne', sans-serif;
            font-size: 36px; font-weight: 800;
            line-height: 1.1;
        }

        .page-time {
            font-size: 18px;
            color: var(--muted);
            margin-top: 4px;
        }

        .badge {
            font-family: 'Syne', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 1px;
            padding: 8px 14px;
            border-radius: 100px;
            border: 1px solid;
            white-space: nowrap;
        }

        /* Map */
        #map {
            height: 440px; width: 100%;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            animation: fadeUp 0.5s 0.1s ease both;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
            animation: fadeUp 0.5s 0.15s ease both;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 16px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.2s;
        }

        .stat-card:hover { border-color: var(--muted2); transform: translateY(-2px); }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent2), var(--accent));
        }

        .stat-label {
            font-family: 'Syne', sans-serif;
            font-size: 9px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 30px; font-weight: 800;
            line-height: 1; color: var(--accent);
        }

        .stat-unit { font-size: 13px; color: var(--muted); font-weight: 400; }

        /* Route path info */
        .route-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 13px;
            color: var(--muted);
            animation: fadeUp 0.5s 0.2s ease both;
        }

        .route-meta .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .route-meta span { font-weight: 500; color: var(--text); margin-left: 4px; }

        /* No GPS */
        .no-route {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            height: 200px;
            border-radius: 16px;
            border: 1px dashed var(--border);
            color: var(--muted);
            margin-bottom: 20px;
        }

        .no-route .icon { font-size: 36px; opacity: 0.5; }

        .no-route p {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700;
        }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .page-date { font-size: 26px; }
            .hero { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">🚲</div>
                <h1>Bike<span>Trails</span></h1>
            </div>
            <a href="storico.php" class="btn-back">← Storico</a>
        </header>

        <div class="hero">
            <div class="hero-left">
                <div class="page-eyebrow">Percorso #<?php echo $id; ?></div>
                <div class="page-date"><?php echo date('d M Y', strtotime($row['data_creazione'])); ?></div>
                <div class="page-time">ore <?php echo date('H:i', strtotime($row['data_creazione'])); ?></div>
            </div>
            <?php if ($badge): ?>
            <div class="badge" style="color: <?php echo $badge[1]; ?>; border-color: <?php echo $badge[1]; ?>33; background: <?php echo $badge[1]; ?>11">
                <?php echo $badge[0]; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($punti > 0): ?>
            <div id="map"></div>

            <div class="route-meta">
                <div class="dot" style="background:#00e676; box-shadow: 0 0 8px #00e676"></div>
                Partenza <span><?php echo round($coords[0][0], 5).', '.round($coords[0][1], 5); ?></span>
                <div style="flex:1"></div>
                <div class="dot" style="background:#ff4d4d; box-shadow: 0 0 8px #ff4d4d"></div>
                Arrivo <span><?php echo round($coords[count($coords)-1][0], 5).', '.round($coords[count($coords)-1][1], 5); ?></span>
            </div>
        <?php else: ?>
            <div class="no-route">
                <div class="icon">📍</div>
                <p>Nessun dato GPS per questo percorso</p>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Distanza</div>
                <div class="stat-value"><?php echo number_format($row['distanza'], 2); ?><span class="stat-unit"> km</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Durata</div>
                <div class="stat-value" style="font-size:22px"><?php echo $row['durata'] ?: '—'; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Vel. Media</div>
                <div class="stat-value">
                    <?php echo $vel_media !== null ? number_format($vel_media, 1) : '—'; ?>
                    <span class="stat-unit"> km/h</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Punti GPS</div>
                <div class="stat-value"><?php echo $punti; ?></div>
            </div>
        </div>
    </div>

    <?php if ($punti > 0): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const coords = <?php echo json_encode($coords); ?>;

        const map = L.map('map', { zoomControl: true });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        map.getContainer().style.filter = 'brightness(0.85) contrast(1.1) saturate(0.8)';

        // Glow polyline (shadow)
        L.polyline(coords, {
            color: '#f7b731',
            weight: 14,
            opacity: 0.12
        }).addTo(map);

        // Main route line
        const polyline = L.polyline(coords, {
            color: '#f7b731',
            weight: 5,
            opacity: 0.95,
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(map);

        // Start marker
        L.circleMarker(coords[0], {
            radius: 10, fillColor: '#00e676', color: '#fff',
            weight: 3, opacity: 1, fillOpacity: 1
        }).addTo(map).bindTooltip('Partenza', { permanent: false, direction: 'top' });

        // End marker
        L.circleMarker(coords[coords.length - 1], {
            radius: 10, fillColor: '#ff4d4d', color: '#fff',
            weight: 3, opacity: 1, fillOpacity: 1
        }).addTo(map).bindTooltip('Arrivo', { permanent: false, direction: 'top' });

        map.fitBounds(polyline.getBounds(), { padding: [48, 48] });
    </script>
    <?php endif; ?>
</body>
</html>
