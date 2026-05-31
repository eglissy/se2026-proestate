# Software Design Document — ProEstate

## Revision History

| Version | Date | Author | Description |
|---|---|---|---|
| 1.0 | 2026 | Eglis Haderaj | Initial Software Design Document for ProEstate |


## 1. Hyrje
Ky dokument përshkruan konceptimin teknik dhe arkitekturor të sistemit ProEstate. Qëllimi është të sqarohet si do të ndërtohet sistemi, si ndahen modulet, si menaxhohen të dhënat dhe si sigurohet sistemi.

## 2. Arkitektura e sistemit
ProEstate projektohet si Monolithic Web Application me strukturë modulare. Arkitektura ndjek modelin Three-Tier Architecture:

### 2.1 Presentation Layer
Përbëhet nga HTML5, CSS3, JavaScript, jQuery dhe AJAX. Kjo shtresë menaxhon ndërfaqen që shikon përdoruesi, format, tabelat, filtrat, faqet e pronave dhe dashboard-et.

### 2.2 Business Logic Layer
Përbëhet nga PHP 8+. Kjo shtresë përpunon kërkesat, validon inputet, kontrollon rolet, menaxhon sesionet, kryen CRUD dhe lidhet me shërbimet e jashtme.

### 2.3 Data Access Layer
Përbëhet nga MySQL 8+. Të dhënat ruhen në tabela relacionale të normalizuara. Përdoren prepared statements për siguri dhe indexes për performancë.

## 3. Modulet kryesore

### 3.1 Authentication & User Management
Përgjegjës për regjistrimin, login, logout, reset password, email verification dhe rolet.

Komponentë:
- register.php
- login.php
- logout.php
- forgot-password.php
- reset-password.php
- includes/auth.php

### 3.2 Property Management Module
Përgjegjës për shtimin, ndryshimin, fshirjen dhe shfaqjen e pronave.

Komponentë:
- properties/index.php
- properties/create.php
- properties/edit.php
- properties/delete.php
- properties/show.php
- api/properties.php

### 3.3 Search & Filtering Module
Përgjegjës për kërkim të avancuar me filtra.

Komponentë:
- search.php
- api/search.php
- includes/search-functions.php

### 3.4 Appointment Module
Përgjegjës për rezervimet e takimeve.

Komponentë:
- appointments/create.php
- appointments/index.php
- api/appointments.php

### 3.5 Email Notification Module
Përdor PHPMailer ose SMTP për njoftime.

Komponentë:
- includes/mailer.php
- templates/email-confirmation.php
- templates/appointment-confirmation.php

### 3.6 Admin Panel
Menaxhon përdoruesit, pronat, raportet dhe moderimin.

Komponentë:
- admin/dashboard.php
- admin/users.php
- admin/properties.php
- admin/reports.php

### 3.7 Reviews Module
Lejon vlerësime për agjentët.

Komponentë:
- reviews/create.php
- reviews/list.php
- api/reviews.php

### 3.8 Messaging Module
Lejon komunikimin midis klientëve, agjentëve dhe pronarëve.

Komponentë:
- messages/inbox.php
- messages/thread.php
- api/messages.php

## 4. Struktura e folderave të aplikacionit

```text
src/
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── validation.php
│   └── mailer.php
├── templates/
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/
├── api/
├── admin/
├── dashboard/
├── properties/
├── appointments/
├── messages/
└── reviews/
```

## 5. Database Design
Databaza është relacionale dhe përdor foreign keys për lidhjet midis tabelave.

Tabelat kryesore:
- users
- properties
- property_images
- property_documents
- appointments
- payments
- reviews
- messages
- favorites
- activity_log

## 6. Kontrolli i aksesit
Sistemi përdor Role-Based Access Control.

| Roli | Aksesi |
|---|---|
| Admin | Menaxhim i plotë |
| Agent | Menaxhon pronat dhe takimet e veta |
| Owner | Menaxhon pronat e veta |
| Client | Kërkon prona, rezervon takime, shton favorites |
| Guest | Sheh faqet publike dhe mund të regjistrohet |

## 7. Siguria
Masat kryesore:
- `password_hash()` për fjalëkalime
- Prepared Statements me PDO
- CSRF tokens në format kryesore
- Escaping me `htmlspecialchars()`
- MIME validation për upload
- Kufizim i aksesit në folderin uploads
- Session regeneration pas login-it
- Validation në frontend dhe backend

## 8. Rrjedha e rezervimit të takimit
1. Klienti hap faqen e pronës.
2. Zgjedh datën dhe orën.
3. Sistemi kontrollon disponueshmërinë.
4. Takimi ruhet në databazë.
5. Agjenti/pronari merr email.
6. Klienti sheh statusin e takimit.

## 9. Backup dhe rikuperim
- Backup i kodit në GitHub
- Backup manual ose automatik i databazës me `mysqldump`
- Backup i folderit uploads
- Testim periodik i rikuperimit

## 10. Mirëmbajtja
- Gabimet ruhen në log
- Query-t optimizohen me indexes
- Dokumentacioni përditësohet në çdo sprint
- Pull Request-et kontrollohen nga anëtarët e ekipit
