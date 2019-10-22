Vagrant.configure("2") do |config|

  ENV["LC_ALL"] = "en_US.UTF-8"

  #config.vm.box = "centos/7"
  config.vm.box = "geerlingguy/centos7"
  config.vm.hostname = "tarallo"

  config.vm.synced_folder ".", "/vagrant", disabled: true
	
  if Vagrant::Util::Platform.windows? then
    config.vm.synced_folder "./utils/data", "/data", type: "smb"
    config.vm.synced_folder ".", "/var/www/html/server", type: "smb"
  else
    config.vm.synced_folder "./utils/data", "/data"
    config.vm.synced_folder ".", "/var/www/html/server"
  end

  if Vagrant::Util::Platform.windows? then
    config.vm.synced_folder "./utils/xdebug", "/xdebug", type: "smb",
      :mount_options => ['dir_mode=0777,file_mode=0666']
  else
    config.vm.synced_folder "./utils/xdebug", "/xdebug",
      #:owner => 'nobody',
      #:group => 'nobody',
      :mount_options => ['dmode=777', 'fmode=666']
      # or use 775, 664 and user/group php-fpm, but that user doesn't exist before provisioning...
  end

  config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"
  config.vm.network "forwarded_port", guest: 81, host: 8081, host_ip: "127.0.0.1"
  config.vm.network "forwarded_port", guest: 3306, host: 3307, host_ip: "127.0.0.1"

  config.vm.provider "virtualbox" do |v|
    v.name = "tarallo"
  end

  # This works only if Windows uses SMB...
  if Vagrant::Util::Platform.windows? then
    config.vm.synced_folder "./utils/provision", "/provision", type: "smb",
	  :owner => 'vagrant',
	  :group => 'vagrant',
	  :mount_options => ['dir_mode=0775,file_mode=0775']
	config.vm.provision :shell, :inline => "/provision/windows-host.sh"
  else
    config.vm.provision "ansible" do |ansible|
	  #ansible.verbose = "v"
	  ansible.compatibility_mode = "2.0"
	  ansible.playbook = "utils/provision/playbook.yml"
    end
    config.vm.provision "db_update", type: "shell", run: "always" do |up|
        up.privileged = false
        up.inline = "php /var/www/html/server/bin/update.php"
    end
    config.vm.provision "test_db_update", type: "shell", run: "always" do |up|
        up.privileged = false
        up.inline = "php /var/www/html/server/bin/update.php test_db"
    end
  end

end
