<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('hr.nav.my_attendance') }}</title>
    <style>
        :root {
            --bg: #f1f5f9;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #059669;
            --accent-soft: #ecfdf5;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --warn: #d97706;
            --warn-soft: #fffbeb;
            --info: #2563eb;
            --info-soft: #eff6ff;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #ecfdf5 0%, var(--bg) 40%);
            color: var(--text);
            min-height: 100vh;
        }
        .wrap { max-width: 640px; margin: 0 auto; padding: 1.25rem; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.15rem 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }
        h1 { font-size: 1.35rem; margin: 0 0 .35rem; }
        .subtitle { color: var(--muted); font-size: .92rem; margin: 0 0 1rem; }
        .muted { color: var(--muted); font-size: .9rem; }
        .row { display: flex; justify-content: space-between; gap: 1rem; margin: .4rem 0; align-items: baseline; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem .85rem;
            border-radius: .75rem;
            font-weight: 600;
            font-size: .95rem;
            margin: .75rem 0 1rem;
        }
        .status-pill.ok { background: var(--accent-soft); color: #047857; }
        .status-pill.warn { background: var(--warn-soft); color: #b45309; }
        .status-pill.bad { background: var(--danger-soft); color: #b91c1c; }
        .status-pill.info { background: var(--info-soft); color: #1d4ed8; }
        .status-pill.neutral { background: #f8fafc; color: #475569; border: 1px solid var(--border); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            border: 0; border-radius: .75rem; padding: .9rem 1rem; font: inherit; cursor: pointer;
            width: 100%; margin-top: .55rem; font-weight: 700; font-size: 1rem;
        }
        .btn-in { background: var(--accent); color: #fff; }
        .btn-out { background: var(--info); color: #fff; }
        .btn-ghost { background: #fff; color: var(--text); border: 1px solid var(--border); font-weight: 600; }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .msg { padding: .7rem .85rem; border-radius: .65rem; margin: .55rem 0; border: 1px solid var(--border); }
        .msg.error { background: var(--danger-soft); color: #991b1b; border-color: #fecaca; }
        .msg.success { background: var(--accent-soft); color: #065f46; border-color: #a7f3d0; }
        .msg.info { background: var(--info-soft); color: #1e3a8a; border-color: #bfdbfe; }
        a.back { color: var(--muted); text-decoration: none; font-size: .9rem; }
        .details { border-top: 1px solid var(--border); margin-top: .85rem; padding-top: .85rem; }
        .spinner {
            width: 1rem; height: 1rem; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff;
            border-radius: 50%; display: inline-block; animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .geo-ok { color: #047857; font-weight: 600; }
        .geo-bad { color: #b91c1c; font-weight: 600; }
        .note { font-size: .82rem; color: var(--muted); margin-top: .75rem; }
    </style>
</head>
<body>
@php
    $statusText = match ($uiState) {
        'not_linked' => __('hr.validation.user_not_linked_employee'),
        'inactive' => __('hr.labels.employee_inactive_state'),
        'incomplete_settings' => __('hr.labels.incomplete_settings'),
        'day_off' => __('hr.labels.day_off'),
        'checked_out' => __('hr.labels.checked_out'),
        'checked_in' => __('hr.labels.checked_in'),
        'late' => __('hr.labels.late'),
        default => __('hr.labels.not_checked_in'),
    };
    $statusClass = match ($uiState) {
        'checked_in' => 'ok',
        'checked_out' => 'info',
        'late' => 'warn',
        'day_off', 'incomplete_settings', 'not_linked', 'inactive' => 'bad',
        default => 'neutral',
    };
@endphp
<div class="wrap"
     id="hr-attendance-app"
     data-api-base="{{ $apiBase }}"
     data-locale="{{ $locale }}"
     data-can-check-in="{{ $canCheckIn ? '1' : '0' }}"
     data-can-check-out="{{ $canCheckOut ? '1' : '0' }}"
     data-employee-active="{{ $employeeActive ? '1' : '0' }}"
     data-ui-state="{{ $uiState }}"
     data-has-schedule="{{ $schedule ? '1' : '0' }}"
     data-has-location="{{ $location ? '1' : '0' }}"
     data-is-working-day="{{ ($window['is_working_day'] ?? false) ? '1' : '0' }}">
    <a class="back" href="{{ url('/app') }}">← {{ __('hr.actions.back_to_dashboard') }}</a>

    <div class="card" style="margin-top:1rem">
        <h1>{{ __('hr.nav.my_attendance') }}</h1>
        <p class="subtitle">{{ __('hr.labels.server_time_note') }}</p>

        @if($employee)
            <div class="row"><span class="muted">{{ __('hr.fields.employee') }}</span><strong>{{ $employee->full_name }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.employee_number') }}</span><strong>{{ $employee->employee_number }}</strong></div>
        @endif
        <div class="row"><span class="muted">{{ __('hr.fields.date') }}</span><strong id="today-date">{{ $now->toDateString() }}</strong></div>
        <div class="row"><span class="muted">{{ __('hr.labels.current_time') }}</span><strong id="clock">{{ $now->format('H:i:s') }}</strong></div>
        <div class="row"><span class="muted">{{ __('hr.fields.schedule') }}</span><strong>{{ $schedule?->name ?: '—' }}</strong></div>
        <div class="row"><span class="muted">{{ __('hr.fields.expected_time') }}</span>
            <strong>
                @if($window && ($window['is_working_day'] ?? false))
                    {{ $window['start']?->format('H:i') }} – {{ $window['end']?->format('H:i') }}
                @else
                    {{ __('hr.labels.day_off') }}
                @endif
            </strong>
        </div>
        <div class="row"><span class="muted">{{ __('hr.fields.location') }}</span><strong>{{ $location?->name ?: '—' }}</strong></div>

        <div id="status-pill" class="status-pill {{ $statusClass }}">{{ $statusText }}</div>

        <div class="details">
            <div class="row"><span class="muted">{{ __('hr.fields.status') }}</span><strong id="status-label">{{ $today?->status?->label() ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.check_in_at') }}</span><strong id="check-in-at">{{ $today?->check_in_at?->format('H:i:s') ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.check_out_at') }}</span><strong id="check-out-at">{{ $today?->check_out_at?->format('H:i:s') ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.late_minutes') }}</span><strong id="late-minutes">{{ $today?->late_minutes ?? '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.worked_minutes') }}</span><strong id="worked-minutes">{{ $today?->worked_minutes ?? '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.geo_status') }}</span><strong id="geo-status" class="muted">{{ __('hr.labels.locating') }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.accuracy') ?? 'Accuracy' }}</span><strong id="geo-accuracy" class="muted">—</strong></div>
        </div>

        <div id="flash"></div>

        @if($employee && $employeeActive && ($window['is_working_day'] ?? false) && $schedule && $location)
            <button class="btn btn-in" id="btn-check-in" type="button"
                @disabled(! $canCheckIn || ($today && $today->check_in_at))>
                <span class="btn-label">{{ __('hr.actions.check_in') }}</span>
            </button>
            <button class="btn btn-out" id="btn-check-out" type="button"
                @disabled(! $canCheckOut || ! $today?->check_in_at || $today?->check_out_at)>
                <span class="btn-label">{{ __('hr.actions.check_out') }}</span>
            </button>
            <button class="btn btn-ghost" id="btn-retry-geo" type="button">{{ __('hr.labels.retry_location') }}</button>
        @endif
    </div>
</div>

<script type="application/json" id="hr-attendance-i18n">{!! json_encode($i18n, JSON_UNESCAPED_UNICODE) !!}</script>

@if($employee && $employeeActive)
<script>
(() => {
  const root = document.getElementById('hr-attendance-app');
  if (!root || root.dataset.bound === '1') return;
  root.dataset.bound = '1';

  const apiBase = root.dataset.apiBase;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const t = JSON.parse(document.getElementById('hr-attendance-i18n').textContent);
  const flash = document.getElementById('flash');
  const geoStatus = document.getElementById('geo-status');
  const geoAccuracy = document.getElementById('geo-accuracy');
  const statusPill = document.getElementById('status-pill');
  const btnIn = document.getElementById('btn-check-in');
  const btnOut = document.getElementById('btn-check-out');
  const btnRetry = document.getElementById('btn-retry-geo');
  const clock = document.getElementById('clock');

  let lastPos = null;
  let busy = false;

  function show(msg, type = 'error') {
    flash.innerHTML = `<div class="msg ${type}">${msg}</div>`;
  }

  function headers() {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  function formatTime(iso) {
    if (!iso) return '—';
    try {
      return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (e) {
      return iso;
    }
  }

  function setButtonLoading(btn, loading) {
    if (!btn) return;
    const label = btn.querySelector('.btn-label');
    if (loading) {
      btn.disabled = true;
      btn.dataset.prevHtml = label ? label.innerHTML : btn.innerHTML;
      if (label) label.innerHTML = `<span class="spinner"></span> ${t.sending}`;
      else btn.innerHTML = `<span class="spinner"></span> ${t.sending}`;
    } else if (btn.dataset.prevHtml) {
      if (label) label.innerHTML = btn.dataset.prevHtml;
      else btn.innerHTML = btn.dataset.prevHtml;
    }
  }

  function updateButtonsAfterPunch(path) {
    if (path === 'check-in') {
      if (btnIn) btnIn.disabled = true;
      if (btnOut && root.dataset.canCheckOut === '1') btnOut.disabled = false;
      statusPill.className = 'status-pill ok';
      statusPill.textContent = t.checkedIn;
    }
    if (path === 'check-out') {
      if (btnOut) btnOut.disabled = true;
      statusPill.className = 'status-pill info';
      statusPill.textContent = t.checkedOut;
    }
  }

  async function refreshDistance() {
    if (!lastPos) return;
    try {
      const url = `${apiBase}/distance?latitude=${encodeURIComponent(lastPos.latitude)}&longitude=${encodeURIComponent(lastPos.longitude)}`;
      const res = await fetch(url, { headers: headers(), credentials: 'same-origin' });
      const json = await res.json();
      const d = json.data || {};
      if (!d.has_location) {
        geoStatus.textContent = '—';
        geoStatus.className = 'muted';
        return;
      }
      const inside = !!d.inside;
      geoStatus.textContent = `${inside ? t.inside : t.outside} (${t.distanceHint
        .replace(':distance', d.distance_meters)
        .replace(':radius', d.allowed_radius_meters)})`;
      geoStatus.className = inside ? 'geo-ok' : 'geo-bad';
    } catch (e) {
      // المسافة تلميح فقط — لا تمنع التسجيل
    }
  }

  function applyPosition(coords) {
    lastPos = {
      latitude: coords.latitude,
      longitude: coords.longitude,
      accuracy: coords.accuracy,
    };
    geoStatus.textContent = t.locationReady;
    geoStatus.className = 'geo-ok';
    geoAccuracy.textContent = t.accuracy.replace(':meters', Math.round(coords.accuracy || 0));
    refreshDistance();
  }

  function geoError(err) {
    lastPos = null;
    let msg = t.geoDenied;
    if (err && typeof err.code === 'number') {
      if (err.code === 1) msg = t.geoDenied;
      else if (err.code === 2) msg = t.geoUnavailable;
      else if (err.code === 3) msg = t.geoTimeout;
    }
    if (!window.isSecureContext) {
      msg = t.httpsRequired;
    }
    geoStatus.textContent = msg;
    geoStatus.className = 'geo-bad';
    geoAccuracy.textContent = '—';
  }

  function acquireLocation(forcePrompt = false) {
    if (!navigator.geolocation) {
      geoStatus.textContent = t.geoUnsupported;
      geoStatus.className = 'geo-bad';
      return Promise.reject(new Error(t.geoUnsupported));
    }

    geoStatus.textContent = t.locating;
    geoStatus.className = 'muted';

    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition((pos) => {
        applyPosition(pos.coords);
        resolve(lastPos);
      }, (err) => {
        geoError(err);
        reject(err);
      }, {
        enableHighAccuracy: true,
        maximumAge: forcePrompt ? 0 : 10000,
        timeout: 20000,
      });
    });
  }

  async function punch(path, successMsg) {
    if (busy) return;
    flash.innerHTML = '';
    const btn = path === 'check-in' ? btnIn : btnOut;

    busy = true;
    setButtonLoading(btn, true);

    try {
      if (!lastPos) {
        await acquireLocation(true);
      }
      if (!lastPos) {
        show(t.geoRequired, 'error');
        return;
      }

      if (path === 'check-out' && !window.confirm(t.confirmOut)) {
        return;
      }

      const res = await fetch(`${apiBase}/${path}`, {
        method: 'POST',
        headers: headers(),
        credentials: 'same-origin',
        body: JSON.stringify({
          latitude: lastPos.latitude,
          longitude: lastPos.longitude,
          accuracy: lastPos.accuracy,
        }),
      });

      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg = json.message
          || (json.errors && Object.values(json.errors).flat()[0])
          || t.error;
        show(msg, 'error');
        return;
      }

      show(successMsg, 'success');
      const data = json.data || {};
      document.getElementById('check-in-at').textContent = formatTime(data.check_in_at);
      document.getElementById('check-out-at').textContent = formatTime(data.check_out_at);
      document.getElementById('status-label').textContent = data.status_label
        || (t.statusLabels && t.statusLabels[data.status])
        || data.status
        || '—';
      document.getElementById('late-minutes').textContent = data.late_minutes ?? '—';
      document.getElementById('worked-minutes').textContent = data.worked_minutes ?? '—';
      if (data.status === 'late') {
        statusPill.className = 'status-pill warn';
        statusPill.textContent = t.late;
      }
      updateButtonsAfterPunch(path);
    } catch (e) {
      show((e && e.message) || t.error, 'error');
    } finally {
      busy = false;
      setButtonLoading(btn, false);
      if (path === 'check-in' && document.getElementById('check-in-at').textContent !== '—') {
        if (btnIn) btnIn.disabled = true;
      }
      if (path === 'check-out' && document.getElementById('check-out-at').textContent !== '—') {
        if (btnOut) btnOut.disabled = true;
      }
    }
  }

  btnIn?.addEventListener('click', () => punch('check-in', t.successIn));
  btnOut?.addEventListener('click', () => punch('check-out', t.successOut));
  btnRetry?.addEventListener('click', () => acquireLocation(true).catch(() => {}));

  // تحديد الموقع عند أول تفاعل جاهز — دون watchPosition المستمر
  acquireLocation(false).catch(() => {});

  setInterval(() => {
    if (clock) clock.textContent = new Date().toLocaleTimeString();
  }, 1000);
})();
</script>
@endif
</body>
</html>
