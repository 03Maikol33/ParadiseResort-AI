# ParadiseResort - User Stories & Implementation Tracking

Questo documento tiene traccia strutturata di tutte le User Stories del progetto ParadiseResort, separando quelle completate da quelle in corso o da implementare, specificando i file PHP, i template DTML e le tabelle del database coinvolte.

---

## 🟢 User Stories Completate / in Implementazione

### Gruppo 1: Autenticazione & Gestione Account (Guest & Staff)
- [x] **US-01: Registrazione Ospite sicuro**
  - *Descrizione:* Come Ospite, voglio poter compilare un form di registrazione sicuro, perché voglio creare un account guest per prenotare le mie vacanze.
  - *Target PHP:* `register.php`
  - *Target Template:* `skins/customers/dtml/register.html`
  - *Tabelle DB:* `users`, `user_gruppi` (`group_id = 3` per Guest), `group_services`
- [x] **US-02: Login e Logout sicuro**
  - *Descrizione:* Come Utente, voglio poter effettuare il login e il logout, perché voglio accedere alla mia area personale in sicurezza.
  - *Target PHP:* `login.php`, `logout.php`
  - *Target Template:* `skins/customers/dtml/login.html`
  - *Tabelle DB:* `users`, `user_gruppi`, `group_services`, `services`
- [x] **US-03: Mantenimento Accesso (Ricordami / Sessione persistente)**
  - *Descrizione:* Come Utente, voglio che il sistema ricordi che ho già fatto l’accesso, perché voglio poter accedere ai servizi rapidamente.
  - *Target PHP:* Check in `include/bootstrap.inc.php` e `login.php` via Cookie/Sessione automatica.
- [x] **US-04: Amministratore - Lista Utenti Registrati**
  - *Descrizione:* Come Amministratore, voglio visualizzare la lista di tutti gli utenti registrati, perché devo poter monitorare chi si iscrive al portale.
  - *Target PHP:* `admin/users.php`
  - *Target Template:* `skins/administration/dtml/users.html`
  - *Tabelle DB:* `users`, `user_gruppi`, `gruppi`
- [x] **US-05: Amministratore - Registrazione Staff (Gerarchia)**
  - *Descrizione:* Come Amministratore, voglio registrare nuovi membri dello staff stabilendo chi è Amministratore o Receptionist, perché voglio definire la gerarchia aziendale.
  - *Target PHP:* `admin/staff.php`
  - *Target Template:* `skins/administration/dtml/staff.html`
  - *Tabelle DB:* `users`, `user_gruppi` (`group_id = 1` o `2`), `gruppi`
- [x] **US-06: Amministratore - Collegamento Servizi ai Gruppi (RBAC Matrix)**
  - *Descrizione:* Come Amministratore, voglio poter collegare i "Servizi" (le singole pagine PHP) ai "Gruppi", perché voglio impedire che un semplice Guest acceda alle dashboard CRUD riservate allo staff.
  - *Target PHP:* `admin/services.php`
  - *Target Template:* `skins/administration/dtml/services.html`
  - *Tabelle DB:* `services`, `gruppi`, `group_services`

### Gruppo 2: Catalogo Camere, Categorie & Gestione Inventario
- [x] **US-07: Guest - Visualizzazione Catalogo Camere**
  - *Descrizione:* Come Ospite, voglio visualizzare le camere disponibili con prezzo, foto e capienza, perché voglio scegliere la sistemazione adatta.
  - *Target PHP:* `rooms.php`
  - *Target Template:* `skins/customers/dtml/rooms.html`
  - *Tabelle DB:* `room_categories`
- [x] **US-08: Guest - Filtro per Capienza e Prezzo**
  - *Descrizione:* Come Ospite, voglio poter filtrare le camere per numero di persone e prezzo massimo, perché voglio trovare rapidamente la soluzione che rientra nel mio budget.
  - *Target PHP:* `rooms.php` (`GET` parameters `capacity`, `max_price`)
