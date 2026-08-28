# Kölcsönpont — belső eszközkölcsönző

**Csapatfeladat · PHP · Yii2 basic · MySQL · 3 fejlesztő · 1 munkanap**

A cég laptopokat, projektorokat, kamerákat és szerszámokat ad kölcsön a kollégáknak.
Ma ez egy Excelben él, amiben senki nem találja, hogy a 3-as projektor kinél van.
A feladat: egy működő, demózható Yii2 alkalmazás, ami erre választ ad.
Nem TODO-lista — valódi állapotgép, valódi ütközések, valódi riport.

| | |
|---|---|
| Csapat | 3 fejlesztő |
| Időkeret | 1 munkanap |
| Migrációk | 5 db, adott sorrendben |
| Kimenet | Demó + hibátlanul lefutó `migrate` |

---

## 0. Fejlesztői indítás

```bash
composer install

# séma + demó adatok a fejlesztői adatbázisba (config/db.php)
php yii migrate

# séma a teszt-adatbázisba (config/test_db.php -> kolcsonpont_test)
php yii migrate --db=dbTest

# alkalmazás: http://localhost:8000
php -S localhost:8000 -t web

# tesztek; a végén JSON összefoglaló: tests/_output/test-results.json
php test.php              # minden
php test.php unit
php test.php functional
```

VS Code-ból a tesztek `Ctrl+Shift+B`-vel is indíthatók (lásd `.vscode/tasks.json`).

Belépés: `admin / admin` teljes hozzáféréssel; a `kollega` és a `demo` felhasználó a
katalógust és a késés-riportot látja.

## 1. Mit tud a kész alkalmazás

- Az irodavezető felveszi az eszközöket leltári számmal, kategóriával, státusszal.
- Kiad egy eszközt egy kollégának határidővel — a rendszer nem engedi kiadni azt, ami már kint van vagy karbantartás alatt áll.
- Visszavételkor egy kattintás: a kölcsönzés lezárul, az eszköz azonnal újra foglalható.
- A kollégák egy szűrhető katalógusban látják, mi elérhető most.
- A nyitóoldal megmutatja, hány eszköz van kint, mennyi a késésben, mit várunk vissza ma.
- A késés-riport listázza a lejárt kölcsönzéseket napokkal és díjjal, és CSV-be exportálható.

---

## 2. Adatmodell — a séma, amit 09:45-kor befagyasztotok

```
category   id · name · slug (UNIQUE) · created_at

equipment  id · category_id → category.id · inventory_no (UNIQUE, pl. "LP-0007")
           name · description · status (SMALLINT: 0 elérhető, 1 kiadva,
           2 karbantartás, 3 selejt) · purchased_at (DATE) · deposit (INT)
           created_at · updated_at            INDEX (category_id, status)

borrower   id · full_name · email (UNIQUE) · phone · is_active (BOOL)

loan       id · equipment_id → equipment.id · borrower_id → borrower.id
           loaned_at (DATE) · due_at (DATE) · returned_at (DATE, NULL)
           note · created_at                  INDEX (equipment_id, returned_at)
```

**Kulcsszabály:** a „ki van kint most” kérdésre a `loan.returned_at IS NULL` a válasz —
az `equipment.status` ennek csak a gyorsított tükre. A kettő soha nem mondhat mást,
ezért megy minden állapotváltás tranzakcióban.

### Migrációk sorrendje és gazdája

| # | Migráció | Gazda | Amit tartalmaz |
|---|---|---|---|
| 1 | `create_category_table` | A | unique index a `slug`-on |
| 2 | `create_equipment_table` | A | FK a kategóriára `RESTRICT`-tel, unique `inventory_no` |
| 3 | `create_borrower_table` | B | unique e-mail, `is_active` alapértéke 1 |
| 4 | `create_loan_table` | B | két idegen kulcs, összetett index a nyitott kölcsönzésekre |
| 5 | `seed_demo_data` | C | 4 kategória, 12 eszköz, 5 kölcsönző, 6 kölcsönzés — ebből 2 késésben |

A sorrend kötelező, mert az idegen kulcsok erre épülnek. Mindenki a saját gépén generálja
a fájlt (`php yii migrate/create`), az időbélyeg így nem ütközik — de a fenti sorrendben
kell keletkezniük. A `down()` metódusokat is meg kell írni, fordított sorrendben bontva.

---

## 3. Üzleti szabályok

Ezek a szabályok a feladat lényege: nem a CRUD-tól lesz nehéz, hanem ezektől.
Az elfogadási lista pontosan ezekre hivatkozik.

