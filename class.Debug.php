<?php

class Debug extends Container implements IService {

    private static $instance = null;

    private $enabled = true;
    private $node_debug_stream = null;



    public function provides() {
        return 'debug';
    }

    public function __construct(App $app) {
        parent::__construct();

        self::$instance = $this;


        $this->enabled( $this->app->getConfig('debug', true) );

        $logdir = dirname($_SERVER['SCRIPT_FILENAME']);

        if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            define('AJAX', true);
        }

        if ( !defined('DEBUGLOG') ) {
            define('DEBUGLOG', $logdir . '/debug.htm');
        }
        if ( defined('ERRORLOG') ) {
            $ERRORLOG = ERRORLOG;
        }
        else {
            //if (!is_writable($) || (file_exists($ERRORLOG) && !is_writable($ERRORLOG))) $ERRORLOG = '/www/errors.htm';
            define('ERRORLOG', $logdir . '/errors.htm');
        }
        //openlog('NoahDebug', LOG_ODELAY, LOG_LOCAL5);

        if ( strstr($_SERVER['PHP_SELF'], 'ajax') !== false && !defined('DEBUGTOFILE') ) define('DEBUGTOFILE', 1);
        if ( defined('WEBSITE') && session_id() ) {
            ini_set('html_errors', 1);
            if ( !isset($_SESSION['__warnings']) ) $_SESSION['__warnings'] = [];
            if ( !isset($_SESSION['__notices']) ) $_SESSION['__notices'] = [];
            if ( !isset($_SESSION['__debug']) ) $_SESSION['__debug'] = [];
        }
        else {
            $GLOBALS['__warnings'] = [];
            $GLOBALS['__notices'] = [];
            $GLOBALS['__debug'] = [];
            //     ini_set('html_errors', 0);
            //    ini_set('error_prepend_string', '');
            //   ini_set('error_append_string', '');
        }

        $this->benchmark();

