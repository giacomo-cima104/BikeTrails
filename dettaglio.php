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
$punti = count($coords);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Percorso — BikeTrails</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg: #0d0f14; --surface: #161920; --surface2: #1e2230;
            --border: #2a2f42; --accent: #f5a623; --accent2: #e8421a;
            --blue: #3b82f6; --text: #f0f2f8; --muted: #6b7491;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: 'Barlow', sans-serif; min-height: 100vh; }
        body::before { content: ''; position: fixed; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--accent2), var(--accent), var(--blue)); z-index: 100; }
        .container { max-width: 860px; margin: 0 auto; padding: 32px 20px 60px; }
        header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; padding-top: 12px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--accent2), var(--accent));
            border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .logo h1 { font-family: 'Barlow Condensed', sans-serif; font-size: 26px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .logo h1 span { color: var(--accent); }
        .btn-back { font-family: 'Barlow Condensed', sans-serif; font-size: 13px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); text-decoration: none;
            padding: 8px 14px; border: 1px solid var(--border); border-radius: 6px; transition: all 0.2s; }
        .btn-back:hover { color: var(--accent); border-color: var(--accent); }

        .page-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 14px; font-weight: 700; letter-spacing: 3px;
            text-transform: uppercase; color: var(--muted);
            margin-bottom: 6px;
        }
        .page-date {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 32px; font-weight: 900;
            margin-bottom: 24px;
        }

        /* Map */
        #map { height: 420px; width: 100%; border-radius: 12px; margin-bottom: 24px;
            border: 1px solid var(--border); overflow: hidden; }

        /* Stats row */
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
            padding: 18px 16px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent2), var(--accent)); }
        .stat-label { font-family: 'Barlow Condensed', sans-serif; font-size: 10px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
        .stat-value { font-family: 'Barlow Condensed', sans-serif; font-size: 28px; font-weight: 900; line-height: 1; color: var(--accent); }
        .stat-unit { font-size: 13px; color: var(--muted); font-weight: 400; }

        .no-route { text-align: center; padding: 80px 20px; color: var(--muted); }
        .no-route p { font-family: 'Barlow Condensed', sans-serif; font-size: 18px; font-weight: 700; }
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

        <div class="page-title">Dettaglio Percorso #<?php echo $id; ?></div>
        <div class="page-date">
            <?php echo date('d/m/Y', strtotime($row['data_creazione'])); ?>
            <small style="font-size:18px; color:var(--muted);">
                alle <?php echo date('H:i', strtotime($row['data_creazione'])); ?>
            </small>
        </div>

        <?php if ($punti > 0): ?>
            <div id="map"></div>
        <?php else: ?>
            <div class="no-route" id="map" style="height:200px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:12px; border-radius:12px; border:1px solid var(--border);">
                <div style="font-size:32px">📍</div>
                <p>Nessun dato GPS disponibile per questo percorso</p>
            </div>
        <?php endif; ?>

        <?php
        // Calcolo velocità media
        $vel_media = '—';
        if ($row['distanza'] > 0 && $row['durata']) {
            $parts = explode(':', $row['durata']);
            $tot_min = 0;
            if (count($parts) == 3) $tot_min = $parts[0]*60 + $parts[1] + $parts[2]/60;
            elseif (count($parts) == 2) $tot_min = $parts[0] + $parts[1]/60;
            if ($tot_min > 0) $vel_media = number_format($row['distanza'] / ($tot_min / 60), 1);
        }
        ?>

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
                <div class="stat-value"><?php echo $vel_media; ?><span class="stat-unit"> km/h</span></div>
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

        const map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Disegna il percorso
        const polyline = L.polyline(coords, {
            color: '#f5a623',
            weight: 5,
            opacity: 0.9
        }).addTo(map);

        // Marker inizio (verde)
        L.circleMarker(coords[0], {
            radius: 10, fillColor: '#22c55e', color: '#fff',
            weight: 3, opacity: 1, fillOpacity: 1
        }).addTo(map).bindTooltip('Partenza', {permanent: false});

        // Marker fine (rosso)
        L.circleMarker(coords[coords.length - 1], {
            radius: 10, fillColor: '#ef4444', color: '#fff',
            weight: 3, opacity: 1, fillOpacity: 1
        }).addTo(map).bindTooltip('Arrivo', {permanent: false});

        // Fit bounds
        map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
    </script>
    <?php endif; ?>
</body>
</html>
