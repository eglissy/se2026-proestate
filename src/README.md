# 🏠 ProEstate — Platforma Web për Menaxhimin e Pronave

**Projekt Universiteti · Programim në Web · Bachelor Informatikë · Viti 3 · 2025-2026**

---

## 📋 Përshkrim i Shkurtër

ProEstate është një platformë web për menaxhimin e pronave të paluajtshme në Shqipëri.  
Ofron shitje, qiradhënie, rezervim takimesh, mesazhe, upload dokumentesh dhe panel admin.

**Stack:** PHP 8 · MySQL · HTML/CSS/JS · jQuery · AJAX · PayPal REST API

---

## 🗂️ Struktura e Projektit

```
ProEstate/
├── config/
│   └── config.php              ← Konfigurimi qendror (DB, API keys, etj.)
├── includes/
│   ├── db.php                  ← Lidhja me DB (PDO Singleton)
│   ├── auth.php                ← Autentifikim & autorizim
│   ├── security.php            ← CSRF, sanitizim, rate limiting
│   ├── functions.php           ← Funksione ndihmëse
│   └── email.php               ← Dërgim emailesh
├── templates/
│   ├── header.php              ← Header global me navbar
│   └── footer.php              ← Footer global
├── assets/
│   ├── css/style.css           ← Stilet kryesore
│   └── js/main.js              ← JavaScript kryesor
├── database/
│   └── proesta.sql             ← Skema + të dhëna fillestare
├── api/
│   ├── upload.php              ← Upload i sigurt imazhesh/dok.
│   ├── favorites.php           ← Toggle favorites
│   └── admin-actions.php       ← Veprime AJAX admin
├── dashboard/
│   ├── index.php               ← Paneli kryesor
│   ├── sidebar.php             ← Navigim dashboard
│   ├── profile.php             ← Profili i perdoruesit
│   ├── my-properties.php       ← Lista pronave
│   ├── add-property.php        ← Shto/edito pronë
│   ├── appointments.php        ← Menaxhim takimesh
│   ├── messages.php            ← Inbox mesazhesh
│   └── favorites.php           ← Prona të preferuara
├── admin/
│   ├── index.php               ← Admin overview
│   ├── users.php               ← Menaxhim perdoruesve
│   └── properties.php          ← Menaxhim të gjitha pronave
├── uploads/
│   ├── properties/             ← Imazhet e pronave
│   ├── documents/              ← Dokumentet
│   └── avatars/                ← Avatarët
├── index.php                   ← Faqja kryesore
├── properties.php              ← Lista pronave + kërkim
├── property.php                ← Detajet e pronës
├── agents.php                  ← Lista agjentëve
├── agent.php                   ← Profili agjentit + vlerësime
├── login.php                   ← Hyrja
├── register.php                ← Regjistrimi
├── logout.php                  ← Dalja
├── forgot-password.php         ← Harrova fjalëkalimin
├── reset-password.php          ← Rivendos fjalëkalimin
├── verify-email.php            ← Verifikimi email
├── contact.php                 ← Kontakt
├── about.php                   ← Rreth nesh
└── .htaccess                   ← Siguri & cache
```

---

## ⚙️ Instalimi Hap pas Hapi

### 1. Kërkesat
- PHP 8.0+
- MySQL 8.0+
- Apache me mod_rewrite aktiv
- XAMPP / WAMP / Laragon (për lokal)

### 2. Vendosja e Skedarëve
```bash
# Kopjo folderin ProEstate/ te:
C:\xampp\htdocs\ProEstate\    # Windows XAMPP
/var/www/html/ProEstate/      # Linux Apache
```

### 3. Krijo Databazën
```sql
-- Hap phpMyAdmin ose MySQL client dhe ekzekuto:
SOURCE /path/to/ProEstate/database/proesta.sql;
```
Ose nga terminali:
```bash
mysql -u root -p < ProEstate/database/proesta.sql
```

### 4. Konfigurimi
Vlerat kryesore lexohen nga environment variables, me fallback për XAMPP lokal:

```bash
DB_HOST=localhost:3308
DB_NAME=proesta
DB_USER=root
DB_PASS=

PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=vendos-client-id
PAYPAL_CLIENT_SECRET=vendos-client-secret

OPENAI_API_KEY=vendos-openai-api-key
OPENAI_MODEL=gpt-5.4-mini
OPENAI_CHATBOT_ENABLED=1
```

