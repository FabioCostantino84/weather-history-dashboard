@extends('layouts.app')

@section('content')
  <h1 class="h3 mb-1">Statistiche meteo — {{ $city->name }}</h1>
  <p class="text-muted mb-3">
    Paese: {{ $city->country ?? 'n/d' }} ·
    Lat: {{ number_format($city->latitude, 4, ',', ' ') }} ·
    Lon: {{ number_format($city->longitude, 4, ',', ' ') }}
  </p>

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('cities.stats', $city) }}" class="row g-2 align-items-end">
        <div class="col-12 col-sm-4">
          <label for="from" class="form-label">Da (incluso)</label>
          <input type="date" id="from" name="from" class="form-control" value="{{ $fromInput ?? '' }}">
        </div>
        <div class="col-12 col-sm-4">
          <label for="to" class="form-label">A (incluso)</label>
          <input type="date" id="to" name="to" class="form-control" value="{{ $toInput ?? '' }}">
        </div>
        <div class="col-12 col-sm-4">
          <button type="submit" class="btn btn-dark w-100">Aggiorna</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="card h-100 text-center">
        <div class="card-body">
          <div class="text-muted">Media (°C)</div>
          <div class="fs-4 fw-semibold">{{ $stats['avg'] ?? 'n/d' }}</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100 text-center">
        <div class="card-body">
          <div class="text-muted">Min (°C)</div>
          <div class="fs-4 fw-semibold">{{ $stats['min'] ?? 'n/d' }}</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100 text-center">
        <div class="card-body">
          <div class="text-muted">Max (°C)</div>
          <div class="fs-4 fw-semibold">{{ $stats['max'] ?? 'n/d' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3 class="h5">Dati orari</h3>
      @if(($rows ?? collect())->isEmpty())
        <p class="text-muted mb-0">Nessun dato nell'intervallo selezionato.</p>
      @else
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Ora</th>
                <th>Temperatura (°C)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rows as $r)
                <tr>
                  <td>{{ \Carbon\Carbon::parse($r->recorded_at)->format('Y-m-d H:i') }}</td>
                  <td>{{ $r->temperature ?? 'n/d' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <small class="text-muted">Totale righe: {{ $rows->count() }}</small>
      @endif
    </div>
  </div>
@endsection
