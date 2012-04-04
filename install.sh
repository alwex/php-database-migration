#!/bin/bash
user=`whoami`
completion_path=`pwd`
sudo ln -s $completion_path/install/migrate_bash_completion.sh /etc/bash_completion.d/migrate
echo "to complete install run:"
echo "source ~/.bashrc"
