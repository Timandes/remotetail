# remotetail
Get the last part of files from remote server

## Requirements
- [PHP](http://www.php.net/) >= 5.4.x
- [PECL_Event](https://pecl.php.net/package/event)
- [PECL_Inotify](https://pecl.php.net/package/inotify)
- [Workerman](https://github.com/walkor/Workerman)

## Install
```
cp remotetail.conf.example remotetail.conf
./remotetail start
```
Run as a daemon:
```
./remotetail start -d
```
Stop a daemon:
```
./remotetail stop
```
