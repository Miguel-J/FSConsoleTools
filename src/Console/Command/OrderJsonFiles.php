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

use FacturaScripts\Core\Base\FileManager;
use FacturaScriptsUtils\Console\ConsoleAbstract;

/**
 * Class OrderJsonFiles
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class OrderJsonFiles extends ConsoleAbstract
{
    /**
     * Constant values for return, to easy know how execution ends.
     */
    const RETURN_SUCCESS = 0;
    const RETURN_SRC_FOLDER_NOT_SET = 1;
    const RETURN_DST_FOLDER_NOT_SET = 2;
    const RETURN_CANT_CREATE_FOLDER = 3;
    const RETURN_FAIL_SAVING_FILE = 4;
    const RETURN_NO_FILES = 5;
    const RETURN_SRC_FOLDER_NOT_EXISTS = 6;

    /**
     * Folder source path.
     *
     * @var string
     */
    private $srcFolder;

    /**
     * Folder destiny path.
     *
     * @var string
     */
    private $dstFolder;

    /**
     * Set default source folder.
     *
     * @param string $srcFolder
     *
     * @return $this
     */
    public function setSrcFolder(string $srcFolder): self
    {
        $this->srcFolder = $srcFolder;
        return $this;
    }

    /**
     * Set default destiny folder.
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
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        $this->autoReply = (bool) $params[2] ?? false;
        $this->autoHide = (bool) $params[3] ?? false;

        $status = $this->checkOptions($params);
        if ($status !== 0) {
            return $status;
        }

        $this->setSrcFolder(\FS_FOLDER . ($params[0] ?? 'Core/Translation/'));
        $this->setDstFolder(\FS_FOLDER . ($params[1] ?? 'Core/Translation/'));

        $this->showMessage('Ordering JSON content' . \PHP_EOL . \PHP_EOL);
        $this->showMessage('   Options setted:' . \PHP_EOL);
        $this->showMessage('      Source path: ' . $this->srcFolder . \PHP_EOL);
        $this->showMessage('      Destiny path: ' . $this->dstFolder . \PHP_EOL);

        if (!$this->areYouSure($this->autoReply)) {
            $this->showMessage('   Options [SRC] [DST] [TAG]' . \PHP_EOL);
            return self::RETURN_SUCCESS;
        }

        $status = $this->check();
        if ($status !== 0) {
            return $status;
        }

        $files = FileManager::scanFolder($this->srcFolder);

        if (\count($files) === 0) {
            trigger_error('ERROR: No files on folder' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_NO_FILES;
        }

        return $this->orderJson($files);
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Order JSON content files by key.';
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
            . \PHP_EOL
            . '   OPTION1           Source path' . \PHP_EOL
            . '   OPTION2           Destiny path' . \PHP_EOL
            . \PHP_EOL;
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
            }
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
        if ($this->srcFolder === null) {
            trigger_error('ERROR: Source folder not setted.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_SRC_FOLDER_NOT_SET;
        }
        if ($this->dstFolder === null) {
            trigger_error('ERROR: Destiny folder not setted.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_DST_FOLDER_NOT_SET;
        }
        if (!is_dir($this->srcFolder)) {
            trigger_error('ERROR: Source folder ' . $this->srcFolder . ' not exists.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_SRC_FOLDER_NOT_EXISTS;
        }
        if (!is_file($this->dstFolder) && !@mkdir($this->dstFolder) && !is_dir($this->dstFolder)) {
            trigger_error("ERROR: Can't create folder " . $this->dstFolder . '.' . \PHP_EOL . \PHP_EOL);
            return self::RETURN_CANT_CREATE_FOLDER;
        }
        return self::RETURN_SUCCESS;
    }

    /**
     * Order JSON files
     *
     * @param array $files
     *
     * @return int
     */
    private function orderJson(array $files): int
    {
        foreach ($files as $fileName) {
            $arrayContent = $this->readJSON($this->srcFolder . $fileName);
            \ksort($arrayContent);
            if (!$this->saveJSON($arrayContent, $this->dstFolder . $fileName)) {
                trigger_error("ERROR: Can't save file " . $fileName . \PHP_EOL);
            }
        }

        $this->showMessage('Finished! Look at "' . $this->dstFolder . '"' . \PHP_EOL);
        return self::RETURN_SUCCESS;
    }

    /**
     * Reads a JSON from disc and return it content as array.
     *
     * @param string $pathName
     *
     * @return array
     */
    private function readJSON(string $pathName): array
    {
        $data = json_decode(file_get_contents($pathName), true);
        return \is_array($data) ? (array) $data : [];
    }

    /**
     * Write a JSON from an array to disc and return its result.
     *
     * @param array  $data
     * @param string $pathName
     *
     * @return int
     */
    private function saveJSON(array $data, string $pathName): int
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (int) file_put_contents($pathName, $jsonData);
    }
}
