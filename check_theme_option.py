import subprocess

MYSQL_EXE = r"C:\xampp\mysql\bin\mysql.exe"
res = subprocess.run([
    MYSQL_EXE, "-u", "root", "teknopremium", "-e",
    "SELECT option_name, option_value FROM wp_options WHERE option_name IN ('template', 'stylesheet', 'current_theme');"
], capture_output=True, text=True)
print(res.stdout)
