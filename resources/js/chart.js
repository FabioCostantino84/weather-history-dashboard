// resources/js/chart.js
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

export function mountDailyChart() {
  const canvas = document.getElementById('dailyChart');
  const dataEl = document.getElementById('dailyData');
  if (!canvas || !dataEl) return;

  let payload = null;
  try {
    payload = JSON.parse(dataEl.textContent || '{}');
  } catch (_) {
    return;
  }

  const labels = Array.isArray(payload.labels) ? payload.labels : [];
  const data   = Array.isArray(payload.data)   ? payload.data   : [];

  if (!labels.length) return;

  const ctx = canvas.getContext('2d');

  if (canvas._chartInstance) {
    canvas._chartInstance.destroy();
  }

  // Funzione per interpolare i colori (gradiente blu → arancione → rosso)
  function tempToColor(temp) {
    const min = -5;   // temperatura minima considerata
    const max = 40;   // temperatura massima considerata
    const mid = 20;   // temperatura di transizione (verso arancione)

    if (temp <= mid) {
      // Interpolazione blu (2196F3) → arancione (FFA726)
      const ratio = (temp - min) / (mid - min);
      return interpolateColor([33, 150, 243], [255, 167, 38], ratio);
    } else {
      // Interpolazione arancione (FFA726) → rosso (E53935)
      const ratio = (temp - mid) / (max - mid);
      return interpolateColor([255, 167, 38], [229, 57, 53], ratio);
    }
  }

  // Funzione che interpola fra due colori RGB
  function interpolateColor(rgb1, rgb2, ratio) {
    ratio = Math.min(Math.max(ratio, 0), 1); // clamp 0-1
    const r = Math.round(rgb1[0] + (rgb2[0] - rgb1[0]) * ratio);
    const g = Math.round(rgb1[1] + (rgb2[1] - rgb1[1]) * ratio);
    const b = Math.round(rgb1[2] + (rgb2[2] - rgb1[2]) * ratio);
    return `rgba(${r}, ${g}, ${b}, 0.9)`;
  }

  const colors = data.map(tempToColor);

  canvas._chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Media giornaliera (°C)',
        data,
        borderWidth: 1,
        backgroundColor: colors,
        borderColor: colors.map(c => c.replace('0.9', '1')),
        hoverBackgroundColor: colors.map(c => c.replace('0.9', '1')),
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { ticks: { callback: (v) => v + '°' } },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: { label: (ctx) => ` ${ctx.parsed.y} °C` },
        },
      },
    },
  });
}