let map = L.map('map').setView([45.4642, 9.1900], 13); // Default Milano
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let watchID;
let path = [];
let polyline = L.polyline([], {color: 'blue'}).addTo(map);

document.getElementById('startBtn').addEventListener('click', () => {
    document.getElementById('startBtn').classList.add('hidden');
    document.getElementById('stopBtn').classList.remove('hidden');
    
    if (navigator.geolocation) {
        watchID = navigator.geolocation.watchPosition(position => {
            const { latitude, longitude } = position.coords;
            const pos = [latitude, longitude];
            path.push(pos);
            polyline.setLatLngs(path);
            map.setView(pos, 15);
        }, err => console.error(err), { enableHighAccuracy: true });
    }
});

document.getElementById('stopBtn').addEventListener('click', () => {
    navigator.geolocation.clearWatch(watchID);
    saveRoute();
});

function saveRoute() {
    const data = {
        distanza: (Math.random() * 10).toFixed(2), // Esempio semplificato
        coordinate: JSON.stringify(path)
    };

    fetch('save_route.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: { 'Content-Type': 'application/json' }
    }).then(res => res.text()).then(msg => alert("Percorso salvato!"));
}