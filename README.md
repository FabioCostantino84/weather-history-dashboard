# Weather History Dashboard

## Descrizione
Applicazione Laravel che permette di cercare una città, scaricare i dati storici delle temperature orarie da Open-Meteo e visualizzarli in tabella e in forma aggregata.

---

## Funzionalità implementate
- [x] Ricerca città tramite API Open-Meteo Geocoding  
- [ ] Salvataggio città nel database  
- [x] Download dati storici temperatura (Archive API)  
- [ ] Salvataggio dati storici nel database  
- [ ] API interne per restituzione statistiche aggregate  
- [ ] Frontend con tabella dati e box statistiche  

**Extra (se presenti):**
- [ ] Cache locale dei dati  
- [ ] Grafico delle temperature (es. Chart.js)  
- [ ] Seed iniziale con città predefinite  
- [ ] Test unitari  

---

## Struttura del codice
- **Controller** → gestione input utente e logica di coordinamento  
- **Service (OpenMeteoService)** → chiamate API esterne a Open-Meteo  
- **Model** → City e WeatherRecord con relazioni al DB  
- **View Blade** → interfaccia frontend per visualizzare risultati e statistiche  

---

## Scelte tecniche
- Uso di `Http::retry()` per rendere più robuste le chiamate esterne.  
- Normalizzazione dati: latitudine/longitudine salvati come `decimal` nel DB e cast a `float` nei model.  
- Uso di `Carbon` per la gestione sicura delle date.  
- Preparazione alle statistiche aggregate (media, min, max) direttamente da DB per efficienza.  

---
