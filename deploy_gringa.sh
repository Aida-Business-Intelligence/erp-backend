#!/bin/bash
cd /home/projetos/erp-backend
git checkout gringa
git pull origin gringa
rsync -av --delete /home/projetos/erp-backend/ /home/gringa/www/
sudo systemctl restart apache2
