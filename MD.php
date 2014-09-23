<?php

/**
 * Multi-Deploy
 * https://github.com/ncareau/Multi-Deploy
 * Small/Fast script that helps deploying multiple PHP applications with Git.
 *
 * MD, short for MultiDeploy, consist of a main file and a unlimited number of config files. One for each site you want to deploy.
 *
 * View README for more information on configuration and utilisation.
 *
 * @author  NMC <admin@nmc-lab.com>
 *
 */

class MD {

    const VERSION = "0.9.3";

    // MD Main Config.
    const CONFIG_PATH = '.';
    const CONFIG_SUFFIX = '-configMD.php';
    const CMD_TIME_LIMIT = 30;

    // Main HTML Template for Email and HTTP.
    const HTMLPAGE_HEADER = "
	<!DOCTYPE html>
	<html lang=\"en\">
		<head>
			<meta charset=\"utf-8\">
			<meta name=\"robots\" content=\"noindex\">
			<title>MultiDeploy</title>
			<style>
				body { padding: 0 1em; background: #222; color: #fff; }
				h2, .err { color: #c33; }
				.prpt { color: #6be234; }
				.cmd { color: #729fcf; }
				.out { color: #999; }
				label { display: inline-block; width: 120px; height: 25px; }
				input[type=text] { width: 300px; }
				.cb { vertical-align: top; } /* Checkbox Class */
			</style>
		</head>
		<body>
			<pre>";
    const HTMLPAGE_FOOTER = "
			</pre>
		</body>
	</html>";

    //Deploying stage.
    const PRE_DEPLOY = 1;
    const DEPLOY = 2;
    const POST_DEPLOY = 3;
    const ON_SUCCESS = 4;
    const ON_FAIL = 9;

    private static $project; //Project ID
    private static $sat; // Secure Access Token
    private static $state = "SUCCESS"; //Trigger of the Success state.
    private static $configs = array(); //List of config files.
    private static $output = array(); //Output buffer.
    private static $queue = array(); //Command Queue MD_CMD[projectname][stage][]
    private static $obfuscate = array(); //Contain an array of string to obfuscate in the output (for passwords and token)

    private static $direct_output = false;

    private static $email;
    private static $email_subject;
    private static $email_addresses;

    private function __construct() {
        //This is not the construct you are looking for.
    }

    /**
     * Main entry of the project.
     * This will determine which step it will run.
     * Step 1: Project Login
     * Step 2: Deploy Config Form
     * Step 3: Deployment
     */
    public static function start() {
        self::$project = filter_input(INPUT_GET, 'project');
        self::$sat = filter_input(INPUT_GET, 'sat');

        self::getConfigs();

        if (self::$project && self::$sat) {
            if (isset(self::$configs[self::$project])) {
                if (self::$sat === self::$configs[self::$project]["SECRET_ACCESS_TOKEN"]) {
                    if (filter_input(INPUT_POST, 'deploy') === 'true' || filter_input(INPUT_GET, 'deploy') === 'true') {
                        //Show Step 3 - Deployment
                        if (self::getParam('EMAIL_SEND') === 'true' && self::getParam('EMAIL_SEND') !== '1') {
                            self::$email = true;
                            self::$email_subject = self::getParam('PROJECT_NAME');
                            self::$email_addresses = self::getParam('EMAIL_ADDRESS');
                        }

                        self::$obfuscate[] = self::getParam("SECRET_ACCESS_TOKEN");

                        self::step3();
                    } else {
                        //Show Step 2 - Deploy Configuration
                        self::step2();
                    }
                } else {
                    self::$output[] = '<h2>ACCESS DENIED!</h2>';
                    self::exitHTML();
                }
            } else {
                self::$output[] = '<h2>CONFIG ERROR!</h2>';
                self::exitHTML();
            }
        } else {
            //Show Step 1 - Project Login
            self::step1();
        }
    }

    /**
     * Get all config files.
     */
    public static function getConfigs() {
        $files = scandir(self::CONFIG_PATH);
        if ($files !== false && !empty($files)) {
            foreach ($files as $file) {
                if (strpos($file, self::CONFIG_SUFFIX) !== false) {
                    $project = str_replace(self::CONFIG_SUFFIX, '', $file);
                    self::$configs[$project] = require(self::CONFIG_PATH . DIRECTORY_SEPARATOR . $file);

                    //Load custom_fields defaults.
                    foreach(self::$configs[$project]["CUSTOM_FIELDS"] as $field){
                        self::$configs[$project][$field->name] = $field->default;
                    }
                }
            }
        }
    }

    public static function output($line){
        if(self::$direct_output){
            printf('<br/>'. str_replace(self::$obfuscate, '**PASSWORD**', $line));
            ob_flush();
            flush();
        }
        self::$output[] = $line;
    }

    public static function exitHTML() {
        $msg = self::HTMLPAGE_HEADER . implode('<br/>', self::$output) . self::HTMLPAGE_FOOTER;
        printf( str_replace(self::$obfuscate, '**PASSWORD**', $msg));
        exit;
    }

    public static function mailOutput() {
        $subject = 'MD - ' . self::$state . ' - ' . self::$email_subject;
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "Subject: {$subject}";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $msg = self::HTMLPAGE_HEADER . implode('<br/>', self::$output) . self::HTMLPAGE_FOOTER;

        //Obfuscate Passwords
        $msg = str_replace(self::$obfuscate, '**PASSWORD**', $msg);

        $recipients = explode("\n", self::$email_addresses);

        foreach ($recipients as $to) {
            mail(trim($to), $subject, $msg, implode("\r\n", $headers));
        }
    }

    public static function getParam($name, $project = null) {
        if($project == null){
            $project = self::$project;
        }

        if (isset($_POST[$project.'_'.$name])) {
            return $_POST[$project.'_'.$name];
        } elseif (isset(self::$configs[$project][$name])) {
            return self::$configs[$project][$name];
        }
    }

    public static function getCheckbox($name, $project = null) {
        if($project == null){
            $project = self::$project;
        }

        if (empty($_POST)) {
            return self::$configs[$project][$name];
        } else {
            if (isset($_POST[$project.'_'.$name])) {
                if($_POST[$project.'_'.$name] == 'true'){
                    return true;
                }
            }
        }
        return false;
    }

    public static function buildQueue($project){

        $projectpath = self::getParam('PROJECT_PATH', $project);

        //PRE-DEPLOY
        self::$queue[$project][MD::PRE_DEPLOY] = self::getParam('CMDS_PRE_DEPLOY', $project);

        self::$queue[$project][MD::PRE_DEPLOY][] = new MD_CMD(
            'git fetch origin', array(
            $projectpath,
            $projectpath
        ));

        //DEPLOY
        self::$queue[$project][MD::DEPLOY] = self::getParam('CMDS_DEPLOY', $project);

        self::$queue[$project][MD::DEPLOY][] = new MD_CMD(
            'git reset --hard', array(
            $projectpath,
            $projectpath
        ));

        self::$queue[$project][MD::DEPLOY][] = new MD_CMD(
            'git checkout origin/%s', array(
            self::getParam('PROJECT_BRANCH', $project)
        ));

        self::$queue[$project][MD::DEPLOY][] = new MD_CMD(
            'git submodule update --init --recursive', array(), 'UPDATE_SUBMODULE');

        //Run composer
        self::$queue[$project][MD::DEPLOY][] = new MD_CMD(
            'COMPOSER_HOME="/tmp/composerhome" composer --no-ansi --no-interaction --no-progress --working-dir=%s install', array(
            $projectpath
        ), 'RUN_COMPOSER');

        self::$queue[$project][MD::DEPLOY][] = new MD_CMD(
            'COMPOSER_HOME="/tmp/composerhome" composer --working-dir=%s dump-autoload --optimize', array(
            $projectpath
        ), 'RUN_COMPOSER');

        //POST-DEPLOY
        self::$queue[$project][MD::POST_DEPLOY] = self::getParam('CMDS_POST_DEPLOY', $project);

        //ON-SUCCESS
        self::$queue[$project][MD::ON_SUCCESS] = self::getParam('CMDS_ON_SUCCESS', $project);

        //ON-FAIL
        self::$queue[$project][MD::ON_FAIL] = self::getParam('CMDS_ON_FAIL', $project);

    }

    //Step 1 - Choose project Form.
    public static function step1() {

        $projectselect = '';
        foreach (self::$configs as $project => $config) {
            $projectselect .= '<option value="' . $project . '">' . $project . '</option>';
        }

        self::output('<form action="" method="get">');
        self::output('<label class="prpt">Project </label> : <select name="project" value="" />' . $projectselect . '</select>');
        self::output('<label class="prpt">Secret Token </label> : <input type="text" name="sat" value="" />');
        self::output('<input type="submit" value="Next" />');
        self::output('</form>');

        self::exitHTML();
    }

    //Step 2 - Config Form.
    public static function step2()
    {

        self::output("<label class=\"prpt\">Multi Deploy </label>  Version  " . self::VERSION);
        self::output('<form action="" method="post">');

        //Calculate all project to deploy.
        $project_array[] = self::$project;
        if (count(self::getParam('RUN_AFTER', self::$project)) > 0) {
            $project_array = array_merge($project_array, self::getParam('RUN_AFTER', self::$project));
        }

        //List each projects with their respective fields.
        foreach ($project_array as $project) {
            self::output('<fieldset><legend>' . self::getParam("PROJECT_NAME", $project) . '</legend>');
            self::output('<label class="prpt">Branch </label> : <input type="text" name="'.$project.'_PROJECT_BRANCH" value="' . self::getParam("PROJECT_BRANCH", $project) . '" />');
            self::output('<label class="prpt">Destination </label> : <input type="text" name="'.$project.'_PROJECT_PATH" value="' . self::getParam("PROJECT_PATH", $project) . '" />');
            self::output('<label class="prpt">Run Composer </label> : <input type="checkbox" value="true" class="cb" name="'.$project.'_RUN_COMPOSER" ' . (self::getCheckbox("RUN_COMPOSER", $project) ? "checked" : "") . ' />');
            self::output('<label class="prpt">Update SubModule </label> : <input type="checkbox" value="true" class="cb" name="'.$project.'_UPDATE_SUBMODULE" ' . (self::getCheckbox("UPDATE_SUBMODULE", $project) ? "checked" : "") . ' />');
            self::output('Customs:');

            foreach(self::getParam("CUSTOM_FIELDS", $project) as $field){
                self::output($field->get($project));
            }

            self::output('</fieldset>');
            self::output('');
        }

        self::output('');
        self::output('<label class="prpt">Send Emails </label> : <input type="checkbox" value="true" class="cb" onclick="document.getElementById(\'emailBox\').disabled=!this.checked;" name="'.self::$project.'_EMAIL_SEND" ' . (self::getCheckbox("EMAIL_SEND", self::$project) == true ? "checked" : "") . ' /> ');
        self::output('<label class="prpt">Emails </label> :<br/> <textarea name="'.self::$project.'_EMAIL_ADDRESS" id="emailBox" rows="4" cols="25" ' . (self::getCheckbox("EMAIL_SEND", self::$project) ? '' : 'disabled') . ' >' . self::getParam("EMAIL_ADDRESS") . '</textarea>');
        self::output('');
        self::output('<input type="submit" value="deploy" />');
        self::output('<input type="hidden" name="deploy" value="true" />');
        self::output('</form>');

        self::exitHTML();
    }

    //Step 3 - Deploy Action
    public static function step3($exit = true) {
        //Direct output mode.
        ob_implicit_flush();
        self::$direct_output = true;
        printf(self::HTMLPAGE_HEADER);


        //Checking Environment.
        self::output('');
        self::output('Starting Deployment. MultiDeploy v.'. self::VERSION);
        self::output('');
        self::output('Running as <b>' . trim(shell_exec('whoami')) . '</b>.');

        //Build Queue
        self::buildQueue(self::$project);
        if (count(self::getParam('RUN_AFTER', self::$project)) > 0) {
            foreach(self::getParam('RUN_AFTER', self::$project) as $project){
                self::buildQueue($project);
            }
        }

        //Deploy
        foreach(self::$queue as $project => $cmds){

            self::output('');
            self::output('Deploying: ' . self::getParam('PROJECT_NAME', $project));
            self::output('Branch: ' . self::getParam('PROJECT_BRANCH', $project));
            self::output('Path: ' . self::getParam('PROJECT_PATH', $project));
            self::output('');

            //Change to the project directory
            chdir(self::getParam('PROJECT_PATH', $project));

            //Deploy from stage 1 to 5.
            for($i = 1; $i <= 5; ++$i) {
                if(isset($cmds[$i])){
                    foreach ($cmds[$i] as $cmd) {
                        if($cmd->check_run($project)){
                            $run = $cmd->get();
                            set_time_limit(MD::CMD_TIME_LIMIT); // Reset the time limit for each command

                            self::output(sprintf('<span class="prpt">$</span> <span class="cmd">%s</span>'
                                , htmlentities(trim($run))));

                            $tmp = array();
                            exec($run . ' 2>&1', $tmp, $return_code); // Execute the command
                            // Output the result
                            self::output(sprintf('<div class="out">%s</div>'
                                , htmlentities(trim(implode("\n", $tmp)))));


                            // Error handling and cleanup
                            if ($return_code !== 0) {
                                self::output(sprintf('<div class="err">
                                    Error encountered!
                                    Stopping the script to prevent possible data loss.
                                    CHECK THE DATA IN YOUR TARGET DIR!
                                    </div>'));

                                error_log(sprintf(
                                    'Deployment error! %s'
                                    , __FILE__
                                ));

                                if(isset($cmds[MD::ON_FAIL])) {
                                    foreach ($cmds[MD::ON_FAIL] as $cmd){
                                        if($cmd->check_run($project)){
                                            $run = $cmd->run();
                                            set_time_limit(MD::CMD_TIME_LIMIT); // Reset the time limit for each command
                                            $tmp = array();
                                            exec($run . ' 2>&1', $tmp, $return_code); // Execute the command
                                            // Output the result
                                            self::output(sprintf('<span class="prpt">$</span> <span class="cmd">%s</span> <div class="out">%s</div>'
                                                , htmlentities(trim($run))
                                                , htmlentities(trim(implode("\n", $tmp)))));

                                            if ($return_code !== 0) {
                                                break;
                                            }
                                        }
                                    }
                                }

                                self::$state = "FAIL";

                                break 2;
                            }
                        }
                    }
                }
            }
        }

        if(self::$state == "SUCCESS") {
            self::output('');
            self::output('<span class="prpt">Deployment succeeded !</span>');
        }

        printf(self::HTMLPAGE_FOOTER);

        //Email result
        if (self::$email == true) {
            self::mailOutput();
        }

    }

}


/**
 * Class MD_CMD
 */
class MD_CMD {

    public $cmd;
    public $params;
    public $project;
    public $run_checkbox;

    /**
     * @param $cmd
     * @param $params
     * @param null $run_checkbox
     */
    public function __Construct($cmd, $params = array(), $run_checkbox = null){
        $this->cmd = $cmd;
        $this->params = $params;
        $this->run_checkbox = $run_checkbox;
    }

    public function check_run($project){
        $this->project = $project;
        if($this->run_checkbox == null){
            return true;
        }else{
            return MD::getCheckbox($this->run_checkbox, $this->project);
        }
        return false;
    }

    public function get(){
        $cmd = vsprintf($this->cmd, $this->parseParams());

        //Check for remote command.
        if(MD::getParam('PROJECT_REMOTE', $this->project)){
            $runas = self::getParam("PROJECT_REMOTE_RUNAS", $this->project);
            $remotecmd = sprintf("ssh -i %s %s@%s \"%s%s\""
                , self::getParam("PROJECT_REMOTE_KEYFILE", $this->project)
                , self::getParam("PROJECT_REMOTE_USER", $this->project)
                , self::getParam("PROJECT_REMOTE_HOST", $this->project)
                , empty($runas) ? '' : 'sudo -Hu '.self::getParam("PROJECT_REMOTE_RUNAS", $this->$project).' '
                , $cmd);
            return $remotecmd;
        }else{
            return $cmd;
        }
    }


    public function parseParams(){
        $final_params = array();
        foreach($this->params as $param){
            if(preg_match("/{(.*?)}/", $param, $matches)){
                $final_params[] = MD::getParam($matches[1], $this->project);
            }else{
                $final_params[] = $param;
            }
        }
        return $final_params;
    }
}

/**
 * Class MD_FIELD
 */
class MD_FIELD {

    const TYPE_CHECKBOX = 1;
    const TYPE_TEXTFIELD = 2;

    public $name;
    public $label;
    public $type;
    public $default;
    public $project;

    public function __Construct($name, $label, $type = MD_FIELD::TYPE_TEXTFIELD, $default = null){
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->default = $default;
    }

    public function get($project){
        $this->project = $project;

        switch($this->type) {
            case self::TYPE_CHECKBOX:
                return '<label class="prpt">'.$this->label.'</label> : <input type="checkbox" value="true" class="cb" name="'.$project.'_'.$this->name.'" ' . (MD::getCheckbox($this->name, $project) ? "checked" : "") . ' />';
            case self::TYPE_TEXTFIELD:
                return '<label class="prpt">'.$this->label.'</label> : <input type="text" name="'.$project.'_'.$this->name.'" value="' . MD::getParam($this->name, $project) . '" />';
        }

    }

}

//Start MD.
MD::start();