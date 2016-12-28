<?

class Str {

    public function __construct() {

    }

    public static function contains($hay, $needle) {
        return self::instr($hay, $needle);
    }

    public static function instr($hay, $needle) {
        $len = strlen($needle);
        if ( $len && $needle[0] == '/' && substr($needle, -1) == '/' )
            return (self::match($needle, $hay) !== false);
        else
            return empty($needle) ? false : !(stristr($hay, $needle) === false);
    }

    public static function gettok($del = null, $str = null, $pos = 1) {
        $parts = explode($del, $str);
        if ( $pos < 0 ) {
            $parts = array_reverse($parts);
            $pos = abs($pos);
        }
        return isset($parts[ $pos ]) ? $parts[ $pos - 1 ] : false;
    }

    public static function str_clean($str, $type = 0) {
        if ( $type !== 3 ) $str = strtolower($str);
        if ( $type === 2 ) {
            $str = preg_replace("/[^a-zA-Z0-9]/", '', $str);
        }
        else {
            if ( $type === 1 ) {
                $str = str_replace('\'', '', $str);
                $str = preg_replace('/[,\/\\\.\-]/', ' ', $str);
                $str = preg_replace("/[^a-zA-Z0-9 ]/", '', $str);
            }
            else {
                $str = preg_replace('/[\'".\-]/', '', $str);
                $str = preg_replace("/[^a-zA-Z0-9 ]/", ' ', $str);
            }
            $str = preg_replace("/[ ]{2,}/", ' ', $str);
            $str = trim($str);
        }
        return $str;
    }

    public static function unslash($str) {
        return str_replace(['\"', "\\'", "\\\\"], ['"', "'", "\\"], $str);
    }

    public static function match($reg, $str) {
        $matches = [];
        if ( $reg[0] !== '/' ) $reg = '/' . $reg . '/';
        if ( !preg_match($reg, $str, $matches) ) return false;
        if ( isset($matches[1]) ) return $matches[1];
        else if ( isset($matches[0]) ) return $matches[0];
        else return true;
    }

    public static function matches($reg, $str) {
        $matches = [];
        if ( $reg[0] !== '/' ) $reg = '/' . $reg . '/';
        if ( !preg_match_all($reg, $str, $matches) ) return false;
        array_shift($matches);
        return $matches;
    }

    public static function isUTF8($str) {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]
        |\xE0[\xA0-\xBF][\x80-\xBF]
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
        |\xED[\x80-\x9F][\x80-\xBF]
        |\xF0[\x90-\xBF][\x80-\xBF]{2}
		  |[\xF1-\xF3][\x80-\xBF]{3}
        |\xF4[\x80-\x8F][\x80-\xBF]{2}
        )+%xs', $str);
    }

    ///////////////////////////////////////////////////
    // Make SEO-optimized URI from human string
    ///////////////////////////////////////////////////
    public static function href($str) {
        $str = trim(html_entity_decode(urldecode($str)));
        $str = str_replace('_', ' ', $str);
        $str = preg_replace('/[^A-Za-z0-9 \-.]/', '', $str);
        $str = preg_replace('/(\.|-)([a-z])/e', '"\1".strtoupper("\2")', ucwords($str));
        $str = str_replace(['.', ' '], ['', '_'], str_replace('  ', ' ', $str));
        $str = str_replace(['__', '_-_', '-_', '_-'], ['_', '_', '', ''], $str);
        return str_replace('Dj ', 'DJ ', $str);
    }

    ///////////////////////////////////////////////////
    // Make human string from URI
    ///////////////////////////////////////////////////
    public static function label($name) {
        return ucwords(str_replace('_', ' ', (html_entity_decode(urldecode($name)))));
    }

}
