<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    }

    // Para el preview, devuelve todas las hojas (nombre => filas)
    public function loadAllSheets()
    {
        return $this->tables;
    }

    // Generar SQL para todas las tablas con claves, tipos y relaciones detectadas
    public function generateFullSQL()
    {
        $sqlStatements = [];

        // Inferir esquema
        $schemas = [];
        foreach ($this->tables as $tableName => $rows) {
            $headers = $rows[0];
            $dataRows = $rows->slice(1);

            $columnsInfo = $this->inferColumns($headers, $dataRows);
            $schemas[$tableName] = $columnsInfo;
        }

        // Detectar PK
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectPrimaryKey($columnsInfo);
        }

        // Detectar FK
        foreach ($schemas as $tableName => &$columnsInfo) {
            $columnsInfo = $this->detectForeignKeys($columnsInfo, $schemas);
        }

        // Crear SQL
        foreach ($schemas as $tableName => $columnsInfo) {
            $sqlStatements[] = $this->createTableSQL($tableName, $columnsInfo);
        }

        return implode("\n\n", $sqlStatements);
    }

    protected function inferColumns($headers, $dataRows)
    {
        $columns = [];
        foreach ($headers as $i => $colName) {
            $colValues = $dataRows->pluck($i)->filter(fn($v) => $v !== null && $v !== '');

            $type = 'VARCHAR';
            $maxLength = 1;
            $nullable = $colValues->count() < $dataRows->count();

            if ($colValues->every(fn($v) => is_numeric($v) && intval($v) == $v)) {
                $type = 'INT';
                $maxLength = null;
            } elseif ($colValues->every(fn($v) => $this->isValidDate($v))) {
                $type = 'DATE';
                $maxLength = null;
            } elseif ($colValues->every(fn($v) => is_numeric($v))) {
                $type = 'DECIMAL(10,2)';
                $maxLength = null;
            } else {
                $maxLength = max($colValues->map(fn($v) => strlen((string)$v))->toArray() ?: [1]);
                $maxLength = min($maxLength, 255);
            }

            $columns[$colName] = [
                'type' => $type,
                'length' => $maxLength,
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
                if (isset($schemas[$refTable])) {
                    if (isset($schemas[$refTable]['id']) && $schemas[$refTable]['id']['is_primary']) {
                        $info['is_foreign'] = true;
                        $info['references'] = $refTable;
                        $info['nullable'] = true;
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
}
