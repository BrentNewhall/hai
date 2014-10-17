#!/bin/sh

folder_name=hai-source-`date +%F`

mkdir $folder_name/
cp *.php $folder_name/
cp -r assets $folder_name/
rm -rf $folder_name/assets/images/avatars/*
rm $folder_name/assets/*.zip
zip -9ry assets/$folder_name.zip $folder_name
rm -rf $folder_name/
cp -f assets/$folder_name.zip assets/hai-source.zip
