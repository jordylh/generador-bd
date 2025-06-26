{{-- Extiende la plantilla base de AdminLTE --}}
@extends('adminlte::page')

{{-- Define el contenido principal de la página --}}
@section('content')

    {{-- Título de la sección --}}
    <h1>Subir Archivo Excel</h1>

    {{-- Formulario para subir el archivo Excel --}}
    <form method="POST" action="{{ route('upload') }}" enctype="multipart/form-data">
        
        {{-- Token CSRF necesario para formularios POST en Laravel --}}
        @csrf

        {{-- Campo de tipo archivo para seleccionar el Excel --}}
        <input type="file" name="excel_file" required>

        {{-- Botón para enviar el formulario --}}
        <button class="btn btn-primary" type="submit">Cargar</button>
    </form>

{{-- Fin de la sección de contenido --}}
@endsection
