<?

class DBDebug extends DB {

    private $debug_qtime, $debug_qcount, $debug_timer;


    public function query($query, $ret = 1, $fail = 1) {
        $this->debug_qcount++;
        $log = str_replace(["\t",
                            "\n"], ' ', htmlspecialchars($query));
        $this->debug_timer = (float)microtime(true);
        $r = parent::query($query);
        $this->debug_timer = round(microtime(true) - $this->debug_timer, 4);
        $this->debug_qtime += $this->debug_timer;
        $timer = '';
        if ( $this->debug_timer > 0.010 ) $timer = $this->debug_timer < 1.0 ?
            '<br><b style="color: red;">&nbsp;&nbsp;To Slow! (' . number_format(( $this->debug_timer * 1000 )) . ' ms)&nbsp;&nbsp;</b>' :
            '<br><b style="color: red;">&nbsp;&nbsp;To Slow! (' . ( $this->debug_timer ) . ' seconds)&nbsp;&nbsp;</b>';

        $log = str_replace(["\t",
                            "\n"], ' ', '<DIV class=q><B style="background-color:#FF6600;">&nbsp;&nbsp;&nbsp;</B>&nbsp;<I>' . htmlspecialchars($query) . '</I>');

        if ( $r === false ) {
            $this->log($log . $timer . '</DIV>');
        } else {
            if ( !is_array($r) ) {
                $this->log($log . $timer . '&nbsp;<B>RESULT: <i>' . $r . '</i></b></DIV>');
            } else {
                $this->log($log . $timer . '</DIV>' . $this->logDump($r, 'RESULT', true));
            }
        }

        return $r;
    }


}

?>