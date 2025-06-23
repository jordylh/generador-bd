<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ExcelAnalyzer
{
    protected $path;
    protected $tables; // aquí guardamos todas las hojas procesadas

    public function __construct($absolutePath)
    {
        $this->path = $absolutePath;

        if (!file_exists($this->path)) {
            throw new \Exception("Archivo no encontrado en: {$this->path}");
        }

        $collections = Excel::toCollection(null, $this->path);
        $reader = IOFactory::load($this->path);
        $sheetNames = $reader->getSheetNames();

        $this->tables = collect();
        foreach ($sheetNames as $index => $name) {
            $rows = $collections[$index];

            // Filtrar filas vacías
            $rows = $rows->filter(function ($row) {
                return $row->filter(fn($cell) => !is_null($cell) && trim($cell) !== '')->isNotEmpty();
            })->values();

            $this->tables[$name] = $rows;
        }
        //foreach ($this->tables as $tableName => $rows) {
        //    dump("Tabla: $tableName");
        //    dump($rows->toArray());
        //}

    }

    // Para el preview, devuelve todas las hojas (nombre => filas)
    public function loadAllSheets()
    {
        return $this->tables;
    }

    // Generar SQL para todas las tablas con claves, tipos y relaciones detectadas
    public function generateFullSQLWithInserts()
    {
        $schemas = [];
        foreach ($this->tables as $tableName => $rows) {
            $headers = $rows[0]->toArray();
            $dataRows = $rows->slice(1);

            $columnsInfo = $this->inferColumns($headers, $dataRows);
            $schemas[$tableName] = $columnsInfo;
        }

        // Detectar PK
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectPrimaryKey($columnsInfo);
        }
        unset($columnsInfo);

        // Detectar FK
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectForeignKeys($columnsInfo, $schemas);
        }
        unset($columnsInfo);

        $sqlStatements = [];

        // Crear tablas
        foreach ($schemas as $tableName => $columnsInfo) {
            $sqlStatements[] = $this->createTableSQL($tableName, $columnsInfo);
        }

        // Crear inserts
        foreach ($this->tables as $tableName => $rows) {
            $headers = $rows[0]->toArray();
            $dataRows = $rows->slice(1);

            $sqlStatements[] = $this->createInsertSQL($tableName, $headers, $dataRows);
        }

        return implode("\n\n", $sqlStatements);
    }


    protected function inferColumns($headers, $dataRows)
    {
        $columns = [];
        foreach ($headers as $i => $colName) {
            $colValues = $dataRows->pluck($i)->filter(fn($v) => $v !== null && $v !== '');

            // Aquí va tu lógica para determinar tipo, longitud, nullable...
            // Ejemplo:
            $type = 'VARCHAR';
            $nullable = $colValues->count() < $dataRows->count();

            if ($colValues->every(fn($v) => is_numeric($v) && intval($v) == $v)) {
                $type = 'INT';
            } elseif ($colValues->every(fn($v) => $this->isValidDate($v))) {
                $type = 'DATE';
            } elseif ($colValues->every(fn($v) => is_numeric($v))) {
                $type = 'DECIMAL(10,2)';
            } else {
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

    protected function detectForeignKeys(array $columnsInfo, array $schemas)
    {
        foreach ($columnsInfo as $colName => &$info) {
        if ($info['is_primary']) continue;

        if (preg_match('/^(.*)_id$/i', $colName, $matches)) {
            $refTable = $matches[1];

            // ✅ Probar singular y plural usando Str helper de Laravel
            $singular = Str::singular($refTable);
            $plural = Str::plural($refTable);

            foreach ([$singular, $plural] as $candidate) {
                if (isset($schemas[$candidate])) {
                    if (isset($schemas[$candidate]['id']) && $schemas[$candidate]['id']['is_primary']) {
                        $info['is_foreign'] = true;
                        $info['references'] = $candidate;
                        $info['nullable'] = true;
                        break; // una coincidencia es suficiente
                    }
                }
            }
        }
    }

    return $columnsInfo;
    }

    protected function createTableSQL(string $tableName, array $columnsInfo)
    {
        $lines = [];
        $primaryKeys = [];

        foreach ($columnsInfo as $colName => $info) {
            $line = "`$colName` ";

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

        if ($primaryKeys) {
            $sql .= ",\n    PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }

        foreach ($columnsInfo as $colName => $info) {
            if ($info['is_foreign'] && $info['references']) {
                $sql .= ",\n    FOREIGN KEY (`$colName`) REFERENCES `{$info['references']}`(`id`)";
            }
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        return $sql;
    }

    protected function isValidDate($value)
    {
        if ($value instanceof \DateTime) return true;

        if (is_string($value)) {
            $time = strtotime($value);
            return $time !== false && checkdate(date('m', $time), date('d', $time), date('Y', $time));
        }

        return false;
    }

    protected function createInsertSQL(string $tableName, array $headers, $dataRows)
    {
        if ($dataRows->isEmpty()) {
            return ""; // no hay datos para insertar
        }

        // Preparar columnas para el INSERT
        $columns = array_map(fn($col) => "`$col`", $headers);
        $columnsList = implode(", ", $columns);

        // Preparar valores para cada fila
        $values = [];

        foreach ($dataRows as $row) {
            // Cada fila puede ser colección o array, por seguridad convertimos a array simple
            $rowArray = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array)$row;

            // Escapar y preparar cada valor para SQL
            $escapedValues = array_map(function ($value) {
                if (is_null($value)) {
                    return "NULL";
                }
                // Escapa comillas simples y envuelve en comillas simples
                return "'" . str_replace("'", "''", (string)$value) . "'";
            }, $rowArray);

            $values[] = "(" . implode(", ", $escapedValues) . ")";
        }

        $valuesList = implode(",\n", $values);

        return "INSERT INTO `$tableName` ($columnsList) VALUES\n$valuesList;";
    }

    public function debugTables()
{
    foreach ($this->tables as $tableName => $rows) {
        dump("Tabla: $tableName");
        dump("Headers: ", $rows[0]);
    }
}
}
