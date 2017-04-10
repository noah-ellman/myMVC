<?
/**
 * Class SyntaxHighlight
 * Description
 *
 * @package Dizzler
 *
 */

class SQLSyntaxHighlighter {

    private $colors;
    private $words;
    private $sql;

    public function __construct($sql=NULL,$error=NULL)
    {
        $this->error = $error;
        $this->sql = $sql;
        $this->words = array(
            array('AND', 'IS', '&&', 'LOG', 'NOT', 'NOW', 'MIN', '!', '||', 'OR', 'OCT', 'TAN', 'STD', 'SHA', 'ORD', 'XOR'),
            array('SELECT', 'UPDATE', 'INSERT', 'DELETE', 'USING', 'LIMIT', 'OFFSET', 'SET'),
            array('DATE', 'INTO', 'FROM', 'THEN', 'WHEN', 'WHERE', 'JOIN', 'ELSE'),
            array('ABS', 'ACOS', 'ADDDATE', 'ADDTIME', 'AES_DECRYPT', 'AES_ENCRYPT', '&&', 'ASCII', 'ASIN', 'ATAN2', 'ATAN', 'AVG', 'BETWEEN', 'BIN', 'BINARY', 'BIT_AND', 'BIT_LENGTH', 'BIT_OR', 'BIT_XOR', 'CASE', 'CAST', 'CEIL', 'CEILING', 'CHAR_LENGTH', 'CHAR', 'CHARACTER_LENGTH', 'CHARSET', 'COALESCE', 'COERCIBILITY', 'COLLATION', 'COMPRESS', 'CONCAT_WS', 'CONCAT', 'CONNECTION_ID', 'CONV', 'CONVERT_TZ', 'Convert', 'COS', 'COT', 'COUNT', 'COUNT', 'COUNT(DISTINCT)', 'CRC32', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURTIME', 'DATABASE', 'DATE_ADD', 'DATE_FORMAT', 'DATE_SUB', 'DATEDIFF', 'DAY ', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'DECODE', 'DEFAULT', 'DEGREES', 'DES_DECRYPT', 'DES_ENCRYPT', 'DIV', 'ELT', 'ENCODE', 'ENCRYPT', '<=>', 'EXP()', 'EXPORT_SET', 'EXTRACT', 'FIELD', 'FIND_IN_SET', 'FLOOR', 'FORMAT', 'FOUND_ROWS', 'FROM_DAYS', 'FROM_UNIXTIME', 'GET_FORMAT', 'GET_LOCK', 'GREATEST', 'GROUP_CONCAT', 'HEX ', 'HOUR', ' IF ', 'IFNULL', ' IN ', 'INET_ATON', 'INET_NTOA', 'INSTR', 'IS_FREE_LOCK', 'IS NOT NULL', 'IS NOT', 'IS NULL', 'IS_USED_LOCK', 'ISNULL', 'LAST_DAY', 'LAST_INSERT_ID', 'LCASE', 'LEAST', '<<', 'LEFT', 'LENGTH', 'LIKE', 'LN', 'LOAD_FILE', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATE', 'LOG10', 'LOG2', 'LOWER', 'LPAD', 'LTRIM', 'MAKE_SET', 'MAKEDATE', 'MAKETIME', 'MASTER_POS_WAIT', 'MATCH', 'MAX', 'MD5', 'MICROSECOND',  'MINUTE', 'MOD', '%', 'MONTH', 'MONTHNAME', 'NOT BETWEEN', '!=', 'NOT IN', 'NOT LIKE', 'NOT REGEXP', 'NULLIF', 'OCTET_LENGTH', 'OLD_PASSWORD', 'ORD', 'PASSWORD', 'PERIOD_ADD', 'PERIOD_DIFF', 'PI', '+', 'POSITION', 'POW', 'POWER', 'PROCEDURE ANALYSE', 'QUARTER', 'QUOTE', 'RADIANS', 'RAND', 'REGEXP', 'RELEASE_LOCK', 'REPEAT', 'REPLACE', 'REVERSE', '>>', 'RIGHT', 'RLIKE', 'ROUND', 'ROW_COUN', 'RPAD', 'RTRIM', 'SCHEMA', 'SEC_TO_TIME', 'SECOND', 'SESSION_USER', 'SHA1', 'SIGN', 'SLEEP', 'SOUNDEX', 'SOUNDS LIKE', 'SPACE', 'SQRT', 'STDDEV_POP', 'STDDEV_SAMP', 'STDDEV', 'STR_TO_DATE', 'SUBDATE', 'SUBSTR', 'SUBSTRING_INDEX', 'SUBSTRING', 'SUBTIME', 'SUM', 'SYSDATE', 'SYSTEM_USER', 'TIME_FORMAT', 'TIME_TO_SEC', 'TIME', 'TIMEDIFF', '*', 'TIMESTAMP', 'TIMESTAMPADD', 'TIMESTAMPDIFF', 'TO_DAYS', 'TRIM', 'TRUNCATE', 'UCASE', 'UNCOMPRESS', 'UNCOMPRESSED_LENGTH', 'UNHEX', 'UNIX_TIMESTAMP', 'UPPER', 'USER', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'UUID', 'VALUES', 'VAR_POP', 'VAR_SAMP', 'VARIANCE', 'VERSION', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR', 'YEARWEE')
        );
        $this->colors = array('orange', 'blue', 'green', 'brown');
    }


    public function addStyle($sql, $string, $color)
    {
        $string = trim($string);
        $string = str_replace(array('|','*','+','.'),array('\|','\*','\+','\.'),$string);
        return preg_replace('/\b({$string})\b/i', "<font color={$color}>{$string}</font>", $sql);
    }


    public function highlight($sql)
    {
        $i = 0;
        $this->colors = array('orange', 'blue', 'green', 'brown');
        foreach($this->colors as $color)
        {
            foreach($this->words[$i] as $word)
            {
                $sql = $this->addStyle( $sql, $word, $color);
            }
            $i++;
        }

        if( $this->error ) {
            $error = substr(strstr($this->error,"'"),1,10);
            $error = str_replace($error,'<span style=background:yellow;>'.$error,$error);
            $error .= '</span>';
        }

//       $sql = '<DIV style="font-family:Courier New;font-size:11px;font-weight:bold;">'.str_replace("\n","<BR>",str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$sql)).'</DIV>';
//				$sql = str_replace(' WHERE ','<br>WHERE ',$sql);
        return '<div>'.$sql.'</div>';
    }

    public function __tostring() {
//	 return $this->ql;
        return $this->highlight($this->sql);
    }

}