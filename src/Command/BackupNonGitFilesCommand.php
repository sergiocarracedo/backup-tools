<?php
/**
 *
 * backup-tools.php
 *
 * This file is part of backup-tool.php
 * backup-tool.php is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 * backup-tool.php is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with jquery.chromecast. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Sergio Carracedo Martinez <info@sergiocarracedo.es>
 * @copyright   2016 Sergio Carracedo Martinez
 * @license     http://www.gnu.org/licenses/lgpl-3.0.txt GNU LGPL 3.0
 *
 */

namespace BackupTool\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class BackupNonGitFilesCommand extends Command
{
    private $config;

    function __construct($name = null, $config)
    {
        parent::__construct($name);
        $this->config = $config;
    }

    use LockableTrait;


    protected function configure()
    {
        $this
            ->setName('backup:exclude-list')
            ->setDescription('Backup files not in git repo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return 0;
        }

        if ($this->config['exclude-repo-files']) {
            $this->prepareExcludeList();
        }

        $rsyncCommand = 'rsync -rav';
        if ($this->config['exclude-repo-files']) {
            $rsyncCommand .= ' --exclude-from \'' . $this->config['exclude-list-tmp'] . '\'';
        }

        $rsyncCommand .= ' --prune-empty-dirs  --cvs-exclude --delete';
        $rsyncCommand .= ' ' . $this->config['src'];
        $rsyncCommand .= ' ' . $this->config['dst'];

        $process = new Process($rsyncCommand);
        $process->setTimeout(4 * 60 * 60);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo $buffer;
            }
        });

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        //$output = explode(PHP_EOL, $process->getOutput());



        $this->release();
    }

    protected function prepareExcludeList() {
        $excludeList = array();
        $gitDirectories = $this->findGitDirectoriesRecusive($this->config['src']);

        foreach($gitDirectories as $directory) {
            if (!empty($directory)) {
                $excludeList = array_merge($excludeList, $this->getRepoFilesRecursive($directory));
            }
        };

        $excludeList = array_filter($excludeList, function($item) {
            return !is_dir($item);
        });

        $rootPathLength = strlen($this->config['src']);
        array_walk($excludeList, function (&$item, $key) use($rootPathLength) {
            $item = substr($item, $rootPathLength);
        });


        $f = fopen($this->config['exclude-list-tmp'], 'w+');
        fwrite($f, implode(PHP_EOL, $excludeList));
        fclose($f);
    }

    protected function findGitDirectoriesRecusive($path)
    {
        $process = new Process('find ' . $path . ' -type d -name ".git"');
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = explode(PHP_EOL, $process->getOutput());
        array_walk($output, function(&$item, $key) {
            $path = explode('/', $item);
            $dir = array_pop($path);

            if ($dir == '.git') {
                $item = implode('/', $path);
            }
        });
        return $output;
    }



    protected function getFilesRercursive($path)
    {
        $process = new Process('find ' . $path);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return explode(PHP_EOL, $process->getOutput());
    }

    protected function getRepoFilesRecursive($path)
    {
        $process = new Process('cd '.$path .' && git ls-tree --full-tree -r HEAD | awk \'{print $4}\'');
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = explode(PHP_EOL, $process->getOutput());

        array_walk($output, function (&$item, $key) use ($path){
            $item = $path . '/' . $item;
        });

        return $output;
    }

}