<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BikeTrails - Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style> #map { height: 400px; width: 100%; border-radius: 15px; } </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="max-w-4xl mx-auto p-6">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">🚲 BikeTrails</h1>
            <div id="status" class="text-sm font-medium text-gray-500">Pronto a partire</div>
        </header>

        <div id="map" class="shadow-lg mb-6"></div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-center">
            <div class="bg-white p-4 rounded-xl shadow">
                <p class="text-gray-500 uppercase text-xs">Distanza (km)</p>
                <span id="distanza" class="text-2xl font-bold text-gray-800">0.00</span>
            </div>
            <div class="bg-white p-4 rounded-xl shadow">
                <p class="text-gray-500 uppercase text-xs">Tempo</p>
                <span id="timer" class="text-2xl font-bold text-gray-800">00:00</span>
            </div>
        </div>

        <div class="flex gap-4">
            <button id="startBtn" class="flex-1 bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">INIZIA</button>
            <button id="stopBtn" class="flex-1 bg-red-500 text-white py-3 rounded-lg font-bold hover:bg-red-600 transition hidden">FERMA E SALVA</button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="script.js"></script>
</body>
</html>