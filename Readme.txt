=== Cursant PDF Generator ===

Contributors: Iftodi Petru
Tags: pdf, cursant, test, generare pdf, contact form 7
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generează PDF-uri cu răspunsurile cursanților pentru teste/evaluări. Suport pentru template-uri din admin, import utilizatori din Excel, update-uri din GitHub.

== Description ==

Plugin WordPress pentru generarea de fișiere PDF cu răspunsurile cursanților. Include template-uri de test editabile din admin, import utilizatori din fișiere Excel (.xlsx), import template-uri din JSON, generare atât pentru evaluare cât și pentru examen, și opțional update-uri automate din GitHub.

== Changelog ==

= 1.5 =

* **Template-uri Test PDF (CPT)** – Custom Post Type „Template-uri Test PDF”: creare și editare template-uri cu titlu, link test, tip (examen/evaluare), comisie și întrebări cu variante A/B/C și răspuns corect. Listare cu număr de întrebări pe coloană.

* **Import template-uri din JSON** – Submeniu „Import template-uri” (sub PDF Cursanți): încărcare fișier JSON cu unul sau mai multe template-uri. Format: title, examen_link, examen_type, commission (array), questions (array cu text, options a/b/c, correct). Corectare automată a diacriticelor stocate ca \uXXXX la import și la afișare.

* **Generare PDF din template selectat** – În pagina „Generare PDF” se selectează template-ul de test din dropdown (din CPT), grupă (rol), notă minimă și opțional „Denumire în fișier PDF”. Generatorul folosește datele din template-ul ales; dacă nu e selectat niciun template, se folosește testul din fișier (test_activ) ca în versiunile anterioare.

* **Denumire personalizată în numele fișierului PDF** – Setare „Denumire în fișier PDF”: textul introdus apare în numele fișierului generat (ex: Prenume-Nume-Denumire-evaluare.pdf). Lăsat gol = se folosește titlul examenului din template.

* **Generare dublă (evaluare + examen)** – Pentru fiecare utilizator se generează atât PDF de tip „evaluare” cât și „examen” (aceleași întrebări, tip diferit în header și tabel comisie).

* **Setări GitHub din admin** – Configurarea update-urilor automate (URL repository, branch, token, activat/dezactivat) se face din **PDF Cursanți → Setări → GitHub Credentials**, fără modificare de cod. Necesită `composer install` pentru Plugin Update Checker.

* **Import utilizatori din Excel (.xlsx)** – Pagină „Import utilizatori”: încărcare fișier .xlsx cu coloane CNP, NUME PRENUME (sau coloane separate nume/prenume), opțional EMAIL și TELEFON. Antetul se detectează automat. Utilizatorii sunt creați sau actualizați; li se poate atribui un rol existent sau se poate crea un rol nou. Email generat automat (ex: login@import.local) dacă lipsește.

* **Decriptare CNP** – Pagină „Decriptare CNP” pentru transformarea codului numeric (ID criptat) înapoi în CNP, după selectarea grupului (rolului).

* **Template PDF** – În PDF-ul generat, Nume și CNP cursantului se afișează doar pentru tipul „evaluare”; tabelul comisie are rowspan corespunzător pentru evaluare.

= 1.4 =

* Script de build pentru pachet de instalare: `build-plugin-zip.sh` pentru generarea `wordpress-pdf-generator.zip`.
* Opțiune `INCLUDE_TESTS=1` la rularea scriptului pentru a include directorul `tests/` în arhivă.
* Actualizat `.gitignore`: excludere ZIP, composer.lock, directoare IDE, fișiere temporare.

= 1.3 =

* Sistem de update automat din GitHub (Plugin Update Checker), suport Composer.
* Notă minimă în loc de „Rată success”; note generate aleatoriu între nota minimă și 10.
* Algoritm îmbunătățit pentru generare răspunsuri (generate_random_answers).

= 1.0 =

* Versiunea inițială: generare PDF cu răspunsurile cursanților, răspunsuri aleatorii.
