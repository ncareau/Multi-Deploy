<?php

/**
 * This is the default configuration file for Multi-Deploy.
 *
 * Edit this file according to your setup configuration. Go here for more information on how to use custom Field and Commands.
 *
 * @version 0.9.2
 * @author  NMC <admin@nmc-lab.com>
 */
return array(

    //SECRET_ACCESS_TOKEN - Secret token or password that is used to deploy the application.
    "SECRET_ACCESS_TOKEN" => "ChangeMe-Changezmoi",

    //PROJECT_NAME - Project name for
    "PROJECT_NAME" => "TEST-NAME",

    //PROJECT_PATH - Path of the project which should also contains the git project (.git folder)
    "PROJECT_PATH" => "/path/to/project",

    //PROJECT_DEPLOY_BRANCH - Default branch to deploy.
    "PROJECT_BRANCH" => "master",

    //UPDATE_SUBMODULE - Should the app update the submodule of the application.
    "UPDATE_SUBMODULE" => true,

    //RUN_COMPOSER - Should the app run "composer install && optimize". 
    "RUN_COMPOSER" => true,

    //PROJECT_REMOTE - Is the server on a remote server. SSH?
    "PROJECT_REMOTE" => false,
    //PROJECT_REMOTE_USER - User to user for remote connection.
    "PROJECT_REMOTE_USER" => "remoteuser",
    //PROJECT_REMOTE_HOST - Host of the remote connection.
    "PROJECT_REMOTE_HOST" => "site.host.com",
    //PROJECT_REMOTE_KEYFILE - Path to the private key of the connection.
    "PROJECT_REMOTE_KEYFILE" => "/path/to/key.ssl",
    //PROJECT_REMOTE_RUNAS - Leave blank if you want to run as logging user, Put a string to use : sudo -Hu $user.
    "PROJECT_REMOTE_RUNAS" => "",

    //EMAIL_SEND - should the app send emails to the following with the results of the deployment.
    "EMAIL_SEND" => false,
    //EMAIL_DEFAULT - List of the user that should receive an email about the state of deployment. Each email must be on a different line. (\n)
    "EMAIL_ADDRESS" => "ncareau@som.ca\nncareau@som.ca",

    //RUN_AFTER - Array of script to deploy after this one.
    "RUN_AFTER" => array('test2'),


    /************************************
     * Custom Fields
     *
     * Use MD_FIELD for creating new form field.
     */

    "CUSTOM_FIELDS" => array(
    ),

    /************************************
     * Custom Commands
     *
     * Use MD_CMD for creating a new command.
     * For dynamic parameter fot he command options, use %name% and the get_param function will be called.
     */

    "CMDS_PRE_DEPLOY" => array(

    ),
    "CMDS_DEPLOY" => array(

    ),
    "CMDS_POST_DEPLOY" => array(

    ),
    "CMDS_ON_SUCCESS" => array(

    ),
    "CMDS_ON_FAIL" => array(

    ),

);