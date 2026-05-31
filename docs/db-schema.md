# Database Schema — ProEstate

## Revision History

| Version | Date | Author | Description |
|---|---|---|---|
| 1.0 | 2026 | Harilla Bica | Initial database schema documentation for ProEstate |

## users
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues unik |
| first_name | VARCHAR(100) | Emri |
| last_name | VARCHAR(100) | Mbiemri |
| email | VARCHAR(150) UNIQUE | Email |
| password_hash | VARCHAR(255) | Fjalëkalimi i hash-uar |
| role | ENUM('admin','agent','owner','client') | Roli |
| status | ENUM('pending','active','suspended','deleted') | Statusi |
| email_verified_at | DATETIME NULL | Data e verifikimit |
| created_at | DATETIME | Data e krijimit |

## properties
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues unik |
| user_id | INT FK users(id) | Pronari/agjenti që e ka shtuar |
| title | VARCHAR(200) | Titulli |
| description | TEXT | Përshkrimi |
| price | DECIMAL(12,2) | Çmimi |
| city | VARCHAR(100) | Qyteti |
| address | VARCHAR(255) | Adresa |
| type | ENUM('sale','rent') | Shitje ose qira |
| rooms | INT | Numri i dhomave |
| area | DECIMAL(10,2) | Sipërfaqja |
| status | ENUM('available','reserved','sold','rented','archived') | Statusi |
| created_at | DATETIME | Data e krijimit |

## property_images
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| property_id | INT FK properties(id) | Prona |
| image_path | VARCHAR(255) | Path i fotos |
| is_primary | BOOLEAN | Foto kryesore |
| created_at | DATETIME | Data |

## property_documents
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| property_id | INT FK properties(id) | Prona |
| document_path | VARCHAR(255) | Path i dokumentit |
| document_type | VARCHAR(100) | Tipi |
| created_at | DATETIME | Data |

## appointments
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| property_id | INT FK properties(id) | Prona |
| client_id | INT FK users(id) | Klienti |
| agent_id | INT FK users(id) | Agjenti/pronari |
| appointment_date | DATE | Data |
| appointment_time | TIME | Ora |
| status | ENUM('pending','confirmed','cancelled','completed') | Statusi |
| message | TEXT NULL | Mesazh opsional |
| created_at | DATETIME | Data |

## payments
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| appointment_id | INT FK appointments(id) | Takimi |
| provider | VARCHAR(50) | PayPal ose tjetër |
| order_id | VARCHAR(255) | Order ID |
| capture_id | VARCHAR(255) | Capture ID |
| amount | DECIMAL(10,2) | Shuma |
| status | VARCHAR(50) | Statusi |
| created_at | DATETIME | Data |

## reviews
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| agent_id | INT FK users(id) | Agjenti |
| client_id | INT FK users(id) | Klienti |
| appointment_id | INT FK appointments(id) | Takimi |
| rating | INT | 1–5 |
| comment | TEXT NULL | Komenti |
| created_at | DATETIME | Data |

## messages
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| sender_id | INT FK users(id) | Dërguesi |
| receiver_id | INT FK users(id) | Marrësi |
| property_id | INT FK properties(id) NULL | Prona |
| message | TEXT | Mesazhi |
| read_at | DATETIME NULL | Lexuar më |
| created_at | DATETIME | Data |

## favorites
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| user_id | INT FK users(id) | Klienti |
| property_id | INT FK properties(id) | Prona |
| created_at | DATETIME | Data |

## activity_log
| Field | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | Identifikues |
| user_id | INT FK users(id) NULL | Përdoruesi |
| action | VARCHAR(150) | Veprimi |
| description | TEXT | Përshkrimi |
| ip_address | VARCHAR(45) | IP |
| created_at | DATETIME | Data |
