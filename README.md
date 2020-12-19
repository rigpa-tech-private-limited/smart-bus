<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://github.com/vinoth-rigpa/smart-bus/raw/main/public/images/vaango_logo.png" width="100"></a></p>

## About 

This app is easy to use and puts important information such as routes, schedules and bus tracker at the user’s fingertips.

## Features:

- **Routes** 
    Use this tool to get a listing of SMART bus routes and published times.
- **Tracker** 
    This feature allows you to get the realtime bus arrival information for your stop.
- **Nearest Stops** 
    See the SMART stop nearest to you when you enter an address, a street intersection, or a landmark (such as the Choice School).
- **Trip Planner** 
    What's the best trip for me? If you know where your trip begins, where you need to go, and what time you need to travel, this is the tool for you. Get detailed information about your best route options.
- **Notifications** 
    When service changes or issues occur, it is nice to have a message waiting for you. If a bus is detoured or bad weather hits, the ride SMART Bus app will send you service bulletin notifications to keep you up to date.

## Useful Commands During Development

- php artisan serve --host=157.245.99.180 --port=8081
- php artisan config:cache
- php artisan optimize:clear
- php artisan key:generate
- ps -ef | grep php
- kill -9 17598
- mysql -u root -p
- sudo service apache2 reload
- sudo systemctl restart apache2
- SET SQL_MODE='ALLOW_INVALID_DATES'
- sudo chown -R :www-data storage
- sudo chown -R :www-data bootstrap/cache/
- chmod -R 775 storage
- chmod -R 775 bootstrap/cache/
- sudo a2ensite *