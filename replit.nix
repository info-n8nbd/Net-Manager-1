{pkgs}: {
  deps = [
    pkgs.php82Extensions.intl
    pkgs.php82Extensions.fileinfo
    pkgs.php82Extensions.tokenizer
    pkgs.php82Extensions.bcmath
    pkgs.php82Extensions.curl
    pkgs.php82Extensions.xml
    pkgs.php82Extensions.mbstring
    pkgs.php82Extensions.gd
    pkgs.php82Extensions.zip
    pkgs.php82Extensions.redis
    pkgs.php82Extensions.pgsql
    pkgs.redis
    pkgs.postgresql
  ];
}