- **SZ-1** — Kölcsönözni csak `elérhető` státuszú eszközt lehet. A karbantartás és a selejt nem választható a kiadás űrlapon — nem csak elrejtve, hanem validációval is.
- **SZ-2** — Egy eszközre egyszerre csak **egy** nyitott kölcsönzés létezhet. Az ellenőrzés és a mentés ugyanabban a tranzakcióban fut, különben két egyszerre kattintó felhasználó kiadhatja ugyanazt.
- **SZ-3** — `due_at > loaned_at`, és a kölcsönzés hossza legfeljebb 30 nap. Múltbeli `loaned_at` nem vehető fel.
- **SZ-4** — Egy kölcsönzőnek legfeljebb 3 nyitott kölcsönzése lehet. Inaktív kölcsönző nem vehet fel újat, de a meglévőt visszahozhatja.
- **SZ-5** — Visszavételkor egy tranzakcióban: `returned_at = ma` és `equipment.status = elérhető`. Ha bármelyik mentés elhasal, mindkettő visszagördül.
- **SZ-6** — Késésben van a kölcsönzés, ha `returned_at IS NULL` és `due_at < ma`. Késedelmi díj: napok × 500 Ft, de legfeljebb az eszköz letétjének összege.
- **SZ-7** — Hosszabbítani csak nyitott és *nem késésben* lévő kölcsönzést lehet, +7 nappal — de a `loaned_at`-tól számítva összesen sem léphet 30 nap fölé.
- **SZ-8** — Kategória nem törölhető, amíg eszköz tartozik hozzá. Eszköz nem törölhető, ha bármikor kölcsönözték — helyette `selejt` státuszt kap.

---

## 4. Feladatfelosztás

A három sáv párhuzamos, nem egymásra épülő: mindenki saját migrációt, saját kontrollert
és saját nézeteket szerkeszt. Csak három közös metódusban találkoztok, azok 12:00-ra kellenek.

### A — Törzsadat és katalógus
`CategoryController` · `EquipmentController`

- 1–2. migráció idegen kulccsal és indexekkel
- Gii-vel CRUD kategóriára és eszközre, utána kézzel csiszolva
- `EquipmentSearch`: szűrés névre és leltári számra (LIKE), kategóriára, státuszra, rendezhető oszlopokkal
- Leltári szám formátum-validáció (`match`: két betű, kötőjel, négy számjegy) és egyediség
- SZ-8 törlésvédelem `beforeDelete()`-ben, érthető hibaüzenettel
- Publikus katalógus: `ListView` kártyákkal, csak az elérhető eszközök, kategória-szűrővel

**Kész, ha** a másik kettő tudja hívni: `Equipment::statusLabels()` és `Equipment::isAvailable()`.

### B — Kölcsönzés-motor
`LoanController` · `BorrowerController`

- 3–4. migráció, Borrower CRUD az `is_active` kapcsolóval
- `LoanForm` külön modellként (nem nyers AR-CRUD): eszköz- és kölcsönzőválasztó, dátumok, megjegyzés
- SZ-1 … SZ-4 validációként, saját validátor-metódusokban
- `actionReturn($id)` tranzakcióban (SZ-5) és `actionExtend($id)` (SZ-7)
- Nyitott kölcsönzések listája „késésben” oszloppal, `with(['equipment','borrower'])`-szel
- `Loan::isOverdue()`, `getOverdueDays()`, `getLateFee()`

**Kész, ha** C tud rá riportot építeni: `Loan::isOverdue()` és `getLateFee()` áll.

### C — Áttekintés és keret
`SiteController` · `ReportController` · layout

- 5. migráció: seed adat, ami az első perctől demózhatóvá teszi az appot
- Layout: menü, morzsa, flash üzenetek, magyar formatter (`hu-HU`, időzóna, dátumformátum)
- Nyitóoldal: négy mérőszám (összes / kint / késésben / ma visszavárt) és az utolsó 5 mozgás
- Késés-riport: szűrés kölcsönzőre, kategóriára, időszakra, összesített díjjal
- CSV export ugyanarra a lekérdezésre (`Response::sendContentAsFile`)
- Belépés az alap User modellel: `admin` mindent szerkeszt, `kollega` csak néz — `AccessControl`-lal

**Kész, ha** a seed lefuttatása után a nyitóoldal magától mutat 2 késésben lévő tételt.

---

## 5. Megállapodások

### Fix pontok
- A séma 09:45-kor befagy. Ha mégis változni kell: **új** migráció, a régit soha nem írjuk át.
- Branch fejenként: `feat/a-torzsadat`, `feat/b-kolcsonzes`, `feat/c-riport`. PR a `main`-re, egy kötelező review.
- Gii-t csak a nap elején futtatunk, egyszer. Az újragenerálás felülírja a kézi módosítást.
- Senki nem szerkeszti a másik kontrollerét vagy nézetét. Ha kell valami, szólunk, és a gazda írja meg.

