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

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use BackupTool\Command\BackupNonGitFilesCommand;
use Symfony\Component\Yaml\Yaml;

$application = new Application();

$config = Yaml::parse(file_get_contents(__DIR__ . '/config.yml'));

$command = new BackupNonGitFilesCommand(null, $config);

$application->add($command);
$application->config = $config;

$application->setDefaultCommand($command->getName(), true);

$application->run();
