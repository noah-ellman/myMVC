<?

///////////////////////////////////////////////////
class Arr {

    public function __construct() {
    }

    public static function smash($a) {
        if (!is_array($a)) {
            if (!empty($a)) return [$a];
        }
        if (!is_array($a[0])) {
            return $a;
        }
        $k = array_keys($a[0]);
        if (!count($k)) return FALSE;
        $k = $k[0];
        $new = [];
        foreach ($a as $v) $new[] = $v[$k];

        return $new;
    }

    public static function rip($a) {
        if (is_array($a)) return is_array($a[0]) ? $a[0] : $a;
        else if (!empty($a)) return $a;
        else return NULL;
    }

    public static function riplist($a) {
        if (!is_array($a)) return [0 => $a];
        if (!is_array($a[0])) return NULL;

        return array_values($a[0]);
    }

    public static function array_select($a, $key, $val) {
        $new = [];
        if (!is_array($a)) {
            _("!Empty array in array_select");

            return FALSE;
        }
        foreach ($a as $k => $v) {
            if (isset($v[$key]) && $v[$key] == $val) {
                if (is_numeric($k)) $new[] = $v;
                else $new[$k] = $v;
            }
        }

        return $new;
    }

    public static function array_unset(&$a, $key, $val = FALSE) {
        if ($val === FALSE) {
            foreach ($a as $k => $v) {
                if (isset($v[$key])) unset($a[$k][$key]);
            }
        } else {
            foreach ($a as $k => $v) {
                if (isset($v[$key]) && $v[$key] == $val) unset($a[$k]);
            }
        }
    }

    public static function array_find($a, $key, $val) {
        foreach ($a as $k => $v) {
            if (isset($v[$key]) && $v[$key] == $val) return $k;
        }

        return FALSE;
    }

    public static function array_fetch($a, $key, $val) {
        foreach ($a as $k => $v) {
            if (isset($v[$key]) && $v[$key] == $val) return $v;
        }

        return FALSE;
    }

    public static function obj2array($a) {
        if (is_object($a)) $a = get_object_vars($a);
        if (is_array($a)) {
            foreach ($a as $k => $v) {
                if (is_object($v) || is_array($v)) $a[$k] = obj2array($v);
            }
        }

        return $a;
    }

    public static function in_array_2d($a, $key, $val) {
        foreach ($a as $k => $v) {
            if ($v[$key] == $val) return $k;
        }

        return FALSE;
    }

    public static function array_numeric($a) {
        if (!is_array($a)) return [];
        $keys = array_keys($a);
        $new = [];
        for ($i = 0, $c = count($keys); $i < $c; $i++) {
            $k = $keys[$i];
            if (is_numeric($k)) {
                $new[(int)$k] = $a[$k];
            }
        }

        return $new;
    }

    public static function is_array_scalar($a) {
        if (!is_array($a)) return NULL;
        foreach ($a as $v) {
            if ($v !== NULL && !is_scalar($v)) return FALSE;
        }

        return TRUE;
    }

// -------- //
    public static function is_numeric_array($a) {
        if (!is_array($a)) return NULL;
        $last = count($a) - 1;
        if ($last < 0) return TRUE;
        if (isset($a[0]) && isset($a[$last])) return TRUE;
        else return FALSE;
    }

    public static function array_randoms($a, $num = 1) {
        $rands = array_rand($a, min($num, count($a)));
        $num = count($rands);
        if ($num === 1) {
            $rands = is_array($rands) ? $rands[0] : $rands;

            return $a[$rands];
        }
        $new = [];
        for ($i = 0; $i < $num; $i++) $new[] = $a[$rands[$i]];

        return $new;
    }


    public static function array_sort_2d(&$a, $key, $rev = FALSE) {
        if ($rev) $op = '>'; else $op = '<';
        $args = '$a,$b';
        $func = "if(\$a['{$key}']==\$b['{$key}']) return 0; else { if( is_numeric(\$a['{$key}']) ) return ((int)\$a['{$key}']{$op}(int)\$b['{$key}']) ? -1 : 1; else return ((string)\$a['{$key}']{$op}(string)\$b['{$key}']) ? -1 : 1; }";
        if (isset($a[0]) && isset($a[1]) && is_array($a[1])) usort($a, create_function($args, $func));
        else uasort($a, create_function($args, $func));
    }

    public static function levenshtein_sort(&$a, $key, $val, $i = 1, $ii = 1, $iii = 1) {
        foreach ($a as &$v) $v['sort'] = levenshtein($val, $key, $i, $ii, $iii);
        array_sort_2d($a, 'sort');
        foreach ($a as &$v) unset($v['sort']);
    }

    public static function array_rekey_merge($a, $key) {
        $new = [];
        foreach ($a as $k => $v) {
            $nkey = $v[$key];
            unset($v[$key]);
            if (!isset($new[$nkey])) $new[$nkey] = [];
            $new[$nkey][] = $v;
        }

        return $new;
    }

    public static function array_rekey($a, $key) {
        $new = [];
        foreach ($a as $k => $v) {
            $nkey = $v[$key];
            unset($v[$key]);
            $new[$nkey] = $v;
        }

        return $new;
    }

    public static function array_combine_2d($a) {
        $k = array_keys($a[0]);

        return array_combine(array_values_2d($a, $k[0]), array_values_2d($a, $k[1]));
    }

    public static function array_values_2d($a, $key, $key2 = NULL) {
        $newarray = [];
        foreach ($a as $k => $v) {
            if (isset($v[$key]))
                if (!is_null($key2)) {
                    $newarray[] = [$v[$key], $v[$key2]];
                } else {
                    $newarray[] = $v[$key];
                }
        }

        return $newarray;
    }

// -------- //
    public static function array_unique_2d($a, $key) {
        $newarray = [];
        if (!is_array($a)) $a = [];
        foreach ($a as $k => $v) {
            if (isset($v[$key])) {
                if (in_array($v[$key], $newarray))
                    unset($a[$k]);
                else
                    $newarray[] = $v[$key];
            }
        }

        return array_values($a);
    }

///////////////////////////////////////////////////
    public static function array_diff_assoc_recursive($a1, $a2) {
        foreach ($a1 as $key => $value) {
            if (is_array($value)) {
                if (!is_array($a2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = array_diff_assoc_recursive($value, $a2[$key]);
                    if ($new_diff != FALSE) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!isset($a2[$key]) || $a2[$key] != $value) {
                $difference[$key] = $value;
            }
        }

        return !isset($difference) ? 0 : $difference;
    }

///////////////////////////////////////////////////
    public static function array_rebuild($a) {
        $new = [];
        foreach ($a as $v) $new[] = $v;

        return $new;
    }
///////////////////////////////////////////////////
}

?>