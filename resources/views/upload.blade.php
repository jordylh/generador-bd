@extends('adminlte::page')

@section('content')
    <h1>Subir Archivo Excel</h1>
    <form method="POST" action="{{ route('upload') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="excel_file" required>
        <button class="btn btn-primary" type="submit">Cargar</button>
    </form>
@endsection
