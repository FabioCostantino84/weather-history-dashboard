# Weather History Dashboard

## Descrizione
Progetto realizzato come assignment per **Innovhead**.  
L’applicazione consente di visualizzare le **statistiche storiche delle temperature** di una città, sfruttando le API di [Open-Meteo](https://open-meteo.com/).  
Il progetto è stato sviluppato con **Laravel** e un’attenzione particolare alla **pulizia del codice**, **usabilità** e **presentazione grafica**.

---

## Scelte progettuali
- **Laravel come framework**: per la struttura MVC, la gestione semplice di rotte, validazioni e cache.
- **Open-Meteo API**: scelta perché è gratuita, precisa e adatta a un progetto didattico.
- **Bootstrap 5**: adottato per la parte di UI/UX, sfruttando le sue classi per una grafica pulita e responsive.
- **Chart.js**: libreria leggera e immediata per i grafici interattivi.
- **Caching lato server**: implementato per ridurre le chiamate ripetute alle API e migliorare le performance.
- **Card trasparenti e overlay scuro**: per garantire leggibilità su uno sfondo stagionale senza sacrificare l’estetica.

---

## Scelte del codice
- Controller → gestiscono le richieste HTTP e orchestrano logica e viste
- Service (OpenMeteoService) → centralizza le chiamate alle API esterne e la normalizzazione dei dati
- Model → City, WeatherRecord, relazioni con il DB
- View Blade → frontend responsivo con Bootstrap 5

## Funzionalità implementate
- Ricerca di una città tramite form.
- Selezione di un intervallo di date (se vuoto → ultimi 7 giorni).
- Statistiche principali:
  - Temperatura media
  - Temperatura minima
  - Temperatura massima
- Grafico interattivo a barre con medie giornaliere.
- Tabella dettagliata con toggle (Bootstrap collapse).
- Gestione errori (città non trovata, API non disponibili).
- Cache delle ricerche e dei dati orari per evitare chiamate duplicate.

---

## Funzionalità extra
- **UI migliorata** con card “vetro” semitrasparenti, overlay scuro e testi leggibili.
- **Responsive design** per adattarsi anche su mobile.
- **Toggle tabella dati**: possibilità di mostrare/nascondere i dati dettagliati senza appesantire la UI.
- **Colori dinamici nel grafico**: sfumatura blu → arancione → rosso in base alla temperatura.

---

## Struttura progetto
- `app/Services/OpenMeteoService.php` → gestione API, normalizzazione dati, caching.
- `resources/views/dashboard.blade.php` → dashboard principale (form, statistiche, grafico, tabella).
- `resources/js/chart.js` → logica grafico Chart.js.
- `resources/js/app.js` → script di interazione frontend (toggle tabella, scroll).
- `resources/css/app.css` → stili custom (overlay, card vetro, grafica migliorata).

---

## Installazione e Setup
1. Clona la repo:
   git clone <url-repository>
   cd <nome cartella>

2. Installa le dipendenze:
   composer install
   npm install

3. Configura l'ambiente
   - Copia .env.example in .env

   - Imposta i parametri del database
     php artisan key:generate  
     php artisan migrate

4. Avvia l'applicazione
   - Avvio del backend laravel
     php artisan serve  
   
   - Avvio del frontend
     * Modalità sviluppo
       npm run dev   
   
     * Build di produzione
       npm run build  