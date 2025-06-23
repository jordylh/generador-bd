<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExcelAnalyzer;
use Illuminate\Support\Str;

class ExcelAnalyzerTest extends TestCase
{
    protected $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta de un archivo Excel de prueba que debes crear con varias hojas, por ejemplo:
        // Sheet1: usuarios (id, nombre, email, fecha_registro)
        // Sheet2: productos (id, nombre, precio, stock)
        // Sheet3: ordenes (id, usuario_id, producto_id, cantidad, fecha_orden)
        $this->testFilePath = base_path('tests/fixtures/sample.xlsx');
    }

    public function test_load_all_sheets()
    {
        $analyzer = new ExcelAnalyzer($this->testFilePath);

        $tables = $analyzer->loadAllSheets();

        $this->assertIsIterable($tables);
        $this->assertArrayHasKey('usuarios', $tables->toArray());
        $this->assertArrayHasKey('productos', $tables->toArray());
        $this->assertArrayHasKey('ordenes', $tables->toArray());

        // Comprobar que la primera fila son headers (ejemplo)
        $usuariosHeaders = $tables['usuarios'][0]->toArray();
        $this->assertContains('id', $usuariosHeaders);
        $this->assertContains('nombre', $usuariosHeaders);
    }

    public function test_generate_full_sql_with_inserts()
    {
        $analyzer = new ExcelAnalyzer($this->testFilePath);

        $sql = $analyzer->generateFullSQLWithInserts();

        $this->assertIsString($sql);

        // Validar que incluya CREATE TABLE para usuarios
        $this->assertStringContainsString('CREATE TABLE `usuarios`', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);

        // Validar que incluya FOREIGN KEY para ordenes
        $this->assertStringContainsString('FOREIGN KEY (`usuario_id`)', $sql);
        $this->assertStringContainsString('FOREIGN KEY (`producto_id`)', $sql);

        // Validar que incluya INSERT INTO usuarios
        $this->assertStringContainsString('INSERT INTO `usuarios`', $sql);
        $this->assertStringContainsString('INSERT INTO `productos`', $sql);
        $this->assertStringContainsString('INSERT INTO `ordenes`', $sql);

        // Opcional: imprimir para ver (comentar en CI)
        // echo "\n\n--- SQL GENERADO ---\n\n" . $sql . "\n\n";
    }

    public function test_infer_columns_types()
    {
        $analyzer = new ExcelAnalyzer($this->testFilePath);

        // Tomamos una tabla y probamos inferColumns
        $tables = $analyzer->loadAllSheets();
        $headers = $tables['usuarios'][0]->toArray();
        $dataRows = $tables['usuarios']->slice(1);

        $columnsInfo = $this->callProtectedMethod($analyzer, 'inferColumns', [$headers, $dataRows]);

        $this->assertArrayHasKey('id', $columnsInfo);
        $this->assertEquals('INT', $columnsInfo['id']['type']);
        $this->assertEquals('VARCHAR', $columnsInfo['nombre']['type']);
    }

    // Helper para llamar métodos protegidos
    protected function callProtectedMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}