- [x] **US-09: Guest - Dettaglio Categoria Camera e Lista Servizi Esclusivi**
  - *Descrizione:* Come Ospite, voglio consultare una pagina di dettaglio per ogni categoria di camera e vedere i servizi inclusi (es. Wi-Fi, SPA), perché voglio conoscere tutti i comfort prima di prenotare.
  - *Target PHP:* `room_details.php`
  - *Target Template:* `skins/customers/dtml/room_details.html`
  - *Tabelle DB:* `room_categories`, `room_category_amenities`, `amenities`
- [x] **US-10: Amministratore - CRUD Categorie Camere**
  - *Descrizione:* Come Amministratore, voglio poter creare e gestire le Categorie Camere stabilendo prezzo base, capienza e servizi, perché definiscono il listino del resort.
  - *Target PHP:* `admin/categories.php`
  - *Target Template:* `skins/administration/dtml/categories.html`
  - *Tabelle DB:* `room_categories`, `room_category_amenities`
- [x] **US-11: Amministratore - CRUD Camere Fisiche**
  - *Descrizione:* Come Amministratore, voglio poter creare e gestire le singole Camere Fisiche assegnandole alle categorie e definendone lo stato (Disponibile / Manutenzione / Occupata), perché l'inventario fisico deve corrispondere alle stanze reali.
  - *Target PHP:* `admin/rooms.php`
  - *Target Template:* `skins/administration/dtml/rooms.html`
  - *Tabelle DB:* `rooms`, `room_categories`

### Gruppo 4: Carrello Prenotazioni, Servizi Extra & Storico (Guest)
- [x] **US-12: Utente Loggato - Selezione Camera e Date in Carrello (Sessione/DB In Cart)**
  - *Descrizione:* Come Utente loggato, voglio poter selezionare una camera e le date di check-in/check-out inserendole in un "carrello" in sessione, perché voglio bloccare la mia scelta prima della conferma.
  - *Target PHP:* `room_details.php` (form POST), `cart.php`, `cart_add.php`
  - *Target Template:* `skins/customers/dtml/cart.html`
  - *Tabelle DB:* `bookings` (con `status_id = 1`, In Cart), check disponibilità su `rooms`
- [x] **US-13: Utente Loggato - Aggiunta Servizi Extra al Carrello con Prezzo Real-Time**
  - *Descrizione:* Come Utente loggato, voglio poter aggiungere servizi extra (es. Spa, Navetta) al mio carrello, perché voglio personalizzare il mio soggiorno vedendo il prezzo totale aggiornarsi in tempo reale.
  - *Target PHP:* `cart.php`, `checkout.php`
  - *Tabelle DB:* `amenities`, `booking_amenities`, `bookings`
- [x] **US-14: Utente Loggato - Conferma Carrello -> Prenotazione Reale**
  - *Descrizione:* Come Utente loggato, voglio confermare il carrello, perché voglio trasformarlo in una vera e propria prenotazione nel database.
  - *Target PHP:* `checkout.php` -> aggiorna `bookings.status_id = 3` ('Confirmed') e genera `invoices`.
- [x] **US-15: Utente Loggato - Storico Prenotazioni**
  - *Descrizione:* Come Utente loggato, voglio avere una pagina con lo storico delle mie prenotazioni, perché voglio tenere traccia dei miei viaggi passati e futuri.
  - *Target PHP:* `profile.php`
  - *Target Template:* `skins/customers/dtml/profile.html`
  - *Tabelle DB:* `bookings`, `booking_statuses`, `rooms`, `room_categories`, `invoices`
- [x] **US-16: Utente Loggato - Annullamento Prenotazione Futura**
  - *Descrizione:* Come Utente loggato, voglio poter annullare una prenotazione futura, perché i miei piani di viaggio potrebbero cambiare.
  - *Target PHP:* `profile.php?cancel_id=X` -> imposta `status_id = 4` ('Cancelled').

