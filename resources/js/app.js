// resources/js/app.js
import './bootstrap';
import 'bootstrap';
import '../css/app.css';

import { mountDailyChart } from './chart';

document.addEventListener('DOMContentLoaded', () => {
  mountDailyChart();
});