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

namespace FacturaScriptsUtils\Console;

use FacturaScripts\Core\Base\DataBase;

const DS = DIRECTORY_SEPARATOR;

if (!\defined('FS_FOLDER')) {
    \define('FS_FOLDER', __DIR__ . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS . '..' . DS);
}

if (!\defined('FS_DEBUG')) {
    \define('FS_DEBUG', true);
}

/**
 * Class ConsoleAbstract
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
abstract class ConsoleAbstract
{
    /**
     * Arguments received from command execution.
     *
     * @var array
     */
    protected $argv;

    /**
     * DataBase object.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    abstract public function run(...$params): int;

    /**
     * Return description about this class.
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Print help information to the user.
     *
     * @return string
     */
    abstract public function getHelpMsg(): string;

    /**
     * Initialize.
     */
    public function init()
    {
        if (\DB_CONNECTION) {
            if (!isset($this->dataBase)) {
                $this->dataBase = new DataBase();
                $this->dataBase->connect();
            }
        } else {
            echo 'A database connection is needed. Do you set your config.php file?';
        }
    }

    /**
     * Terminate.
     */
    public function terminate()
    {
        $this->dataBase->close();
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
        return [
            '-h' => 'getHelpMsg',
            '--help' => 'getHelpMsg',
        ];
    }

    /**
     * Ask user to continue and return a boolean.
     *
     * @param bool $autoReply
     *
     * @return bool
     */
    public function areYouSure($autoReply = false)
    {
        echo \PHP_EOL . 'Are you sure? [y/n] ';
        $stdin = fgets(STDIN);
        switch (trim($stdin)) {
            case 'y':
            case 'Y':
            case 'yes':
            case 'Yes':
                return true;
            default:
                return $autoReply;
        }
    }
}
