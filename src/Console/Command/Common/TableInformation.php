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
     * Folder destiny path.
     *
     * @var string
     */
    private $dstFolder;

    /**
     * Name of the model.
     *
     * @var string
     */
    private $modelName;

    /**
     * Name of the table.
     *
     * @var string
     */
    private $tableName;

    /**
     * Text of the table field prefix.
     *
     * @var string
     */
    private $fieldPrefix;
    /**
     * Extra fields.
     *
     * @var array
     */
    private $extraFields = [];

    /**
     * Set destiny folder.
     *
     * @param string $dstFolder
     *
     * @return $this
     */
    public function setDstFolder(string $dstFolder): self
    {
        $this->dstFolder = $dstFolder;
        return $this;
    }

    /**
     * Set the model name to use.
     *
     * @param string $modelName
     *
     * @return $this
     */
    public function setModelName(string $modelName): self
    {
        $this->modelName = $modelName;
        return $this;
    }

    /**
     * Set table name to use.
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Return the folder destiny path.
     *
     * @return string
     */
    public function getDstFolder(): string
    {
        return $this->dstFolder;
    }

    /**
     * Return the model name.
     *
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * Return the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

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
     * Return constraints grouped by name.
     *
     * @return array
     */
    public function getConstraintsGroupByName(): array
    {
        $constraint = [];
        foreach ($this->getConstraintFromTable($this->dataBase, $this->getTableName()) as $cons) {
            if (isset($constraint[$cons['name']])) {
                $constraint[$cons['name']]['column_name'] .= ', ' . $cons['column_name'];
            } else {
                $constraint[$cons['name']] = $cons;
            }
        }
        \ksort($constraint);
        return $constraint;
    }

    /**
     * Return items from table by type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getItemsFromTableBy(string $type = ''): array
    {
        $items = [];
        foreach ($this->getItemsFromTable($this->dataBase, $this->getTableName()) as $item) {
            $colType = $this->parseType($item['type'], 'view');
            if (0 === \strpos($colType, $type)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Return items from table ordered, primary key first.
     *
     * @return array
     */
    public function getItemsFromTableOrdered(): array
    {
        $items = [];
        $primaryKey = null;
        foreach ($this->getItemsFromTable($this->dataBase, $this->getTableName()) as $item) {
            $items[] = $item;
            if (0 === \strpos($item['default'], 'nextval')) {
                $primaryKey = array_pop($items);
            }
        }
        array_unshift($items, $primaryKey);
        return $items;
    }

    /**
     * Return a list of tables.
     *
     * @return string
     */
    public function getTablesMsg(): string
    {
        return \implode(', ', $this->getTables($this->dataBase));
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
                $type = str_replace('string', 'character varying', $type);
                $type = preg_replace('/^double$/', 'double precision', $type);
                $type = preg_replace('/^int\(\d+\)/', 'integer', $type);
                $type = preg_replace('/tinyint\(1\)/', 'boolean', $type);
                $type = preg_replace('/tinyint-$/', 'bool', $type);
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
            case 'xml-view':
                $type = preg_replace('/^character varying\(\d+\)/', 'text', $type);
                $type = preg_replace('/^varchar\(\d+\)/', 'text', $type);
                $type = preg_replace('/^double$/', 'number-decimal', $type);
                $type = preg_replace('/^int\(\d+\)/', 'number', $type);
                $type = str_replace('boolean', 'checkbox', $type);
                $type = preg_replace('/^timestamp$/', 'text', $type);
                $type = str_replace('time without time zone', 'text', $type);
                $type = str_replace('date', 'datepicker', $type);
                break;
        }

        return $type;
    }

    /**
     * Add extra fields details.
     *
     * @param array  $fields
     * @param string $prefix
     */
    public function addExtraFields(array $fields, string $prefix = '')
    {
        $this->fieldPrefix = $prefix;
        foreach ($fields as $field) {
            $this->extraFields[$field['name']] = [
                'type' => $this->parseType($field['type'], 'table'),
                'key' => '',
                'default' => $field['default'],
                'extra' => '',
                'is_nullable' => strtoupper($field['is_nullable']),
                'name' => $this->fieldPrefix . $field['name'],
                'length' => $field['length'],
            ];
        }
    }

    /**
     * Return a list of fields like getItemsFromTable method.
     *
     * @param DataBase $dataBase
     * @param string   $tableName
     *
     * @return array
     */
    public function getExtraItemsFromTable(DataBase $dataBase, string $tableName): array
    {
        $fields = [];
        /// Fill with original fields
        $items = $this->getItemsFromTable($dataBase, $tableName);
        foreach ($items as $key => $item) {
            if (strpos($item['name'], $this->fieldPrefix) !== false) {
                $fields[$key] = $item;
            }
        }
        /// Fill with extra fields
        foreach ($this->extraFields as $key => $item) {
            if (strpos($item['name'], $this->fieldPrefix) !== false) {
                $fields[$this->fieldPrefix . $key] = $item;
            }
        }
        return $fields;
    }
}
