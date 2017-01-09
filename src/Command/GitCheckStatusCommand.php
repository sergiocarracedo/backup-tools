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


define('GIT_STATUS_PUSHED', 1);
define('GIT_STATUS_COMMITED', 2);
define('GIT_STATUS_PENDING_CHANGES', 3);
define('GIT_STATUS_UNKNOWN', 0);

class GitCheckStatusCommand extends Command {

  use LockableTrait;

  private $config;

  function __construct($name = NULL, $config) {
    parent::__construct($name);
    $this->config = $config;
  }



  protected function configure() {
    $this
      ->setName('git:push-status')
      ->setDescription('Check git repos push status');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->lock()) {
      $output->writeln('The command is already running in another process.');
      return 0;
    }

    $gitDirs = $this->findGitDirectoriesRecusive($this->config['src']);

    foreach ($gitDirs as $dir) {
      $status = $this->checkGitStatus($dir);

      switch($status) {
        case GIT_STATUS_PUSHED:
          $output->writeln('<info>' . $dir . ' : Pushed</info>');
          break;
        case GIT_STATUS_COMMITED:
          $output->writeln('<comment>' . $dir . ' : Pushed</comment>');
          break;
        case GIT_STATUS_PENDING_CHANGES:
          $output->writeln('<error>' . $dir . ' : Changes not staged for commit</error>');
          break;
        default:
          $output->writeln('<error>' . $dir . ' : Other cases</error>');
          break;

      }

    }

    /*$process = new Process($rsyncCommand);
    $process->setTimeout(4 * 60 * 60);
    $process->run(function ($type, $buffer) {
        if (Process::ERR === $type) {
            echo 'ERR > '.$buffer;
        } else {
            echo $buffer;
        }
    });*/

    //$output = explode(PHP_EOL, $process->getOutput());


    $this->release();
  }


  protected  function checkGitStatus($path) {
    $process = new Process('cd ' . $path. ' && git status');
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $output = explode(PHP_EOL, $process->getOutput());

    $output = array_filter($output, function($value) {
      return $value !== '';
    });

    if (strpos($output[1], 'Your branch is up-to-date') !== FALSE) {
      $status = GIT_STATUS_PUSHED;
    } elseif (strpos($output[1], 'nothing to commit, working directory clean') !== FALSE) {
      $status = GIT_STATUS_COMMITED;
    } elseif (strpos($output[1], 'Changes not staged for commit') !== FALSE) {
      $status = GIT_STATUS_PENDING_CHANGES;
    } else {
      $status = GIT_STATUS_UNKNOWN;
    }

    return $status;
  }

  protected function findGitDirectoriesRecusive($path) {
    $process = new Process('find ' . $path . ' -type d -name ".git"');
    $process->setTimeout(4 * 60 * 60);
    $process->run();

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $output = explode(PHP_EOL, $process->getOutput());
    array_walk($output, function (&$item, $key) {
      $path = explode('/', $item);
      $dir = array_pop($path);

      if ($dir == '.git') {
        $item = implode('/', $path);
      }
    });

    $output = array_filter($output, function($value) {
      return $value !== '';
    });

    return $output;
  }

}
