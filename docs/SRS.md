# Software Requirements Specification — ProEstate

## Revision History

| Version | Date | Author | Description |
|---|---|---|---|
| 1.0 | 2026 | Eglis Haderaj | Initial Software Requirements Specification for ProEstate |


## 1. Hyrje

### 1.1 Qëllimi i dokumentit
Ky dokument përshkruan kërkesat funksionale dhe jo-funksionale të sistemit ProEstate, një platformë web për menaxhimin e pronave të paluajtshme dhe qirave. Dokumenti përdoret nga ekipi i zhvillimit për të kuptuar çfarë duhet të ndërtohet, nga pedagogu për të vlerësuar projektin dhe nga ekipi për të krijuar rastet e testimit.

### 1.2 Fusha e sistemit
ProEstate do të jetë një aplikacion web ku përdoruesit mund të regjistrohen, të menaxhojnë prona, të kërkojnë prona me filtra, të rezervojnë takime dhe të marrin njoftime përmes email-it.

## 2. Përshkrim i përgjithshëm

### 2.1 Problemi
Në tregun e pasurive të paluajtshme në Shqipëri, informacioni për pronat shpesh menaxhohet me Excel, telefonata, email dhe rrjete sociale. Kjo krijon fragmentim, vonesa, gabime dhe komunikim joefikas midis pronarëve, agjentëve dhe klientëve.

### 2.2 Zgjidhja
ProEstate ofron një platformë të centralizuar ku pronat, përdoruesit, kërkimet, takimet dhe komunikimi ruhen dhe menaxhohen në një sistem të vetëm.

### 2.3 Palët e interesuara
- Agjentët imobiliarë
- Pronarët e pronave
- Klientët / blerësit / qiramarrësit
- Administratori i sistemit
- Pedagogu / vlerësuesi i projektit

### 2.4 Përdoruesit
#### Agjent
Krijon dhe menaxhon prona, sheh takimet dhe komunikon me klientët.

#### Pronar
Shton prona, sheh interesin e klientëve dhe menaxhon rezervimet.

#### Klient
Kërkon prona, ruan prona favorite dhe rezervon takime.

#### Administrator
Menaxhon përdoruesit, pronat, raportet dhe moderimin e sistemit.

## 3. Kërkesat funksionale

### FR-01 Regjistrimi i përdoruesve
Sistemi duhet të lejojë regjistrimin e përdoruesve me emër, mbiemër, email, fjalëkalim dhe rol.

Acceptance Criteria:
- Email-i validohet.
- Fjalëkalimi ka minimum 8 karaktere.
- Email-i nuk lejohet të jetë i dublikuar.
- Fjalëkalimi ruhet me hash.
- Përdoruesi merr mesazh suksesi.

### FR-02 Login dhe logout
Sistemi duhet të lejojë hyrjen dhe daljen nga llogaria.

Acceptance Criteria:
- Login bëhet me email dhe fjalëkalim.
- Nëse kredencialet janë gabim, shfaqet mesazh gabimi.
- Pas login-it përdoruesi ridrejtohet në dashboard.
- Logout shkatërron sesionin.

### FR-03 Menaxhimi i roleve
Sistemi duhet të dallojë role të ndryshme: Admin, Agent, Owner dhe Client.

Acceptance Criteria:
- Çdo përdorues ka vetëm një rol kryesor.
- Çdo rol shikon vetëm funksionet që i takojnë.
- Admin ka akses të plotë.

### FR-04 Menaxhimi i pronave CRUD
Agjentët dhe pronarët duhet të krijojnë, shohin, ndryshojnë dhe fshijnë pronat e tyre.

Acceptance Criteria:
- Prona ka titull, përshkrim, çmim, qytet, adresë, tip, numër dhomash dhe sipërfaqe.
- Mund të ngarkohen foto dhe dokumente.
- Një përdorues nuk mund të ndryshojë pronën e një përdoruesi tjetër.

### FR-05 Upload i fotove dhe dokumenteve
Sistemi duhet të pranojë foto JPG/PNG dhe dokumente PDF.

Acceptance Criteria:
- Maksimum 5 foto për pronë.
- Maksimum 3 dokumente për pronë.
- File-t me format të gabuar refuzohen.
- Madhësia kontrollohet në backend.

