<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('hr.nav.my_attendance') }}</title>
    <style>
        :root { --bg:#0f172a; --panel:#111827; --card:#1f2937; --text:#f8fafc; --muted:#94a3b8; --accent:#10b981; --danger:#ef4444; --border:#334155; }
        *{box-sizing:border-box} body{margin:0;font-family:Tahoma,Segoe UI,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        .wrap{max-width:560px;margin:0 auto;padding:1.25rem}
        .card{background:var(--panel);border:1px solid var(--border);border-radius:.85rem;padding:1rem;margin-bottom:1rem}
        h1{font-size:1.25rem;margin:0 0 .75rem}
        .muted{color:var(--muted);font-size:.9rem}
        .row{display:flex;justify-content:space-between;gap:1rem;margin:.35rem 0}
        .btn{display:inline-block;border:0;border-radius:.6rem;padding:.75rem 1rem;font:inherit;cursor:pointer;width:100%;margin-top:.5rem;font-weight:600}
        .btn-in{background:var(--accent);color:#042f2e}
        .btn-out{background:#2563eb;color:#fff}
        .btn:disabled{opacity:.5;cursor:not-allowed}
        .ok{color:var(--accent)}.bad{color:var(--danger)}
        .msg{padding:.65rem .75rem;border-radius:.5rem;margin:.5rem 0;border:1px solid var(--border)}
        .msg.error{background:rgba(239,68,68,.12);color:#fecaca}
        .msg.success{background:rgba(16,185,129,.12);color:#a7f3d0}
        a.back{color:var(--muted);text-decoration:none;font-size:.9rem}
    </style>
</head>
<body>
<div class="wrap" id="hr-attendance-app"
     data-api-base="{{ $apiBase }}"
     data-locale="{{ $locale }}">
    <a class="back" href="{{ url('/app') }}">← {{ __('hr.actions.back_to_dashboard') }}</a>
    <div class="card" style="margin-top:1rem">
        <h1>{{ __('hr.nav.my_attendance') }}</h1>
        @if(! $employee)
            <div class="msg error">{{ __('hr.validation.user_not_linked_employee') }}</div>
        @else
            <div class="row"><span class="muted">{{ __('hr.fields.employee') }}</span><strong>{{ $employee->full_name }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.employee_number') }}</span><strong>{{ $employee->employee_number }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.date') }}</span><strong id="today-date">{{ $now->toDateString() }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.schedule') }}</span><strong>{{ $schedule?->name ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.expected_time') }}</span>
                <strong>
                    @if($window && $window['is_working_day'])
                        {{ $window['start']?->format('H:i') }} – {{ $window['end']?->format('H:i') }}
                    @else
                        {{ __('hr.labels.day_off') }}
                    @endif
                </strong>
            </div>
            <div class="row"><span class="muted">{{ __('hr.fields.location') }}</span><strong>{{ $location?->name ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.status') }}</span><strong id="status-label">{{ $today?->status?->label() ?: __('hr.labels.not_checked_in') }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.check_in_at') }}</span><strong id="check-in-at">{{ $today?->check_in_at?->format('H:i:s') ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.check_out_at') }}</span><strong id="check-out-at">{{ $today?->check_out_at?->format('H:i:s') ?: '—' }}</strong></div>
            <div class="row"><span class="muted">{{ __('hr.fields.geo_status') }}</span><strong id="geo-status" class="muted">{{ __('hr.labels.locating') }}</strong></div>
            <div id="flash"></div>
            <button class="btn btn-in" id="btn-check-in" type="button" @disabled(!$employee || ($today && $today->check_in_at))>{{ __('hr.actions.check_in') }}</button>
            <button class="btn btn-out" id="btn-check-out" type="button" @disabled(!$employee || !$today?->check_in_at || $today?->check_out_at)>{{ __('hr.actions.check_out') }}</button>
        @endif
    </div>
</div>
@if($employee)
<script>
(() => {
  const root = document.getElementById('hr-attendance-app');
  const apiBase = root.dataset.apiBase;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const flash = document.getElementById('flash');
  const geoStatus = document.getElementById('geo-status');
  const btnIn = document.getElementById('btn-check-in');
  const btnOut = document.getElementById('btn-check-out');
  let lastPos = null;

  const t = {
    locating: @json(__('hr.labels.locating')),
    inside: @json(__('hr.labels.inside_geofence')),
    outside: @json(__('hr.labels.outside_geofence')),
    geoDenied: @json(__('hr.validation.geolocation_denied')),
    successIn: @json(__('hr.notifications.checked_in')),
    successOut: @json(__('hr.notifications.checked_out')),
  };

  function show(msg, type='error') {
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

  async function refreshDistance() {
    if (!lastPos) return;
    try {
      const url = `${apiBase}/distance?latitude=${lastPos.latitude}&longitude=${lastPos.longitude}`;
      const res = await fetch(url, { headers: headers(), credentials: 'same-origin' });
      const json = await res.json();
      const d = json.data || {};
      if (!d.has_location) {
        geoStatus.textContent = '—';
        geoStatus.className = 'muted';
        return;
      }
      const label = d.inside ? t.inside : t.outside;
      geoStatus.textContent = `${label} (~${d.distance_meters}m / ${d.allowed_radius_meters}m)`;
      geoStatus.className = d.inside ? 'ok' : 'bad';
    } catch (e) {
      geoStatus.textContent = t.geoDenied;
      geoStatus.className = 'bad';
    }
  }

  function watchGeo() {
    if (!navigator.geolocation) {
      geoStatus.textContent = t.geoDenied;
      geoStatus.className = 'bad';
      return;
    }
    navigator.geolocation.watchPosition((pos) => {
      lastPos = {
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude,
        accuracy: pos.coords.accuracy,
      };
      refreshDistance();
    }, () => {
      geoStatus.textContent = t.geoDenied;
      geoStatus.className = 'bad';
    }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
  }

  async function punch(path, successMsg) {
    flash.innerHTML = '';
    if (!lastPos) {
      show(t.geoDenied);
      return;
    }
    try {
      const res = await fetch(`${apiBase}/${path}`, {
        method: 'POST',
        headers: headers(),
        credentials: 'same-origin',
        body: JSON.stringify({
          latitude: lastPos.latitude,
          longitude: lastPos.longitude,
          accuracy: lastPos.accuracy,
          captured_at: new Date().toISOString(),
        }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg = json.message || (json.errors && Object.values(json.errors).flat()[0]) || 'Error';
        show(msg, 'error');
        return;
      }
      show(successMsg, 'success');
      const data = json.data || {};
      if (data.check_in_at) document.getElementById('check-in-at').textContent = new Date(data.check_in_at).toLocaleTimeString();
      if (data.check_out_at) document.getElementById('check-out-at').textContent = new Date(data.check_out_at).toLocaleTimeString();
      if (data.status) document.getElementById('status-label').textContent = data.status;
      if (path === 'check-in') { btnIn.disabled = true; btnOut.disabled = false; }
      if (path === 'check-out') { btnOut.disabled = true; }
    } catch (e) {
      show(e.message || 'Error');
    }
  }

  btnIn?.addEventListener('click', () => punch('check-in', t.successIn));
  btnOut?.addEventListener('click', () => punch('check-out', t.successOut));
  watchGeo();
})();
</script>
@endif
</body>
</html>
