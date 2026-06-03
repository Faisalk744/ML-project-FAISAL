(function () {
  const API_BASE = 'api';

  const form = document.getElementById('predictForm');
  const submitBtn = document.getElementById('submitBtn');
  const sampleBtn = document.getElementById('sampleBtn');
  const resultPanel = document.getElementById('resultPanel');
  const resultMinutes = document.getElementById('resultMinutes');
  const resultNote = document.getElementById('resultNote');
  const formError = document.getElementById('formError');
  const historyList = document.getElementById('historyList');
  const historyEmpty = document.getElementById('historyEmpty');
  const refreshHistory = document.getElementById('refreshHistory');
  const systemStatus = document.getElementById('systemStatus');
  const metricsCard = document.getElementById('metricsCard');
  const metricsList = document.getElementById('metricsList');

  const FALLBACK_OPTIONS = {
    weather: ['Clear', 'Rainy', 'Foggy', 'Snowy', 'Windy'],
    traffic_level: ['Low', 'Medium', 'High'],
    time_of_day: ['Morning', 'Afternoon', 'Evening', 'Night'],
    vehicle_type: ['Bike', 'Scooter', 'Car'],
  };

  async function apiGet(path) {
    const res = await fetch(`${API_BASE}/${path}`);
    return res.json();
  }

  async function apiPost(path, body) {
    const res = await fetch(`${API_BASE}/${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    return res.json();
  }

  function fillSelect(name, options) {
    const el = form.elements[name];
    if (!el) return;
    el.innerHTML = options
      .map((o) => `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`)
      .join('');
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function showError(msg) {
    formError.hidden = !msg;
    formError.textContent = msg || '';
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    submitBtn.querySelector('.btn-text').hidden = loading;
    submitBtn.querySelector('.btn-loader').hidden = !loading;
  }

  function getFormPayload() {
    const fd = new FormData(form);
    return {
      distance_km: parseFloat(fd.get('distance_km')),
      weather: fd.get('weather'),
      traffic_level: fd.get('traffic_level'),
      time_of_day: fd.get('time_of_day'),
      vehicle_type: fd.get('vehicle_type'),
      preparation_time_min: parseInt(fd.get('preparation_time_min'), 10),
      courier_experience_yrs: parseFloat(fd.get('courier_experience_yrs')),
    };
  }

  async function loadMeta() {
    try {
      const data = await apiGet('meta.php');
      if (data.success && data.meta?.categorical_options) {
        const opts = data.meta.categorical_options;
        fillSelect('weather', opts.Weather || FALLBACK_OPTIONS.weather);
        fillSelect('traffic_level', opts.Traffic_Level || FALLBACK_OPTIONS.traffic_level);
        fillSelect('time_of_day', opts.Time_of_Day || FALLBACK_OPTIONS.time_of_day);
        fillSelect('vehicle_type', opts.Vehicle_Type || FALLBACK_OPTIONS.vehicle_type);

        if (data.meta.metrics) {
          const m = data.meta.metrics;
          metricsCard.hidden = false;
          metricsList.innerHTML = `
            <li><span>MAE (test)</span><strong>${m.mae_minutes} min</strong></li>
            <li><span>RMSE (test)</span><strong>${m.rmse_minutes} min</strong></li>
            <li><span>R² score</span><strong>${m.r2_score}</strong></li>
            <li><span>Training samples</span><strong>${m.train_samples}</strong></li>
          `;
        }
        return;
      }
    } catch (_) { /* use fallback */ }

    fillSelect('weather', FALLBACK_OPTIONS.weather);
    fillSelect('traffic_level', FALLBACK_OPTIONS.traffic_level);
    fillSelect('time_of_day', FALLBACK_OPTIONS.time_of_day);
    fillSelect('vehicle_type', FALLBACK_OPTIONS.vehicle_type);
  }

  async function checkHealth() {
    try {
      const data = await apiGet('health.php');
      const c = data.checks || {};
      if (data.success) {
        systemStatus.textContent = 'System ready';
        systemStatus.className = 'status-badge ok';
      } else {
        const parts = [];
        if (!c.database) parts.push('DB');
        if (!c.model) parts.push('Model');
        if (!c.python) parts.push('Python');
        systemStatus.textContent = `Issues: ${parts.join(', ') || 'unknown'}`;
        systemStatus.className = 'status-badge warn';
      }
    } catch (e) {
      systemStatus.textContent = 'API unreachable';
      systemStatus.className = 'status-badge err';
    }
  }

  async function loadHistory() {
    try {
      const data = await apiGet('history.php?limit=15');
      if (!data.success || !data.predictions?.length) {
        historyList.innerHTML = '';
        historyEmpty.hidden = false;
        return;
      }
      historyEmpty.hidden = true;
      historyList.innerHTML = data.predictions
        .map(
          (p) => `
        <li class="history-item">
          <div class="eta">${p.predicted_delivery_min} min</div>
          <div class="meta">
            ${p.distance_km} km · ${p.weather} · ${p.traffic_level} traffic · ${p.vehicle_type}
            <br><small>${formatDate(p.created_at)}</small>
          </div>
        </li>`
        )
        .join('');
    } catch (_) {
      historyEmpty.textContent = 'Could not load history (check database).';
      historyEmpty.hidden = false;
    }
  }

  function formatDate(iso) {
    try {
      return new Date(iso).toLocaleString();
    } catch {
      return iso;
    }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    setLoading(true);
    resultPanel.hidden = true;

    try {
      const payload = getFormPayload();
      if (Number.isNaN(payload.distance_km) || payload.distance_km < 0) {
        throw new Error('Enter a valid distance.');
      }
      const data = await apiPost('predict.php', payload);
      if (!data.success) {
        throw new Error(data.error || 'Prediction failed');
      }
      resultMinutes.textContent = data.predicted_delivery_min;
      resultNote.textContent = data.warning || `Saved as prediction #${data.id || '—'}`;
      resultPanel.hidden = false;
      await loadHistory();
    } catch (err) {
      showError(err.message || 'Something went wrong');
    } finally {
      setLoading(false);
    }
  });

  sampleBtn.addEventListener('click', () => {
    form.distance_km.value = '9.5';
    form.preparation_time_min.value = '18';
    form.courier_experience_yrs.value = '4';
    if (form.weather.querySelector('option[value="Rainy"]')) form.weather.value = 'Rainy';
    if (form.traffic_level.querySelector('option[value="Medium"]')) form.traffic_level.value = 'Medium';
    if (form.time_of_day.querySelector('option[value="Evening"]')) form.time_of_day.value = 'Evening';
    if (form.vehicle_type.querySelector('option[value="Scooter"]')) form.vehicle_type.value = 'Scooter';
  });

  refreshHistory.addEventListener('click', loadHistory);

  loadMeta();
  checkHealth();
  loadHistory();
})();
