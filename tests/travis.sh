sudo sh -c "echo 'JVM_OPTS=\"\${JVM_OPTS} -Djava.net.preferIPv4Stack=false\"' >> /usr/local/cassandra/conf/cassandra-env.sh"
sudo service cassandra start
sudo apt-add-repository -y ppa:linuxjedi/ppa
sudo apt-get update
sudo apt-get install -y libuv-dev libssl-dev
cd /tmp && git clone https://github.com/datastax/php-driver.git && cd php-driver && git submodule update --init
cd ext && ./install.sh && cd "$TRAVIS_BUILD_DIR"
echo "extension=cassandra.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
