// Map init — centered on Milan
let map = L.map('map').setView([45.4642, 9.1900], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

// Dark map overlay for aesthetics
map.getContainer().style.filter = 'brightness(0.85) contrast(1.1) saturate(0.9)';

let watchID, timerInterval;
let path = [];
let totalDistance = 0;
let seconds = 0;
let maxSpeed = 0;
let polyline = L.polyline([], { color: '#f7b731', weight: 5, opacity: 0.95, lineCap: 'round', lineJoin: 'round' }).addTo(map);
let startMarker = null;
let tracking = false;

// ---------- Haversine ----------
function calcDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// ---------- Timer ----------
function updateTimer() {
    seconds++;
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    document.getElementById('timerLabel').innerText =
        `${hrs > 0 ? hrs + ':' : ''}${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

    // Update live avg speed every 5s
    if (seconds % 5 === 0 && totalDistance > 0 && seconds > 0) {
        const avgKmh = (totalDistance / (seconds / 3600));
        document.getElementById('speedLabel').innerHTML = avgKmh.toFixed(1) + '<span class="stat-unit" style="font-size:11px"> km/h</span>';
    }
}

// ---------- Toast ----------
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.className = `toast ${type}`;
    t.innerText = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}

// ---------- Start ----------
document.getElementById('startBtn').addEventListener('click', () => {
    if (!navigator.geolocation) {
        showToast('⚠ GPS non disponibile su questo dispositivo', 'error');
        return;
    }

    tracking = true;
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = 'block';
    document.getElementById('statusDot').classList.add('active');
    document.getElementById('statusText').innerText = 'Tracciamento attivo';
    document.getElementById('cardDist').classList.add('active-card');
    document.getElementById('cardTime').classList.add('active-card');
    document.getElementById('speedCard').classList.add('active-card');
    document.getElementById('gpsInfo').innerText = 'Acquisizione segnale GPS...';

    timerInterval = setInterval(updateTimer, 1000);

    watchID = navigator.geolocation.watchPosition(
        position => {
            const { latitude, longitude, accuracy, speed } = position.coords;
            const newPos = [latitude, longitude];

            // GPS accuracy info
            document.getElementById('gpsInfo').innerText = `Precisione GPS: ±${Math.round(accuracy)}m`;

            // Live speed from device if available
            if (speed !== null && speed >= 0) {
                const kmh = speed * 3.6;
                document.getElementById('speedLabel').innerHTML = kmh.toFixed(1) + '<span class="stat-unit" style="font-size:11px"> km/h</span>';
                if (kmh > maxSpeed) {
                    maxSpeed = kmh;
                    document.getElementById('maxSpeedLabel').innerHTML = kmh.toFixed(1) + '<span class="stat-unit" style="font-size:11px"> km/h</span>';
                }
            }

            if (path.length > 0) {
                const last = path[path.length - 1];
                const d = calcDistance(last[0], last[1], latitude, longitude);
                // Filter out GPS noise (ignore jumps > 50m in 1s)
                if (d < 0.05) {
                    totalDistance += d;
                    document.getElementById('distanzaLabel').innerText = totalDistance.toFixed(2);
                }
            } else {
                // First point — place start marker
                startMarker = L.circleMarker(newPos, {
                    radius: 9, fillColor: '#00e676', color: '#fff',
                    weight: 2, opacity: 1, fillOpacity: 1
                }).addTo(map).bindTooltip('Partenza');
                map.setView(newPos, 16);
            }

            path.push(newPos);
            polyline.setLatLngs(path);
            document.getElementById('pointsLabel').innerText = path.length;

            if (path.length > 1) map.panTo(newPos);
        },
        err => {
            const msgs = { 1: 'Permesso GPS negato', 2: 'GPS non disponibile', 3: 'Timeout GPS' };
            showToast('⚠ ' + (msgs[err.code] || 'Errore GPS'), 'error');
            document.getElementById('gpsInfo').innerText = msgs[err.code] || 'Errore GPS';
        },
        { enableHighAccuracy: true, maximumAge: 1000, timeout: 10000 }
    );
});

// ---------- Stop (with confirm) ----------
document.getElementById('stopBtn').addEventListener('click', () => {
    const km = totalDistance.toFixed(2);
    const time = document.getElementById('timerLabel').innerText;
    document.getElementById('confirmStats').innerText =
        `Distanza: ${km} km  ·  Tempo: ${time}`;
    document.getElementById('confirmOverlay').classList.add('show');
});

document.getElementById('cancelBtn').addEventListener('click', () => {
    document.getElementById('confirmOverlay').classList.remove('show');
});

document.getElementById('confirmBtn').addEventListener('click', () => {
    document.getElementById('confirmOverlay').classList.remove('show');
    saveRoute();
});

// ---------- Save ----------
function saveRoute() {
    clearInterval(timerInterval);
    navigator.geolocation.clearWatch(watchID);

    const dot = document.getElementById('statusDot');
    dot.classList.remove('active');
    dot.classList.add('saving');
    document.getElementById('statusText').innerText = 'Salvataggio...';

    const data = {
        distanza: totalDistance.toFixed(2),
        durata: document.getElementById('timerLabel').innerText,
        coordinate: JSON.stringify(path)
    };

    fetch('save_route.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('✓ Percorso salvato!', 'success');
            document.getElementById('statusText').innerText = 'Salvato';
            dot.classList.remove('saving');
            setTimeout(() => location.reload(), 2800);
        } else {
            throw new Error(res.error || 'Errore server');
        }
    })
    .catch(err => {
        showToast('✗ Errore nel salvataggio', 'error');
        document.getElementById('statusText').innerText = 'Errore';
        dot.classList.remove('saving');
        console.error(err);
    });
}
