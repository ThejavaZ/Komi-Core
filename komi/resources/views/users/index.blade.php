@extends('layout.main')

@section('title', 'Usuarios')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('home') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Usuarios</li>
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
        <i class="fas fa-users"></i>
        Usuarios
    </div>
    <div class="card-body">
        <table id="datatablesSimple">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Idioma</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                    <th>Reportes</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Idioma</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                    <th>Reportes</th>
                </tr>
            </tfoot>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $counter++ }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @switch($user->role)
                            @case(1)
                                Super Admin
                                @break
                            
                                @case(2)
                                    Administrador
                                @break

                                @case(3)
                                    Operador
                                @break
                        
                            @default
                                No info
                        @endswitch
                    </td>
                    <td>
                        @switch($user->language)
                            @case(1)
                                Español
                                @break

                            @case(2)
                                Inglés
                            @break
                            @default
                                No info
                        @endswitch
                    </td>
                    <td>
                        @switch($user->status)
                            @case(1)
                                Activo
                                @break
                            @case(2)
                                Inactivo
                            @break
                            @default
                                No info
                        @endswitch
                    </td>
                    <td>
                        <a href="{{ route('communities.show', $user->id) }}" class="btn btn-lg btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        {{-- <a href="{{ route('communities.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('communities.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>  --}}
                    </td>
                    <td>
                        <a href="" class="btn btn-lg btn-outline-danger">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <a href="" class="btn btn-lg btn-outline-success">
                            <i class="fas fa-file-excel"></i>
                        </a>
                        <a href="" class="btn btn-lg btn-outline-primary">
                            <i class="fas fa-file-word"></i>
                        </a>
                        <a href="" class="btn btn-lg btn-outline-info">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </td>
                </tr>
                @endforeach


            </tbody>
        </table>
    </div>
</div>
@endsection
