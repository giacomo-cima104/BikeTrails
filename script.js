let map = L.map('map').setView([45.4642, 9.1900], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let watchID, timerInterval;
let path = [];
let totalDistance = 0;
let seconds = 0;
let polyline = L.polyline([], {color: '#f5a623', weight: 5, opacity: 0.9}).addTo(map);

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
}

function updateTimer() {
    seconds++;
    let hrs = Math.floor(seconds / 3600);
    let mins = Math.floor((seconds % 3600) / 60);
    let secs = seconds % 60;
    document.getElementById('timerLabel').innerText =
        `${hrs > 0 ? hrs + ':' : ''}${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.innerText = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

document.getElementById('startBtn').addEventListener('click', () => {
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = 'block';
    document.getElementById('statusDot').classList.add('active');
    document.getElementById('statusText').innerText = 'Tracciamento attivo';
    document.getElementById('cardDist').classList.add('active-card');
    document.getElementById('cardTime').classList.add('active-card');

    timerInterval = setInterval(updateTimer, 1000);

    if (navigator.geolocation) {
        watchID = navigator.geolocation.watchPosition(position => {
            const { latitude, longitude } = position.coords;
            const newPos = [latitude, longitude];

            if (path.length > 0) {
                const lastPos = path[path.length - 1];
                totalDistance += calculateDistance(lastPos[0], lastPos[1], latitude, longitude);
                document.getElementById('distanzaLabel').innerText = totalDistance.toFixed(2);
            }

            path.push(newPos);
            polyline.setLatLngs(path);
            map.setView(newPos, 16);
        }, null, { enableHighAccuracy: true });
    }
});

document.getElementById('stopBtn').addEventListener('click', () => {
    clearInterval(timerInterval);
    navigator.geolocation.clearWatch(watchID);
    document.getElementById('statusDot').classList.remove('active');
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
    }).then(() => {
        showToast('✓ Percorso salvato!');
        setTimeout(() => location.reload(), 2500);
    }).catch(() => {
        showToast('✗ Errore nel salvataggio');
        document.getElementById('statusText').innerText = 'Errore';
    });
});
