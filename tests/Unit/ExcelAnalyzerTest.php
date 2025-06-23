<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExcelAnalyzer;
use Illuminate\Support\Facades\Storage;

class ExcelAnalyzerTest extends TestCase
{
    protected $samplePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta del archivo Excel de prueba dentro de storage/testing
        $this->samplePath = storage_path('testing/sample.xlsx');

        // Asegurarse que el archivo existe antes de correr tests
        if (!file_exists($this->samplePath)) {
            $this->markTestSkipped('Archivo de prueba sample.xlsx no encontrado en storage/testing.');
        }
    }

    /** @test */
    public function it_loads_all_sheets_and_returns_collections()
    {
        $analyzer = new ExcelAnalyzer($this->samplePath);

        $tables = $analyzer->loadAllSheets();

        $this->assertIsIterable($tables);
        $this->assertNotEmpty($tables);

        foreach ($tables as $sheetName => $rows) {
            $this->assertIsString($sheetName);
            $this->assertNotEmpty($rows);
            $this->assertIsIterable($rows);
        }
    }

    /** @test */
    public function it_detects_column_types_and_keys_and_generates_sql()
    {
        $analyzer = new ExcelAnalyzer($this->samplePath);

        $sql = $analyzer->generateFullSQL();

        $this->assertIsString($sql);
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);

        // Puedes agregar aserciones más específicas según tu sample.xlsx, por ejemplo:
        $this->assertStringContainsString('FOREIGN KEY', $sql);

        // O verificar nombres de tablas y columnas esperadas en el SQL
        $this->assertMatchesRegularExpression('/CREATE TABLE `\w+`/', $sql);
    }
}
