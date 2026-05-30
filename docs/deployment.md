# Deployment Guide — ProEstate

## 1. Kërkesat
- PHP 8+
- MySQL 8+
- Apache ose Nginx
- XAMPP/Laragon për zhvillim lokal
- Git

## 2. Instalimi lokal

### Hapi 1: Klono repository
```bash
git clone https://github.com/[username]/se2026-proestate.git
```

### Hapi 2: Vendos projektin në XAMPP
Kopjo folderin në:
```text
C:/xampp/htdocs/se2026-proestate
```

### Hapi 3: Krijo databazën
Hap phpMyAdmin dhe krijo databazën:
```sql
CREATE DATABASE proestate_db;
```

### Hapi 4: Importo strukturën SQL
Importo file-in SQL të projektit në phpMyAdmin.

### Hapi 5: Konfiguro lidhjen me databazën
Në `src/config/database.php`, vendos:
```php
$host = 'localhost';
$dbname = 'proestate_db';
$username = 'root';
$password = '';
```

### Hapi 6: Hap projektin
Në browser:
```text
http://localhost/se2026-proestate/src/
```

## 3. Konfigurimi i email-it
Vendos SMTP credentials në `.env` ose në config, sipas implementimit.

## 4. Backup
- Eksporto databazën nga phpMyAdmin.
- Ruaj folderin uploads.
- Ruaj kodin në GitHub.

## 5. Probleme të zakonshme
### Database connection failed
Kontrollo emrin e databazës, portën MySQL dhe kredencialet.

### CSS nuk ngarkohet
Kontrollo `SITE_URL` dhe path-et e assets.

### Upload nuk punon
Kontrollo permissions të folderit uploads.
