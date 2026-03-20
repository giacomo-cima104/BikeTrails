<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BikeTrails — Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;700;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg: #0d0f14;
            --surface: #161920;
            --surface2: #1e2230;
            --border: #2a2f42;
            --accent: #f5a623;
            --accent2: #e8421a;
            --blue: #3b82f6;
            --text: #f0f2f8;
            --muted: #6b7491;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent2), var(--accent), var(--blue));
            z-index: 100;
        }

        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-top: 12px;
        }

        .logo { display: flex; align-items: center; gap: 10px; }

        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }

        .logo h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 26px; font-weight: 900;
            letter-spacing: 1px; text-transform: uppercase;
        }

        .logo h1 span { color: var(--accent); }

        header a {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 13px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: var(--muted); text-decoration: none;
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            transition: all 0.2s;
        }

        header a:hover { color: var(--accent); border-color: var(--accent); }

        .status-bar {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 14px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted);
        }

        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--muted);
            transition: background 0.3s;
        }

        .status-dot.active {
            background: #22c55e;
            box-shadow: 0 0 8px #22c55e88;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        #map {
            height: 320px; width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .stats-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-bottom: 20px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 16px;
            position: relative; overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 3px; height: 100%;
            background: var(--accent);
            opacity: 0; transition: opacity 0.3s;
        }

        .stat-card.active-card::before { opacity: 1; }

        .stat-label {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 10px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--muted); margin-bottom: 6px;
        }

        .stat-value {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 38px; font-weight: 900;
            line-height: 1; color: var(--text);
        }

        .stat-unit { font-size: 16px; font-weight: 400; color: var(--muted); }

        .btn-start {
            width: 100%;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
            color: #fff; border: none;
            padding: 18px;
            border-radius: 12px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 20px; font-weight: 900;
            letter-spacing: 3px; text-transform: uppercase;
            cursor: pointer; transition: all 0.2s;
        }

        .btn-start:hover { filter: brightness(1.1); }
        .btn-start:active { transform: scale(0.98); }

        .btn-stop {
            width: 100%;
            background: var(--surface);
            color: #ef4444;
            border: 2px solid #ef4444;
            padding: 16px; border-radius: 12px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 20px; font-weight: 900;
            letter-spacing: 3px; text-transform: uppercase;
            cursor: pointer; transition: all 0.2s;
            display: none;
        }

        .btn-stop:hover { background: #ef4444; color: #fff; }
        .btn-stop:active { transform: scale(0.98); }

        .toast {
            position: fixed;
            bottom: 24px; left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #22c55e; color: #fff;
            padding: 14px 24px; border-radius: 10px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 16px; font-weight: 700; letter-spacing: 1px;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 999;
        }

        .toast.show { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">🚲</div>
                <h1>Bike<span>Trails</span></h1>
            </div>
            <a href="storico.php">Storico →</a>
        </header>

        <div class="status-bar">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">In attesa</span>
        </div>

        <div id="map"></div>

        <div class="stats-grid">
            <div class="stat-card" id="cardDist">
                <div class="stat-label">Distanza</div>
                <div class="stat-value">
                    <span id="distanzaLabel">0.00</span>
                    <span class="stat-unit">km</span>
                </div>
            </div>
            <div class="stat-card" id="cardTime">
                <div class="stat-label">Tempo</div>
                <div class="stat-value" id="timerLabel">00:00</div>
            </div>
        </div>

        <button id="startBtn" class="btn-start">⚡ Inizia Corsa</button>
        <button id="stopBtn" class="btn-stop">⏹ Termina e Salva</button>
    </div>

    <div class="toast" id="toast">✓ Percorso salvato!</div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="script.js"></script>
</body>
</html>
