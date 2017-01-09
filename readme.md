**Description**
Find all files in repos in `src` 
and generate a exclude-list.txt for `rsync` 
to only backup not in repo files 

**Install**

run: `composer install`

**Configure**

Rename or copy `config-example.yml` to `config.yml` and edit as you need

**Usage**

`php backup-tool.php`backup:exclude-list
`php backup-tool.php`git:check-status

