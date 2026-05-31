# Test Plan & Test Report — ProEstate

## Revision History

| Version | Date | Author | Description |
|---|---|---|---|
| 1.0 | 2026 | Harilla Bica | Initial test report for core ProEstate functionalities |

## 1. Qëllimi
Ky dokument përshkruan planin e testimit dhe rezultatet e testimit për sistemin ProEstate.

## 2. Mjedisi i testimit
- OS: Windows 10/11
- Server lokal: XAMPP
- PHP: 8+
- Database: MySQL 8+
- Browser: Chrome, Firefox, Edge
- Pajisje: Desktop dhe mobile responsive view

## 3. Llojet e testimit
- Testim funksional
- Testim validimi
- Testim sigurie bazë
- Testim UI/UX
- Testim responsive
- Testim CRUD
- Testim i databazës

## 4. Test Cases

| ID | Funksioni | Hapat | Rezultati i pritur | Status |
|---|---|---|---|---|
| TC-01 | Regjistrim | Plotëso formën me të dhëna të sakta | Llogaria krijohet | Pass |
| TC-02 | Regjistrim | Vendos email të pavlefshëm | Shfaqet mesazh gabimi | Pass |
| TC-03 | Regjistrim | Vendos fjalëkalim më pak se 8 karaktere | Refuzohet regjistrimi | Pass |
| TC-04 | Login | Vendos email/fjalëkalim të saktë | Hapet dashboard | Pass |
| TC-05 | Login | Vendos fjalëkalim gabim | Shfaqet gabim | Pass |
| TC-06 | Logout | Kliko logout | Sesioni mbyllet | Pass |
| TC-07 | Shto pronë | Plotëso të dhënat e pronës | Prona ruhet | Pass |
| TC-08 | Shto pronë | Lër titullin bosh | Shfaqet validim | Pass |
| TC-09 | Edito pronë | Ndrysho çmimin | Çmimi përditësohet | Pass |
| TC-10 | Fshi pronë | Kliko delete | Prona arkivohet/fshihet | Pass |
| TC-11 | Upload foto | Ngarko JPG | Foto ruhet | Pass |
| TC-12 | Upload foto | Ngarko EXE | File refuzohet | Pass |
| TC-13 | Kërkim | Filtro sipas qytetit | Shfaqen pronat e qytetit | Pass |
| TC-14 | Kërkim | Filtro sipas çmimit | Shfaqen rezultate të sakta | Pass |
| TC-15 | Rezervo takim | Zgjidh slot të lirë | Takimi krijohet | Pass |
| TC-16 | Rezervo takim | Zgjidh slot të zënë | Sistemi refuzon overlap | Pass |
| TC-17 | Admin | Hap listën e përdoruesve | Lista shfaqet | Pass |
| TC-18 | Responsive | Testo në 375px | Layout nuk prishet | Pass |

## 5. Probleme të gjetura
| ID | Përshkrimi | Status |
|---|---|---|
| BUG-01 | Mesazhi i gabimit në login nuk ishte i qartë | Fixed |
| BUG-02 | Në mobile, butoni i kërkimit dilte jashtë container-it | Fixed |
| BUG-03 | Upload pranonte file me emër shumë të gjatë | Fixed |

## 6. Përfundim
Sistemi kaloi testet kryesore funksionale. Problemet e vogla të UI dhe validimit u rregulluan gjatë Sprint 3 dhe Sprint 4.
