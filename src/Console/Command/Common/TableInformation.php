<?php
/**
 * Created by PhpStorm.
 * User: shawe francesc.pineda.segarra@gmail.com
 * Date: 26/05/18
 * Time: 22:11
 */

namespace FacturaScriptsUtils\Console\Command\Common;

use FacturaScripts\Core\Base\DataBase;

/**
 * Trait TableInformation.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
trait TableInformation
{

    /**
     * Returns an array with tables.
     *
     * @param DataBase $dataBase
     *
     * @return array
     */
    public function getTables(DataBase $dataBase): array
    {
        return $dataBase->getTables();
    }

    /**
     * Returns an array with the columns of a given table.
     *
     * @param DataBase $dataBase
     * @param string   $tableName
     *
     * @return array
     */
    public function getItemsFromTable(DataBase $dataBase, string $tableName): array
    {
        return $dataBase->getColumns($tableName);
    }

    /**
     * Returns an array with the constraints of a table.
     *
     * @param DataBase $dataBase
     * @param string   $tableName
     *
     * @return array
     */
    public function getConstraintFromTable(DataBase $dataBase, $tableName): array
    {
        return $dataBase->getConstraints($tableName, true);
    }

    /**
     * Convert type to XML/PHP type.
     *
     * @param string $type
     * @param string $usedOn
     *
     * @return string
     */
    public function parseType($type = '', string $usedOn = ''): string
    {
        switch ($usedOn) {
            case 'table':
                $type = str_replace('varchar', 'character varying', $type);
                $type = preg_replace('/^double$/', 'double precision', $type);
                $type = preg_replace('/^int\(\d+\)/', 'integer', $type);
                $type = preg_replace('/tinyint\(1\)/', 'boolean', $type);
                $type = preg_replace('/^timestamp$/', 'timestamp without time zone', $type);
                break;
            case 'php':
                $type = preg_replace('/^varchar\(\d+\)/', 'string', $type);
                $type = preg_replace('/^character varying\(\d+\)/', 'string', $type);
                $type = str_replace('text', 'string', $type);
                $type = str_replace('double precision', 'float', $type);
                $type = preg_replace('/^int\(\d+\)/', 'int', $type);
                $type = preg_replace('/tinyint\(1\)/', 'bool', $type);
                $type = preg_replace('/^timestamp$/', 'string', $type);
                $type = str_replace('date', 'string', $type);
                break;
            case 'view':
                $type = preg_replace('/^character varying\(\d+\)/', 'text', $type);
                $type = str_replace('double precision', 'number-decimal', $type);
                $type = str_replace('integer', 'number', $type);
                $type = str_replace('boolean', 'checkbox', $type);
                $type = preg_replace('/^timestamp$/', 'text', $type);
                $type = str_replace('time without time zone', 'text', $type);
                $type = str_replace('date', 'datepicker', $type);
                break;
        }

        return $type;
    }
}
