@extends('adminlte::page')

@section('title', 'Resultado SQL')

@section('content_header')
    <h1>SQL Generado</h1>
@endsection

@section('content')
    

    <pre id="sql-content" style="white-space: pre-wrap; word-wrap: break-word;">
{{ $sql }}
    </pre>

    <div class="mb-3">
        <a href="/" class="btn btn-primary">Enviar otro Excel</a>
        <button id="copy-btn" class="btn btn-primary">
            Copiar al Portapapeles
        </button>
    </div>
@endsection

@section('js')
<script>
    document.getElementById('copy-btn').addEventListener('click', function() {
        // Obtener el texto del <pre>
        let sqlText = document.getElementById('sql-content').innerText;

        // Usar Clipboard API moderna
        navigator.clipboard.writeText(sqlText).then(function() {
            alert('¡SQL copiado al portapapeles!');
        }).catch(function(err) {
            console.error('Error al copiar: ', err);
        });
    });
</script>
@endsection
