Multi-Deploy
===

Lightweight and fast script to deploy multiple PHP applications using Git. 
The script is highly configurable with options to create new field and commands to run while deploying the app.

MD, short for Multi-Deploy, consist of a main file and a unlimited number of config file. One for each site you want to deploy.

##How to use

Download the MD.php page and transfer it to the server. Copy the file changeme-configMD.dist.php to changeme-configMD.php and modify to match your config.

##Config

###MD Global Config

These config are located in the header section of the MD.php file. Changing these settings will affect the whole MultiDeploy setup. 

* PATH_CONFIG
* CONFIG_SUFFIX
* CMD_TIME_LIMIT

###MD project Config

Deployment stage


MD_FIELD

* $name
* $label
* $type
* $default

MD_COMMAND

* $cmd
* $params
* $run_checkbox

##About

This project was inspired by : [markomarkovic/simple-php-git-deploy](https://github.com/markomarkovic/simple-php-git-deploy) and [Gist by oodavid](https://gist.github.com/oodavid/1809044)

More info at [ncareau/Multi-Deploy](https://github.com/ncareau/Multi-Deploy)