### 5. Lejet e Direktorive
```bash
chmod 755 uploads/
chmod 755 uploads/properties/
chmod 755 uploads/documents/
chmod 755 uploads/avatars/
```
Windows XAMPP: Ato janë writable automatikisht.

### 6. Hap Aplikacionin
Shko te: `http://localhost/ProEstate`

---

## 👤 Llogaritë Demo

Kredencialet demo nuk publikohen ne README. Administratori hyn vetem nga faqja e dedikuar `/admin/login.php`; perdoruesit e tjere hyjne nga `login.php`.

---

## ✅ Kriteret e Projektit — Çecklista

| Kriteri | Implementuar |
|---------|:---:|
| Propozim Projekt | ✅ |
| Baza e të Dhënave Relacionale (3NF) | ✅ |
| Të dhëna sensitive të enkriptuara (bcrypt) | ✅ |
| Page Flow / Sitemap | ✅ |
| User Authentication (login, register, role) | ✅ |
| Email Integration | ✅ |
| File Upload & Download | ✅ |
| Data Management (CRUD) | ✅ |
| Search Utility (FULLTEXT + filtra) | ✅ |
| HTML + CSS + JS | ✅ |
| PHP + MySQL | ✅ |
| AJAX + jQuery | ✅ |
| Validim inputi | ✅ |
| Akses vetëm nga persona të autorizuar | ✅ |
| Mbrojtje SQL Injection (PDO prepared) | ✅ |
| Referrer check (vetëm nga faqet e aplikimit) | ✅ |
| **Integrim PayPal (rezervim takimesh)** | ✅ |
| Design modern, jo template i gatshëm | ✅ |

---

## 🛡️ Siguria

- **bcrypt** cost-12 për fjalëkalimet
- **CSRF tokens** në të gjitha format
- **PDO Prepared Statements** — zero SQL Injection
- **Referrer check** — faqet aksesohen vetëm nga aplikacioni
- **Rate limiting** session-based (login, kontakt)
- **MIME-type validation** për uploads (jo vetëm extension)
- **EXIF stripping** nga imazhet
- **XSS protection** — `htmlspecialchars()` kudo
- **Security headers** via `.htaccess`

---

## 📊 Databaza — Tabelat

| Tabela | Përshkrim |
|--------|-----------|
| `users` | Perdoruesit (admin, agent, owner, client) |
| `properties` | Pronat |
| `property_images` | Imazhet e pronave |
| `property_documents` | Dokumentet |
| `property_features` | Karakteristikat |
| `appointments` | Takimet |
| `messages` | Mesazhet |
| `favorites` | Preferuarat |
| `reviews` | Vlerësimet e agjentëve |
| `activity_log` | Log aktivitetesh |
| `email_queue` | Radhë emailesh |

---

## 💳 Integrim PayPal

Platforma përdor **PayPal JS SDK + REST API** për pagesa rezervimi takimesh.

**Si funksionon:**
1. Klienti zgjedh datën dhe orën → klik "Vazhdo me Pagesën"
2. PayPal Buttons shfaqen inline (pa redirect)
3. Klienti paguan me llogari PayPal ose kartë
4. `paypal-create-order.php` krijon order në PayPal REST API
5. `paypal-capture-order.php` kap pagesën dhe krijon takimin automatikisht
6. Email konfirmimi i dërgohet klientit dhe agjentit

**Skedarë kryesorë:**
| Skedar | Funksioni |
|--------|-----------|
| `api/paypal-create-order.php` | Krijon PayPal Order (OAuth2 + REST) |
| `api/paypal-capture-order.php` | Kap pagesën + krijon takimin + dërgon email |
| `payment-success.php` | Faqja e suksesit me faturë |
| `dashboard/payments.php` | Historia e pagesave |
| `database/proesta.sql` → tabela `payments` | Ruan të gjitha transaksionet |

**Tarifa rezervimi:** €50 (e konfigurueshme te `PAYPAL_RESERVATION_FEE`)
**Mënyra testimit:** Sandbox (konfiguro me kredencialet nga developer.paypal.com)

---

## 👥 Ekipi

- **Joey Koçi** — Scrum Master & Backend Developer
- **Anëtar 2** — Frontend Developer & UI/UX Lead
- **Anëtar 3** — Backend Developer & QA Lead

**Lënda:** Programim në Web · Bachelor Informatikë · Viti i 3-të · 2025-2026
