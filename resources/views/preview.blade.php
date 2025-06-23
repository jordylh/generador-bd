@extends('adminlte::page')

@section('content')

@foreach($tables as $tableName => $rows)
    <h3>Tabla: {{ $tableName }}</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                @foreach($rows[0] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows->slice(1) as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

<form method="POST" action="{{ route('generate') }}">
    @csrf
    <button type="submit" class="btn btn-primary mt-3">Generar Base de Datos</button>
</form>


@endsection
