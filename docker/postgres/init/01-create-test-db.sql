-- Dijalankan sekali saat volume database pertama kali dibuat.
-- Database utama (cafeapp) dibuat oleh variabel POSTGRES_DB pada docker-compose.yml.
CREATE DATABASE cafeapp_test OWNER cafeapp;
