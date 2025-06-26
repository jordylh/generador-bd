{{-- Extiende la plantilla base de AdminLTE --}}
@extends('adminlte::page')

{{-- Define el título de la pestaña del navegador --}}
@section('title', 'Resultado SQL')

{{-- Encabezado principal de la página --}}
@section('content_header')
    <h1>SQL Generado</h1>
@endsection

{{-- Contenido principal de la página --}}
@section('content')

    {{-- Muestra el contenido SQL generado dentro de un bloque <pre> para respetar el formato --}}
    <pre id="sql-content" style="white-space: pre-wrap; word-wrap: break-word;">
{{ $sql }}
    </pre>

    {{-- Botones para acciones posteriores --}}
    <div class="mb-3">
        {{-- Botón para volver al formulario y subir otro archivo --}}
        <a href="/" class="btn btn-primary">Enviar otro Excel</a>

        {{-- Botón para copiar el SQL al portapapeles --}}
        <button id="copy-btn" class="btn btn-primary">
            Copiar al Portapapeles
        </button>
    </div>
@endsection

{{-- Sección para scripts JavaScript --}}
@section('js')
<script>
    // Al hacer clic en el botón de copiar
    document.getElementById('copy-btn').addEventListener('click', function() {
        // Obtener el contenido del bloque <pre> que contiene el SQL
        let sqlText = document.getElementById('sql-content').innerText;

        // Usar la API moderna del navegador para copiar al portapapeles
        navigator.clipboard.writeText(sqlText).then(function() {
            // Mostrar mensaje de éxito
            alert('¡SQL copiado al portapapeles!');
        }).catch(function(err) {
            // Mostrar error si algo sale mal
            console.error('Error al copiar: ', err);
        });
    });
</script>
@endsection

