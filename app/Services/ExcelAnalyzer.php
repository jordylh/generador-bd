<?php

namespace App\Services;

// Importamos las clases necesarias para trabajar con Excel
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ExcelAnalyzer
{
    protected $path;     // Ruta absoluta al archivo Excel
    protected $tables;   // Colección con todas las hojas del Excel procesadas

    public function __construct($absolutePath)
    {
        $this->path = $absolutePath;

        // Verificamos que el archivo exista
        if (!file_exists($this->path)) {
            throw new \Exception("Archivo no encontrado en: {$this->path}");
        }

        // Leemos el contenido del Excel en colecciones de Laravel
        $collections = Excel::toCollection(null, $this->path);
        
        // Cargamos el archivo Excel para obtener nombres de las hojas
        $reader = IOFactory::load($this->path);
        $sheetNames = $reader->getSheetNames();

        $this->tables = collect(); // Inicializamos la colección de tablas

        // Recorremos todas las hojas del archivo
        foreach ($sheetNames as $index => $name) {
            $rows = $collections[$index];

            // Eliminamos filas vacías
            $rows = $rows->filter(function ($row) {
                return $row->filter(fn($cell) => !is_null($cell) && trim($cell) !== '')->isNotEmpty();
            })->values();

            // Guardamos la hoja con su nombre como clave
            $this->tables[$name] = $rows;
        }

        // Código para debug que se puede activar:
        // foreach ($this->tables as $tableName => $rows) {
        //     dump("Tabla: $tableName");
        //     dump($rows->toArray());
        // }
    }

    // Devuelve todas las hojas con sus filas
    public function loadAllSheets()
    {
        return $this->tables;
    }

    // Genera SQL completo (CREATE TABLE + INSERTs) para todas las hojas
    public function generateFullSQLWithInserts()
    {
        $schemas = [];

        // 1. Inferir columnas y tipos para cada hoja
        foreach ($this->tables as $tableName => $rows) {
            $headers = $rows[0]->toArray(); // Primera fila como encabezados
            $dataRows = $rows->slice(1);    // El resto son datos

            $columnsInfo = $this->inferColumns($headers, $dataRows);
            $schemas[$tableName] = $columnsInfo;
        }

        // 2. Detectar llaves primarias
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectPrimaryKey($columnsInfo);
        }
        unset($columnsInfo);

        // 3. Detectar llaves foráneas
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectForeignKeys($columnsInfo, $schemas);
        }
        unset($columnsInfo);

        $sqlStatements = [];

        // 4. Generar SQL de creación de tablas
        foreach ($schemas as $tableName => $columnsInfo) {
            $sqlStatements[] = $this->createTableSQL($tableName, $columnsInfo);
        }

        // 5. Generar SQL de inserts con datos
        foreach ($this->tables as $tableName => $rows) {
            $headers = $rows[0]->toArray();
            $dataRows = $rows->slice(1);

            $sqlStatements[] = $this->createInsertSQL($tableName, $headers, $dataRows);
        }

        // Unir todo el SQL generado
        return implode("\n\n", $sqlStatements);
    }

    // Infere el tipo de cada columna basado en los valores
    protected function inferColumns($headers, $dataRows)
    {
        $columns = [];

        foreach ($headers as $i => $colName) {
            $colValues = $dataRows->pluck($i)->filter(fn($v) => $v !== null && $v !== '');

            $type = 'VARCHAR';
            $nullable = $colValues->count() < $dataRows->count(); // Si hay valores faltantes, es nullable

            if ($colValues->every(fn($v) => is_numeric($v) && intval($v) == $v)) {
                $type = 'INT';
            } elseif ($colValues->every(fn($v) => $this->isValidDate($v))) {
                $type = 'DATE';
            } elseif ($colValues->every(fn($v) => is_numeric($v))) {
                $type = 'DECIMAL(10,2)';
            } else {
                // Calcular longitud máxima para VARCHAR
                $maxLength = max($colValues->map(fn($v) => strlen((string)$v))->toArray() ?: [1]);
                $maxLength = min($maxLength, 255);
            }

            $columns[$colName] = [
                'type' => $type,
                'length' => $maxLength ?? null,
                'nullable' => $nullable,
                'is_primary' => false,
                'is_foreign' => false,
                'references' => null,
            ];
        }

        return $columns;
    }

    // Detecta si hay una columna que debería ser llave primaria
    protected function detectPrimaryKey(array $columnsInfo)
    {
        foreach ($columnsInfo as $colName => &$info) {
            if (strtolower($colName) === 'id') {
                $info['is_primary'] = true;
                $info['nullable'] = false;
                break;
            }
        }
        return $columnsInfo;
    }

    // Detecta llaves foráneas (basado en columnas *_id que coincidan con otras tablas)
    protected function detectForeignKeys(array $columnsInfo, array $schemas)
    {
        foreach ($columnsInfo as $colName => &$info) {
            if ($info['is_primary']) continue;

            // Buscar columnas que terminen en _id
            if (preg_match('/^(.*)_id$/i', $colName, $matches)) {
                $refTable = $matches[1];

                // Probar con nombre singular y plural
                $singular = Str::singular($refTable);
                $plural = Str::plural($refTable);

                foreach ([$singular, $plural] as $candidate) {
                    if (isset($schemas[$candidate]) &&
                        isset($schemas[$candidate]['id']) &&
                        $schemas[$candidate]['id']['is_primary']) {

                        $info['is_foreign'] = true;
                        $info['references'] = $candidate;
                        $info['nullable'] = true;
                        break;
                    }
                }
            }
        }

        return $columnsInfo;
    }

    // Genera el SQL para CREATE TABLE
    protected function createTableSQL(string $tableName, array $columnsInfo)
    {
        $lines = [];
        $primaryKeys = [];

        foreach ($columnsInfo as $colName => $info) {
            $line = "`$colName` ";

            // Tipo de datos
            switch ($info['type']) {
                case 'VARCHAR':
                    $line .= "VARCHAR({$info['length']})";
                    break;
                case 'INT':
                    $line .= "INT";
                    break;
                case 'DATE':
                    $line .= "DATE";
                    break;
                case 'DECIMAL(10,2)':
                    $line .= "DECIMAL(10,2)";
                    break;
                default:
                    $line .= $info['type'];
            }

            $line .= $info['nullable'] ? " NULL" : " NOT NULL";

            $lines[] = $line;

            if ($info['is_primary']) {
                $primaryKeys[] = "`$colName`";
            }
        }

        $sql = "CREATE TABLE `$tableName` (\n    ";
        $sql .= implode(",\n    ", $lines);

        // Agregar PRIMARY KEY si existe
        if ($primaryKeys) {
            $sql .= ",\n    PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }

        // Agregar FOREIGN KEYs
        foreach ($columnsInfo as $colName => $info) {
            if ($info['is_foreign'] && $info['references']) {
                $sql .= ",\n    FOREIGN KEY (`$colName`) REFERENCES `{$info['references']}`(`id`)";
            }
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $sql;
    }

    // Verifica si un valor es una fecha válida
    protected function isValidDate($value)
    {
        if ($value instanceof \DateTime) return true;

        if (is_string($value)) {
            $time = strtotime($value);
            return $time !== false && checkdate(date('m', $time), date('d', $time), date('Y', $time));
        }

        return false;
    }

    // Genera el SQL para los INSERTs
    protected function createInsertSQL(string $tableName, array $headers, $dataRows)
    {
        if ($dataRows->isEmpty()) {
            return ""; // No hay datos
        }

        // Preparar columnas
        $columns = array_map(fn($col) => "`$col`", $headers);
        $columnsList = implode(", ", $columns);

        $values = [];

        // Recorrer las filas de datos
        foreach ($dataRows as $row) {
            $rowArray = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array)$row;

            // Escapar los valores para SQL
            $escapedValues = array_map(function ($value) {
                if (is_null($value)) return "NULL";
                return "'" . str_replace("'", "''", (string)$value) . "'";
            }, $rowArray);

            $values[] = "(" . implode(", ", $escapedValues) . ")";
        }

        $valuesList = implode(",\n", $values);

        return "INSERT INTO `$tableName` ($columnsList) VALUES\n$valuesList;";
    }

    // Método para depurar las tablas cargadas (muestra nombres y encabezados)
    public function debugTables()
    {
        foreach ($this->tables as $tableName => $rows) {
            dump("Tabla: $tableName");
            dump("Headers: ", $rows[0]);
        }
    }
}