        if ( defined('DEBUG') && !defined('NODEBUG') ) {
            ini_set('display_errors', 'on');
            set_error_handler([$this, "onError"]);
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);
        }
        else if ( !defined('NODEBUG') ) {
            set_error_handler([$this, "onError"], E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);
        }
        if ( defined('NODEBUG') ) {
            ini_set('display_errors', 'off');
            //set_error_handler("onError",E_ALL &~ E_NOTICE &~ E_WARNING &~ E_STRICT &~ E_DEPRECATED );
        }
        set_exception_handler([$this, 'onException']);

       /* if( is_null($this->node_debug_stream)) {
            $this->node_debug_stream = fsockopen('localhost',13371,$errno,$errstr,3);
            if(  $this->node_debug_stream ) {
                $this->log("~Opened node stream!");
            } else {
                $this->node_debug_stream = null;
                $this->log("!Couldn't open node stream", $errstr);
            }
        }*/

        $this->log('<BR><br><br><B style="color:yellow; font-size: 1.1em;">[' . $_SERVER['PHP_SELF'] . ']</B> [' . (isset($_REQUEST['_route_']) ? $_REQUEST['_route_'] : '?') . '] <b style="float:right;">' . date("g:i a") . ' </b><BR>');
        parent::__construct();
    }

    public function __destruct() {
        fclose($this->node_debug_stream);
    }

    public static function __callStatic($func, $args) {
        if ( self::$instance === null ) return null;
        $me = self::$instance;

        return call_user_func_array($me->$func, $args);
    }

    public function enabled($onoff=null) {
        if( $onoff === null ) return $this->enabled;
        $this->enabled = !!$onoff;
    }

    public function log($msg) {
        if( !$this->enabled() ) return;
        $cargs = func_num_args();
        if ( $cargs > 1 ) {
            if ( ($cargs == 2 || $cargs == 3) && (is_array($msg) || is_object($msg)) ) {
                $label = func_get_arg(1);
                if ( is_string($label) ) return $this->__($msg, '<strong>' . $label . '</strong>');
            }
            for ( $i = 0; $i < $cargs; $i++ ) {
                $arg = func_get_arg($i);
                $this->log($arg);
            }
            return;
        }
        if ( is_array($msg) || is_object($msg) ) $msg = $this->_dump($msg, '$' . ucwords(gettype($msg)), (is_object($msg) || (is_array($msg) && count($msg) > 5) ? 1 : 0));
        else {
            if ( !isset($msg) ) $msg = '*UNSET*';
            else if ( is_null($msg) ) $msg = '*NULL*';
            else if ( is_bool($msg) ) $msg = $msg ? 'TRUE' : 'FALSE';
            else if ( empty($msg) || (string)$msg == '' ) $msg = '*EMPTY*';
            else if ( is_string($msg) ) {
                $pre = substr($msg, 0, 1);
                if ( $pre == '!' ) $msg = '<strong style="color:#FF7777;">' . substr($msg, 1) . '</strong>';
                else if ( $pre == '~' ) $msg = '<strong style="color:#77FF77;">' . substr($msg, 1) . '</strong>';
            }
        }
        if ( isset($_SESSION['__debug']) ) $_SESSION['__debug'][] = $msg;
        else    $GLOBALS['__debug'][] = $msg;
        if( !is_null($this->node_debug_stream)) {
            fwrite($this->node_debug_stream, $msg . "\n");
        }
    }

    function _dump($object, $label = '', $dhtml = true) {
        if( !$this->enabled() ) return;

        $c = 0;
        $str = $this->_vardump($object, '', 1, $c);
        if ( empty($label) ) {
            $dhtml = false;
        }
        $len = strlen($str);
        $open = false;
        if ( $c > 100 || $len > 2000 ) {
            $open = false;
            $dhtml = true;
        }
        else if ( $c < 3 || $len < 50 ) $open = true;
        if ( $dhtml ) {
            if ( is_scalar($object) ) $cc = $c = '(1)';
            else {
                $cc = is_object($object) ? $c : count($object);
                if ( $c > $cc ) $c = $cc = "($cc) <I style=\"color:#777777;\">[$c nested]</I>";
                else $cc = $c = "($cc)";
            }
            if ( $open ) {
                $str = "<DIV style=\"clear: both; padding:3px 3px 3px 8px; margin-top: 1px; background-color:#333333; color: #EEEEEE;\"><table cellpadding=0 cellspacing=0 border=0><tr><td nowrap width=50 align=left valign=top style='font-size: 9px; font-family:'MS Reference Sans Serif', monospace; white-space:nowrap; background-color: #333333; border-right: 2px solid #444444; padding:5px 2px 1px 0px;'><B style='color:#3665b4;'> $label </B></td><td style='font-size: 9px; font-family:'MS Reference Sans Serif', monospace; padding-left: 0px;'>$str</td></tr></table></DIV>";
            }
            else {
                $id = uniqid();
                $label = '<DIV style="clear: both; background-color:#333333; padding:2px 2px 2px 8px; margin-top:1px; cursor:pointer;" onmouseout="this.style.background=\'#333333\';" onmouseover="this.style.background=\'#555555\';" onclick="document.getElementById(\'' . $id . '\').style.display=(document.getElementById(\'' . $id . '\').style.display==\'block\'?\'none\':\'block\');"><B style="color:#6699cc;">' . $label . '</b>&nbsp;&raquo;&nbsp;' . $c . '</DIV>';
                $dhtml = '<DIV id="' . $id . '" style="margin:0px 10px 5px 20px;padding:6px;width:auto;display:' . ($open ? 'block' : 'none') . '; background-color:#2A2A2A; border-bottom: 1px dotted #000000; border-left: 1px dotted #000000; font-size: 9px;">' . $str . '</DIV>';
                $str = $label . $dhtml;
            }
        }
        else {
            if ( $label ) $str = "<BR>&nbsp;&nbsp;&nbsp;&nbsp;[<i>$label</i>]<BR>$str<BR>";
        }

        return $str;
    }

    public function __invoke(...$args) {
        $this->log(...$args);
    }

    public function onError($errno, $errstr, $errfile, $errline) {
        if( !$this->enabled() ) return;
        static $count = 0;
        static $lasterror = '';
        static $repeat = false;
        $count++;
        $md5 = md5($errno . $errstr . $errfile . $errline);
        $errfile = substr($errfile, strrpos($errfile, '/') + 1);
        $fail = false;
        $errtype = $this->errno2str($errno, $fail);
        $errortxt = $errtype . " [$errfile ($errline)] : '" . strip_tags($errstr) . "\n";
        if ( defined('STDERR') ) fwrite(STDERR, $errortxt);
        $error = " <small>$errtype</small> [<var>$errfile</var> (<B>$errline</B>)] : $errstr";
        if ( $errno == 8 || $errno == 1024 || $errno == 2047 || $errno == 2048 ) {
            if ( $count >= 1000 ) return;
            if ( $errno == 2048 || $errno == 2047 ) $error = '* ' . $error;
            if ( $md5 != $lasterror ) {
                $repeat = false;
            }
            if ( $md5 == $lasterror && $repeat != true ) {
                $repeat = true;
                if ( isset($_SESSION['__notices']) ) $_SESSION['__notices'][] = "<b>Error above repeated many times</b>";
                else $GLOBALS['__notices'][] = "<b>Error above repeated many times</b>";
            }
            else if ( $md5 != $lasterror ) {
                if ( isset($_SESSION['__notices']) ) $_SESSION['__notices'][] = $error;
                else $GLOBALS['__notices'][] = $error;
                $lasterror = md5($errno . $errstr . $errfile . $errline);
            }

            return false;
        }
        else if ( $errno == 2 || $errno == 32 || $errno == 128 || $errno == 512 || $errno == 4096 ) {
            if ( $errno == 4096 ) $this->log("!{$errortxt}");
            if ( $count >= 1000 ) return;
            if ( isset($_SESSION['__warnings']) ) $_SESSION['__warnings'][] = $error;
            else $GLOBALS['__warnings'][] = $error;

            return false;
        }
        $error .= $this->backtrace(debug_backtrace());
        $this->log($error);
        $fail = 0;
        $this->_error([$error, $errortxt], $fail);
        if ( $fail ) {
            if ( !defined('CRASHED') ) define('CRASHED', 1);
        }

        return $fail;
    }

    function _error($msg, $fail = 0) {
        if ( is_array($msg) ) {
            $msgtxt = $msg[1];
            $msg = $msg[0];
        }
        else {
            $msgtxt = str_replace(["\n", "\t", "\r", "\f"], ' ', strip_tags(html_entity_decode($msg)));
        }
        $msghtml = '<DIV style="padding:10px;margin:10px;border:2px solid #FF6600;background:#EEDDDD;color:black;font-size:12px;font-family:Calibri,Arial;">' . $msg . '</DIV>';
        $this->log($msghtml);
        error_log($msghtml, 3, ERRORLOG);
        syslog(LOG_CRIT, $msgtxt);
        if ( defined('STDERR') ) {
            fwrite(STDERR, "\n" . $msgtxt . "\n");

            return;
        }
        if ( $fail ) {

            echo $msghtml;
        }
    }

    public function onException(Throwable $e) {
        if ( !defined('CRASHED') ) define('CRASHED', 1);
        $errorstr = $e->getMessage();
        if ( method_exists($e, 'getSeverity()') ) $severity = $e->getSeverity();
        $errorno = $e->getCode() ? $e->getCode() : $severity ?? '(' . get_class($e) . ')';
        $errfile = $e->getFile();
        $errline = $e->getLine();
        $errortrace = $this->backtrace($e->getTrace(), $errfile, $errline);
        $errfile = substr($errfile, strrpos($errfile, '/') + 1);
        $msgtext = html_entity_decode("EXCEPTION [$errfile ($errline)] $errorstr");
        $msg = "<big>EXCEPTION $errorno [<em>$errfile</em>] (Line </b>$errline</b>)</big> <small style=\"color:#BBBBBB;\">(" . date("g:i a") . ")</small><br>\n" . substr($errorstr, 0, 400) . "<br>";
        $msghtml = '<DIV style="padding:10px;margin:10px;margin-top:25px;line-height:25px;border:1px solid #c82829;background:#ffff00;color:black;font-size:18px;font-family:Calibri,Arial;">' . $msg . '<DIV style="font-size:12px;line-height:12px;">' . $errortrace . '</div></DIV>';
        if ( $errorno != 666 ) error_log($msghtml, 3, ERRORLOG);
        syslog(LOG_CRIT, strip_tags($msgtext));
        $this->log($msghtml);

        if ( defined('CONSOLE') ) {
            fwrite(STDERR, "\n" . $msgtext . "\n");
        }
        else if ( defined('WEBSITE') && !defined('DEBUG') ) {
            ob_clean();
            header('HTTP/1.1 500 Internal Server Error');
            readfile('/www/500.htm');
            Bootstrap::Goodbye();
        }
        else if ( defined('DEBUG') ) {
            echo($msghtml);
            header('HTTP/1.1 500 Internal Server Error');
        }
        else {
            echo("\n" . $msgtext . "\n");
        }
        Bootstrap::Goodbye();
    }

    public function saveLog($crashed = false) {
        if( !$this->enabled() ) return;
        if ( defined('DEBUG_PRINTED') ) return;
        define('DEBUG_PRINTED', 1);
        $output = '';
        if ( $crashed || defined('CRASHED') ) {
            $output .= '<br><br><EM>SCRIPT CRASHED! (No <i>Goodbye()</i>?)</EM><BR>';
            $e = error_get_last();
            if ( $e !== null ) {
                $fail = false;
                $errtype = $this->errno2str($e['type'], $fail);
                if ( $fail ) {
                    $errfile = substr($e['file'], strrpos($e['file'], '/') + 1);
                    $errortxt = $errtype . " [{$errfile} ({$e['line']})] : {$e['message']}\n";
                    $errorhtml = " <small>$errtype</small> [<var>$errfile</var> (<B>{$e['line']}</B>)] : {$e['message']}";
                    //$errorhtml .=   __backtrace(array(0=>$e));
                    $this->_error([$errorhtml, $errortxt], false);
                }
            }
        }
        if ( !defined('DEBUG') || defined('NODEBUG') ) return;
        if ( defined('CONSOLE') && !defined('DEBUGTOFILE') ) define('DEBUGTOFILE', 1);
        $output_styles = <<<'EOD'
		<STYLE type="text/css">
		    BODY { color: #888; line-height:1.05em; font-family: "Source Code Pro"; }
			DIV.q { background-color:#292929;padding:3px 5px 4px 3px;margin:2px 2px 2px 1px; font-family:Consolas; font-size:12px !important; border-top:1px solid #777; border-left:1px solid #888;}
			div.odump { clear: both; padding:2px 3px 2px 8px; margin-top: 1px; background-color:#363636; color: #EEEEEE; border-top:1px solid #222; }
			div.odump table { border:0; padding:0; margin:0; }
			div.odump td { font-size: 9px; font-family:"MS Reference Sans Serif", "Arial", monospace; padding:2px 5px 2px 5px background-color: #333333; }
			div.cdumph { clear: both; background:#333133; padding:3px 3px 3px 8px; margin-top:1px; cursor:pointer; border-top:1px solid #222; }
			div.cdump { margin:0px 10px 5px 20px;padding:6px;width:auto;  background:#2A2A2A; border-bottom: 1px dotted #000000; border-left: 1px dotted #000000; font-size: 9px; }
			.debug { font-family:"MS Reference Sans Serif",Calibri; color: #EEEEEE; line-height:14px !important; font-size:12px; }
			.debug STRONG { color:yellow; font-size:1.1em; font-weight: bold; display: inline; }
			.debug HR { height: 2px; line-height: 2px; border: 2px solid orange; margin: 10px; }
			.debug I, .debug * I { color: #FFFFBB; font-style:normal; font-weight: normal; font-size:11px; }
			.debug B {color:orange; font-weight: bold; font-size:12px;  }
			.debug EM {display: block; width: auto; padding: 3px; 15px 3px 15px; color:pink; font-weight: bold; font-style: normal; font-size:1.15em;}
			VAR { color:#5577BB; font-style:normal; white-space:pre; }
			P { margin:2px 5px 2px 10px; text-indent:0px; line-height:12px; }
	</STYLE>
EOD;

        $outputwww = '';
        if ( isset($_SESSION) && isset($_SESSION['__debug']) ) {
            if ( isset($GLOBALS['__debug']) && is_array($GLOBALS['_debug']) ) {
                $_SESSION['__debug'] = array_merge($GLOBALS['__debug'], $_SESSION['__debug']);
                $_SESSION['__notices'] = array_merge($GLOBALS['__notices'], $_SESSION['__notices']);
                $_SESSION['__warnings'] = array_merge($GLOBALS['__warnings'], $_SESSION['__warnings']);
            }
            $dump = $_SESSION['__debug'];
            $notices = $_SESSION['__notices'];
            $warnings = $_SESSION['__warnings'];
            $_SESSION['__debug'] = $_SESSION['__notices'] = $_SESSION['__warnings'] = [];
        }
        else {
            $dump = $GLOBALS['__debug'];
            $notices = $GLOBALS['__notices'];
            $warnings = $GLOBALS['__warnings'];
        }
        $output .= array_shift($dump);
        if ( count($warnings) || count($notices) > 10 ) $redalert = "#FF0000";
        else $redalert = "#00FF00";
        $tmpfile = '/tmp/debug_' . uniqid('', true) . '.htm';
        if ( defined('WEBSITE') && !defined('DEBUGTOFILE') ) {
            $javascript = '"if(document.getElementById(\'debugbox\').style.display==\'block\'){setTimeout(function(){document.getElementById(\'debugbox\').style.display = \'none\';},100);}else{document.getElementById(\'debugbox\').style.display=\'block\';document.getElementById(\'debugbox\').style.top=parseInt(document.documentElement.scrollTop)+\'px\';if(!document.getElementById(\'debugbox\').getAttribute(\'src\')){document.getElementById(\'debugbox\').setAttribute(\'src\',\'' . $tmpfile . '\');}}"';
            $position = 'absolute';
            if ( count($warnings) ) $background = $redalert;
            else $background = 'transparent';
            $debugimg = '<img align="center" style="margin-left:7px;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACIAAAASCAYAAAA+PQxvAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAxtJREFUeNqsVk1IVFEUvjPNwmgUhbKmdKEYKcxIgQxugsShVWBKEESSC2sThUutFi1KdyVom4LIolk6TNjuVYJhpYGB0RSGBjlpMjDmTOhCmM5359435/2Viw6cx733nXPfd893zrnPVygUhM/nE0xaSI+TVqr5B9Ip0oywSkDZecku9X5d+b9X6zG1vkT6mvQrMAj5KAGYJi146ARprbINk376i62bxpW67utTEQGIWXyhqT4oopEqUbEnIL+YWsyJmfl1sfF7G9MV0pOkM6S7o5FKz3DA58q5OumXMFa0v9z3QketSK9tCuNNRq8/10BGaXIZBtcuNjg2zZFx/92UMN5mNJhQV+yAGOpr8gTSPTAnngwdk+Mfa1viPM3TP7cE98O+LWfBmtjwKz/JNYzcpJxOce9GRMRa92IawqMzFvIE8Xkpb5kfrC6TIOx+iJqSKT9Fo4oGzQhZY12wZPRx3fGB65cOu9NAttz+9v0FV7tD+8tENFyic9xY0cNJP1GTRfaCq4HhlGkEB4SXnw4nw2aQkadL5noNrXf3z4kjp15JZSe1SE9HrTkGXYzqR5qapJtjmozHkt+tp6ouAuliIQZ1SFytdsFHEfHO9hL1I3HzIC8pGJmAmpyQBd66z8Kz5DVi438xLynimwKITkwIosJlmYCgEMpVJSJJUTFKbuJhSVZ+Gm1oBwcKG+uDnomK09ulWLI15nwsuWyWLRqa7I6qh1Sgf2jEkHfzWQmsvVgpqqcU8yWX33Ykq5ZEKQFN4UWAaDC6hx1tGk3MnlgcBDYYVNUAQPodIoBE3akMPljg0TA4kKO6o3LhICBoaLo7RpsrLZX0ZaLN0shQNW6VA2rHjVUMN0n7+Du/upDIMet5itH4N15qDmp2Kqy/PNS5wYFMAiGQJl6sOrg/fXWWl9odW+k5rgIViQ2ZL2y/x8+W9buUPRpS1O3bq29DSlCpthtyWl3f4h83NL9R5ZgoL9DVUaDK0e/OuGJgvwG9Llc7aLtl8wurdTcAv9jvQq8L6LhbJO1AdPK2KQ2I/yMohh7SBi8DYPgjwABhw5W2FjXgmQAAAABJRU5ErkJggg=="/>';
            $outputwww .= '<div style="display:block;width:50px;height:22px;cursor:pointer;font-weight:bold;z-index:999999;border:0px solid #333;background:' . $background . ';position:' . $position . ';top:3px;left:3px;" onmouseover=' . $javascript . '>' . $debugimg . '</div>';
            $outputwww .= "<iframe id=debugbox allowtransparency=0 frameborder=0 scrolling=auto style=\"position:absolute;display:none;z-index:999998;left:0px;top:0px;border:2px solid blue;background-color:black;min-width:800px;width:90%;height:auto;min-height:600px;overflow:visible;overflow-x:visible;\" onmouseout=\"var o=this, t=setTimeout(function(){o.style.display='none';},750);o.onmouseover=function(){clearInterval(t);};\"></iframe>";
        }
        $runtime = round($this->benchmark(), 2);

        $debug_qtime = App::getInstance()->db()->getQueryTimeTotal();
        $debug_qcount = App::getInstance()->db()->getQueryCount();

        $output .= '<BR><B>Run Time: </B>' . $runtime . ' secs<BR>';
        $output .= '<B>CPU Time: </B>' . (int)(($runtime - $debug_qtime) * 1000) . 'ms<BR>';
        $output .= '<B>Peak Memory Use: </B>' . round((memory_get_peak_usage() / 1024) / 1024, 2) . ' MB<BR>';
        $output .= '<B>Total Queries Made: </B>' . $debug_qcount . '<BR>';
        $output .= '<B>Total Query Time: </B>' . $debug_qtime . ' secs' . '<BR>';
        $output .= '<br>';
        if ( count($warnings) ) $output .= $this->_dump($warnings, '<span style="color:red !important;">WARNINGS</span>');
        if ( count($notices) ) $output .= $this->_dump($notices, 'NOTICES', true);
        if ( count($_POST) ) $output .= $this->_dump($_POST, '<span style="color:yellow;">POST</span>');
        if ( count($_GET) ) $output .= $this->_dump($_GET, 'GET', true);
        if ( isset($_SESSION) && count($_SESSION) ) $output .= $this->_dump($_SESSION, 'SESSION', true);
        if ( count($_COOKIE) ) $output .= $this->_dump($_COOKIE, 'COOKIE', true);
        if ( count($_ENV) ) $output .= $this->_dump($_ENV, 'ENV', true);
        if ( isset($_SERVER) && count($_SERVER) ) $output .= $this->_dump($_SERVER, '$_SERVER', true);
        $constants = get_defined_constants(true);
        if ( isset($constants['user']) ) {
            $output .= $this->_dump($constants['user'], 'Defined CONSTANTS', true);
        }
        $output .= $this->_dump(get_included_files(), 'INCLUDES (in order)', true);
        $output .= '<br>';

        $output .= implode("\n<BR>", $dump);
        if ( isset($GLOBALS['goto']) ) $output .= '<DIV style="border: 2px dotted orange;background-color:#000000;padding:10px;margin:10px 3px 10px 3px;"><EM>Redirecting to <B>' . $GLOBALS['goto'] . '</B></EM></DIV>';
        if ( defined('WEBSITE') && (isset($GLOBALS['goto']) || defined('AJAX')) ) {
            if ( !isset($_SESSION['__debug_tmp']) ) $_SESSION['__debug_tmp'] = $output;
            else $_SESSION['__debug_tmp'] .= '<hr>' . $output;
        }
        else {
            if ( isset($_SESSION['__debug_tmp']) ) {
                $output = $_SESSION['__debug_tmp'] . $output;
                unset($_SESSION['__debug_tmp']);
            }
        }
        $output = "<table id=\"debugtable\" cellpadding=0 cellspacing=0 border=0 width=100%><tr><td class=debug style=\"padding: 4px; font-family:'MS Reference Sans Serif', monospace; background-color: #444444; text-align: left; font-size:10px;color:white;font-weight:normal;\">" . $output;
        if ( defined('WEBSITE') && !defined('DEBUGTOFILE') && !defined('AJAX') ) {
            $javascript = '<script type="text/javascript">function sizeme() { window.frameElement.style.height=(document.getElementById(\'debugtable\').offsetHeight+100)+\'px\'; } window.onscroll = sizeme;</script>';
            $outputtmp = '<html><head>' . $javascript . '<style type="text/css">BODY { background-color:#444444; margin:0px;}</style>' . $output_styles . '</head><body onLoad="sizeme();">' . $output . '</td></tr></table></body></html>';
            file_put_contents($tmpfile, $outputtmp);
            echo $outputwww;
        }
        else {
            $output = $output_styles . $output . '</td></tr></table></div>';
            file_put_contents(DEBUGLOG, $output . (is_readable(DEBUGLOG) ? file_get_contents(DEBUGLOG, true, null, 0, 500000) : '') . "\"'></html>");
            if ( defined('CONSOLE') ) fwrite(STDERR, "\n[Debugged into debug.htm]\n");
        }
    }

    public function logDump($o, $l = null, $return = false) {
        if( !$this->enabled() ) return;
        $dump = $this->_dump($o, $l, (is_object($o) || (is_array($o) && count($o) > 5) ? 1 : 0));
        if ( $return ) return $dump;
        else $this->log(is_null($l) ? $o : $dump);
    }

    protected function errno2str($errno, &$fail = null) : string {
        $errno = (int)$errno;
        static $errorlevels = [E_ALL             => 'ALL', E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
                               E_STRICT          => 'STRICT', E_USER_NOTICE => 'USER_NOTICE',
                               E_USER_WARNING    => 'USER_WARNING', E_USER_ERROR => 'USER_ERROR',
                               E_COMPILE_WARNING => 'COMPILE_WARNING', E_COMPILE_ERROR => 'COMPILE_ERROR',
                               E_CORE_WARNING    => 'CORE_WARNING', E_CORE_ERROR => 'CORE_ERROR', E_NOTICE => 'NOTICE',
                               E_PARSE           => 'PARSE_ERROR', E_WARNING => 'WARNING', E_ERROR => 'ERROR',
                               E_DEPRECATED      => 'DEPRECATED'];
        //if( $errno === 1 || $errno === 256 || $errno === 4 || $errno === 16 || $errno === 64 ) $fail = TRUE;
        if ( $errno === 256 || $errno === 4 || $errno === 16 || $errno === 64 ) $fail = true;
        else $fail = false;

        return isset($errorlevels[ $errno ]) ? $errorlevels[ $errno ] : 'UNKNOWN_ERROR';
    }

    private function benchmark() {
        static $timer;
        if ( $timer ) return (microtime(true) - $timer);
        else $timer = microtime(true);
    }

    private function __($o, $l = null) {
        $this->log(is_null($l) ? $o : $this->_dump($o, $l, (is_object($o) || (is_array($o) && count($o) > 5) ? 1 : 0)));
    }

    private function _vardump($object, $str = '', $level = 1, &$c = 0) {
        if( !$this->enabled() ) return;

        $pad = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        if ( $level > 8 ) {
            $str .= "$pad<span style='color:red;'>*Too much recursion*</span><BR>\r\n";

            return $str;
        }
        $isObject = false;
        if ( is_object($object) ) {
            $class = get_class($object);
            $vars = get_object_vars($object);
            $object = $vars;
            $isObject = true;
            if ( $level == 1 ) $str .= '{<small style=\"color:yellow;\">' . $class . '</small>} &#8594;<BR>' . "\r\n";
        }
        else if ( is_array($object) ) {
            //	if( $level == 1 ) $str .= "[<small style='color:#666666;'>Array</small>][] =&gt; <BR>\r\n";
        }
        else if ( !is_array($object) ) {
            $vartype = gettype($object);
            if ( $vartype == 'boolean' ) return '[<b style="color:#888;">boolean</b>] : <i>' . ($object ? 'TRUE' : 'FALSE') . "</i><BR>\r\n";
            else return "[<b style=\"color:#888;\">$vartype</b>] : <i>$object</i><BR>\n";
        }
        $i = 0;
        foreach ( $object as $k => $v ) {
            if ( substr($k, 0, 2) == '__' ) continue;
            $i++;
            $c++;
            if ( $i > 100 || $c > 500 ) {
                return $str . "<BR><span style=\"color:red;\">$pad*** " . (count($object) - $i) . " more items ***</span><BR>\r\n";
            }
            $str .= $pad;
            if ( is_object($v) ) {
                $class = get_class($v);
                if ( $class == 'stdClass' || $class == 'O' ) $str .= '{<b style="color:orange;">' . $k . '</b>:} &#8594;<BR>' . "\r\n";
                else $str .= '{<b style="color:orange;">' . $k . '</b>:<small style="color:#AAAAAA;">' . $class . '</small>} &#8594;<BR>' . "\r\n";
                $str .= $this->_vardump($v, '', $level + 1, $c);
            }
            else if ( is_array($v) ) {
                if ( $isObject ) $str .= "<b style=\"color:#FFAA33;\">$k</b>[] &rarr; ";
                else $str .= "<b style=\"color:#FFBB55;\">$k</b>[] &rarr; ";
                $str .= "<BR>\r\n";
                $str .= $this->_vardump($v, '', $level + 1, $c);
            }
            else {
                $v2 = gettype($v);
                $suffix = '';
                if ( $v2 == 'integer' ) $suffix = '#';
                else if ( $v2 == 'boolean' ) {
                    $suffix = '!';
                    if ( $v === true ) $v = 'TRUE';
                    else $v = 'FALSE';
                }
                if ( !empty($suffix) ) $v2 = '';
                if ( is_null($v) ) $str .= "<b>$k</b>$suffix : <i style=\"color:#888;font-style:italic;\">NULL</i><BR>\n";
                else if ( !is_string($v) && (string)$v == '' ) $str .= "<b>$k</b>$suffix : <i style=\"color:#888;font-style:italic;\">$v2</i><BR>";
                else $str .= "<b>$k</b>$suffix : <i>" . ((string)$v) . "</i><BR>\n";
            }
        }

        return $str;
    }

    private function backtrace($errortrace, &$errorfile = null, &$errorline = null) {
        if ( !is_array($errortrace) ) return '';
        $newtrace = [];
        for ( $i = 0; $i < count($errortrace); $i++ ) {
            if ( isset($errortrace[ $i ]['file']) && isset($errortrace[ $i ]['line']) ) {
                $errorfile = $errortrace[ $i ]['file'];
                if ( strstr($errorfile, 'debug') !== false || strstr($errorfile, 'db.') !== false ) continue;
                $errorline = $errortrace[ $i ]['line'];
                $errorlines = file($errorfile);
                $code = '<? ';
                for ( $ii = max($errorline - 3, 0); $ii <= $errorline + 3; $ii++ ) {
                    $errorlines[ $ii ] = str_replace('__TAB__', "\t", trim(str_replace("\t", "__TAB__", $errorlines[ $ii ])));
                    if ( $errorlines[ $ii ] != '<?' && $errorlines[ $ii ] != '?>' && $errorlines[ $ii ] != '' ) {
                        $code .= "\n" . $errorlines[ $ii ];
                        if ( $ii == $errorline - 1 ) $code .= '  // <- HERE! (LINE ' . $errortrace[ $i ]['line'] . ')';
                    }
                }
                $code .= ' ?>';
                $code = highlight_string($code, true);
                $code = str_replace(['&lt;?', '?&gt;'], '', $code);
                $newtrace[] = "<div style=\"font-style:normal !important;font-size:11px !important;margin:1px;padding:2px;\">" . str_replace("\n", "\n<BR>", trim($code)) . '</div>';
                break;
            }
        }
        if ( !count($newtrace) ) return '';

        //$newtrace = implode('<BR><BR>',$newtrace);
        return $newtrace[0];
    }

    private function timer($print = false) {
        static $t;
        if ( $t ) {
            $now = microtime(true);
            $tt = $now - $t;
            $t = $now;
            $tt = round($tt * 1000, 1);
            if ( $print ) $this->log("!Timer: $tt ms");

            return (float)$tt;
        }
        else $t = microtime(true);

        return 0.0;
    }

}