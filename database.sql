CREATE DATABASE IF NOT EXISTS biketrails_db;
USE biketrails_db;

CREATE TABLE IF NOT EXISTS percorsi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    durata VARCHAR(50),
    distanza FLOAT,
    coordinate LONGTEXT -- Qui salveremo l'array JSON del percorso
);