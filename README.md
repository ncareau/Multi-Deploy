Multi-Deploy
===

Lightweight and fast script to deploy multiple PHP applications using Git. 
The script is highly configurable with options to create new field and commands to run while deploying the app.

MD, short for Multi-Deploy, consist of a main file and a unlimited number of config file. One for each site you want to deploy.

##How to use

Download the MD.php page and transfer it to the server. Copy the file changeme-configMD.dist.php to changeme-configMD.php and modify to match your config.

The script contain 3 steps.

Step 1 is a simple form with a dropdown of each found config.

Step 2 is the configuration phase of the deployment where the user can change settings and start deploying.

Step 3 is the actual deployment process where the commands are runned and output is logued.

##Config

###MD Global Config

These config are located in the header section of the MD.php file. Changing these settings will affect the whole MultiDeploy setup.

* `PATH_CONFIG` Path of the config directory. 
* `CONFIG_SUFFIX` The suffix that the script use to find config file.
* `CMD_TIME_LIMIT` Max exec time for each command runned.
* `SHOW_STEP1` Will return access error when trying to log without any credentials. 
* `SHOW_STEP2`
* `LOCALE`
* `IP_WHITELIST`
* `ip`

###MD project Config

Deployment stage

1. `PRE_DEPLOY`
2. `DEPLOY`
3. `POST_DEPLOY`
4. `ON_SUCCESS`

If a command fail while deploying, the script will switch to the `ON_FAIL` stage.

MD_FIELD

`new MD_FIELD($name, $label, $type = MD_FIELD::TYPE_TEXTFIELD, $default  = null),`

Use this object in the `CUSTOM_FIELDS` section of the config to add TextField and Checkbox to the form (step2) of the project. There are 2 type of field available, `MD_FIELD::TYPE_TEXTFIELD` and `MD_FIELD::TYPE_CHECKBOX`. 

* `$name` Name of the field that will be used to refer to this field.
* `$label` Label to use next to the field.
* `$type` Use either MD_FIELD::TYPE_TEXTFIELD or MD_FIELD::TYPE_CHECKBOX
* `$default` When using a textfield, the default refers to a string. For a checkbox, the default is true/false with null being false.

MD_CMD

`new MD_CMD($cmd, $params = array(), $run_checkbox = null),`

Use this object in the `CMDS` section of the config to add command to run while deploying. 

* `$cmd` Command to run.
* `$params` Array of MD_FIELD name.
* `$run_checkbox` Name of the checkbox field that will control if this command should run.

//todo change here.
Using this object, you can substitute part of the command with a field object. For each part of the command you want to dynamically change with a field, use the `%s` value where you want to replace the text, and add the name of the field as a string in the params array. Each parameters with curly brace (ex `{PROJECT_PATH}` ) will then take the value of the corresponding MD_FIELD, which mean it will change/update depending on what you write in step2. 
Much like the [sprintf](http://php.net/manual/en/function.sprintf.php) function, the script will replace each occurance of `%s` with each one of the parameters. (make sure you match the number of the `%s` in the cmd and the number of parameters or you will get an error) 


###Config Example


MYSQL_DUMP

    //Place in the "CUSTOM_FIELDS"
    new MD_FIELD('MYSQL_BACKUP', 'Backup Mysql?', $type = MD_FIELD::TYPE_CHECKBOX, $default = true),
    new MD_FIELD('MYSQL_USER', 'Mysql User', $type = MD_FIELD::TYPE_TEXTFIELD, $default = 'root'),
    new MD_FIELD('MYSQL_PASS', 'Mysql Pass', $type = MD_FIELD::TYPE_TEXTFIELD, $default = ''),
    new MD_FIELD('MYSQL_DBNAME', 'Mysql DB name', $type = MD_FIELD::TYPE_TEXTFIELD, $default = 'my_db_name'),
    new MD_FIELD('MYSQL_FILE', 'Mysql backup file', $type = MD_FIELD::TYPE_TEXTFIELD, $default = 'path/to/backup'),

    //Place in "CMDS_PRE_DEPLOY"
    new MD_CMD("mysqldump -u%s -p%s %s > %s", array(
        '{MYSQL_USER}',
        '{MYSQL_PASS}',
        '{MYSQL_DBNAME}',
        '{MYSQL_FILE}',
    ), 'MYSQL_BACKUP'),


S3CMD Backup all Files

    //Place in the "CUSTOM_FIELDS"
    new MD_FIELD('S3CMD_BACKUP', 'S3 Backup?', $type = MD_FIELD::TYPE_CHECKBOX, $default = true),
    
    //Place in "CMDS_PRE_DEPLOY"
    ne MD_CMD("s3cmd put -r %s/ s3://%s/${date +%y%m%d}/", array(
        '{PROJECT_PATH}',
        '{S3_CONTAINER}'
        '{S3_PATH}'
    ),'S3CMD_BACKUP'),
    
    
Print disk space left.

    new MD_CMD("df -h"),
    
Clear cache folder.

    //Place in CMDS_POST_DEPLOY
    new MD_CMD("rm -Rf var/cache/*"),
    new MD_CMD("rm -Rf var/twig/*"),
    

##About

This project was inspired by : [markomarkovic/simple-php-git-deploy](https://github.com/markomarkovic/simple-php-git-deploy) and [Gist by oodavid](https://gist.github.com/oodavid/1809044)

More info at [ncareau/Multi-Deploy](https://github.com/ncareau/Multi-Deploy)
