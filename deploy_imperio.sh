#!/bin/bash
cd /home/projetos/erp-backend
git checkout imperio
git pull origin imperio
rsync -av --delete /home/projetos/erp-backend/ /home/imperio/www/
sudo systemctl restart apache2