### Közös felület
- `Equipment::statusLabels()` — A írja, B és C használja
- `Equipment::isAvailable()` — A írja, B hívja az SZ-1-hez
- `Loan::isOverdue()` és `getLateFee()` — B írja, C hívja a riporthoz

Ezek szignatúrái már 10:00-kor legyenek meg üres törzzsel és bepusholva, hogy a másik kettő ne várjon rájuk.

---

## 6. A nap menete

| Idő | Mi történik |
|---|---|
| **09:00** | **Közös indítás** — séma átbeszélése és véglegesítése, repo és adatbázis felállítása, a migrációk sorrendjének kiosztása, branch-ek létrehozása. |
| 09:45 | Migrációk és modellek — A és B tolja a sajátját, C közben a layoutot és a seedet írja, úgy, hogy üres táblákra is lefusson. |
| **11:15** | **Integráció #1** — minden branch merge-elve, `php yii migrate/fresh` egy üres adatbázison. Ha az idegen kulcsok sorrendje rossz, most derül ki, nem 16:30-kor. |
| 12:00 | Ebéd. |
| 12:45 | Üzleti szabályok köre — a nehéz rész: `LoanForm` validációk, tranzakciós visszavétel, riport-lekérdezés, hozzáférés-kezelés. |
| **15:00** | **Integráció #2 és közös teszt** — egy eszköz teljes útja hármasban, kézzel: felvétel → kiadás → sikertelen második kiadás → hosszabbítás → késés a riportban → visszavétel → újra a katalógusban. |
| 16:15 | Csiszolás — az elfogadási lista végigpipálása, hibaüzenetek magyarra, üres állapotok kezelése. |
| **17:00** | **Demó és retró** — fejenként 5 perc a saját sávjából, majd: mi lassított, mit csinálnátok máshogy. |

---

## 7. Elfogadási kritériumok — akkor kész, ha mind a nyolc igaz

- [ ] Üres adatbázison a `php yii migrate` hibátlanul lefut, és a `php yii migrate/down 5` nyom nélkül visszabontja.
- [ ] A seed után a nyitóoldal valós számokat mutat, köztük 2 késésben lévő kölcsönzést.
- [ ] Duplikált leltári szám mentése magyar nyelvű mezőhibát ad, nem 500-as oldalt.
- [ ] Kiadott eszköz nem választható újra a kölcsönzés űrlapon — sem listából, sem kézzel írt `equipment_id`-vel (SZ-1, SZ-2).
- [ ] Visszavétel után az eszköz azonnal megjelenik a publikus katalógusban (SZ-5).
- [ ] A késés-riport napokat és díjat számol, és a szűrésnek megfelelő sorokkal tölt le CSV-t (SZ-6).
- [ ] A `kollega` felhasználó nem lát szerkesztő gombot, és 403-at kap a `/equipment/create` címen.
- [ ] A nyitott kölcsönzések oldal a debug toolbar szerint 10 lekérdezés alatt marad — nincs N+1.

---

## 8. Ahol el fog hasalni

- A `down()` metódusban **fordított** sorrendben kell bontani: előbb az idegen kulcs, aztán a tábla.
- MySQL `ENUM` helyett `smallInteger()` és osztálykonstansok — a migráció és a validáció így marad hordozható.
- Dátumot ne stringként hasonlítsatok össze: `strtotime()` vagy `DateTime`, kiírásra `Yii::$app->formatter->asDate()`.
- Amit az űrlap küld, annak szerepelnie kell a `rules()`-ban, különben csendben elveszik mentéskor.
- Tranzakció: `$tx = Yii::$app->db->beginTransaction();` majd `try / commit / catch / rollBack` — `rollBack()`, nagy B-vel.
- Listákban `with(['equipment','borrower'])`, különben soronként külön lekérdezés megy ki.

---

## 9. Az első tizenöt perc

```bash
composer create-project --prefer-dist yiisoft/yii2-app-basic kolcsonpont
cd kolcsonpont

# config/db.php:
#   'dsn'     => 'mysql:host=localhost;dbname=kolcsonpont',
#   'charset' => 'utf8mb4'

php yii migrate/create create_category_table     # majd 2., 3., 4., 5.
php yii migrate

php -S localhost:8080 -t web                     # app:  http://localhost:8080
                                                 # gii:  /index.php?r=gii
```

**Ha marad idő:** soft delete a kölcsönzéseken, e-mail értesítés a lejárat előtti napon
(fájlba mentő mailerrel), QR-kód a leltári számhoz, Codeception unit teszt az SZ-6
díjszámításra, vagy AJAX-os visszavétel oldalfrissítés nélkül.

---

*Kölcsönpont — egynapos csapatfeladat a Yii2 basic sablonra. Az elfogadási lista az
SZ-1 … SZ-8 szabályokra hivatkozik; ha egy szabály értelmezése vitás, a séma és az
elfogadási lista dönt.*
