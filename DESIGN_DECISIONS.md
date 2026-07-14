# ParadiseResort - Design Decisions & Architecture

Questo documento raccoglie le decisioni architetturali, i pattern di sviluppo e le scelte tecniche prese durante l'implementazione del resort di lusso **ParadiseResort**, conformemente al file `guidaAI.md` e alle direttive del progetto.

---

## 1. Motore di Template & Struttura delle Skin

### Scelta Architetturale: Uso Diretto di `Template` + Helper `page.inc.php`
- **Contesto:** Il progetto richiede l'utilizzo del template engine in `template2.inc.php` (`Template`), che imposta come sintassi di default la modalità SQUARE (`<[placeholder]>`, `<[foreach ...]>`, `<[if ...]>`).
- **Problema con Skin/Skinlet:** Come documentato in `include/page.inc.php`, le vecchie classi wrapper `Skin` e `Skinlet` accodavano una doppia estensione `.html` causando errori ("file non trovato").
- **Decisione:** Abbiamo adottato pienamente il pattern di helper definito in `include/page.inc.php` (`new_page()` e `new_block()`), che instanzia direttamente la classe `Template("skins/{$skinName}/dtml/{$template}")`.
- **Organizzazione file:** 
  - I template HTML per gli ospiti risiedono in `skins/customers/dtml/` e fanno riferimento agli asset visivi commerciali forniti nella cartella `marian-master`.
  - I template HTML per l'area di amministrazione e reception risiedono in `skins/administration/dtml/` e utilizzano gli asset commerciali forniti nella cartella `adminhmd-1.0.0`.

---

## 2. Gestione degli URL di Base (`$config['base']`)

### Scelta Architetturale: Auto-Rilevamento Dinamico e Fallback
- **Contesto:** Il file `include/config.inc.php` originariamente definiva `$config['base'] = '/progetto/zParadiseResort'`. Poiché il workspace su XAMPP di Windows si trova in `c:\xampp\htdocs\progettoAi` (o percorsi variabili a seconda dell'ambiente), un percorso statico errato causava errori di reindirizzamento HTTP 404.
- **Decisione:** Abbiamo implementato una logica di auto-detect in `include/config.inc.php` che calcola dinamicamente la root dell'applicazione rispetto alla `DOCUMENT_ROOT` del server Web (con fallback a `/progettoAi`). Questo garantisce che tutti i link CSS/JS/Immagini e i reindirizzamenti (`header('Location: ...')`) funzionino perfettamente in qualsiasi ambiente di test o produzione.
- **Eccezione DB:** Le credenziali del database non vengono alterate come da specifica ("ad eccezione delle credenziali del db").

---

## 3. Modello di Autenticazione e Controllo degli Accessi (RBAC/ACL)

### Scelta Architetturale: Servizi in Sessione + Auto-guarigione (`auth.inc.php`)
- **Contesto:** Il database definisce tre gruppi (`1 = Admin`, `2 = Receptionist`, `3 = Guest`) legati ai singoli script PHP tramite `services` e `group_services`.
- **Decisione:** 
  - Ad ogni caricamento o accesso (e durante il login), `load_user_services($userId)` carica l'elenco dei permessi effettivi dell'utente e li memorizza in `$_SESSION['user']['services']`.
  - Se un utente appena registrato non possiede un record in `user_gruppi`, il sistema applica il pattern di auto-guarigione assegnandolo al gruppo `3` (Guest).
  - L'accesso al backoffice è strettamente controllato da `require_service()` e dai redirect automatici di `block_admin()` e `block_staff()`.

---

## 4. Gestione del Carrello e Flusso di Prenotazione

### Scelta Architetturale: "In Cart" come Stato DB (`status_id = 1`)
- **Contesto:** La specifica chiede di poter selezionare camera, date e servizi aggiuntivi in un carrello, calcolando il prezzo in tempo reale, prima di trasformarlo in una prenotazione confermata/in attesa.
- **Decisione:** 
  - Sfruttiamo lo stato `booking_statuses.id = 1` (`In Cart`). Questo permette a `bootstrap.inc.php -> get_cart_count()` di operare sul database, eliminando automaticamente i carrelli abbandonati vecchi di 30 minuti ed evitando il blocco indefinito delle stanze fisiche.
  - Al momento del checkout (`cart.php?action=confirm`), lo stato passa a `2` (`Pending`), viene generata la fattura (`invoices` con `payment_status = 'unpaid'`) e le stanze vengono ufficialmente prenotate.

---

## 5. Chiusura Segnalazioni di Manutenzione (`maintenance_tickets`)

### Scelta Architetturale: Eliminazione Fisica (Niente Storico)
- **Contesto:** La User Story US-33 richiede esplicitamente: *"Chiuderle se sono completate -> alla chiusura vengono eliminate dal db, niente storico"*.
- **Decisione:** Quando un Amministratore o un Receptionist chiude un ticket in `admin/segnalazioni.php` o `receptionist/segnalazioni.php`, eseguiamo una query di cancellazione diretta (`DELETE FROM maintenance_tickets WHERE id = ?`) invece di aggiornare lo `status_id` a `3` (`Resolved`). Questo soddisfa rigorosamente il requisito aziendale.

---

## 6. Separazione delle Viste Receptionist e Admin

### Scelta Architetturale: Crud Specializzati per Receptionist (US-29, US-30)
- **Contesto:** Per i receptionist, `users.php` e `rooms.php` non devono offrire funzionalità di cancellazione/creazione arbitraria come per l'Admin, ma focus operativo.
- **Decisione:** 
  - `receptionist/users.php` mostra solo i clienti registrati con conteggio dinamico delle prenotazioni attive (`status_id IN (2,3)`).
  - `receptionist/rooms.php` presenta una griglia interattiva con badge colorati di disponibilità (`available`, `cleaning`, `maintenance`) e filtri veloci per gestire la pulizia e l'operatività quotidiana.
