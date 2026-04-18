@extends('layout.main')

@section('title', $community->name)

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('home') }}">Dashboard</a>
</li>
<li class="breadcrumb breadcrumb-item">
    <a href="{{ route('communities.index') }}">Comunidades</a>
</li>
<li class="breadcrumb-item active">{{ $community->name }}</li>
@endsection

@section('content')

<form action="" class="card"> 
    <div class="card-header">
        <i class="fas fa-church me-1"></i>
        Comunidades
    </div>

    <div class="card-body">
        <label for="" class="form-label">Nombre</label>
        <input type="text" name="" id="" class="form-control" value="{{ $community->name }}" >
        <label for="" class="form-label">Descripcion</label>
        <textarea name="" id="" class="form-control" width="300" rows="5" style="resize: none"></textarea>
        <label for="" class="form-label">Icono</label>
        <input type="file" name="" id="" class="form-control">
        <label for="" class="form-label">Banner</label>
        <input type="file" name="" id="" class="form-control">
        <label for="" class="form-label">Dueño</label>
        <select name="" id="" class="form-select">
            <option value="" selected disabled>Eliga una opcion...</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
        <label for="" class="form-label">Estado</label>
        <select name="" id="" class="form-select">
            <option value="" selected disabled>Eliga una opcion...</option>
            <option value=""></option>
            <option value=""></option>
            <option value=""></option>
        </select>

    </div>

    <div class="card-footer d-flex justify-content-between">
        <button class="btn btn-lg btn-primary">
            <i class="fas fa-save"></i>
        </button>

        <a href="{{ route('communities.index') }}" class="btn btn-lg btn-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</form>

@endsection