<?php

namespace Tests\Unit;

use App\Services\ExcelAnalyzer;
use Tests\TestCase;

class ExcelAnalyzerTest extends TestCase
{
    public function test_generates_sql_from_sample_excel()
    {
        // 1️⃣ Ruta al archivo Excel de prueba
        $filePath = base_path('tests/Fixtures/sample.xlsx');

        // 2️⃣ Asegura que el archivo existe
        $this->assertFileExists($filePath);

        // 3️⃣ Crea la instancia del analizador usando el archivo de prueba
        $analyzer = new ExcelAnalyzer($filePath);

        // 4️⃣ Genera el SQL
        $sql = $analyzer->generateSQL();

        // 5️⃣ Verifica que contenga CREATE TABLE
        $this->assertStringContainsString('CREATE TABLE', $sql);
    }
}
