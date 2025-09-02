@extends('layouts.app')

@section('content')
  <div class="page-content"><!-- tutto sopra l'overlay -->

    {{-- Titolo + intro sopra lo sfondo.
         Se abbiamo risultati ($city non è vuota) uso la variante compatta "hero-small"
         così form e statistiche restano subito visibili senza scroll. --}}
    <div class="hero {{ !empty($city) ? 'hero-small' : '' }} on-bg">
      <h1 class="h1 mb-1">{{ config('app.name', 'WEATHER HISTORY By Innovhead') }}</h1>
      <p class="subtitle">
        Cerca una città e scegli un intervallo: vedrai statistiche e le <strong>medie giornaliere</strong>.
      </p>
    </div>

    {{-- Messaggi flash (eventuali errori/ok) --}}
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(!empty($error))
      <div class="alert alert-danger">{{ $error }}</div>
    @endif

    {{-- FORM: città + from/to (GET sulla stessa pagina, con ancora #results) --}}
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="{{ route('dashboard') }}#results" class="row g-2">
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

    {{-- Se non abbiamo ancora fatto una ricerca, non mostriamo il resto --}}
    @if(empty($city))
      <p class="text-muted">Suggerimento: prova con <em>Roma</em>, <em>Milano</em>, <em>Napoli</em>…</p>
    @else
      {{-- Sezione risultati con ancoraggio e margine di scroll (niente salto “sotto” l’header) --}}
      <section id="results" class="scroll-target">
        <h2 class="h5 mb-1">Statistiche — {{ $city->name }} ({{ $city->country ?? 'n/d' }})</h2>

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
      </section>
    @endif

  </div> {{-- /.page-content --}}
@endsection