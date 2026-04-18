@extends('layout.main')

@section('title', 'Comunidades')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Comunidades</li>
@endsection

@section('content')

<div class="d-flex justify-content-between mb-4">
    <a href="" class="btn btn-lg btn-outline-danger">
        <i class="fas fa-file-pdf"></i>
    </a>
        <a href="" class="btn btn-lg btn-outline-success">
        <i class="fas fa-file-excel"></i>
    </a>
    <a href="" class="btn btn-lg btn-outline-primary">
        <i class="fas fa-file-word"></i>
    </a>
    <a href="">

    </a>
    <a href="{{ route('communities.create') }}" class="btn btn-lg btn-outline-warning">
        <i class="fas fa-plus"></i>
    </a>
</div>


<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-church me-1"></i>
        Comunidades
    </div>
    <div class="card-body">
        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Descripcion</th>
                    <th>Dueño</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Slug</th>
                    <th>Descripcion</th>
                    <th>Dueño</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </tfoot>
            <tbody>
                @foreach ($communities as $community)
                <tr>
                    <td>{{ $counter++ }}</td>
                    <td>{{ $community->name }}</td>
                    <td>{{ $community->slug }}</td>
                    <td>{{ $community->description }}</td>
                    <td>{{ $community->owner->name ?? 'No Owner' }}</td>
                    <td>{{ $community->is_active ? 'Activo' : 'Inactivo' }}</td>
                    <td>
                        <a href="{{ route('communities.show', $community->id) }}" class="btn btn-lg btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        {{-- <a href="{{ route('communities.edit', $community) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('communities.destroy', $community) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td> --}}
                </tr>
                @endforeach


            </tbody>
        </table>
    </div>
</div>
@endsection
