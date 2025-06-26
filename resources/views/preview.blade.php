{{-- Extiende la plantilla base de AdminLTE --}}
@extends('adminlte::page')

{{-- Sección principal de contenido --}}
@section('content')

{{-- Recorre cada hoja del archivo Excel como si fuera una "tabla" --}}
@foreach($tables as $tableName => $rows)

    {{-- Muestra el nombre de la hoja como título de tabla --}}
    <h3>Tabla: {{ $tableName }}</h3>

    {{-- Crea una tabla HTML con estilos de Bootstrap --}}
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                {{-- Primera fila contiene los encabezados de la tabla --}}
                @foreach($rows[0] as $header)
                    <th>{{ $header }}</th> {{-- Celda de encabezado --}}
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{-- Recorre todas las filas excepto la primera (que es encabezado) --}}
            @foreach($rows->slice(1) as $row)
                <tr>
                    {{-- Recorre cada celda de la fila y la imprime --}}
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- Formulario para enviar la solicitud de generar el SQL de la base de datos --}}
<form method="POST" action="{{ route('generate') }}">
    {{-- Token CSRF obligatorio en formularios POST --}}
    @csrf

    {{-- Botón que envía el formulario para generar el SQL --}}
    <button type="submit" class="btn btn-primary mt-3">Generar Base de Datos</button>
</form>

{{-- Fin de la sección de contenido --}}
@endsection

