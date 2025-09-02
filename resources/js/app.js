// resources/js/app.js
import './bootstrap';
import 'bootstrap';           // JS di Bootstrap (bundle con Popper)
import '../css/app.css';

import { mountDailyChart } from './chart';

document.addEventListener('DOMContentLoaded', () => {
  // 1) Grafico (se presente nella pagina)
  mountDailyChart();

  // 2) Toggle tabella “dati dettagliati”
  const tableEl = document.getElementById('dailyTable');
  const btn     = document.getElementById('toggleTableBtn');
  if (!tableEl || !btn) return;

  // Inizializza il testo del bottone in base allo stato corrente
  const isOpen = tableEl.classList.contains('show');
  btn.textContent = isOpen ? 'Nascondi dati dettagliati' : 'Mostra dati dettagliati';

  // Aggiorna il testo quando parte l’apertura/chiusura
  tableEl.addEventListener('show.bs.collapse', () => {
    btn.textContent = 'Nascondi dati dettagliati';
  });
  tableEl.addEventListener('hide.bs.collapse', () => {
    btn.textContent = 'Mostra dati dettagliati';
  });

  // Dopo l’apertura, fai lo scroll dolce all’inizio della card
  tableEl.addEventListener('shown.bs.collapse', () => {
    setTimeout(() => {
      tableEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
  });
});