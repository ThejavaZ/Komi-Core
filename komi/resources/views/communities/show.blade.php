@extends('layout.main')

@section('title', $community->name)

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item">
    <a href="{{ route('communities.index') }}">Comunidades</a></li>
<li class="breadcrumb-item active">{{ $community->name }}</li>
@endsection

@section('content')

<div class="d-flex justify-content-between mb-4">
    <a href="" class="btn btn-lg btn-outline-danger">
        <i class="fas fa-file-pdf"></i>
    </a>
    <a href="">
        <i class="fas fa-file-word"></i>
    </a>
    <a href="">
        <i class="fas fa-file-excel"></i>
    </a>
    <a href="">
        <i class="fas fa-edit"></i>
    </a>
</div>

<div class="d-flex justify-content-center">
    <div class="card mb-4" style="width: 50%;">
        <div class="card-header">
            <i class="fas fa-church me-1"></i>
            {{ $community->name }}
        </div>
        <div class="card-body">


            <p>{{ $community->description }}</p>

        </div>
        <div class="card-footer">
            <a href="{{ route('communities.index') }}">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>
</div>

@endsection
