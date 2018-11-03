Vagrant.configure("2") do |config|

  ENV["LC_ALL"] = "en_US.UTF-8"

  config.vm.box = "centos/7"
  config.vm.hostname = "tarallo"

  config.vm.synced_folder ".", "/vagrant", disabled: true
  config.vm.synced_folder "./utils/data", "/data"
  config.vm.synced_folder ".", "/var/www/html/server"
  config.vm.synced_folder "./utils/xdebug", "/xdebug",
    :owner => 'php-fpm',
    :group => 'php-fpm',
    :mount_options => ['dmode=775', 'fmode=664']

  config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"
  config.vm.network "forwarded_port", guest: 81, host: 8081, host_ip: "127.0.0.1"
  config.vm.network "forwarded_port", guest: 3306, host: 3307, host_ip: "127.0.0.1"

  config.vm.provider "virtualbox" do |v|
    v.name = "tarallo"
  end

  if Vagrant::Util::Platform.windows? then
    config.vm.synced_folder "./utils/provision", "/provision",
	  :owner => 'vagrant',
	  :group => 'vagrant',
	  :mount_options => ['dmode=775', 'fmode=775']
	config.vm.provision :shell, :inline => "/provision/windows-host.sh"
  else
    config.vm.provision "ansible" do |ansible|
	  #ansible.verbose = "v"
	  ansible.compatibility_mode = "2.0"
	  ansible.playbook = "utils/provision/playbook.yml"
    end
  end

end
