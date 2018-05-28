<?php
/**
 * This file is part of FSConsoleTools
 * Copyright (C) 2018 Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScriptsUtils\Console\Command;

use FacturaScripts\Core\Base\DataBase;
use FacturaScriptsUtils\Console\ConsoleAbstract;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;

if (!\defined('DB_CONNECTION')) {
    \define('DB_CONNECTION', false);
}

/**
 * Class GeneratePhpModel.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class GeneratePhpListController extends ConsoleAbstract
{
    use Common\TableInformation;

    /**
     * Constant values for return, to easy know how execution ends.
     */
    const RETURN_SUCCESS = 0;
    const RETURN_TABLE_NAME_NOT_SET = 1;
    const RETURN_MODEL_NAME_NOT_SET = 2;
    const RETURN_DST_FOLDER_NOT_SET = 3;
    const RETURN_CANT_CREATE_FOLDER = 4;
    const RETURN_TABLE_NOT_EXISTS = 5;
    const RETURN_FAIL_SAVING_FILE = 6;

    /**
     * Name of the table.
     *
     * @var string
     */
    private $tableName;

    /**
     * Name of the model.
     *
     * @var string
     */
    private $modelName;

    /**
     * Folder destiny path.
     *
     * @var string
     */
    private $folderDstPath;

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        if (!\DB_CONNECTION) {
            echo 'A database connection is needed. Do you set your config.php file?';
        }

        $status = $this->checkOptions($params);
        if ($status !== 0) {
            return $status;
        }

        $status = $this->checkParams($params);
        if ($status !== 0) {
            return $status;
        }

        echo 'Generating List Controller class file' . \PHP_EOL . \PHP_EOL;
        echo '   Options setted:' . \PHP_EOL;
        echo '      Table name: ' . $this->tableName . \PHP_EOL;
        echo '      Model name: ' . $this->modelName . \PHP_EOL;
        echo '      Destiny path: ' . $this->folderDstPath . \PHP_EOL;

        if (!$this->areYouSure()) {
            echo '   Options [TABLE NAME] [MODEL NAME] [DST]' . \PHP_EOL;
            return self::RETURN_SUCCESS;
        }

        $status = $this->check();
        if ($status !== 0) {
            return $status;
        }

        return $this->generateModel();
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Generate a List Controller for model class from database table.';
    }

    /**
     * Print help information to the user.
     *
     * @return string
     */
    public function getHelpMsg(): string
    {
        $array = \explode('\\', __CLASS__);
        $class = array_pop($array);
        return 'Use as: php vendor/bin/console ' . $class . ' [OPTIONS]' . \PHP_EOL
            . 'Available options:' . \PHP_EOL
            . '   -h, --help        Show this help.' . \PHP_EOL
            . '   -t, --tables      Show tables.' . \PHP_EOL
            . '   -g, --gen         Generate model.' . \PHP_EOL
            . \PHP_EOL
            . '   OPTION1           Table name' . \PHP_EOL
            . '   OPTION2           Model name' . \PHP_EOL
            . '   OPTION3           Destiny path' . \PHP_EOL
            . \PHP_EOL;
    }

    /**
     * Returns an associative array of available methods for the user.
     * Add more options if you want to add support for custom methods.
     *      [
     *          '-h'        => 'getHelpMsg',
     *          '--help'    => 'getHelpMsg',
     *      ]
     *
     * @return array
     */
    public function getUserMethods(): array
    {
        // Adding extra method
        $methods = parent::getUserMethods();
        $methods['-t'] = 'getTablesMsg';
        $methods['--tables'] = 'getTablesMsg';
        $methods['-g'] = 'generateModel';
        $methods['--gen'] = 'generateModel';
        return $methods;
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
     * Set destiny folder.
     *
     * @param string $folderDstPath
     *
     * @return $this
     */
    public function setFolderDstPath(string $folderDstPath): self
    {
        $this->folderDstPath = $folderDstPath;
        return $this;
    }

    /**
     * Set database.
     *
     * @param DataBase $dataBase
     */
    public function setDataBase(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
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
     * Return the model name.
     *
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * Return the folder destiny path.
     *
     * @return string
     */
    public function getFolderDstPath(): string
    {
        return $this->folderDstPath;
    }

    /**
     * Return the DataBase object.
     *
     * @return DataBase
     */
    public function getDataBase()
    {
        return $this->dataBase;
    }

    /**
     * Return items from table by type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getItemsFromTableBy(string $type = '')
    {
        $items = [];
        foreach ($this->getItemsFromTable($this->getDataBase(), $this->getTableName()) as $item) {
            $colType = $this->parseType($item['type'], 'view');
            if (0 === \strpos($colType, $type)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Return constraints grouped by name.
     *
     * @return array
     */
    public function getConstraintsGroupByName()
    {
        $constraint = [];
        foreach ($this->getConstraintFromTable($this->getDataBase(), $this->getTableName()) as $cons) {
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
     * Check if options are looking for help.
     *
     * @param array $params
     *
     * @return int
     */
    private function checkOptions(array $params = []): int
    {
        if (isset($params[0])) {
            switch ($params[0]) {
                case '-h':
                case '--help':
                    echo $this->getHelpMsg();
                    return -1;
                case '-t':
                case '--tables':
                    $this->setDataBase($params[1]);
                    echo $this->getTablesMsg();
                    return -1;
                case '-g':
                case '--gen':
                    $this->setTableName($params[1] ?? '');
                    $this->setModelName($params[2] ?? '');
                    $this->setFolderDstPath(\FS_FOLDER . ($params[3] ?? 'Core/Controller'));
                    $this->setDataBase($params[4]);
            }
        }
        return 0;
    }

    /**
     * Check if options are looking for help.
     *
     * @param array $params
     *
     * @return int
     */
    private function checkParams(array $params = []): int
    {
        if (!isset($params[0])) {
            echo 'No table name setted.' . \PHP_EOL;
            return -1;
        }
        if (!isset($params[1])) {
            echo 'No model name setted.' . \PHP_EOL;
            return -1;
        }
        return 0;
    }

    /**
     * Launch basic checks.
     *
     * @return int
     */
    private function check(): int
    {
        if ($this->tableName === null) {
            echo 'ERROR: Table name not setted.' . \PHP_EOL . \PHP_EOL;
            return self::RETURN_TABLE_NAME_NOT_SET;
        }
        if ($this->modelName === null) {
            echo 'ERROR: Model name not setted.' . \PHP_EOL . \PHP_EOL;
            return self::RETURN_MODEL_NAME_NOT_SET;
        }
        if ($this->folderDstPath === null) {
            echo 'ERROR: Destiny folder not setted.' . \PHP_EOL . \PHP_EOL;
            return self::RETURN_DST_FOLDER_NOT_SET;
        }
        if (!is_file($this->folderDstPath) && !@mkdir($this->folderDstPath) && !is_dir($this->folderDstPath)) {
            echo "ERROR: Can't create folder " . $this->folderDstPath . '.' . \PHP_EOL . \PHP_EOL;
            return self::RETURN_CANT_CREATE_FOLDER;
        }
        if (!\in_array($this->tableName, $this->dataBase->getTables(), false)) {
            echo 'ERROR: Table not exists.' . \PHP_EOL . \PHP_EOL;
            return self::RETURN_TABLE_NOT_EXISTS;
        }
        return self::RETURN_SUCCESS;
    }

    /**
     * Generate model file.
     *
     * @return int
     */
    private function generateModel(): int
    {
        $loader = new Twig_Loader_Filesystem([__DIR__ . '/../Template']);
        $twig = new Twig_Environment($loader, ['debug' => \FS_DEBUG,]);
        $twig->addExtension(new Twig_Extension_Debug());
        $txt = $twig->render(
            'ListController.php.twig',
            ['fsc' => $this]
        );

        $status = $this->saveFile($this->folderDstPath . 'List' . $this->modelName . '.php', $txt);
        if (\is_bool($status)) {
            echo "Can't save " . $this->folderDstPath . 'List' . $this->modelName . '.php"' . \PHP_EOL;
            return $status;
        }
        echo 'Finished! Look at "' . $this->folderDstPath . '"' . \PHP_EOL;
        return self::RETURN_SUCCESS;
    }

    /**
     * Save file.
     *
     * @param string $pathName
     * @param string $content
     *
     * @return bool|int
     */
    private function saveFile(string $pathName, string $content)
    {
        return file_put_contents($pathName, $content);
    }
}
