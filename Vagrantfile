$script = <<SCRIPT
#!/usr/bin/env bash

apt-get update
apt-get install -y php php-mbstring php-xml php-curl php-zip composer

apt-key adv --keyserver keyserver.ubuntu.com --recv E0C56BD4    # optional
echo "deb http://repo.yandex.ru/clickhouse/deb/stable/ main/" | tee /etc/apt/sources.list.d/clickhouse.list
apt-get update
apt-get install -y clickhouse-server clickhouse-client
service clickhouse-server start

SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/bionic64"
  config.vm.provider "virtualbox" do |vb|
    vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected" ]
  end
  config.vm.provision :shell, inline: $script
end