### Gruppo 5: Gestione Prenotazioni Resort (Admin & Receptionist)
- [x] **US-17: Admin/Receptionist - Visualizzazione Tabella Prenotazioni Resort**
  - *Descrizione:* Come Amministratore/Receptionist, voglio visualizzare una tabella con tutte le prenotazioni del resort, perché devo gestire gli arrivi e le partenze.
  - *Target PHP:* `admin/bookings.php`, `receptionist/bookings.php`
  - *Target Template:* `skins/administration/dtml/bookings.html`
  - *Tabelle DB:* `bookings`, `booking_statuses`, `users`, `rooms`, `room_categories`
- [x] **US-18: Admin/Receptionist - Cambio Stato Prenotazione**
  - *Descrizione:* Come Amministratore/Receptionist, voglio poter cambiare lo stato di una prenotazione (es. In attesa -> Confermata -> Completata), perché devo riflettere la situazione reale sul gestionale.
  - *Target PHP:* Cambio stato (`status_id`: Pending -> Confirmed -> Completed/Cancelled) via POST su `bookings.php`.

### Gruppo 6: Gestione e Catalogo Servizi Aggiuntivi
- [x] **US-19: Amministratore - Gestione Servizi Aggiuntivi (Amenities CRUD)**
  - *Descrizione:* Come amministratore voglio poter gestire i servizi aggiuntivi disponibili per aggiornare l’offerta del resort.
  - *Target PHP:* `admin/amenities.php`
  - *Target Template:* `skins/administration/dtml/amenities.html`
  - *Tabelle DB:* `amenities`
- [x] **US-20: Admin/Receptionist - Visualizzazione Servizi Aggiuntivi Richiesti dai Clienti**
  - *Descrizione:* Come amministratore/receptionist voglio poter vedere i servizi aggiuntivi richiesti da ogni cliente per poter organizzare il personale.
  - *Target PHP:* `admin/requested_services.php`, `receptionist/requested_services.php`
  - *Target Template:* `skins/administration/dtml/requested_services.html`
  - *Tabelle DB:* `booking_amenities`, `amenities`, `bookings`, `users`
- [x] **US-21: Cliente - Catalogo Servizi Aggiuntivi del Resort**
  - *Descrizione:* Come cliente voglio vedere un catalogo di tutti i servizi aggiuntivi disponibili nel resort per analizzare l’offerta.
  - *Target PHP:* `services.php`
  - *Target Template:* `skins/customers/dtml/services.html`
  - *Tabelle DB:* `amenities` (`WHERE is_suspended = 0`)

### Gruppo 7: Ristorante e Piscine (Guest & Staff)
- [x] **US-22: Cliente - Menu Statico del Ristorante**
  - *Descrizione:* Come cliente voglio visualizzare il menù del ristorante (Menù statico da aggiungere come nuova schermata).
  - *Target PHP:* `restaurant.php`
  - *Target Template:* `skins/customers/dtml/restaurant.html`
- [x] **US-23: Cliente - Prenotazione Tavolo Ristorante (Pranzo / Cena)**
  - *Descrizione:* Come cliente voglio prenotare un tavolo al ristorante (Aggiungere una tabella al db - `restaurant_reservations` già creata nello schema!) (Prenotazioni disponibili Pranzo/cena).
  - *Target PHP:* `restaurant_book.php`
  - *Target Template:* `skins/customers/dtml/restaurant_book.html`
  - *Tabelle DB:* `restaurant_reservations`
- [x] **US-24: Admin/Receptionist - Controllo Prenotazioni Giornaliere al Ristorante**
  - *Descrizione:* Come amministratore/receptionist voglio controllare le prenotazioni del giorno al ristorante.
  - *Target PHP:* `admin/restaurant_bookings.php`, `receptionist/restaurant_bookings.php`
  - *Target Template:* `skins/administration/dtml/restaurant_bookings.html`
  - *Tabelle DB:* `restaurant_reservations`, `users`
- [x] **US-25: Cliente - Pagina Informativa sulle Piscine**
  - *Descrizione:* Come cliente voglio visualizzare le informazioni sulle piscine presenti nel resort (pagina dedicata).
  - *Target PHP:* `pools.php`
  - *Target Template:* `skins/customers/dtml/pools.html`

