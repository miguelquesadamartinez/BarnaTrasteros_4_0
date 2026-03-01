-- Script de inicialización de MySQL para BarnaTrasteros
-- Este script se ejecuta automáticamente cuando se crea el contenedor por primera vez

CREATE DATABASE IF NOT EXISTS barnatrasteros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- El usuario ya fue creado por las variables de entorno de Docker
GRANT ALL PRIVILEGES ON barnatrasteros.* TO 'barnauser'@'%';
FLUSH PRIVILEGES;
