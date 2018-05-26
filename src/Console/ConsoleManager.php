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

use Symfony\Component\Finder\Finder;

/**
 * This class is a start point for php-cli commands.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ConsoleManager extends ConsoleAbstract
{
    /**
     * ConsoleManager constructor.
     *
     * @param int   $argc
     * @param array $argv
     */
    public function __construct(int $argc, array $argv)
    {
        $this->argv = $argv;

        // Check that at least there are 2 params (console & command)
        if ($argc >= 2) {
            // Check if first param is an option or a command
            switch ($this->argv[1]) {
                case '-l':
                case '--list':
                    $this->showAvailableCommands();
                    break;

                case '-h':
                case '--help':
                    break;

                case 0 === \strpos($this->argv[1], '-'):
                case 0 === \strpos($this->argv[1], '--'):
                    $this->optionNotAvailable($this->argv[0], $this->argv[1]);
                    break;
                default:
                    $this->run();
            }
        }

        $this->showHelp();
    }

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        $cmd = $this->argv[1];

        if (class_exists(__NAMESPACE__ . '\Command\\' . $cmd)) {
            return $this->execute();
        }

        echo \PHP_EOL . 'ERROR: Command "' . $cmd . '" not found.' . \PHP_EOL . \PHP_EOL;

        $this->showHelp();
        return -1;
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The Console Manager';
    }

    /**
     * Print help information to the user.
     */
    public function showHelp()
    {
        echo 'Use as: php vendor/bin/console [COMMAND] [OPTIONS]' . \PHP_EOL;
        echo 'Available options:' . \PHP_EOL;
        echo '   -h, --help        Show this help.' . \PHP_EOL;
        echo '   -l, --list        Show a list of available commands.' . \PHP_EOL;
        echo \PHP_EOL;
    }

    /**
     * Returns an associative array of available methods for the user.
     * Add more options if you want to add support for custom methods.
     *      [
     *          '-h'        => 'showHelp',
     *          '--help'    => 'showHelp',
     *          '-l'        => 'showAvailableCommands',
     *          '--list'    => 'showAvailableCommands',
     *      ]
     *
     * @return array
     */
    public function getUserMethods(): array
    {
        // Adding extra method
        $methods = parent::getUserMethods();
        $methods['-l'] = 'showAvailableCommands';
        $methods['--list'] = 'showAvailableCommands';

        return $methods;
    }

    /**
     * Exec the command with the given options
     *
     * @return int
     */
    public function execute(): int
    {
        $status = -1;
        $params = $this->argv;
        \array_shift($params); // Extract console
        // command class
        $cmd = \array_shift($params); // Extract command
        // $params contains adicional parameters if are received

        switch ($cmd) {
            case '-h':
            case '--help':
                $status = $this->getAvailableOptions($cmd);
                break;
            default:
                $className = __NAMESPACE__ . '\Command\\' . $cmd;
                $methods = \call_user_func([new $className(), 'getUserMethods']);
                // Forced in ConsoleAbstract, but we don't want to show it to users
                $methods['run'] = 'run';

                // If not alias, we want to directly run
                $alias = $params[0] ?? 'run';
                // If not method match, show how it works
                $method = $methods[$alias[0]] ?? 'showHelp';

                if (\array_key_exists($alias, $methods)) {
                    // Check if method is in class or parent class
                    if (\in_array($method, \get_class_methods($className), false) ||
                        \in_array($method, \get_class_methods(\get_parent_class($className)), false)
                    ) {
                        $status = (int) \call_user_func_array([new $className(), 'run'], $params);
                        break;
                    }
                    // Can be deleted, but starting with this can be helpful
                    if (\FS_DEBUG) {
                        $msg = '#######################################################################################'
                            . \PHP_EOL . '# ERROR: "' . $method . '" not defined in "' . $className . '"' . \PHP_EOL
                            . '#    Maybe you have a misspelling on the method name or is a missing declaration?'
                            . \PHP_EOL
                            . '#######################################################################################'
                            . \PHP_EOL;
                        echo $msg;
                    }
                    break;
                }

                // Can be deleted, but starting with this can be helpful
                if (\FS_DEBUG) {
                    $msg = '#######################################################################################'
                        . \PHP_EOL . '# ERROR: "' . $alias . '" not in "getUserMethods" for "' . $className . '"'
                        . \PHP_EOL . '#    Maybe you are missing to put it in to getUserMethods?' . \PHP_EOL
                        . '#######################################################################################'
                        . \PHP_EOL;
                    echo $msg;
                }

                $this->optionNotAvailable($cmd, $alias);
                $status = $this->getAvailableOptions($cmd);
        }
        return $status;
    }

    /**
     * Returns a list of available methods for this command.
     *
     * @param string $cmd
     *
     * @return int
     */
    public function getAvailableOptions(string $cmd): int
    {
        echo 'Available options for "' . $cmd . '"' . \PHP_EOL . \PHP_EOL;

        $className = __NAMESPACE__ . '\Command\\' . $cmd;
        $options = \call_user_func([new $className(), 'getUserMethods']);

        foreach ((array) $options as $option => $methods) {
            echo '   ' . $option . \PHP_EOL;
        }

        echo \PHP_EOL . 'Use as: php vendor/bin/console ' . $cmd . ' [OPTIONS]' . \PHP_EOL . \PHP_EOL;

        return -1;
    }

    /**
     * Print help information to the user.
     */
    public function showAvailableCommands()
    {
        echo 'Available commands:' . \PHP_EOL;

        foreach ($this->getAvailableCommands() as $cmd) {
            $className = __NAMESPACE__ . '\Command\\' . $cmd;
            echo '   - ' . $cmd . ' : ' . \call_user_func([new $className(), 'getDescription']) . \PHP_EOL;
        }
        echo \PHP_EOL;
    }

    /**
     * Return a list of available commands
     *
     * @return array
     */
    public function getAvailableCommands(): array
    {
        $available = [];
        $allClasses = $this->getAllFcqns(__DIR__ . '/Command');
        foreach ($allClasses as $class) {
            if (0 === \strpos($class, __NAMESPACE__ . '\Command\\')) {
                $available[] = \str_replace(__NAMESPACE__ . '\Command\\', '', $class);
            }
        }
        return $available;
    }

    /**
     * Show that this option is not available.
     *
     * @param string $cmd
     * @param string $option
     */
    private function optionNotAvailable(string $cmd, string $option)
    {
        echo 'Option "' . $option . '" not available for "' . $cmd . '".' . \PHP_EOL . \PHP_EOL;
    }

    /**
     * Return all FCQNS.
     *
     * @param string $projectRoot
     *
     * @return array
     */
    private function getAllFcqns(string $projectRoot): array
    {
        $fileNames = $this->getFileNames($projectRoot);
        $fcqns = [];
        foreach ($fileNames as $fileName) {
            $fcqns[] = $this->getFullNameSpace($fileName) . '\\' . $this->getClassName($fileName);
        }

        return $fcqns;
    }

    /**
     * Return files on path.
     *
     * @param string $path
     * @param string $pattern
     *
     * @return array
     */
    private function getFileNames(string $path, string $pattern = '*.php'): array
    {
        $finder = new Finder();
        $finder->files()->in($path)->name($pattern);
        $fileNames = [];
        foreach ($finder as $finderFile) {
            $fileNames[] = $finderFile->getRealPath();
        }

        return $fileNames;
    }

    /**
     * Return full namespace of file.
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getFullNameSpace(string $fileName): string
    {
        $lines = file($fileName);
        if (\is_bool($lines)) {
            return '';
        }
        $array = preg_grep('/^namespace /', $lines);
        $namespaceLine = array_shift($array);
        $matches = [];
        preg_match('/^namespace (.*);$/', $namespaceLine, $matches);
        return (string) array_pop($matches);
    }

    /**
     * Return the class name.
     *
     * @param string $fileName
     *
     * @return array
     */
    private function getClassName(string $fileName): array
    {
        $dirsAndFile = explode(DIRECTORY_SEPARATOR, $fileName);
        $fileName = array_pop($dirsAndFile);
        $nameAndExt = explode('.', $fileName);
        return (array) array_shift($nameAndExt);
    }
}
