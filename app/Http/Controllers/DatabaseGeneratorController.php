<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExcelAnalyzer; // Servicio que analiza archivos Excel
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DatabaseGeneratorController extends Controller
{
    // Muestra la vista inicial con el formulario de carga
    public function index()
    {
        return view('upload'); // renderiza upload.blade.php
    }

    // Maneja la subida del archivo Excel
    public function upload(Request $request)
    {
        // Validamos que se haya subido un archivo Excel (xls o xlsx)
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx'
        ]);

        // Creamos un nombre único para el archivo subido
        $filename = uniqid() . '.' . $request->file('excel_file')->getClientOriginalExtension();

        // Definimos la ruta temporal donde se guardará el archivo
        $tempPath = storage_path('app/temp');

        // Creamos la carpeta si no existe
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        // Movemos el archivo a la carpeta temporal
        $request->file('excel_file')->move($tempPath, $filename);

        // Guardamos la ruta relativa del archivo en sesión para usarla después
        $relativePath = 'temp/' . $filename;
        session(['uploaded_excel' => $relativePath]);

        // Redirigimos al paso de vista previa
        return redirect()->route('preview');
    }

    // Muestra la vista previa de los datos del Excel
    public function preview()
    {
        // Recuperamos la ruta del archivo desde la sesión
        $relativePath = session('uploaded_excel');
        $absolutePath = storage_path('app/' . $relativePath);

        // Verificamos que el archivo exista
        if (!$relativePath || !file_exists($absolutePath)) {
            return redirect()->route('home')->withErrors('El archivo no existe o la sesión expiró.');
        }

        // ⏳ Evita que se corte el análisis si toma mucho tiempo
        set_time_limit(0);

        // Creamos una instancia de ExcelAnalyzer para leer el archivo
        $analyzer = new ExcelAnalyzer($absolutePath);

        // (Opcional) Mostrar información por consola para debug
        // $analyzer->debugTables();

        // Cargamos todas las hojas como colecciones
        $tables = $analyzer->loadAllSheets();

        // Mostramos la vista con las tablas
        return view('preview', compact('tables'));
    }

    // Genera y muestra el SQL basado en el archivo Excel
    public function generate(Request $request)
    {
        // Recuperamos la ruta del archivo desde la sesión
        $relativePath = session('uploaded_excel');
        $absolutePath = storage_path('app/' . $relativePath);

        // Verificamos que el archivo exista
        if (!$relativePath || !file_exists($absolutePath)) {
            return redirect()->route('home')->withErrors('El archivo no existe o la sesión expiró.');
        }
        
        // ⏳ Evita que se corte el análisis si toma mucho tiempo
        set_time_limit(0);

        // Instanciamos el analizador de Excel
        $analyzer = new ExcelAnalyzer($absolutePath);

        // Generamos todo el SQL (estructura + inserts)
        $sql = $analyzer->generateFullSQLWithInserts();

        // Mostramos la vista con el SQL generado
        return view('result', compact('sql'));
    }
}

