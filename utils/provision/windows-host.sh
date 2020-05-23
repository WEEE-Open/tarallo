#!/bin/bash

# http://www.thisprogrammingthing.com/2015/using-ansible-with-vagrant-and-windows/
if [[ ! -f /usr/bin/ansible-playbook ]]; then
    yum install -y ansible
fi
	
ansible-galaxy install goozbach.EPEL
ansible-galaxy install geerlingguy.nginx
ansible-galaxy install bertvv.mariadb
ansible-galaxy install geerlingguy.repo-remi
ansible-galaxy install geerlingguy.php

ansible-playbook --inventory="localhost," -c local /provision/playbook.yml
