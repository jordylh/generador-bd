<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExcelAnalyzer;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DatabaseGeneratorController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx'
        ]);

        $filename = uniqid() . '.' . $request->file('excel_file')->getClientOriginalExtension();
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }
        // ✅ GUARDAR usando Storage
        $request->file('excel_file')->move(storage_path('app/temp'), $filename);

        $relativePath = 'temp/' . $filename;
        session(['uploaded_excel' => $relativePath]);
        return redirect()->route('preview');
    }

    public function preview()
{
        $relativePath = session('uploaded_excel');
        $absolutePath = storage_path('app/' . $relativePath);

        if (!$relativePath || !file_exists($absolutePath)) {
            return redirect()->route('home')->withErrors('El archivo no existe o la sesión expiró.');
        }

        $analyzer = new ExcelAnalyzer($absolutePath);
        //$analyzer->debugTables(); 
        $tables = $analyzer->loadAllSheets();
        //dd($preview); // <- Aquí

    return view('preview', compact('tables')); // Aquí pasamos 'tables' no 'preview'
    
}





    public function generate(Request $request)
    {
        $relativePath = session('uploaded_excel');
        $absolutePath = storage_path('app/' . $relativePath);

        if (!$relativePath || !file_exists($absolutePath)) {
            return redirect()->route('home')->withErrors('El archivo no existe o la sesión expiró.');
        }

        $analyzer = new ExcelAnalyzer($absolutePath);
        $sql = $analyzer->generateFullSQLWithInserts();

        return view('result', compact('sql'));
    }
}

