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

use FacturaScriptsUtils\Console\ConsoleAbstract;

if (!\defined('DB_CONNECTION')) {
    \define('DB_CONNECTION', false);
}

/**
 * Class GeneratePhpModel.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class GenerateXmlTable extends ConsoleAbstract
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
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        $this->autoReply = isset($params[4]) ? (bool) $params[4] : false;
        $this->autoHide = isset($params[5]) ? (bool) $params[5] : false;

        if (!\DB_CONNECTION) {
            trigger_error('A database connection is needed. Do you set your config.php file?');
        }

        $status = $this->checkOptions($params);
        if ($status !== 0) {
            return $status;
        }

        $status = $this->checkParams($params);
        if ($status !== 0) {
            return $status;
        }

        $this->showMessage('Generating XML table file' . \PHP_EOL . \PHP_EOL);
        $this->showMessage('   Options setted:' . \PHP_EOL);
        $this->showMessage('      Table name: ' . $this->getTableName() . \PHP_EOL);
        $this->showMessage('      Destiny path: ' . $this->getDstFolder() . \PHP_EOL);

        if (!$this->areYouSure($this->autoReply)) {
            $this->showMessage('   Options [TABLE NAME] [DST]' . \PHP_EOL);
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
        return 'Generate a XML table definition for model from database table.';
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
            . '   OPTION2           Destiny path' . \PHP_EOL
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
                    $this->showMessage($this->getHelpMsg());
                    return -1;
                case '-t':
                case '--tables':
                    $this->setDataBase($params[1]);
                    $this->showMessage($this->getTablesMsg());
                    return -1;
                case '-g':
                case '--gen':
                    $this->setTableName(isset($params[1]) ? $params[1] : '');
                    $this->setDstFolder(\FS_FOLDER . (isset($params[2]) ? $params[2] : 'Core/Table'));
                    $this->setDataBase(isset($params[3]) ? $params[3] : null);
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
            trigger_error('No table name setted.' . \PHP_EOL);
            return -1;
        }
        if (!isset($params[1])) {
            trigger_error('No model name setted.' . \PHP_EOL);
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
        if ($this->getTableName() === '') {
            trigger_error('ERROR: Table name not setted.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_TABLE_NAME_NOT_SET;
        }
        if ($this->getDstFolder() === '') {
            trigger_error('ERROR: Destiny folder not setted.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_DST_FOLDER_NOT_SET;
        }
        if (!is_file($this->getDstFolder()) && !@mkdir($this->getDstFolder()) && !is_dir($this->getDstFolder())) {
            trigger_error("ERROR: Can't create folder " . $this->getDstFolder() . '.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_CANT_CREATE_FOLDER;
        }
        if (!\in_array($this->getTableName(), $this->dataBase->getTables(), false)) {
            trigger_error('ERROR: Table not exists.' . \PHP_EOL . \PHP_EOL);
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
        $loader = new \Twig_Loader_Filesystem([__DIR__ . '/../Template']);
        $twig = new \Twig_Environment($loader, ['debug' => \FS_DEBUG,]);
        $twig->addExtension(new \Twig_Extension_Debug());
        $txt = $twig->render(
            'XmlTable.xml.twig',
            ['fsc' => $this]
        );

        $status = $this->saveFile($this->getDstFolder() . $this->getTableName() . '.xml', $txt);
        if (\is_bool($status)) {
            trigger_error("Can't save " . $this->getDstFolder() . $this->getTableName() . '.xml"' . \PHP_EOL);
            return $status;
        }
        $this->showMessage('Finished! Look at "' . $this->getDstFolder() . '"' . \PHP_EOL);
        return self::RETURN_SUCCESS;
    }
}
