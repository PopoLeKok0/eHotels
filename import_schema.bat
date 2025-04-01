@echo off
cd C:\xampp\mysql\bin
mysql.exe --no-defaults -u ehotels_user -ppassword123 -P 3307 ehotels < C:\xampp\htdocs\eHotels\mysql_schema.sql
echo Schema import completed!
pause 