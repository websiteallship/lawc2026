@echo off
start "Laravel Scheduler" cmd /k "php artisan schedule:work"
start "Laravel Queue Worker" cmd /k "php artisan queue:work"
