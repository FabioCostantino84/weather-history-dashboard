@extends('layouts.app')

@section('content')
  {{-- Titolo + intro --}}
  <h1 class="h3 mb-2">Weather History Dashboard</h1>
  <p class="text-muted">Cerca una città e scegli un intervallo date: vedrai statistiche e la tabella con le <strong>medie giornaliere</strong>.</p>

  {{-- Messaggi flash (eventuali errori/ok) --}}
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  {{-- FORM: città + from/to (GET sulla stessa pagina) --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('dashboard') }}" class="row g-2">
        {{-- Indica che il form è stato inviato (serve alla DashboardRequest) --}}
        <input type="hidden" name="submitted" value="1">
  
        {{-- Riga 1: campo CITTÀ a tutta larghezza --}}
        <div class="col-12">
          <label for="name" class="form-label">Città</label>
          <input
            type="text"
            id="name"
            name="name"
            class="form-control @error('name') is-invalid @enderror"
            placeholder="Es. Roma"
            value="{{ old('name', $nameInput ?? '') }}"
            autocomplete="off"
            required
          >
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
  
        {{-- Riga 2: le DATE, una accanto all’altra (su mobile vanno a capo) --}}
        <div class="col-12 col-sm-6">
          <label for="from" class="form-label">Da (incluso)</label>
          <input
            type="date"
            id="from"
            name="from"
            class="form-control @error('from') is-invalid @enderror"
            value="{{ old('from', $fromInput ?? '') }}"
          >
          @error('from')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
  
        <div class="col-12 col-sm-6">
          <label for="to" class="form-label">A (incluso)</label>
          <input
            type="date"
            id="to"
            name="to"
            class="form-control @error('to') is-invalid @enderror"
            value="{{ old('to', $toInput ?? '') }}"
          >
          @error('to')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
  
        {{-- Pulsante invio --}}
        <div class="col-12 mt-2">
          <button type="submit" class="btn btn-dark">Vai</button>
          <small class="text-muted ms-2">Se lasci vuote le date, useremo gli ultimi 7 giorni.</small>
        </div>
      </form>
    </div>
  </div>
  
  {{-- Messaggi flash (errori/ok) sotto al form --}}
  
  @if(!empty($error))
    <div class="alert alert-danger">{{ $error }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  

  {{-- Se non abbiamo ancora fatto una ricerca, non mostriamo il resto --}}
  @if(empty($city))
    <p class="text-muted">Suggerimento: prova con <em>Roma</em>, <em>Milano</em>, <em>Napoli</em>…</p>
  @else
    {{-- Info città scelte --}}
    <h2 class="h5 mb-1">Risultati — {{ $city->name }} ({{ $city->country ?? 'n/d' }})</h2>
    <p class="text-muted">
      Lat: {{ number_format($city->latitude, 4, ',', ' ') }} ·
      Lon: {{ number_format($city->longitude, 4, ',', ' ') }} ·
      Intervallo: {{ $fromInput }} → {{ $toInput }}
    </p>

    {{-- Box statistiche --}}
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <div class="card h-100 text-center">
          <div class="card-body">
            <div class="text-muted">Media (°C)</div>
            <div class="fs-4 fw-semibold">
              {{ isset($stats['avg']) && $stats['avg'] !== null ? number_format($stats['avg'], 1, ',', '') : 'n/d' }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 text-center">
          <div class="card-body">
            <div class="text-muted">Min (°C)</div>
            <div class="fs-4 fw-semibold">
              {{ isset($stats['min']) && $stats['min'] !== null ? number_format($stats['min'], 1, ',', '') : 'n/d' }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="card h-100 text-center">
          <div class="card-body">
            <div class="text-muted">Max (°C)</div>
            <div class="fs-4 fw-semibold">
              {{ isset($stats['max']) && $stats['max'] !== null ? number_format($stats['max'], 1, ',', '') : 'n/d' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabella medie giornaliere --}}
    <div class="card">
      <div class="card-body">
        <h3 class="h5">Temperature giornaliere (media per giorno)</h3>

        @if(($dailyRows ?? collect())->isEmpty())
          <p class="text-muted mb-0">Nessun dato nell'intervallo selezionato.</p>
        @else
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Giorno</th>
                  <th>Media (°C)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($dailyRows as $d)
                  <tr>
                    <td>{{ \Carbon\Carbon::parse($d->day)->format('Y-m-d') }}</td>
                    <td>{{ $d->avg_temp !== null ? number_format((float)$d->avg_temp, 1, ',', '') : 'n/d' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <small class="text-muted">Giorni totali: {{ $dailyRows->count() }}</small>
        @endif
      </div>
    </div>
  @endif
@endsection