### Gruppo 8: Recensioni Clienti
- [x] **US-26: Cliente - Visualizzazione Recensioni**
  - *Descrizione:* Come cliente voglio vedere le recensioni lasciate dagli altri cliente, per farmi un’idea di come viene vissuto il resort.
  - *Target PHP:* `reviews.php`
  - *Target Template:* `skins/customers/dtml/reviews.html`
  - *Tabelle DB:* `reviews`, `users`, `room_categories`
- [x] **US-27: Cliente - Scrittura Recensione**
  - *Descrizione:* Come cliente voglio scrivere le recensioni del resort, per condividere la mia esperienza ed esprimere la mia opinione.
  - *Target PHP:* `review.php`, `send-review.php`
  - *Target Template:* `skins/customers/dtml/review.html`
  - *Tabelle DB:* `reviews` (`user_id`, `room_category_id`, `rating`, `comment`)
- [x] **US-28: Amministratore - Gestione Recensioni Clienti**
  - *Descrizione:* Come amministratore voglio poter gestire le recensioni lasciate dai clienti del resort.
  - *Target PHP:* `admin/reviews.php`
  - *Target Template:* `skins/administration/dtml/reviews.html`
  - *Tabelle DB:* `reviews`

### Gruppo 9: Viste Receptionist (Ricerca Utenti & Monitoraggio Stanze)
- [x] **US-29: Receptionist - Ricerca Clienti Registrati & Conteggio Prenotazioni Attive**
  - *Descrizione:* Come receptionist voglio poter cercare i clienti registrati e vederne il numero di prenotazioni attive (solo vedere, implementare al posto della schermata receptionist/users.php).
  - *Target PHP:* `receptionist/users.php`
  - *Target Template:* `skins/administration/dtml/receptionist_users.html`
  - *Tabelle DB:* `users`, `bookings` (COUNT su status_id 2 e 3)
- [x] **US-30: Receptionist - Elenco Stanze con Indicatore Disponibilità & Ricerca**
  - *Descrizione:* Come receptionist voglio vedere l’elenco di tutte le stanze con un indicatore di disponibilità e possibilità di ricerca (implementare al posto di receptionist/rooms.php).
  - *Target PHP:* `receptionist/rooms.php`
  - *Target Template:* `skins/administration/dtml/receptionist_rooms.html`
  - *Tabelle DB:* `rooms`, `room_categories`

### Gruppo 10: Segnalazioni Guasti / Manutenzione
- [x] **US-31: Cliente - Segnalazione per Stanza Prenotata**
  - *Descrizione:* Come cliente voglio poter inviare una segnalazione per una stanza che ho prenotato.
  - *Target PHP:* `report-ticket.php`, `send-report-ticket.php`
  - *Target Template:* `skins/customers/dtml/report-ticket.html`
  - *Tabelle DB:* `maintenance_tickets`, `bookings`, `rooms`
- [x] **US-32: Receptionist - Segnalazione per Qualsiasi Stanza**
  - *Descrizione:* Come receptionist voglio poter inviare una segnalazione per una determinata stanza.
  - *Target PHP:* `receptionist/segnalazioni.php` (azione nuova segnalazione)
  - *Tabelle DB:* `maintenance_tickets`, `rooms`
- [x] **US-33: Amministratore - Consultazione e Chiusura Segnalazioni**
  - *Descrizione:* Come admin voglio consultare la lista di tutte le segnalazioni inviate dai clienti e dai receptionist e gestire. (Chiuderle se sono completate -> alla chiusura vengono eliminate dal db, niente storico).
  - *Target PHP:* `admin/segnalazioni.php`, `receptionist/segnalazioni.php`
  - *Target Template:* `skins/administration/dtml/segnalazioni.html`
  - *Tabelle DB:* `maintenance_tickets` (`DELETE FROM maintenance_tickets WHERE id = ?`)
