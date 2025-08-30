@extends('layouts.app')

@section('content')
  <h1 class="h3 mb-3">Cerca una città</h1>

  <form method="POST" action="{{ route('cities.store') }}" class="row g-2">
    @csrf

    <div class="col-12 col-sm-8">
      <label for="name" class="form-label">Nome città</label>
      <input
        type="text"
        id="name"
        name="name"
        class="form-control @error('name') is-invalid @enderror"
        placeholder="Es. Roma"
        value="{{ old('name') }}"
        autocomplete="off"
      >
      @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
      <small class="text-muted">
        Sono consentite lettere, spazi, apostrofi (’ o '), trattini (-) e punto (.).
      </small>
    </div>

    <div class="col-12 col-sm-4 d-flex align-items-end">
      <button type="submit" class="btn btn-dark w-100">Cerca</button>
    </div>
  </form>

  <hr class="my-4">

  <p class="text-muted mb-0">
    Dopo la ricerca verrai reindirizzato alla pagina delle statistiche della città trovata.
  </p>
@endsection