### FR-06 Kërkim i avancuar
Përdoruesit duhet të kërkojnë prona me filtra.

Acceptance Criteria:
- Filtrohet sipas çmimit, qytetit, tipit, dhomave dhe sipërfaqes.
- Rezultatet shfaqen me pagination.
- Rezultatet mund të sortohen sipas çmimit ose datës.

### FR-07 Detajet e pronës
Sistemi duhet të shfaqë një faqe të plotë për çdo pronë.

Acceptance Criteria:
- Shfaqen foto, çmimi, lokacioni, përshkrimi dhe të dhënat kryesore.
- Shfaqet butoni për rezervim takimi.
- Shfaqen të dhënat bazë të agjentit/pronarit.

### FR-08 Rezervimi i takimeve
Klientët duhet të mund të rezervojnë takim për një pronë.

Acceptance Criteria:
- Klienti zgjedh datë dhe orë.
- Sistemi kontrollon që slot-i të mos jetë i zënë.
- Takimi ruhet me status `pending` ose `confirmed`.
- Agjenti/pronari njoftohet me email.

### FR-09 Njoftimet me email
Sistemi duhet të dërgojë email për regjistrim, reset password dhe takime.

Acceptance Criteria:
- Email-i i konfirmimit dërgohet pas regjistrimit.
- Email-i i takimit dërgohet pas rezervimit.
- Gabimi në dërgim log-ohet.

### FR-10 Paneli i administratorit
Admin duhet të menaxhojë përdoruesit, pronat dhe statistikat.

Acceptance Criteria:
- Admin sheh listën e përdoruesve.
- Admin mund të aktivizojë/çaktivizojë përdorues.
- Admin mund të moderojë prona.
- Admin sheh statistika bazë.

### FR-11 Favorites
Klientët duhet të ruajnë pronat e preferuara.

Acceptance Criteria:
- Klienti mund të shtojë një pronë në favorites.
- Klienti mund ta heqë nga favorites.
- Lista e favorites shfaqet në profil.

### FR-12 Reviews
Klientët duhet të lënë vlerësime për agjentët pas një takimi.

Acceptance Criteria:
- Vlerësimi është 1–5 yje.
- Komenti është opsional.
- Review lidhet me klientin dhe agjentin.

## 4. Kërkesat jo-funksionale

### NFR-01 Performanca
Faqet kryesore duhet të ngarkohen në më pak se 2 sekonda në kushte normale lokale.

### NFR-02 Siguria
Sistemi përdor prepared statements, password hashing, validation dhe escaping për të parandaluar SQL Injection dhe XSS.

### NFR-03 Përdorshmëria
Ndërfaqja duhet të jetë e thjeshtë, në shqip dhe responsive.

### NFR-04 Portabiliteti
Sistemi duhet të punojë në Chrome, Firefox, Edge dhe Safari.

### NFR-05 Besueshmëria
Gabimet duhet të logohen dhe përdoruesi duhet të marrë mesazhe të qarta.

## 5. Use Cases

### UC-01 Regjistrim përdoruesi
Aktori: Klient / Agjent / Pronar  
Përshkrim: Përdoruesi plotëson formën e regjistrimit dhe krijon llogari.

### UC-02 Login
Aktori: Të gjithë përdoruesit  
Përshkrim: Përdoruesi hyn në sistem me email dhe fjalëkalim.

### UC-03 Shto pronë
Aktori: Agjent / Pronar  
Përshkrim: Përdoruesi shton një pronë të re me të dhëna, foto dhe dokumente.

### UC-04 Kërko pronë
Aktori: Klient / Agjent  
Përshkrim: Përdoruesi përdor filtra për të gjetur prona.

### UC-05 Rezervo takim
Aktori: Klient  
Përshkrim: Klienti zgjedh datë dhe orë për takim.

### UC-06 Menaxho përdorues
Aktori: Administrator  
Përshkrim: Admin verifikon, çaktivizon ose moderon përdorues.

## 6. Kufizime
- Projekti është web-only.
- Nuk ka aplikacion mobil të dedikuar.
- Versioni fillestar është në gjuhën shqipe.
- Afati i zhvillimit është i kufizuar.
- Disa funksionalitete mund të simulohen në mjedis lokal.
